<?php

/**
 * sitemap.xml body.
 *
 * Each language-neutral path is emitted once per language, and every <url>
 * carries the full set of xhtml:link alternates - the sitemap equivalent of
 * the hreflang tags in the public layout. That is what tells Google the
 * English page and its /ru/ twin are one page in two languages rather than
 * two competing URLs.
 *
 * @var \yii\web\View $this
 * @var array $entries each: path, lastmod, priority, changefreq
 */

use app\helpers\Seo;

/**
 * Dates come out of MySQL as "Y-m-d H:i:s"; <lastmod> wants W3C datetime.
 *
 * @param string|null $value
 * @return string|null
 */
$w3cDate = function ($value) {
    if (empty($value)) {
        return null;
    }
    $timestamp = strtotime((string) $value);

    return $timestamp ? date('Y-m-d', $timestamp) : null;
};

echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:xhtml="http://www.w3.org/1999/xhtml">
<?php foreach ($entries as $entry): ?>
<?php
    $alternates = Seo::alternatesForPath($entry['path']);
    // With a single language configured there are no alternates; fall back to
    // the plain absolute URL so the sitemap is still complete.
    $urls = $alternates ?: [Seo::defaultLanguage() => Seo::absolute($entry['path'])];
    $lastmod = $w3cDate($entry['lastmod'] ?? null);
?>
<?php foreach ($urls as $language => $url): ?>
<?php if ($language === 'x-default') { continue; } ?>
    <url>
        <loc><?= htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?></loc>
<?php if ($lastmod !== null): ?>
        <lastmod><?= $lastmod ?></lastmod>
<?php endif; ?>
        <changefreq><?= $entry['changefreq'] ?></changefreq>
        <priority><?= $entry['priority'] ?></priority>
<?php foreach ($alternates as $altLanguage => $altUrl): ?>
        <xhtml:link rel="alternate" hreflang="<?= htmlspecialchars($altLanguage, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>" href="<?= htmlspecialchars($altUrl, ENT_XML1 | ENT_QUOTES, 'UTF-8') ?>"/>
<?php endforeach; ?>
    </url>
<?php endforeach; ?>
<?php endforeach; ?>
</urlset>
