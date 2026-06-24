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
use app\helpers\RichText;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = $painting->tr('name') ?: ('#' . $painting->id);

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
    ->orderBy(['isMain' => SORT_DESC, 'id' => SORT_ASC])
    ->all();

$description = $painting->tr('description');
$contactEmail = Yii::$app->params['contactEmail'];
?>
<a class="back" href="<?= $backUrl ?>">← <?= Html::encode($backLabel) ?></a>

<header class="shead pj">
    <h1><?= Html::encode($painting->tr('name') ?: ('#' . $painting->id)) ?></h1>
    <?php if ($metaLine): ?><p class="meta"><?= Html::encode($metaLine) ?></p><?php endif; ?>
</header>

<?php // Images are already shown full-size here, so no lightbox: figures carry
      // no data-full, so public.js skips them (no click-to-zoom). ?>
<div class="blogflow blogflow--static">
    <?php foreach ($photos as $photo): ?>
        <?php
        $file = \app\helpers\Img::webp($photo->filename);
        $lg = '/paintings_photo/original_site/' . $file;
        ?>
        <figure>
            <img src="<?= Html::encode($lg) ?>" alt="<?= Html::encode($painting->tr('name')) ?>" loading="lazy">
        </figure>
    <?php endforeach; ?>
</div>

<?php if (trim((string) $description) !== ''): ?>
    <div class="series-intro workdesc"><?= RichText::purify($description) ?></div>
<?php endif; ?>

<div class="series-foot">
    <a class="inquire" href="mailto:<?= Html::encode($contactEmail) ?>">Enquire about prints &amp; licensing</a>
</div>
