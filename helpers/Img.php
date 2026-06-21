<?php

namespace app\helpers;

use Yii;
use Imagine\Image\Box;
use Imagine\Image\Point;
use Imagine\Image\ImageInterface;
use Imagine\Filter\Basic\Autorotate;
use yii\imagine\Image;

/**
 * Image storage helpers shared by the photo and series-cover upload pipelines.
 *
 * Storage scheme (since the WebP migration):
 *   - The *original* master file is always stored as JPG (PNG/other uploads are
 *     converted on the way in). Its name is kept in the DB (e.g. "Ab3kZ9.jpg").
 *   - All derivatives (site/preview/squared/tiny, series thumb/site) are WebP,
 *     named "<base>.webp". Use Img::webp($filename) to get the derivative name.
 *
 * Requires the active Imagine driver (GD or Imagick) to support WebP.
 * GD: PHP built with --with-webp (standard on PHP 7.1+). Imagick: webp delegate.
 */
class Img
{
    /** Reject uploads larger than this (bytes). */
    const MAX_BYTES = 15 * 1024 * 1024; // 15 MB

    /** Reject uploads whose longer side exceeds this (pixels). */
    const MAX_SIDE = 8000;

    /** WebP quality for all derivatives. */
    const WEBP_QUALITY = 82;

    /** JPEG quality when converting a non-JPG original (e.g. PNG) to JPG. */
    const JPEG_QUALITY = 92;

    /** Image types we accept (others are rejected). */
    private static $allowedTypes = [
        IMAGETYPE_JPEG,
        IMAGETYPE_PNG,
        IMAGETYPE_WEBP,
        IMAGETYPE_GIF,
        IMAGETYPE_BMP,
    ];

    /**
     * Derivative (WebP) filename for a given original filename.
     * "Ab3kZ9.jpg" => "Ab3kZ9.webp"
     */
    public static function webp($filename)
    {
        return pathinfo((string) $filename, PATHINFO_FILENAME) . '.webp';
    }

    /**
     * Validate an uploaded image before processing.
     *
     * @param string $tmpPath path to the uploaded temp file
     * @param int $bytes file size in bytes
     * @return string|null human-readable error message, or null if the file is OK
     */
    public static function validate($tmpPath, $bytes)
    {
        if ($bytes <= 0 || !is_file($tmpPath)) {
            return Yii::t('admin', 'The file could not be read.');
        }

        if ($bytes > self::MAX_BYTES) {
            return Yii::t('admin', 'File is too large ({size}). Maximum is {max}.', [
                'size' => self::humanSize($bytes),
                'max' => self::humanSize(self::MAX_BYTES),
            ]);
        }

        $info = @getimagesize($tmpPath);
        if ($info === false) {
            return Yii::t('admin', 'This file is not a valid image.');
        }

        list($w, $h) = $info;
        $type = isset($info[2]) ? $info[2] : null;

        if (!in_array($type, self::$allowedTypes, true)) {
            return Yii::t('admin', 'Unsupported image format. Use JPG or PNG.');
        }

        if (max($w, $h) > self::MAX_SIDE) {
            return Yii::t('admin', 'Image resolution is too high ({w}×{h} px). The longer side must not exceed {max} px.', [
                'w' => $w,
                'h' => $h,
                'max' => self::MAX_SIDE,
            ]);
        }

        return null;
    }

    /**
     * True if the given image type (from getimagesize()[2]) is already JPEG.
     */
    public static function isJpeg($imageType)
    {
        return (int) $imageType === IMAGETYPE_JPEG;
    }

    private static function humanSize($bytes)
    {
        if ($bytes >= 1024 * 1024) {
            return round($bytes / 1024 / 1024, 1) . ' MB';
        }
        return round($bytes / 1024) . ' KB';
    }

    /**
     * Store an uploaded image: keep the master as JPG and build all WebP
     * derivatives (site / preview / squared thumb / tiny thumb). Shared by the
     * AJAX photo uploader (PhotosController) and the inline uploader on the
     * "Add work" page so the storage scheme stays single-sourced.
     *
     * Does NOT touch the database — the caller creates the Photos row with the
     * returned filename. On any failure it rolls back partial files and rethrows.
     *
     * @param string $tmpFilePath path to the source image (an uploaded temp file)
     * @param bool   $isJpeg      whether the source is already JPEG (from getimagesize)
     * @param bool   $moveUploaded move_uploaded_file() the JPG master (true for real
     *                             uploads) or copy() it (false, e.g. reprocessing)
     * @return string the stored master filename, e.g. "Ab3kZ9.jpg"
     * @throws \Exception
     */
    public static function store($tmpFilePath, $isJpeg, $moveUploaded = true)
    {
        $baseName = Yii::$app->security->generateRandomString(10);
        $originalName = $baseName . '.jpg';
        $webpName = $baseName . '.webp';

        $dir = Yii::getAlias('@app') . '/web/paintings_photo/';
        $originalFilePath = $dir . 'original/' . $originalName;
        $originalSiteFilePath = $dir . 'original_site/' . $webpName;
        $previewFilePath = $dir . 'preview/' . $webpName;
        $thumbSquaredFilePath = $dir . 'thumb_squared/' . $webpName;
        $thumbTinyFilePath = $dir . 'thumb_tiny/' . $webpName;

        $imagine = Image::getImagine();

        try {
            if ($isJpeg) {
                // Keep the uploaded JPG byte-for-byte (best fidelity for the master).
                $ok = $moveUploaded
                    ? @move_uploaded_file($tmpFilePath, $originalFilePath)
                    : @copy($tmpFilePath, $originalFilePath);
                if (!$ok) {
                    throw new \RuntimeException('Could not save the uploaded file.');
                }
            } else {
                // PNG / other → convert the master to JPG.
                $src = $imagine->open($tmpFilePath);
                (new Autorotate())->apply($src);
                $src->save($originalFilePath, ['jpeg_quality' => self::JPEG_QUALITY]);
            }

            // Build WebP derivatives from the stored master.
            $image = $imagine->open($originalFilePath);
            (new Autorotate())->apply($image);

            $image->thumbnail(new Box(2000, 2000))
                ->save($originalSiteFilePath, ['webp_quality' => self::WEBP_QUALITY]);
            $image->thumbnail(new Box(900, 900))
                ->save($previewFilePath, ['webp_quality' => self::WEBP_QUALITY]);
            self::resizeInset($originalFilePath, $thumbSquaredFilePath, 700, 700);
            $image->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_OUTBOUND)
                ->save($thumbTinyFilePath, ['webp_quality' => self::WEBP_QUALITY]);
        } catch (\Exception $e) {
            // Roll back any partial files so we never leave orphans behind.
            foreach ([$originalFilePath, $originalSiteFilePath, $previewFilePath, $thumbSquaredFilePath, $thumbTinyFilePath] as $f) {
                @unlink($f);
            }
            throw $e;
        }

        return $originalName;
    }

    /**
     * Resize $input into a $width × $height WebP, letterboxed (INSET) and centred
     * on a canvas of exactly that size. Used for the square thumbnail.
     */
    public static function resizeInset($input, $output, $width, $height)
    {
        $imagine = Image::getImagine();
        $size = new Box($width, $height);
        $image = $imagine->open($input);
        (new Autorotate())->apply($image);

        $resizeimg = $image->thumbnail($size, ImageInterface::THUMBNAIL_INSET);
        $sizeR = $resizeimg->getSize();

        $startX = $startY = 0;
        if ($sizeR->getWidth() < $width) {
            $startX = ($width - $sizeR->getWidth()) / 2;
        }
        if ($sizeR->getHeight() < $height) {
            $startY = ($height - $sizeR->getHeight()) / 2;
        }

        $imagine->create($size)
            ->paste($resizeimg, new Point($startX, $startY))
            ->save($output, ['webp_quality' => self::WEBP_QUALITY]);
    }

    /**
     * Best-effort EXIF metadata from a JPG: the capture month and GPS position.
     * Both are optional — missing/garbled EXIF just yields nulls.
     *
     * @param string $jpgPath
     * @return array{date: ?string, lat: ?float, lng: ?float}
     *         date is "YYYY-MM" (the month the photo was taken).
     */
    public static function exifMeta($jpgPath)
    {
        $res = ['date' => null, 'lat' => null, 'lng' => null];

        if (!function_exists('exif_read_data') || !is_file($jpgPath)) {
            return $res;
        }

        $exif = @exif_read_data($jpgPath, 'ANY_TAG', true);
        if (!is_array($exif)) {
            return $res;
        }

        $dt = $exif['EXIF']['DateTimeOriginal']
            ?? $exif['EXIF']['DateTimeDigitized']
            ?? $exif['IFD0']['DateTime']
            ?? null;
        if ($dt && preg_match('/^(\d{4}):(\d{2})/', (string) $dt, $m)) {
            $res['date'] = $m[1] . '-' . $m[2];
        }

        $gps = $exif['GPS'] ?? null;
        if (is_array($gps) && isset($gps['GPSLatitude'], $gps['GPSLongitude'])) {
            $lat = self::gpsToDecimal($gps['GPSLatitude'], $gps['GPSLatitudeRef'] ?? 'N');
            $lng = self::gpsToDecimal($gps['GPSLongitude'], $gps['GPSLongitudeRef'] ?? 'E');
            if ($lat !== null && $lng !== null && ($lat !== 0.0 || $lng !== 0.0)) {
                $res['lat'] = $lat;
                $res['lng'] = $lng;
            }
        }

        return $res;
    }

    /** Convert an EXIF [deg, min, sec] (each "n/d" or number) + ref to decimal degrees. */
    private static function gpsToDecimal($coord, $ref)
    {
        if (!is_array($coord) || count($coord) < 3) {
            return null;
        }
        $d = self::frac($coord[0]);
        $m = self::frac($coord[1]);
        $s = self::frac($coord[2]);
        if ($d === null || $m === null || $s === null) {
            return null;
        }
        $dec = $d + $m / 60 + $s / 3600;
        if (in_array(strtoupper((string) $ref), ['S', 'W'], true)) {
            $dec = -$dec;
        }
        return round($dec, 6);
    }

    /** Parse an EXIF rational ("numerator/denominator") or plain number to float. */
    private static function frac($v)
    {
        if (is_numeric($v)) {
            return (float) $v;
        }
        if (is_string($v) && strpos($v, '/') !== false) {
            list($n, $d) = explode('/', $v, 2);
            $d = (float) $d;
            return $d != 0.0 ? (float) $n / $d : 0.0;
        }
        return null;
    }
}
