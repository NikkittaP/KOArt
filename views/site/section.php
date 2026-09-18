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
use app\helpers\PortfolioPdf;
use app\helpers\RichText;
use app\helpers\Seo;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $section->tr('title');

// The section intro is the only prose on this page, so it is also the best
// meta description. Sections with no intro fall back to a line that still
// names the section and where the artist works.
$this->params['seo'] = [
    'description' => Seo::firstExcerpt([
        $intro,
        $section->tr('title') . ' by ' . Yii::$app->params['siteName']
            . ', illustrator and artist based in '
            . Yii::$app->params['contactLocation'] . '.',
    ]),
];
?>
<header class="shead">
    <h1><?= Html::encode($section->tr('title')) ?></h1>
    <?php if (trim((string) $intro) !== ''): ?>
        <?php // Intro is stored as sanitised rich-text HTML (paragraphs etc.),
              // so render it purified rather than escaping the tags. ?>
        <?= RichText::purify($intro) ?>
    <?php endif; ?>
    <?php if (PortfolioPdf::hasContent($paintings)): ?>
        <a class="pdf-dl" href="<?= Url::to(['/portfolio/section', 'slug' => $section->slug, 'language' => 'en']) ?>" target="_blank" rel="noopener">Download portfolio (PDF)</a>
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
            if (!$sm) {
                continue;
            }
            ?>
            <?php $name = $p->tr('name', true); ?>
            <?php if ($p->isProject()): ?>
                <?php // A project (board game, picture book) is read as a whole: the
                      // tile is a plain link to its page. The figure has no
                      // data-full, so public.js leaves it out of the lightbox. ?>
                <a class="proj" href="<?= Url::to(['/paintings/work', 'id' => $p->id]) ?>">
                    <figure>
                        <img src="<?= Html::encode($sm) ?>" alt="<?= Html::encode($name) ?>" loading="lazy">
                        <figcaption class="hov">
                            <?php if ($name !== ''): ?><span class="t"><?= Html::encode($name) ?></span><?php endif; ?>
                            <span class="m"><?= Html::encode(PaintingPresenter::projectLabel($p)) ?></span>
                        </figcaption>
                    </figure>
                </a>
            <?php else: ?>
                <?php
                $lg = PaintingPresenter::photoUrl($p, 'lg');
                $mat = PaintingPresenter::materialsLabel($p);
                $ground = PaintingPresenter::groundLabel($p);
                $year = PaintingPresenter::yearLabel($p);
                $size = PaintingPresenter::sizeLabel($p);
                // The lightbox links to the work's own page when there is more to
                // see there than the viewer shows: a description or extra photos.
                // data-more carries the link text ("Read more" / "All photos (N)").
                $workUrl = PaintingPresenter::hasOwnPage($p) ? Url::to(['/paintings/work', 'id' => $p->id]) : '';
                ?>
                <figure data-full="<?= Html::encode($lg) ?>" data-title="<?= Html::encode($name) ?>" data-mat="<?= Html::encode($mat) ?>" data-ground="<?= Html::encode($ground) ?>" data-year="<?= Html::encode($year) ?>" data-size="<?= Html::encode($size) ?>"<?= $workUrl ? ' data-url="' . Html::encode($workUrl) . '" data-more="' . Html::encode(PaintingPresenter::moreLabel($p)) . '"' : '' ?>>
                    <img src="<?= Html::encode($sm) ?>" alt="<?= Html::encode($name) ?>" loading="lazy">
                    <figcaption class="hov">
                        <?php if ($name !== ''): ?><span class="t"><?= Html::encode($name) ?></span><?php endif; ?>
                        <?php if ($mat): ?><span class="m"><?= Html::encode($mat) ?></span><?php endif; ?>
                    </figcaption>
                </figure>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
