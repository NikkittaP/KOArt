<?php

/* @var $this \yii\web\View */
/* @var $content string */

use app\assets\AppAsset;
use app\assets\AppNoscriptAsset;
use app\widgets\Alert;
use app\widgets\LanguageSwitcher;
use kartik\icons\Icon;
use nivans\Bs4Breadcrumbs\Breadcrumbs;
use yii\bootstrap4\Nav;
use yii\bootstrap4\NavBar;
use yii\helpers\Html;

AppAsset::register($this);
AppNoscriptAsset::register($this);
?>
<?php $this->beginPage()?>
<!DOCTYPE html>
<html lang="<?=Yii::$app->language?>">
<head>

    <?php
if (Yii::$app->user->isGuest) {
    ?>
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript" >
    (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
    m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
    (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");

    ym(72411394, "init", {
            clickmap:true,
            trackLinks:true,
            accurateTrackBounce:true,
            webvisor:true
    });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/72411394" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
    <!-- /Yandex.Metrika counter -->
    <?php
}
?>

    <meta charset="<?=Yii::$app->charset?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
		<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
    <?php $this->registerCsrfMetaTags()?>
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <link rel="stylesheet" href="https://use.fontawesome.com/releases/v5.4.1/css/all.css">
    <title><?=Html::encode($this->title)?></title>
    <?php $this->head()?>
</head>
<body class="is-preload">
<?php $this->beginBody()?>

<div id="wrapper">

    <!-- Header -->
	    <header id="header">
<?php
NavBar::begin([
    'brandLabel' => Yii::$app->name,
    'brandUrl' => Yii::$app->homeUrl,
    'brandOptions' => [
        'class' => 'brand',
    ],
    'options' => [
        'class' => 'navbar-dark bg-dark fixed-top navbar-expand-lg mr-auto',
    ],
]);

/*
echo Html::a(
Icon::show('instagram', ['framework' => Icon::FAB, 'class' => 'fa-2x']),
"https://www.instagram.com/katia.oskina/",
['title' => 'Instagram', 'target' => '_blank']);
echo Html::a(
Icon::show('etsy', ['framework' => Icon::FAB, 'class' => 'fa-lg']),
"https://www.etsy.com/shop/NatureByKatia/",
['title' => 'Etsy', 'target' => '_blank']);
 */

echo Nav::widget([
    'options' => ['class' => 'navbar-nav ml-auto'],
    'items' => [
        '<li>' .
        Html::a(
            Icon::show('instagram', ['framework' => Icon::FAB, 'class' => 'fa-2x']),
            "https://www.instagram.com/katia.oskina/",
            ['title' => 'Instagram', 'target' => '_blank'])
        . '</li>',
        Yii::$app->user->isGuest ? ('') : (
            ['label' => \Yii::t('app', 'Картины'), 'url' => ['/paintings/index']]
        ),
        Yii::$app->user->isGuest ? ('') : (
            ['label' => \Yii::t('app', 'Серии'), 'url' => ['/series/index']]
        ),
        Yii::$app->user->isGuest ? ('') : (
            ['label' => \Yii::t('app', 'Разделы'), 'url' => ['/sections/index']]
        ),
        Yii::$app->user->isGuest ? ('') : (
            ['label' => \Yii::t('app', 'Жанры'), 'url' => ['/art-genres']]
        ),
        Yii::$app->user->isGuest ? ('') : (
            ['label' => \Yii::t('app', 'Основы'), 'url' => ['/grounds']]
        ),
        Yii::$app->user->isGuest ? ('') : (
            ['label' => \Yii::t('app', 'Статистика'), 'url' => ['/paintings/stats']]
        ),
        Yii::$app->user->isGuest ? ('') : (
            '<li>' .
            Html::a(\Yii::t('app', 'Выйти'), ['/site/logout'], ['data' => [
                'method' => 'post',
            ], 'class' => 'nav-link', 'style' => 'border-right: white 2px solid'])
            . '</li>'
        ),
        ['label' => \Yii::t('app', 'Об авторе'), 'url' => ['/site/about']],
        ['label' => \Yii::t('app', 'Контакты'), 'url' => ['/site/contact']],
        Yii::$app->user->isGuest ? ('') : (
            LanguageSwitcher::widget([])
        )
    ],
]);
NavBar::end();

if (!Yii::$app->user->isGuest) {
    echo '<br />';
}

?>
		</header>

    <!-- Main -->
		<div id="main">
			<div class="inner">
                <div class="container h-100 flex-grow-1">
                    <?=Breadcrumbs::widget([
    'links' => isset($this->params['breadcrumbs']) ? $this->params['breadcrumbs'] : [],
])?>
                    <?=Alert::widget()?>
                </div>

                <?=$content?>
			</div>
		</div>

	<!-- Footer -->
        <footer id="footer">
            <span>&copy;  <?=Yii::$app->name?>
            <?php
$text = (date('Y') == "2018") ? date('Y') : "2018-" . date('Y');
echo $text;
?>
            </span>
        </footer>
</div>

<?php $this->endBody()?>
</body>
</html>
<?php $this->endPage()?>
