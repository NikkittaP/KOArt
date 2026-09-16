<?php

namespace app\controllers;

use app\models\Paintings;
use app\models\PaintingsToSeries;
use app\models\Sections;
use app\models\Series;
use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * /sitemap.xml, generated from the database.
 *
 * There was no sitemap at all (the URL 404'd) and robots.txt pointed at
 * nothing, so search engines had to discover every series and work by
 * crawling the nav. A generated sitemap also lets us declare the /ru/
 * translation of each page as an alternate rather than as a separate URL.
 *
 * Kept deliberately in step with what the site itself links: a work only gets
 * its own page in the navigation when it has a description, so only those are
 * listed here. Give a work a description in the admin and it enters the
 * sitemap on the next crawl.
 */
class SitemapController extends Controller
{
    public $enableCsrfValidation = false;

    /** Cache duration. The content changes only when the owner edits it. */
    const CACHE_SECONDS = 3600;

    /**
     * @return Response
     */
    public function actionIndex()
    {
        $xml = Yii::$app->cache->getOrSet(
            'sitemap.xml.' . Yii::$app->request->getHostInfo(),
            function () {
                // renderPartial, not render: XML must not be wrapped in a
                // layout (and this project has no "main" layout anyway - the
                // layouts are public/admin/blank).
                return $this->renderPartial('index', ['entries' => $this->collect()]);
            },
            self::CACHE_SECONDS
        );

        $response = Yii::$app->response;
        $response->format = Response::FORMAT_RAW;
        $response->headers->set('Content-Type', 'application/xml; charset=UTF-8');
        $response->content = $xml;

        return $response;
    }

    /**
     * Every public URL, as [path, lastmod, priority, changefreq].
     *
     * Paths are language-neutral (no /ru prefix); the view expands each one
     * into its per-language alternates.
     *
     * @return array
     */
    private function collect()
    {
        $entries = [];

        // --- Sections (the homepage is the "artworks" section) --------------
        $sectionUpdated = $this->latestWorkPerSection();
        foreach (Sections::find()->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->all() as $section) {
            $entries[] = [
                'path' => $section->slug === 'artworks' ? '/' : '/' . $section->slug,
                'lastmod' => $sectionUpdated[$section->id] ?? null,
                'priority' => $section->slug === 'artworks' ? '1.0' : '0.9',
                'changefreq' => 'weekly',
            ];
        }

        // --- Static pages ---------------------------------------------------
        $entries[] = ['path' => '/about', 'lastmod' => null, 'priority' => '0.7', 'changefreq' => 'yearly'];
        $entries[] = ['path' => '/privacy', 'lastmod' => null, 'priority' => '0.1', 'changefreq' => 'yearly'];

        // --- Series ---------------------------------------------------------
        $seriesUpdated = $this->latestWorkPerSeries();
        foreach (Series::find()->where(['isVisible' => 1])->orderBy(['id' => SORT_ASC])->all() as $series) {
            $entries[] = [
                'path' => '/series/' . $series->id,
                'lastmod' => $seriesUpdated[$series->id] ?? null,
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        // --- Individual works ----------------------------------------------
        // Only works that actually have a page in the navigation, i.e. those
        // with a description - see views/site/section.php, which links
        // /work/<id> only when descPlain() is non-empty.
        $works = Paintings::find()
            ->where(['isVisible' => 1])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        foreach ($works as $work) {
            if (!$this->hasDescription($work)) {
                continue;
            }
            $entries[] = [
                'path' => '/work/' . $work->id,
                'lastmod' => $work->datetime_update ?: null,
                'priority' => '0.6',
                'changefreq' => 'yearly',
            ];
        }

        return $entries;
    }

    /**
     * True when the work has descriptive text in any language, which is what
     * makes it worth its own indexable page.
     *
     * Checked against the raw columns rather than through tr(), which resolves
     * against the *current* request language: the work page exists in both
     * languages, so text in either one is enough to list it.
     *
     * @param Paintings $work
     * @return bool
     */
    private function hasDescription(Paintings $work)
    {
        foreach (['description', 'description_en'] as $attribute) {
            if (!$work->hasAttribute($attribute)) {
                continue;
            }
            if (trim(strip_tags((string) $work->$attribute)) !== '') {
                return true;
            }
        }

        return false;
    }

    /**
     * Newest work update per section, for <lastmod> on section pages.
     *
     * @return array section_id => datetime
     */
    private function latestWorkPerSection()
    {
        $rows = Paintings::find()
            ->select(['section_id', 'updated' => 'MAX(datetime_update)'])
            ->where(['isVisible' => 1])
            ->groupBy('section_id')
            ->asArray()
            ->all();

        return array_column($rows, 'updated', 'section_id');
    }

    /**
     * Newest work update per series, for <lastmod> on series pages. Series
     * carry no timestamp of their own, so the works they contain stand in.
     *
     * @return array series_id => datetime
     */
    private function latestWorkPerSeries()
    {
        $rows = PaintingsToSeries::find()
            ->alias('pts')
            ->select(['pts.series_id', 'updated' => 'MAX(p.datetime_update)'])
            ->innerJoin(['p' => Paintings::tableName()], 'p.id = pts.painting_id')
            ->where(['p.isVisible' => 1])
            ->groupBy('pts.series_id')
            ->asArray()
            ->all();

        return array_column($rows, 'updated', 'series_id');
    }
}
