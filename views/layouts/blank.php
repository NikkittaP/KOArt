<?php

/**
 * Minimal full-page layout for the admin login screen (Phase 4b).
 * No sidebar — the login view paints a centered card on a dark field.
 */

use app\assets\AdminAsset;
use yii\helpers\Html;

AdminAsset::register($this);

$this->beginPage();
?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?> — Katia Oskina</title>
    <?php // Jost is self-hosted; the @font-face rules ship in css/fonts.css,
          // loaded by AdminAsset. ?>
    <link rel="preload" href="<?= \yii\helpers\Url::to('@web/fonts/jost-latin.woff2') ?>" as="font" type="font/woff2" crossorigin>
    <?php $this->head() ?>
</head>
<body>
<?php $this->beginBody() ?>
<?= $content ?>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
