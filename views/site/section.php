<?php

/**
 * Hybrid section page (Phase 3 port of design_mockups_v2 index.html /
 * illustration.html etc.): an optional series-card grid, then an optional
 * mosaic of loose (non-series) works.
 *
 * @var \yii\web\View $this
 * @var \app\models\Sections $section
 * @var string $intro
 * @var \app\models\Series[] $series
 * @var \app\models\Paintings[] $paintings
 */

use app\helpers\PaintingPresenter;
use app\helpers\RichText;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $section->tr('title');
?>
<header class="shead">
    <h1><?= Html::encode($section->tr('title')) ?></h1>
    <?php if (trim((string) $intro) !== ''): ?>
        <?php // Intro is stored as sanitised rich-text HTML (paragraphs etc.),
              // so render it purified rather than escaping the tags. ?>
        <?= RichText::purify($intro) ?>
    <?php endif; ?>
</header>

<?php if ($series): ?>
    <div class="blocklabel"><span>Series</span></div>
    <div class="series-grid">
        <?php foreach ($series as $s): ?>
            <?php $workCount = $s->getPaintingsToSeries()->count(); ?>
            <a class="scard" href="<?= Url::to(['series/show', 'id' => $s->id]) ?>">
                <div class="scard-img">
                    <?php if ($s->cover_filename): ?>
                        <img src="/series_cover/thumb/<?= Html::encode(\app\helpers\Img::webp($s->cover_filename)) ?>" alt="<?= Html::encode($s->tr('name')) ?>" loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="scard-cap">
                    <span class="nm"><?= Html::encode($s->tr('name')) ?></span>
                    <span class="ct"><?= (int) $workCount ?> work<?= $workCount === 1 ? '' : 's' ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($paintings): ?>
    <?php // The "Works" divider is only meaningful when it separates loose works
          // from a series grid above. With no series in this section, we skip it. ?>
    <?php if ($series): ?>
        <div class="blocklabel"><span>Works</span></div>
    <?php endif; ?>
    <div class="mosaic">
        <?php foreach ($paintings as $p): ?>
            <?php
            $sm = PaintingPresenter::photoUrl($p, 'sm');
            $lg = PaintingPresenter::photoUrl($p, 'lg');
            $mat = PaintingPresenter::materialsLabel($p);
            $ground = PaintingPresenter::groundLabel($p);
            $year = PaintingPresenter::yearLabel($p);
            $size = PaintingPresenter::sizeLabel($p);
            // Has a description -> the lightbox shows a "Read more" link to the
            // dedicated work page (where the full rich text is read). No need to
            // dump the whole description into the listing markup anymore.
            $hasDesc = PaintingPresenter::descPlain($p) !== '';
            $workUrl = $hasDesc ? Url::to(['/paintings/work', 'id' => $p->id]) : '';
            if (!$sm) {
                continue;
            }
            ?>
            <?php $name = $p->tr('name', true); ?>
            <figure data-full="<?= Html::encode($lg) ?>" data-title="<?= Html::encode($name) ?>" data-mat="<?= Html::encode($mat) ?>" data-ground="<?= Html::encode($ground) ?>" data-year="<?= Html::encode($year) ?>" data-size="<?= Html::encode($size) ?>"<?= $workUrl ? ' data-url="' . Html::encode($workUrl) . '"' : '' ?>>
                <img src="<?= Html::encode($sm) ?>" alt="<?= Html::encode($name) ?>" loading="lazy">
                <figcaption class="hov">
                    <?php if ($name !== ''): ?><span class="t"><?= Html::encode($name) ?></span><?php endif; ?>
                    <?php if ($mat): ?><span class="m"><?= Html::encode($mat) ?></span><?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
