<?php

/**
 * Public-facing portfolio layout (Phase 3 port of design_mockups_v2).
 * Deliberately separate from views/layouts/main.php (the old admin layout) —
 * see docs/03-data-model-and-decisions.md, "do not re-litigate" Yii2 decision.
 *
 * Expects, optionally, from the view/controller:
 * @var string $this->title            page title (without the " — Katia Oskina" suffix)
 * @var string $this->params['activeNav']  one of: artworks, commercial-illustrations,
 *                                          picturebooks, sketchbooks, about
 */

use yii\helpers\Html;
use yii\helpers\Url;
use app\assets\PublicAsset;
use app\models\Sections;

PublicAsset::register($this);

$activeNav = $this->params['activeNav'] ?? null;
$shopUrl = Yii::$app->params['shopUrl'];
$contactEmail = Yii::$app->params['contactEmail'];
$contactLocation = Yii::$app->params['contactLocation'];
$socialBehance = Yii::$app->params['socialBehance'];
$socialLinkedin = Yii::$app->params['socialLinkedin'];
$socialInstagram = Yii::$app->params['socialInstagram'];
$buildVersion = Yii::$app->params['buildVersion'];

// Nav is built from the DB `sections` (title + order). "artworks" is the
// homepage; the others use the section route. About (static page) and Shop
// (external Etsy link) are fixed items, not DB sections.
$navItems = [];
foreach (Sections::find()->orderBy(['sort' => SORT_ASC, 'id' => SORT_ASC])->all() as $section) {
    $navItems[$section->slug] = [
        'label' => $section->title,
        'url' => $section->slug === 'artworks'
            ? Url::to(['/'])
            : Url::to(['site/section', 'slug' => $section->slug]),
    ];
}
$navItems['about'] = ['label' => 'About', 'url' => Url::to(['site/about'])];

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> — Katia Oskina</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<div class="sidebar">
    <a class="logo-link" href="<?= Url::to(['/']) ?>"><span class="logo logo-text">Katia Oskina</span></a>
    <button class="hamburger" id="burger" aria-label="Menu"><span></span><span></span><span></span></button>
    <nav class="nav" id="nav">
        <?php foreach ($navItems as $key => $item): ?>
            <a href="<?= $item['url'] ?>"<?= $key === $activeNav ? ' class="active"' : '' ?>><?= Html::encode($item['label']) ?></a>
        <?php endforeach; ?>
        <a href="<?= Html::encode($shopUrl) ?>" target="_blank" rel="noopener">Shop ↗</a>
        <div class="foot"><?= Html::encode($contactEmail) ?><br><?= Html::encode($contactLocation) ?></div>
    </nav>
</div>
<main class="main">
<?= $content ?>
<footer class="site-footer">
    <div class="social">
        <a href="<?= Html::encode($socialBehance) ?>" target="_blank" rel="noopener" aria-label="Behance"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M22 7h-7V5h7v2zm1.73 10c-.44 1.3-2.03 3-5.1 3-3.07 0-5.56-1.73-5.56-5.68 0-3.91 2.33-5.92 5.47-5.92 3.08 0 4.96 1.78 5.37 4.43.08.5.11 1.19.1 2.14h-8.06c.13 3.21 3.48 3.31 4.59 2.03h3.19zm-7.69-4h4.97c-.11-1.55-1.14-2.22-2.48-2.22-1.47 0-2.28.77-2.49 2.22zm-9.57 6.99H0V5.02h6.95c5.48.08 5.58 5.44 2.72 6.91 3.46 1.26 3.58 8.06-3.2 8.06zM3 11h3.58c2.51 0 2.91-3-.31-3H3v3zm0 6.02h3.34c3.06 0 2.87-3.02.05-3.02H3v3.02z"/></svg></a>
        <a href="<?= Html::encode($socialLinkedin) ?>" target="_blank" rel="noopener" aria-label="LinkedIn"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20.45 20.45h-3.56v-5.57c0-1.33-.02-3.04-1.85-3.04-1.85 0-2.14 1.45-2.14 2.94v5.67H9.35V9h3.41v1.56h.05c.48-.9 1.64-1.85 3.37-1.85 3.6 0 4.27 2.37 4.27 5.45v6.29zM5.34 7.43a2.06 2.06 0 110-4.13 2.06 2.06 0 010 4.13zM7.12 20.45H3.55V9h3.57v11.45zM22.22 0H1.77C.79 0 0 .77 0 1.73v20.54C0 23.23.79 24 1.77 24h20.45c.98 0 1.78-.77 1.78-1.73V1.73C24 .77 23.2 0 22.22 0z"/></svg></a>
        <a href="<?= Html::encode($socialInstagram) ?>" target="_blank" rel="noopener" aria-label="Instagram"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2.16c3.2 0 3.58.01 4.85.07 1.17.05 1.8.25 2.23.41.56.22.96.48 1.38.9.42.42.68.82.9 1.38.16.43.36 1.06.41 2.23.06 1.27.07 1.65.07 4.85s-.01 3.58-.07 4.85c-.05 1.17-.25 1.8-.41 2.23-.22.56-.48.96-.9 1.38-.42.42-.82.68-1.38.9-.43.16-1.06.36-2.23.41-1.27.06-1.65.07-4.85.07s-3.58-.01-4.85-.07c-1.17-.05-1.8-.25-2.23-.41-.56-.22-.96-.48-1.38-.9-.42-.42-.68-.82-.9-1.38-.16-.43-.36-1.06-.41-2.23C2.17 15.58 2.16 15.2 2.16 12s.01-3.58.07-4.85c.05-1.17.25-1.8.41-2.23.22-.56.48-.96.9-1.38.42-.42.82-.68 1.38-.9.43-.16 1.06-.36 2.23-.41C8.42 2.17 8.8 2.16 12 2.16zM12 0C8.74 0 8.33.01 7.05.07 5.78.13 4.9.33 4.14.63c-.79.3-1.46.72-2.12 1.38C1.36 2.67.94 3.34.63 4.13.33 4.9.13 5.77.07 7.05.01 8.33 0 8.74 0 12s.01 3.67.07 4.95c.06 1.28.26 2.15.56 2.92.3.79.72 1.46 1.38 2.12.66.66 1.33 1.08 2.12 1.38.77.3 1.64.5 2.92.56C8.33 23.99 8.74 24 12 24s3.67-.01 4.95-.07c1.28-.06 2.15-.26 2.92-.56.79-.3 1.46-.72 2.12-1.38.66-.66 1.08-1.33 1.38-2.12.3-.77.5-1.64.56-2.92.06-1.28.07-1.69.07-4.95s-.01-3.67-.07-4.95c-.06-1.28-.26-2.15-.56-2.92-.3-.79-.72-1.46-1.38-2.12C21.33 1.36 20.66.94 19.87.63 19.1.33 18.23.13 16.95.07 15.67.01 15.26 0 12 0zm0 5.84A6.16 6.16 0 1018.16 12 6.16 6.16 0 0012 5.84zM12 16a4 4 0 110-8 4 4 0 010 8zm6.41-10.85a1.44 1.44 0 11-1.44-1.44 1.44 1.44 0 011.44 1.44z"/></svg></a>
        <a href="mailto:<?= Html::encode($contactEmail) ?>" aria-label="Email"><svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2 5.5C2 4.67 2.67 4 3.5 4h17c.83 0 1.5.67 1.5 1.5v13c0 .83-.67 1.5-1.5 1.5h-17C2.67 20 2 19.33 2 18.5v-13zM4 7v.4l8 5 8-5V7H4zm16 2.4l-7.5 4.68a1 1 0 01-1 0L4 9.4V18h16V9.4z"/></svg></a>
    </div>
    <div class="copy">© <?= date('Y') ?> Katia Oskina. All rights reserved. All images and artworks on this site are the property of the artist and may not be reproduced or used without permission.</div>
    <div class="ver">build <?= (int) $buildVersion ?></div>
</footer>
</main>
<div class="lb" id="lb" role="dialog" aria-modal="true" aria-label="Image viewer">
    <button class="x" id="lbx" aria-label="Close">✕</button>
    <button class="nv prev" id="lbprev" aria-label="Previous">‹</button>
    <img id="lbimg" src="" alt="">
    <button class="nv next" id="lbnext" aria-label="Next">›</button>
    <div class="cap" id="lbcap"></div>
</div>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
