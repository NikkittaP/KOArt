<?php

namespace app\helpers;

use app\models\Paintings;
use app\models\Series;
use Yii;

/**
 * Social preview cards (1200x630 JPEG) for individual artworks and series.
 *
 * Why generate anything at all instead of pointing og:image straight at the
 * artwork file:
 *
 *  - Aspect ratio. Artworks are often tall portraits; Facebook and LinkedIn
 *    crop a 2:3 image to 1.91:1 and you get a band across the middle of the
 *    painting. Letterboxing it ourselves keeps the whole work visible.
 *  - Format. The site's derivatives are WebP. LinkedIn's crawler has never
 *    reliably rendered WebP previews, and JPEG is universally accepted.
 *
 * Cards are cached as real files under web/og_cache/ and referenced by a
 * static URL, so after the first fetch Apache serves them directly with the
 * one-year cache header from web/.htaccess and PHP is never involved. The URL
 * carries a short hash of the source filename and its mtime, so replacing a
 * work's photo produces a new URL instead of a year-stale cached card.
 *
 * If generation is impossible (unwritable cache dir, missing source, GD
 * without WebP) every entry point degrades to the static default card.
 */
class OgImage
{
    const WIDTH = 1200;
    const HEIGHT = 630;
    const QUALITY = 86;

    /** Padding around the artwork inside the card. */
    const PAD_X = 120;
    const PAD_Y = 60;

    /** White "mat" drawn around the artwork, like a mounted print. */
    const MAT = 10;

    /** Cache directory, relative to the web root. */
    const CACHE_DIR = 'og_cache';

    /**
     * Card URL for a single work, or the default card if it has no usable photo.
     *
     * @param Paintings $painting
     * @return string web-root-relative URL
     */
    public static function forPainting(Paintings $painting)
    {
        return self::url('work', $painting->id, self::paintingSource($painting));
    }

    /**
     * Card URL for a series, built from its cover image.
     *
     * @param Series $series
     * @return string web-root-relative URL
     */
    public static function forSeries(Series $series)
    {
        return self::url('series', $series->id, self::seriesSource($series));
    }

    /**
     * Absolute filesystem path of the source image for a work, or null.
     *
     * @param Paintings $painting
     * @return string|null
     */
    public static function paintingSource(Paintings $painting)
    {
        $url = PaintingPresenter::photoUrl($painting, 'lg');

        return $url ? self::webPath($url) : null;
    }

    /**
     * Absolute filesystem path of the source image for a series, or null.
     *
     * @param Series $series
     * @return string|null
     */
    public static function seriesSource(Series $series)
    {
        $cover = (string) $series->cover_filename;
        if ($cover === '') {
            return null;
        }

        return self::webPath('/series_cover/' . Img::webp($cover));
    }

    /**
     * Build (or rebuild) the card for a token like "work-156-a1b2c3d4".
     *
     * Returns the absolute path of the written file, or null when the token
     * is unknown, or its hash does not match the current source - which is
     * what stops arbitrary filenames being used to fill the cache directory.
     *
     * @param string $token
     * @return string|null
     */
    public static function generate($token)
    {
        if (!preg_match('/^(work|series)-(\d+)-([a-f0-9]{8})$/', (string) $token, $m)) {
            return null;
        }

        $kind = $m[1];
        $id = (int) $m[2];
        $hash = $m[3];

        if ($kind === 'work') {
            $model = Paintings::findOne($id);
            $source = $model ? self::paintingSource($model) : null;
        } else {
            $model = Series::findOne($id);
            $source = $model ? self::seriesSource($model) : null;
        }

        if (!$source || self::hash($source) !== $hash) {
            return null;
        }

        return self::compose($source, self::cachePath($token));
    }

    /**
     * @param string $token
     * @return string absolute filesystem path of the cached card
     */
    public static function cachePath($token)
    {
        return Yii::getAlias('@webroot') . '/' . self::CACHE_DIR . '/' . $token . '.jpg';
    }

    /**
     * Card URL for a kind/id pair, falling back to the static default card
     * whenever the source image is missing.
     *
     * @param string $kind "work" or "series"
     * @param int $id
     * @param string|null $source absolute path of the source image
     * @return string
     */
    private static function url($kind, $id, $source)
    {
        if (!$source) {
            return (string) Yii::$app->params['ogDefaultImage'];
        }

        return '/' . self::CACHE_DIR . '/' . $kind . '-' . (int) $id . '-' . self::hash($source) . '.jpg';
    }

    /**
     * Short cache-busting hash of a source file: name plus modification time.
     *
     * @param string $path
     * @return string
     */
    private static function hash($path)
    {
        $mtime = @filemtime($path) ?: 0;

        return substr(md5(basename($path) . '|' . $mtime), 0, 8);
    }

    /**
     * @param string $webUrl web-root-relative URL
     * @return string absolute filesystem path
     */
    private static function webPath($webUrl)
    {
        return Yii::getAlias('@webroot') . '/' . ltrim($webUrl, '/');
    }

    /**
     * Compose the card: the whole artwork, mounted on a white mat, on the
     * site's cream paper tone - the same ground as the default card, so a
     * feed of shared links reads as one set.
     *
     * An earlier version used a blurred wash of the artwork itself as the
     * background. It was dropped: upscaling a 48px thumbnail 25x gives
     * visible bilinear blocks, not a blur, and smoothing it properly costs
     * more than a flat ground is worth on a 1200x630 card.
     *
     * @param string $source absolute path of the source image
     * @param string $destination absolute path to write
     * @return string|null the destination on success
     */
    private static function compose($source, $destination)
    {
        $src = self::load($source);
        if (!$src) {
            return null;
        }

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        if ($srcW < 1 || $srcH < 1) {
            imagedestroy($src);

            return null;
        }

        $dir = dirname($destination);
        if (!is_dir($dir) && !@mkdir($dir, 0775, true) && !is_dir($dir)) {
            imagedestroy($src);

            return null;
        }

        $canvas = imagecreatetruecolor(self::WIDTH, self::HEIGHT);
        $cream = imagecolorallocate($canvas, 0xf4, 0xf2, 0xec);
        imagefilledrectangle($canvas, 0, 0, self::WIDTH, self::HEIGHT, $cream);

        self::drawArtwork($canvas, $src, $srcW, $srcH);

        $ok = @imagejpeg($canvas, $destination, self::QUALITY);
        imagedestroy($src);
        imagedestroy($canvas);

        return $ok ? $destination : null;
    }

    /**
     * Foreground: the complete artwork, scaled to fit the padded box and
     * mounted on a white mat so it separates from the wash behind it.
     *
     * @param resource|\GdImage $canvas
     * @param resource|\GdImage $src
     * @param int $srcW
     * @param int $srcH
     */
    private static function drawArtwork($canvas, $src, $srcW, $srcH)
    {
        $maxW = self::WIDTH - 2 * self::PAD_X;
        $maxH = self::HEIGHT - 2 * self::PAD_Y;
        $scale = min($maxW / $srcW, $maxH / $srcH);

        $w = max(1, (int) round($srcW * $scale));
        $h = max(1, (int) round($srcH * $scale));
        $x = (int) round((self::WIDTH - $w) / 2);
        $y = (int) round((self::HEIGHT - $h) / 2);

        $white = imagecolorallocate($canvas, 0xff, 0xff, 0xff);
        imagefilledrectangle(
            $canvas,
            $x - self::MAT,
            $y - self::MAT,
            $x + $w + self::MAT,
            $y + $h + self::MAT,
            $white
        );

        imagecopyresampled($canvas, $src, $x, $y, 0, 0, $w, $h, $srcW, $srcH);
    }

    /**
     * Load an image by extension. Derivatives are WebP; the masters are JPG.
     *
     * @param string $path
     * @return resource|\GdImage|null
     */
    private static function load($path)
    {
        if (!is_file($path) || !is_readable($path)) {
            return null;
        }

        switch (strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            case 'webp':
                $img = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($path) : false;
                break;
            case 'png':
                $img = @imagecreatefrompng($path);
                break;
            case 'jpg':
            case 'jpeg':
                $img = @imagecreatefromjpeg($path);
                break;
            default:
                $img = false;
        }

        return $img ?: null;
    }
}
