<?php

namespace app\helpers;

use app\models\Paintings;
use app\models\Photos;
use app\models\Series;

/**
 * Presentation helpers for rendering Paintings/Series on the public site
 * (mosaic hover overlay, lightbox captions, series meta line).
 *
 * Phase 3 port of design_mockups_v2: the mockup's per-work metadata
 * (materials / year / size / description) was placeholder; this class
 * derives the same labels from real DB data.
 */
class PaintingPresenter
{
    /**
     * Comma-separated materials label, e.g. "Watercolor, ink".
     */
    public static function materialsLabel(Paintings $painting): string
    {
        $names = [];
        foreach ($painting->materialsToPaintings as $mtp) {
            if ($mtp->material) {
                $names[] = $mtp->material->tr('name');
            }
        }
        return implode(', ', $names);
    }

    /**
     * Ground (support/base) label, bilingual. Empty string if none set.
     */
    public static function groundLabel(Paintings $painting): string
    {
        return $painting->ground ? (string) $painting->ground->tr('name') : '';
    }

    /**
     * Year extracted from paintings.date (e.g. "2024"). Empty string if unset.
     */
    public static function yearLabel(Paintings $painting): string
    {
        if (empty($painting->date)) {
            return '';
        }
        $ts = strtotime($painting->date);
        return $ts ? date('Y', $ts) : '';
    }

    /**
     * Size label, e.g. "60 × 80 cm". Empty string if either dimension missing.
     */
    public static function sizeLabel(Paintings $painting): string
    {
        if (empty($painting->width) || empty($painting->height)) {
            return '';
        }
        $w = self::trimNumber($painting->width);
        $h = self::trimNumber($painting->height);
        return "{$w} × {$h} cm";
    }

    /**
     * "Materials · ground · year · size", skipping empty parts. Used by the
     * work page header and the PDF portfolio captions.
     */
    public static function metaLine(Paintings $painting): string
    {
        return implode(' · ', array_filter([
            self::materialsLabel($painting),
            self::groundLabel($painting),
            self::yearLabel($painting),
            self::sizeLabel($painting),
        ]));
    }

    /** Number of photos of a work (uses the eager-loaded relation when present). */
    public static function photoCount(Paintings $painting): int
    {
        return count($painting->photos);
    }

    /**
     * Whether an artwork's lightbox should link to its own page: there is more
     * to see there than the viewer shows — a description, or extra photos.
     */
    public static function hasOwnPage(Paintings $painting): bool
    {
        return self::descPlain($painting) !== '' || self::photoCount($painting) > 1;
    }

    /** Lightbox link text for an artwork that hasOwnPage(). */
    public static function moreLabel(Paintings $painting): string
    {
        if (self::descPlain($painting) !== '') {
            return 'Read more →';
        }
        return 'All photos (' . self::photoCount($painting) . ') →';
    }

    /** Second hover line on a project tile, e.g. "Project · 8 images". */
    public static function projectLabel(Paintings $painting): string
    {
        $n = self::photoCount($painting);
        return $n > 1 ? "Project · {$n} images" : 'Project';
    }

    /**
     * Plain-text description, safe to drop into an HTML data-* attribute
     * (the lightbox JS expects flat text, not markup, for data-desc).
     */
    public static function descPlain(Paintings $painting): string
    {
        $description = $painting->tr('description');
        if (empty($description)) {
            return '';
        }
        $text = strip_tags($description);
        $text = html_entity_decode($text, ENT_QUOTES);
        $text = preg_replace('/\s+/', ' ', $text);
        return trim($text);
    }

    /**
     * Filename of the image to use for a painting in a given variant.
     * Returns null if the painting has no main/first photo.
     *
     * @param string $variant one of: 'sm' (grid/mosaic), 'lg' (lightbox)
     */
    public static function photoUrl(Paintings $painting, string $variant = 'sm'): ?string
    {
        /** @var Photos|null $photo */
        $photo = $painting->mainPhoto ?: ($painting->photos[0] ?? null);
        if (!$photo) {
            return null;
        }
        $dir = $variant === 'lg' ? 'original_site' : 'preview';
        // Derivatives are stored as WebP (the master JPG lives in /original).
        return '/paintings_photo/' . $dir . '/' . Img::webp($photo->filename);
    }

    /**
     * Series meta line, e.g. "2023–2025 · Digital illustration · 5 works".
     * Year range comes from the works' dates; technique takes the first
     * non-empty materials label found (series works are usually one
     * technique — a simplification noted in docs/03-data-model-and-decisions.md).
     *
     * @param Paintings[] $paintings
     */
    public static function seriesMetaLine(Series $series, array $paintings): string
    {
        $years = [];
        $technique = '';
        foreach ($paintings as $p) {
            $y = self::yearLabel($p);
            if ($y) {
                $years[] = (int) $y;
            }
            if (!$technique) {
                $technique = self::materialsLabel($p);
            }
        }

        $parts = [];
        if ($years) {
            $min = min($years);
            $max = max($years);
            $parts[] = $min === $max ? (string) $min : "{$min}–{$max}";
        }
        if ($technique) {
            $parts[] = $technique;
        }
        $count = count($paintings);
        $parts[] = $count . ' work' . ($count === 1 ? '' : 's');

        return implode(' · ', $parts);
    }

    private static function trimNumber($value): string
    {
        $value = (float) $value;
        return rtrim(rtrim(sprintf('%.1f', $value), '0'), '.');
    }
}
