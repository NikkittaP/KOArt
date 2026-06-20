<?php

/**
 * Admin / archive panel layout (Phase 4b).
 * Dark left sidebar so the owner always knows they are in admin, not on the
 * public site. Custom CSS only (AdminAsset) — no Bootstrap, no kartik chrome.
 *
 * Views may set:
 * @var string $this->params['adminNav']  active nav key: dashboard|works|series|
 *                                         sections|genres|grounds
 */

use app\assets\AdminAsset;
use app\helpers\AdminPrefs;
use yii\helpers\Html;
use yii\helpers\Url;

AdminAsset::register($this);

$active = $this->params['adminNav'] ?? null;
$lang = Yii::$app->language;
$back = Yii::$app->request->url;
$hideArchive = AdminPrefs::hideArchive();

$nav = [
    'dashboard' => ['Dashboard', ['/admin/index'], '▦'],
    'works'     => ['Works',     ['/paintings/index'], '▥'],
    'series'    => ['Series',    ['/series/index'], '❏'],
    'sections'  => ['Sections',  ['/sections/index'], '☰'],
    'genres'    => ['Genres',    ['/art-genres/index'], '#'],
    'grounds'   => ['Grounds',   ['/grounds/index'], '◇'],
    'materials' => ['Materials', ['/materials/index'], '◈'],
    'about'     => ['About',     ['/about/edit'], '✎'],
];

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> — <?= Yii::t('admin', 'Admin') ?> · Katia Oskina</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Jost:wght@300;400;500&display=swap" rel="stylesheet">
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>

<aside class="asidebar" id="asidebar">
    <div class="brand">
        <span class="wm">Katia Oskina</span>
        <span class="abadge"><?= Yii::t('admin', 'Archive') ?></span>
        <button class="ahamburger" id="aburger" aria-label="Menu"><span></span><span></span><span></span></button>
    </div>
    <div class="sub"><?= Yii::t('admin', 'Admin panel') ?></div>

    <nav class="anav">
        <?php foreach ($nav as $key => [$label, $route, $icon]): ?>
            <a href="<?= Url::to($route) ?>"<?= $key === $active ? ' class="active"' : '' ?>>
                <span class="ic"><?= $icon ?></span><?= Yii::t('admin', $label) ?>
            </a>
        <?php endforeach; ?>
        <div class="sep"></div>
        <a href="<?= Url::to(['/site/index', 'language' => 'en']) ?>" target="_blank" rel="noopener">
            <span class="ic">↗</span><?= Yii::t('admin', 'View site') ?>
        </a>
    </nav>

    <div class="foot">
        <a class="arch-toggle <?= $hideArchive ? 'on' : '' ?>"
           href="<?= Url::to(['/admin/archive', 'hide' => $hideArchive ? '0' : '1', 'back' => $back]) ?>"
           title="<?= Yii::t('admin', 'When on, hidden (archived) works are excluded from lists and counts.') ?>">
            <span><?= Yii::t('admin', 'Hide archive') ?></span>
            <span class="sw"></span>
        </a>
        <div class="langs">
            <a href="<?= Url::to(['/admin/lang', 'l' => 'en', 'back' => $back]) ?>" class="<?= $lang === 'en' ? 'on' : '' ?>">EN</a>
            <a href="<?= Url::to(['/admin/lang', 'l' => 'ru', 'back' => $back]) ?>" class="<?= strpos($lang, 'ru') === 0 ? 'on' : '' ?>">RU</a>
        </div>
        <?= Html::a(Yii::t('admin', 'Log out'), ['/site/logout'], [
            'class' => 'logout',
            'data' => ['method' => 'post'],
        ]) ?>
    </div>
</aside>

<main class="amain">
    <?php foreach (Yii::$app->session->getAllFlashes() as $type => $message): ?>
        <?php foreach ((array) $message as $msg): ?>
            <div class="flash <?= Html::encode($type) ?>"><?= $msg ?></div>
        <?php endforeach; ?>
    <?php endforeach; ?>

    <?= $content ?>
</main>

<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
