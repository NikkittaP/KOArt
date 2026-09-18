<?php

namespace app\commands;

use app\helpers\Img;
use app\models\Photos;
use app\models\Series;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Yii;
use yii\console\Controller;
use yii\console\ExitCode;
use yii\helpers\Console;
use yii\imagine\Image;

/**
 * One-off / maintenance image tasks.
 *
 * Migrate every existing painting photo and series cover to the new scheme:
 *   - master original kept (or converted) as JPG
 *   - all derivatives rebuilt as WebP
 *   - stale non-WebP derivatives removed
 *
 * Usage (from the project root, with the local PHP that runs the site):
 *   php yii image/regenerate            # do it
 *   php yii image/regenerate --dry=1    # show what would happen, change nothing
 *   php yii image/regenerate --keepOld=1  # keep the old JPG derivatives
 *
 * Safe to re-run: already-converted items are skipped, WebP files are overwritten.
 */
class ImageController extends Controller
{
    /** @var int 1 = report only, make no changes. */
    public $dry = 0;

    /** @var int 1 = keep old (non-WebP) derivative files instead of deleting them. */
    public $keepOld = 0;

    public function options($actionID)
    {
        return ['dry', 'keepOld'];
    }

    public function actionRegenerate()
    {
        $this->stdout("Image regeneration" . ($this->dry ? " (DRY RUN)" : "") . "\n", Console::BOLD);

        $photoBase = Yii::getAlias('@app') . '/web/paintings_photo/';
        $coverBase = Yii::getAlias('@app') . '/web/series_cover/';

        $stats = ['photos' => 0, 'covers' => 0, 'converted' => 0, 'skipped' => 0, 'errors' => 0, 'removed' => 0];

        // -------- Painting photos --------
        $this->stdout("\nPainting photos:\n", Console::FG_CYAN);
        foreach (Photos::find()->each() as $photo) {
            $res = $this->processOne(
                $photoBase . 'original/',
                $photo->filename,
                [
                    ['dir' => 'original_site', 'w' => 2000, 'h' => 2000, 'mode' => 'inset_plain'],
                    ['dir' => 'preview',       'w' => 900,  'h' => 900,  'mode' => 'inset_plain'],
                    ['dir' => 'thumb_squared', 'w' => 700,  'h' => 700,  'mode' => 'pad_square'],
                    ['dir' => 'thumb_tiny',    'w' => 100,  'h' => 100,  'mode' => 'outbound'],
                ],
                $photoBase,
                $stats
            );
            if ($res['newName'] !== null && $res['newName'] !== $photo->filename) {
                if (!$this->dry) {
                    $photo->filename = $res['newName'];
                    $photo->save(false, ['filename']);
                }
                $this->stdout("    DB filename -> {$res['newName']}\n", Console::FG_YELLOW);
            }
            $stats['photos']++;
        }

        // -------- Series covers --------
        $this->stdout("\nSeries covers:\n", Console::FG_CYAN);
        foreach (Series::find()->each() as $series) {
            if (empty($series->cover_filename)) {
                continue;
            }
            $res = $this->processOne(
                $coverBase . 'original/',
                $series->cover_filename,
                [
                    ['dir' => 'thumb', 'w' => 700,  'h' => 700,  'mode' => 'pad_square'],
                    ['dir' => '',      'w' => 2000, 'h' => 2000, 'mode' => 'inset_plain'], // site image at cover root
                ],
                $coverBase,
                $stats,
                // Fallback source if the original master is missing: the 2000px site image at the cover root.
                $coverBase . $series->cover_filename
            );
            if ($res['newName'] !== null && $res['newName'] !== $series->cover_filename) {
                if (!$this->dry) {
                    $series->cover_filename = $res['newName'];
                    $series->save(false, ['cover_filename']);
                }
                $this->stdout("    DB cover_filename -> {$res['newName']}\n", Console::FG_YELLOW);
            }
            $stats['covers']++;
        }

        $this->stdout("\nDone. ", Console::BOLD);
        $this->stdout(sprintf(
            "photos=%d covers=%d converted(PNG→JPG)=%d webp-skipped=%d old-removed=%d errors=%d\n",
            $stats['photos'], $stats['covers'], $stats['converted'], $stats['skipped'], $stats['removed'], $stats['errors']
        ));

        return $stats['errors'] > 0 ? ExitCode::UNSPECIFIED_ERROR : ExitCode::OK;
    }

    /**
     * Ensure a JPG master + rebuild WebP derivatives for a single item.
     *
     * @param string $originalDir directory holding the master (with trailing slash)
     * @param string $filename current stored filename (may be .png/.jpg/etc.)
     * @param array $derivatives list of ['dir','w','h','mode']
     * @param string $rootBase base dir for derivative folders (with trailing slash)
     * @param array $stats by-ref counters
     * @param string|null $fallbackSource source to use if the master is missing
     * @return array ['newName' => string|null]
     */
    private function processOne($originalDir, $filename, array $derivatives, $rootBase, array &$stats, $fallbackSource = null)
    {
        $base = pathinfo($filename, PATHINFO_FILENAME);
        $ext = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        $masterPath = $originalDir . $filename;
        $jpgMasterPath = $originalDir . $base . '.jpg';
        $newName = null;

        // Locate a usable source image.
        $source = null;
        if (is_file($masterPath)) {
            $source = $masterPath;
        } elseif (is_file($jpgMasterPath)) {
            $source = $jpgMasterPath;
            $newName = $base . '.jpg';
        } elseif ($fallbackSource !== null && is_file($fallbackSource)) {
            $source = $fallbackSource;
            $this->stdout("  ! {$filename}: master missing, using fallback " . basename($fallbackSource) . "\n", Console::FG_YELLOW);
        } else {
            $this->stdout("  ! {$filename}: no source image found — skipped\n", Console::FG_RED);
            $stats['errors']++;
            return ['newName' => null];
        }

        $this->stdout("  • {$filename}\n");

        try {
            $imagine = Image::getImagine();

            // 1. Make sure the master is JPG.
            if ($ext !== 'jpg' && $ext !== 'jpeg') {
                $newName = $base . '.jpg';
                if (!$this->dry) {
                    $img = $imagine->open($source);
                    (new Autorotate())->apply($img);
                    $img->save($jpgMasterPath, ['jpeg_quality' => Img::JPEG_QUALITY]);
                    if (is_file($masterPath) && $masterPath !== $jpgMasterPath) {
                        @unlink($masterPath); // remove the old PNG master
                    }
                }
                $source = $jpgMasterPath;
                $stats['converted']++;
                $this->stdout("    converted master {$ext} → jpg\n", Console::FG_GREEN);
            }

            // 2. Rebuild WebP derivatives.
            foreach ($derivatives as $d) {
                $outDir = $rootBase . ($d['dir'] === '' ? '' : $d['dir'] . '/');
                $outPath = $outDir . $base . '.webp';
                if (!$this->dry) {
                    $this->makeDerivative($source, $outPath, $d['w'], $d['h'], $d['mode']);
                }

                // 3. Remove the stale non-WebP derivative, if present.
                if (!$this->keepOld) {
                    $oldExt = $ext ?: 'jpg';
                    foreach (array_unique([$ext, 'jpg', 'jpeg', 'png']) as $oe) {
                        if ($oe === 'webp' || $oe === '') continue;
                        $oldPath = $outDir . $base . '.' . $oe;
                        if (is_file($oldPath)) {
                            if (!$this->dry) @unlink($oldPath);
                            $stats['removed']++;
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            $this->stdout("    ERROR: " . $e->getMessage() . "\n", Console::FG_RED);
            $stats['errors']++;
            return ['newName' => null];
        }

        return ['newName' => $newName];
    }

    /**
     * Produce one WebP derivative. Modes:
     *   - inset_plain: scale to fit within w×h (no upscale), keep aspect
     *   - pad_square:  scale to fit, then center on a w×h canvas (matches resizeImage)
     *   - outbound:    crop to fill exactly w×h
     */
    private function makeDerivative($source, $outPath, $width, $height, $mode)
    {
        $imagine = Image::getImagine();
        $size = new Box($width, $height);

        if ($mode === 'outbound') {
            $image = $imagine->open($source);
            (new Autorotate())->apply($image);
            $image->thumbnail($size, ImageInterface::THUMBNAIL_OUTBOUND)
                ->save($outPath, ['webp_quality' => Img::WEBP_QUALITY]);
            return;
        }

        if ($mode === 'pad_square') {
            $image = $imagine->open($source);
            (new Autorotate())->apply($image);
            $resized = $image->thumbnail($size, ImageInterface::THUMBNAIL_INSET);
            $r = $resized->getSize();
            $canvas = $imagine->create($size);
            $x = $r->getWidth() < $width ? (int) (($width - $r->getWidth()) / 2) : 0;
            $y = $r->getHeight() < $height ? (int) (($height - $r->getHeight()) / 2) : 0;
            $canvas->paste($resized, new Point($x, $y))
                ->save($outPath, ['webp_quality' => Img::WEBP_QUALITY]);
            return;
        }

        // inset_plain
        $image = $imagine->open($source);
        (new Autorotate())->apply($image);
        $image->thumbnail($size)
            ->save($outPath, ['webp_quality' => Img::WEBP_QUALITY]);
    }
}
