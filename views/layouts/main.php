<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\widgets\Alert;
use yii\helpers\Html;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use nivans\Bs4Breadcrumbs\Breadcrumbs;
use app\assets\SimpleAsset;

SimpleAsset::register($this);
?>
<?php $this->beginPage() ?>
<!DOCTYPE html>
<html lang="<?= Yii::$app->language ?>">
<head>
    <meta charset="<?= Yii::$app->charset ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?= Html::csrfMetaTags() ?>
    <title><?= Html::encode($this->title) ?></title>
    <?php $this->head() ?>
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css">
</head>
<body>
<?php $this->beginBody() ?>
<div class="d-flex flex-column h-100">
    <?php
    NavBar::begin([
        'brandLabel' => Yii::$app->name,
        'brandUrl' => Yii::$app->homeUrl,
        'options' => [
            'id' => 'mainNav',
            'class' => 'navbar navbar-expand-lg navbar-dark bg-dark fixed-top',
        ],
    ]);
    echo Nav::widget([
        'options' => ['class' => 'navbar-nav  ml-auto'],
        'items' => [
            ['label' => 'Картины', 'url' => ['/paintings/index']],
           
            /*
            Yii::$app->user->isGuest ? (
                ['label' => 'Login', 'url' => ['/site/login']]
            ) : (
                '<li>'
                . Html::beginForm(['/site/logout'], 'post')
                . Html::submitButton(
                    'Logout (' . Yii::$app->user->identity->username . ')',
                    ['class' => 'btn btn-link logout']
                )
                . Html::endForm()
                . '</li>'
            )
            */
        ],
    ]);
    NavBar::end();
    ?>

    <div class="d-flex flex-column flex-grow-1">
        <div class="container h-100 flex-grow-1">
            <br />
            <?= Breadcrumbs::widget([
                'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
            ]) ?>
            <?= Alert::widget() ?>
            <?= $content ?>
        </div>

        <div class="footer text-center">
            <h5>&copy; Katia Oskina Art 
                <?php
                $text = (date('Y')=="2018") ? date('Y') : "2018-".date('Y');
                echo $text;
                ?>
            </h5>

            <!--
            <p class="float-right"><?= Yii::powered() ?></p>
            -->
        </div>
    </div>
</div>
<?php $this->endBody() ?>
</body>
</html>
<?php $this->endPage() ?>
