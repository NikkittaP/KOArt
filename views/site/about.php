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

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'About';

$bio = $author ? (string) $author->tr('biography') : '';
$paragraphs = array_filter(array_map('trim', preg_split('/\R{2,}|\R/u', trim($bio))), 'strlen');
?>
<header class="shead"><h1>About</h1></header>
<div class="about">
    <div class="about-photo">
        <img src="<?= Url::to('@web/about_photo/about.jpg') ?>" alt="Katia Oskina">
    </div>
    <div class="about-txt">
<?php foreach ($paragraphs as $paragraph): ?>
        <p><?= Html::encode($paragraph) ?></p>
<?php endforeach; ?>
    </div>
</div>
