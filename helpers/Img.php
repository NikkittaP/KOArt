<?php

namespace app\helpers;

use Yii;

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
}
