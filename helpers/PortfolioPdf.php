<?php

namespace app\helpers;

use app\models\Paintings;
use app\models\Photos;
use app\models\Sections;
use Yii;
use yii\helpers\FileHelper;

/**
 * Section PDF portfolios: a cover page, then every work of the section's
 * mosaic, one photo per A4 landscape page, captioned under each work's first
 * photo. Spec: docs/superpowers/specs/2026-09-18-projects-and-portfolio-design.md
 *
 * Built on first request and cached under runtime/portfolio/. The filename
 * carries a hash of everything that affects the output (manifest()), so any
 * edit to the section's works yields a new file; older files for the section
 * are deleted after a successful build. Nothing has to be invalidated by hand.
 * The manifest also records each source image's mtime/size, so a photo that
 * was missing (and so skipped) at build time and is later restored on disk
 * gets a fresh cache key too, instead of the incomplete PDF being served
 * forever.
 *
 * Sized for Hetzner Webhosting S (192 MB, 120 s): images come from the
 * ~1500 px original_site WebP derivatives, are converted to JPEG one at a
 * time (FPDF cannot read WebP) and freed immediately.
 */
class PortfolioPdf
{
    /** Bump whenever the layout changes, so cached PDFs are rebuilt. */
    const LAYOUT_VERSION = 1;

    const SITE_LABEL = 'katiaoskina.com';
    const JPEG_QUALITY = 85;

    // A4 landscape, millimetres.
    const PAGE_W = 297;
    const PAGE_H = 210;
    const MARGIN = 15;
    const FOOTER_H = 12;  // kept free at the bottom for the page footer
    const CAPTION_H = 16; // extra space under the first photo of each work

    /**
     * Path to the section's PDF, building it first if the cached copy is
     * missing or stale. Always rendered in English.
     *
     * @param Paintings[] $works from Paintings::findForSectionMosaic()
     * @throws \Throwable when the build fails (nothing is cached then)
     */
    public static function get(Sections $section, array $works): string
    {
        $prev = Yii::$app->language;
        Yii::$app->language = 'en';
        try {
            $key = sha1(json_encode(self::manifest($section, $works), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
            $dir = Yii::getAlias('@runtime/portfolio');
            $path = $dir . '/' . $section->slug . '-' . $key . '.pdf';
            if (is_file($path)) {
                return $path;
            }

            FileHelper::createDirectory($dir);
            $tmp = $path . '.' . getmypid() . '.tmp';
            try {
                self::build($section, $works, $tmp);
            } catch (\Throwable $e) {
                @unlink($tmp);
                throw $e;
            }
            // A concurrent request may have finished the same build first;
            // the files are identical, so losing that race is harmless.
            if (!@rename($tmp, $path)) {
                @unlink($tmp);
                if (!is_file($path)) {
                    throw new \RuntimeException("Could not move the portfolio PDF into place: {$path}");
                }
            }
            self::pruneOld($dir, $section->slug, $path);
            self::pruneStaleTemp($dir);
            return $path;
        } finally {
            Yii::$app->language = $prev;
        }
    }

    /** @param Paintings[] $works */
    public static function hasContent(array $works): bool
    {
        return self::photoCount($works) > 0;
    }

    /** @param Paintings[] $works */
    public static function photoCount(array $works): int
    {
        $n = 0;
        foreach ($works as $w) {
            $n += count($w->portfolioPhotos());
        }
        return $n;
    }

    public static function countPages(string $pdfPath): int
    {
        return (int) preg_match_all('#/Type\s*/Page(?!s)#', (string) file_get_contents($pdfPath));
    }

    /** e.g. "Katia-Oskina-Commercial-illustrations.pdf" (call with language 'en'). */
    public static function downloadName(Sections $section): string
    {
        $base = Yii::$app->params['siteName'] . ' ' . $section->tr('title');
        return trim(preg_replace('/[^A-Za-z0-9]+/', '-', $base), '-') . '.pdf';
    }

    /**
     * Everything that affects the PDF, in a canonical order. Its hash is the
     * cache key, so anything rendered must be derived from here or be a
     * constant covered by LAYOUT_VERSION. Each photo's tuple also carries its
     * source file's mtime/size (false when missing), so a photo that is
     * missing at build time and later restored on disk changes the key.
     *
     * @param Paintings[] $works
     */
    private static function manifest(Sections $section, array $works): array
    {
        $items = [];
        foreach ($works as $w) {
            $photos = [];
            foreach ($w->portfolioPhotos() as $ph) {
                $src = self::sourcePath($ph);
                $photos[] = [(int) $ph->id, (string) $ph->filename, @filemtime($src), @filesize($src)];
            }
            if ($photos) {
                $items[] = [(int) $w->id, self::title($w), PaintingPresenter::metaLine($w), $photos];
            }
        }
        $p = Yii::$app->params;
        return [
            'layout' => self::LAYOUT_VERSION,
            'section' => (string) $section->tr('title'),
            'contact' => [$p['siteName'], $p['contactEmail'], $p['contactLocation']],
            'works' => $items,
        ];
    }

    /** @param Paintings[] $works */
    private static function build(Sections $section, array $works, string $dest): void
    {
        if (!defined('_SYSTEM_TTFONTS')) {
            // tFPDF looks for TTFs here. It also tries to write a metrics cache
            // next to its own font dir and silently skips that when it is not
            // writable (vendor/ on the host), so no extra writable dir is needed.
            define('_SYSTEM_TTFONTS', Yii::getAlias('@app/assets/fonts') . '/');
        }
        $params = Yii::$app->params;

        $pdf = new PortfolioDocument('L', 'mm', 'A4');
        $pdf->footerText = $params['siteName'] . ' · ' . self::SITE_LABEL;
        $pdf->SetAutoPageBreak(false);
        $pdf->SetMargins(self::MARGIN, self::MARGIN, self::MARGIN);
        $pdf->AddFont('Jost', '', 'Jost-Regular.ttf', true);
        $pdf->AddFont('Jost', 'B', 'Jost-Medium.ttf', true);
        $pdf->SetTitle($params['siteName'] . ' — ' . $section->tr('title'), true);
        $pdf->SetAuthor($params['siteName'], true);

        self::coverPage($pdf, $section);

        $tmpDir = Yii::getAlias('@runtime/portfolio/tmp');
        FileHelper::createDirectory($tmpDir);
        foreach ($works as $w) {
            $first = true;
            foreach ($w->portfolioPhotos() as $photo) {
                $jpg = self::toJpeg($photo, $tmpDir);
                if ($jpg === null) {
                    Yii::warning("Portfolio: skipped photo #{$photo->id} of work #{$w->id} (missing or unreadable)", __METHOD__);
                    continue;
                }
                try {
                    $pdf->AddPage();
                    self::placeImage($pdf, $jpg, $first);
                    if ($first) {
                        self::caption($pdf, $w);
                    }
                } finally {
                    // Delete the temp JPEG even if Image()/getimagesize() throws.
                    @unlink($jpg);
                }
                $first = false;
            }
        }

        $pdf->Output('F', $dest);
    }

    private static function coverPage(PortfolioDocument $pdf, Sections $section): void
    {
        $p = Yii::$app->params;
        $pdf->AddPage();

        $pdf->SetXY(self::MARGIN + 10, 72);
        $pdf->SetFont('Jost', '', 36);
        $pdf->SetTextColor(20, 18, 16);
        $pdf->Cell(0, 16, $p['siteName'], 0, 2);

        $pdf->SetFont('Jost', '', 18);
        $pdf->SetTextColor(90, 86, 82);
        $pdf->Cell(0, 11, (string) $section->tr('title'), 0, 2);

        $pdf->SetXY(self::MARGIN + 10, 150);
        $pdf->SetFont('Jost', '', 10.5);
        $pdf->SetTextColor(120, 116, 112);
        foreach ([self::SITE_LABEL, $p['contactEmail'], $p['contactLocation']] as $line) {
            $pdf->Cell(0, 6, $line, 0, 2);
        }
    }

    /** Fit the image into the page box (above the caption band if any), centred. */
    private static function placeImage(PortfolioDocument $pdf, string $jpg, bool $withCaption): void
    {
        [$pxW, $pxH] = getimagesize($jpg);
        $boxW = self::PAGE_W - 2 * self::MARGIN;
        $boxH = self::PAGE_H - self::MARGIN - self::FOOTER_H - ($withCaption ? self::CAPTION_H : 0);
        $scale = min($boxW / $pxW, $boxH / $pxH);
        $w = $pxW * $scale;
        $h = $pxH * $scale;
        $pdf->Image($jpg, (self::PAGE_W - $w) / 2, self::MARGIN + ($boxH - $h) / 2, $w, $h, 'JPG');
    }

    private static function caption(PortfolioDocument $pdf, Paintings $w): void
    {
        $pdf->SetXY(self::MARGIN, self::PAGE_H - self::FOOTER_H - self::CAPTION_H + 3);
        $title = self::title($w);
        if ($title !== '') {
            $pdf->SetFont('Jost', 'B', 11);
            $pdf->SetTextColor(20, 18, 16);
            $pdf->Cell(0, 6, $title, 0, 2, 'C');
        }
        $meta = PaintingPresenter::metaLine($w);
        if ($meta !== '') {
            $pdf->SetFont('Jost', '', 8.5);
            $pdf->SetTextColor(120, 116, 112);
            $pdf->Cell(0, 5, $meta, 0, 2, 'C');
        }
    }

    private static function title(Paintings $w): string
    {
        return trim((string) $w->tr('name', true));
    }

    /** Absolute path to a photo's original_site WebP derivative (may not exist). */
    private static function sourcePath(Photos $photo): string
    {
        return Yii::getAlias('@app/web/paintings_photo/original_site/') . Img::webp($photo->filename);
    }

    /** original_site WebP → temporary JPEG; null if the source is missing or unreadable. */
    private static function toJpeg(Photos $photo, string $tmpDir): ?string
    {
        $src = self::sourcePath($photo);
        if (!is_file($src) || !function_exists('imagecreatefromwebp')) {
            return null;
        }
        $img = @imagecreatefromwebp($src);
        if (!$img) {
            return null;
        }
        $dst = $tmpDir . '/' . (int) $photo->id . '-' . getmypid() . '.jpg';
        $ok = imagejpeg($img, $dst, self::JPEG_QUALITY);
        imagedestroy($img);
        return $ok ? $dst : null;
    }

    private static function pruneOld(string $dir, string $slug, string $keep): void
    {
        $pattern = '/^' . preg_quote($slug, '/') . '-[0-9a-f]{40}\.pdf$/';
        foreach (glob($dir . '/*.pdf') ?: [] as $file) {
            if ($file !== $keep && preg_match($pattern, basename($file))) {
                @unlink($file);
            }
        }
    }

    /**
     * Deletes stale leftovers a dead build (OOM / hit the timeout) can leave
     * behind: the renamed-in-place *.tmp file, and per-photo JPEGs under
     * tmp/. Only files older than 10 minutes are touched, so an in-progress
     * build running alongside this one is never disturbed.
     */
    private static function pruneStaleTemp(string $dir): void
    {
        $cutoff = time() - 600;
        foreach (glob($dir . '/*.tmp') ?: [] as $file) {
            if ((@filemtime($file) ?: PHP_INT_MAX) < $cutoff) {
                @unlink($file);
            }
        }
        foreach (glob($dir . '/tmp/*.jpg') ?: [] as $file) {
            if ((@filemtime($file) ?: PHP_INT_MAX) < $cutoff) {
                @unlink($file);
            }
        }
    }
}
