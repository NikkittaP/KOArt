<?php

/**
 * Public single-work page. This is the home for long, rich-text descriptions:
 * a large image (or images) followed by the full purified description, laid out
 * as a normal scrolling document so text never overlaps the artwork — on any
 * device or orientation. The lightbox links here via its "Read more" affordance.
 *
 * Mirrors the look of views/series/show.php and reuses its CSS classes
 * (.back, .shead.pj, .blogflow, .series-intro, .series-foot) so no new styles
 * are required.
 *
 * @var \yii\web\View $this
 * @var \app\models\Paintings $painting
 * @var \app\models\Series|null $series
 * @var \app\models\Sections|null $section
 */

use app\helpers\PaintingPresenter;
use app\helpers\OgImage;
use app\helpers\Schema;
use app\helpers\Seo;
use app\helpers\RichText;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $painting->tr('name', true) ?: ('#' . $painting->id);

// Back link: prefer the parent series page, else the section, else home.
if ($series) {
    $backUrl = Url::to(['series/show', 'id' => $series->id]);
    $backLabel = $series->tr('name');
} elseif ($section) {
    $backUrl = $section->slug === 'artworks'
        ? Url::to(['/'])
        : Url::to(['site/section', 'slug' => $section->slug]);
    $backLabel = $section->tr('title');
} else {
    $backUrl = Url::to(['/']);
    $backLabel = 'Artworks';
}

// Meta line: materials · ground · year · size.
$mat = PaintingPresenter::materialsLabel($painting);
$ground = PaintingPresenter::groundLabel($painting);
$year = PaintingPresenter::yearLabel($painting);
$size = PaintingPresenter::sizeLabel($painting);
$metaLine = implode(' · ', array_filter([$mat, $ground, $year, $size]));

// Photos: main first, then any extras, each shown full-width.
$photos = \app\models\Photos::find()
    ->where(['painting_id' => $painting->id])
    ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
    ->all();

$description = $painting->tr('description');
$hasDescription = trim((string) $description) !== '';

// One photo → the classic layout (image beside the description on landscape).
// Several photos → the description sits on top and the photos stack full-width
// below, each capped to the viewport height so one is comfortably in view.
$multi = count($photos) > 1;

// Meta description: the work's own text if it has any, otherwise the
// materials/ground/year/size line, which still describes the piece usefully.
$this->params['seo'] = [
    'description' => Seo::firstExcerpt([
        $description,
        trim($this->title . ($metaLine !== '' ? ' — ' . $metaLine : '')),
    ]),
    'type' => 'article',
    // Generated 1200x630 card: the whole work, letterboxed, so portraits are
    // not cropped through the middle by Facebook/LinkedIn.
    'image' => OgImage::forPainting($painting),
    'imageAlt' => $painting->tr('name', true) ?: ('#' . $painting->id),
];

// schema.org: the work itself, plus the trail that led here. VisualArtwork
// carries the art-specific fields (medium, surface, dimensions, year) that a
// generic CreativeWork cannot express.
$workUrl = Seo::canonical();
$this->params['jsonLd'] = [
    Schema::visualArtwork(
        $painting,
        $workUrl,
        Seo::absolute(OgImage::forPainting($painting)),
        $this->params['seo']['description']
    ),
    Schema::breadcrumbs([
        $backLabel => Seo::absolute($backUrl),
        ($painting->tr('name', true) ?: ('#' . $painting->id)) => $workUrl,
    ]),
];
?>
<a class="back" href="<?= $backUrl ?>">← <?= Html::encode($backLabel) ?></a>

<header class="shead pj">
    <h1><?= Html::encode($painting->tr('name', true) ?: ('#' . $painting->id)) ?></h1>
    <?php if ($metaLine): ?><p class="meta"><?= Html::encode($metaLine) ?></p><?php endif; ?>
</header>

<?php
// The info block (description). Rendered above the photos when there are
// several, or beside them (single) — see the CSS. Figures carry no data-full,
// so public.js skips them (no lightbox / click-to-zoom).
$infoHtml = '';
if ($hasDescription) {
    $infoHtml = '<div class="workpage-info"><div class="series-intro workdesc">'
        . RichText::purify($description) . '</div></div>';
}
?>
<div class="workpage <?= $multi ? 'multi' : 'single' ?>">
    <?php if ($multi): echo $infoHtml; endif; ?>

    <div class="workpage-media">
        <?php foreach ($photos as $photo): ?>
            <?php
            $file = \app\helpers\Img::webp($photo->filename);
            $lg = '/paintings_photo/original_site/' . $file;
            ?>
            <figure>
                <img src="<?= Html::encode($lg) ?>" alt="<?= Html::encode($painting->tr('name', true)) ?>" loading="lazy">
            </figure>
        <?php endforeach; ?>
    </div>

    <?php if (!$multi): echo $infoHtml; endif; ?>
</div>
