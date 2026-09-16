<?php

namespace app\helpers;

use app\models\Paintings;
use Yii;

/**
 * schema.org JSON-LD for the public site.
 *
 * Two jobs:
 *
 *  1. Tell search engines what this site is - a named artist's portfolio, not
 *     a shop or a blog - so it can be shown as a person/creative-work result.
 *  2. State, in machine-readable form, that the artist works in Malmö,
 *     Sweden. The site is in English and says "Malmö, Sweden" only in the
 *     sidebar footer; a Person with a postalAddress is what actually gives
 *     Swedish local search something to match on.
 *
 * Kept as plain arrays so the layout can merge page-level entities (a
 * VisualArtwork, a breadcrumb trail) into one @graph.
 */
class Schema
{
    /**
     * Stable @id for the artist, so other entities can reference her rather
     * than repeating the whole Person object.
     *
     * @return string
     */
    public static function personId()
    {
        return Seo::absolute('/about') . '#person';
    }

    /**
     * The artist.
     *
     * @return array
     */
    public static function person()
    {
        $params = Yii::$app->params;

        $sameAs = array_values(array_filter([
            $params['socialInstagram'] ?? null,
            $params['socialBehance'] ?? null,
            $params['socialLinkedin'] ?? null,
        ]));

        return [
            '@type' => 'Person',
            '@id' => self::personId(),
            'name' => $params['siteName'],
            'url' => Seo::absolute('/'),
            'jobTitle' => 'Illustrator and artist',
            'description' => $params['siteDescription'],
            'email' => 'mailto:' . $params['contactEmail'],
            'image' => Seo::absolute('/about_photo/about.jpg'),
            'sameAs' => $sameAs,
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $params['artistCity'],
                'addressRegion' => $params['artistRegion'],
                'addressCountry' => $params['artistCountry'],
            ],
            'workLocation' => [
                '@type' => 'Place',
                'name' => $params['contactLocation'],
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $params['artistCity'],
                    'addressCountry' => $params['artistCountry'],
                ],
                'geo' => [
                    '@type' => 'GeoCoordinates',
                    'latitude' => $params['artistLatitude'],
                    'longitude' => $params['artistLongitude'],
                ],
            ],
        ];
    }

    /**
     * The site itself, credited to the artist.
     *
     * @return array
     */
    public static function website()
    {
        return [
            '@type' => 'WebSite',
            '@id' => Seo::absolute('/') . '#website',
            'url' => Seo::absolute('/'),
            'name' => Yii::$app->params['siteName'],
            'description' => Yii::$app->params['siteDescription'],
            'inLanguage' => Yii::$app->language,
            'author' => ['@id' => self::personId()],
            'copyrightHolder' => ['@id' => self::personId()],
        ];
    }

    /**
     * A single artwork, with whatever of medium/surface/year/size is filled in.
     *
     * VisualArtwork is the closest schema.org type to a painting or drawing
     * and carries the art-specific properties (artMedium, artworkSurface,
     * artform) that a generic CreativeWork cannot express.
     *
     * @param Paintings $painting
     * @param string $url canonical URL of the work page
     * @param string $image absolute image URL
     * @param string $description
     * @return array
     */
    public static function visualArtwork(Paintings $painting, $url, $image, $description = '')
    {
        $name = $painting->tr('name', true) ?: ('#' . $painting->id);
        $year = PaintingPresenter::yearLabel($painting);
        $medium = PaintingPresenter::materialsLabel($painting);
        $surface = PaintingPresenter::groundLabel($painting);

        $artwork = [
            '@type' => 'VisualArtwork',
            '@id' => $url . '#artwork',
            'name' => $name,
            'url' => $url,
            'image' => $image,
            'creator' => ['@id' => self::personId()],
            'copyrightHolder' => ['@id' => self::personId()],
            'inLanguage' => Yii::$app->language,
        ];

        if ($description !== '') {
            $artwork['description'] = $description;
        }
        if ($year !== '') {
            $artwork['dateCreated'] = $year;
        }
        if ($medium !== '') {
            $artwork['artMedium'] = $medium;
        }
        if ($surface !== '') {
            $artwork['artworkSurface'] = $surface;
        }

        // Dimensions are stored in centimetres.
        if (!empty($painting->width) && !empty($painting->height)) {
            $artwork['width'] = [
                '@type' => 'QuantitativeValue',
                'value' => (float) $painting->width,
                'unitCode' => 'CMT',
            ];
            $artwork['height'] = [
                '@type' => 'QuantitativeValue',
                'value' => (float) $painting->height,
                'unitCode' => 'CMT',
            ];
        }

        return $artwork;
    }

    /**
     * Breadcrumb trail.
     *
     * @param array $items ordered [label => absolute URL]
     * @return array
     */
    public static function breadcrumbs(array $items)
    {
        $elements = [];
        $position = 1;
        foreach ($items as $label => $url) {
            $elements[] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'name' => $label,
                'item' => $url,
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $elements,
        ];
    }

    /**
     * Render one @graph document.
     *
     * @param array $entities
     * @return string JSON, ready to drop inside a ld+json script tag
     */
    public static function render(array $entities)
    {
        // JSON_HEX_TAG escapes < and > as < / >. Titles and
        // descriptions come from the database, so without it a stray
        // "</script>" in an artwork description would close the tag and dump
        // the rest of the graph into the page as markup.
        return json_encode(
            ['@context' => 'https://schema.org', '@graph' => array_values($entities)],
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT | JSON_HEX_TAG | JSON_HEX_AMP
        );
    }
}
