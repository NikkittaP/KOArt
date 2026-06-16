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
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $section->title;
?>
<header class="shead">
    <h1><?= Html::encode($section->title) ?></h1>
    <?php if ($intro): ?>
        <p><?= Html::encode($intro) ?></p>
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
                        <img src="/series_cover/thumb/<?= Html::encode($s->cover_filename) ?>" alt="<?= Html::encode($s->name) ?>" loading="lazy">
                    <?php endif; ?>
                </div>
                <div class="scard-cap">
                    <span class="nm"><?= Html::encode($s->name) ?></span>
                    <span class="ct"><?= (int) $workCount ?> work<?= $workCount === 1 ? '' : 's' ?></span>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php if ($paintings): ?>
    <div class="blocklabel"><span>Works</span></div>
    <div class="mosaic">
        <?php foreach ($paintings as $p): ?>
            <?php
            $sm = PaintingPresenter::photoUrl($p, 'sm');
            $lg = PaintingPresenter::photoUrl($p, 'lg');
            $mat = PaintingPresenter::materialsLabel($p);
            $year = PaintingPresenter::yearLabel($p);
            $size = PaintingPresenter::sizeLabel($p);
            $desc = PaintingPresenter::descPlain($p);
            if (!$sm) {
                continue;
            }
            ?>
            <figure data-full="<?= Html::encode($lg) ?>" data-title="<?= Html::encode($p->name) ?>" data-mat="<?= Html::encode($mat) ?>" data-year="<?= Html::encode($year) ?>" data-size="<?= Html::encode($size) ?>" data-desc="<?= Html::encode($desc) ?>">
                <img src="<?= Html::encode($sm) ?>" alt="<?= Html::encode($p->name) ?>" loading="lazy">
                <figcaption class="hov">
                    <span class="t"><?= Html::encode($p->name) ?></span>
                    <?php if ($mat): ?><span class="m"><?= Html::encode($mat) ?></span><?php endif; ?>
                </figcaption>
            </figure>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
