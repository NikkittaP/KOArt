<?php

/**
 * Series "blog" detail page (Phase 3 port of design_mockups_v2/series/*.html).
 * Back link to the parent section, title + meta line, series description,
 * then full-width images with per-work descriptions interleaved as text
 * blocks (only when a work actually has a description).
 *
 * @var \yii\web\View $this
 * @var \app\models\Series $series
 * @var \app\models\Paintings[] $paintings
 */

use app\helpers\PaintingPresenter;
use app\helpers\RichText;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $series->tr('name');

$section = $series->section;
$backUrl = $section
    ? ($section->slug === 'artworks' ? Url::to(['/']) : Url::to(['site/section', 'slug' => $section->slug]))
    : Url::to(['/']);
$backLabel = $section ? $section->tr('title') : 'Back';

$metaLine = PaintingPresenter::seriesMetaLine($series, $paintings);
?>
<a class="back" href="<?= $backUrl ?>">← <?= Html::encode($backLabel) ?></a>
<header class="shead pj">
    <h1><?= Html::encode($series->tr('name')) ?></h1>
    <?php if ($metaLine): ?><p class="meta"><?= Html::encode($metaLine) ?></p><?php endif; ?>
</header>

<?php if ($series->tr('description')): ?>
    <div class="series-intro"><?= RichText::purify($series->tr('description')) ?></div>
<?php endif; ?>

<div class="blogflow">
    <?php foreach ($paintings as $p): ?>
        <?php
        $lg = PaintingPresenter::photoUrl($p, 'lg') ?: PaintingPresenter::photoUrl($p, 'sm');
        $mat = PaintingPresenter::materialsLabel($p);
        $ground = PaintingPresenter::groundLabel($p);
        $year = PaintingPresenter::yearLabel($p);
        $size = PaintingPresenter::sizeLabel($p);
        $descPlain = PaintingPresenter::descPlain($p);
        if (!$lg) {
            continue;
        }
        ?>
        <figure data-full="<?= Html::encode($lg) ?>" data-title="<?= Html::encode($p->tr('name')) ?>" data-mat="<?= Html::encode($mat) ?>" data-ground="<?= Html::encode($ground) ?>" data-year="<?= Html::encode($year) ?>" data-size="<?= Html::encode($size) ?>" data-desc="<?= Html::encode($descPlain) ?>">
            <img src="<?= Html::encode($lg) ?>" alt="<?= Html::encode($p->tr('name')) ?>" loading="lazy">
        </figure>
        <?php if ($p->tr('description')): ?>
            <div class="blogtext"><?= RichText::purify($p->tr('description')) ?></div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
