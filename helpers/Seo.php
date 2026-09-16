<?php

namespace app\helpers;

use Yii;
use yii\helpers\StringHelper;

/**
 * Everything the public <head> needs: meta descriptions, canonical URLs and
 * hreflang alternates.
 *
 * Before this existed the site shipped a <title> and nothing else — no
 * description on any page, no canonical, and no hint that /ru/ is a parallel
 * translation of the same content rather than duplicate content.
 *
 * Views fill in $this->params['seo'] (see views/layouts/public.php for the
 * recognised keys); this class only computes values.
 */
class Seo
{
    /** Google truncates around 155–160 characters. */
    const DESC_LIMIT = 160;

    /**
     * Collapse rich text (or plain text) into a single-line meta description.
     *
     * Descriptions in the database are HtmlPurifier-ed rich text, so tags,
     * entities and hard line breaks all have to go before the string is fit
     * for a meta attribute.
     *
     * @param string|null $html
     * @param int $limit
     * @return string
     */
    public static function excerpt($html, $limit = self::DESC_LIMIT)
    {
        $text = (string) $html;
        if ($text === '') {
            return '';
        }

        // <br> and block ends are sentence boundaries, not word boundaries:
        // turn them into spaces before dropping tags, or words glue together.
        $text = preg_replace('~<(br|/p|/div|/li|/h[1-6])[^>]*>~i', ' ', $text);
        $text = strip_tags($text);
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace('/\s+/u', ' ', $text);
        $text = trim($text);

        if ($text === '') {
            return '';
        }

        return StringHelper::truncate($text, $limit, '…');
    }

    /**
     * First non-empty excerpt from the candidates, else the site-wide default.
     *
     * @param array $candidates
     * @return string
     */
    public static function firstExcerpt(array $candidates)
    {
        foreach ($candidates as $candidate) {
            $text = self::excerpt($candidate);
            if ($text !== '') {
                return $text;
            }
        }

        return (string) Yii::$app->params['siteDescription'];
    }

    /**
     * Absolute URL for a path that is already rooted at the web root.
     *
     * @param string $path e.g. "/img/og-default.jpg" or a full URL (returned as is)
     * @return string
     */
    public static function absolute($path)
    {
        $path = (string) $path;
        if ($path === '' || preg_match('~^https?://~i', $path)) {
            return $path;
        }

        return rtrim(Yii::$app->request->getHostInfo(), '/') . '/' . ltrim($path, '/');
    }

    /**
     * Canonical URL of the current request: absolute, query string dropped.
     *
     * Public pages carry no meaningful query parameters, so anything after
     * "?" is a tracking or share artefact that must not fragment the
     * canonical signal.
     *
     * @return string
     */
    public static function canonical()
    {
        $path = Yii::$app->request->getUrl();
        $path = strtok($path, '?');

        // "/index.php" never appears (showScriptName=false) but a stray
        // trailing slash on a sub-path would still split the signal.
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = rtrim($path, '/');
        }

        return self::absolute($path === '' ? '/' : $path);
    }

    /**
     * hreflang alternates for the current page, as [language => absolute URL].
     *
     * Derived from the request path rather than from the URL manager: with
     * enableDefaultLanguageUrlCode = false the mapping is simply "English is
     * the bare path, every other language is /<code>/ + that path", which is
     * far easier to reason about than round-tripping through route creation.
     *
     * @return array
     */
    public static function alternates()
    {
        $languages = Yii::$app->urlManager->languages ?? ['en'];
        if (count($languages) < 2) {
            return [];
        }

        $path = strtok(Yii::$app->request->getUrl(), '?');

        // Strip any existing language prefix to get the neutral path.
        foreach ($languages as $code) {
            if ($path === '/' . $code || strpos($path, '/' . $code . '/') === 0) {
                $path = substr($path, strlen($code) + 1);
                break;
            }
        }
        $path = '/' . ltrim((string) $path, '/');
        $path = $path !== '/' ? rtrim($path, '/') : '/';

        // English is the language without a URL prefix (see config/web.php:
        // enableDefaultLanguageUrlCode = false).
        $default = in_array('en', $languages, true) ? 'en' : reset($languages);

        $out = [];
        foreach ($languages as $code) {
            $out[$code] = $code === $default
                ? self::absolute($path)
                : self::absolute('/' . $code . ($path === '/' ? '' : $path));
        }
        $out['x-default'] = $out[$default];

        return $out;
    }
}
