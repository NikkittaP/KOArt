<?php

/**
 * About page (Phase 3 port of design_mockups_v2/about.html): two-column
 * layout, portrait + bio. Content now comes from the database (authors table,
 * bilingual via Authors::tr()) instead of being hard-coded — see
 * m260620_120000_add_about_bio_to_authors and docs/02-design-spec.md.
 * Contact info lives only in the footer (no duplicate links in the body).
 *
 * @var \yii\web\View $this
 * @var \app\models\Authors|null $author
 */

use app\helpers\Seo;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'About';

$bio = $author ? (string) $author->tr('biography') : '';
$paragraphs = array_filter(array_map('trim', preg_split('/\R{2,}|\R/u', trim($bio))), 'strlen');

$this->params['seo'] = [
    'description' => Seo::firstExcerpt([$bio]),
    'type' => 'profile',
];

// The portrait is a static file replaced by hand over SFTP (see
// views/about/edit.php), so a WebP derivative cannot be regenerated
// automatically. Serve it only while it is at least as new as the JPG: upload
// a new about.jpg on its own and the stale WebP is ignored rather than
// showing the previous photo forever.
$portraitJpg = Yii::getAlias('@webroot') . '/about_photo/about.jpg';
$portraitWebp = Yii::getAlias('@webroot') . '/about_photo/about.webp';
$hasWebp = is_file($portraitWebp)
    && (!is_file($portraitJpg) || filemtime($portraitWebp) >= filemtime($portraitJpg));

// Intrinsic size, so the column does not jump while the photo loads.
$portraitSize = is_file($portraitJpg) ? @getimagesize($portraitJpg) : false;
?>
<header class="shead"><h1>About</h1></header>
<div class="about">
    <div class="about-photo">
        <picture>
<?php if ($hasWebp): ?>
            <source srcset="<?= Url::to('@web/about_photo/about.webp') ?>" type="image/webp">
<?php endif; ?>
            <img src="<?= Url::to('@web/about_photo/about.jpg') ?>"
                 alt="Katia Oskina"
<?php if ($portraitSize): ?>
                 width="<?= (int) $portraitSize[0] ?>" height="<?= (int) $portraitSize[1] ?>"
<?php endif; ?>
                 >
        </picture>
    </div>
    <div class="about-txt">
<?php foreach ($paragraphs as $paragraph): ?>
        <p><?= Html::encode($paragraph) ?></p>
<?php endforeach; ?>
    </div>
</div>
