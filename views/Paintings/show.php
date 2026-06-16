<?php

use app\models\Photos;
use yii\helpers\Html;
use yii\helpers\Url;

if ($dateLabel != '') {
    $this->title = $painting->name . ', ' . $dateLabel;
} else {
    $this->title = $painting->name;
}

//$parentTitle = \Yii::t('app', 'Серия работ') . ' "' . $series->name . '"';
$parentTitle = $series->name;
$this->params['breadcrumbs'][] = ['label' => $parentTitle, 'url' => ['series/show', 'id' => $series->id]];//, 'style' => 'text-transform: uppercase;'];
$this->params['breadcrumbs'][] = $painting->name;
?>
        <?php
echo newerton\fancybox3\FancyBox::widget([
    'target' => '[data-fancybox]',
    'config' => [
        'loop' => true,
        'margin' => [44, 0],
        'gutter' => 30,
        'keyboard' => true,
        'arrows' => true,
        'infobar' => true,
        'toolbar' => true,
        'buttons' => [
            'slideShow',
            'fullScreen',
            'thumbs',
            'close',
        ],
        'idleTime' => 4,
        'smallBtn' => 'auto',
        'protect' => false,
        // Shortcut to make content "modal" - disable keyboard navigtion, hide buttons, etc
        'modal' => false,

        'image' => [
            // Wait for images to load before displaying
            // Requires predefined image dimensions
            // If 'auto' - will zoom in thumbnail if 'width' and 'height' attributes are found
            'preload' => "auto",
        ],

        // Open/close animation type
        // Possible values:
        //   false            - disable
        //   "zoom"           - zoom images from/to thumbnail
        //   "fade"
        //   "zoom-in-out"
        //
        'animationEffect' => "zoom",
        'animationDuration' => 366,

        // Should image change opacity while zooming
        // If opacity is 'auto', then opacity will be changed if image and thumbnail have different aspect ratios
        'zoomOpacity' => 'auto',

        // Transition effect between slides
        //
        // Possible values:
        //   false            - disable
        //   "fade'
        //   "slide'
        //   "circular'
        //   "tube'
        //   "zoom-in-out'
        //   "rotate'
        //
        'transitionEffect' => "fade",
        'transitionDuration' => 366,

        // Container is injected into this element
        'parentEl' => 'body',
    ],
]);
?>

<div class="container">
    <div class="row">
        <div class="col-12 col-sm-6 col-lg-7">
<?php
$photos = Photos::find()->where(['painting_id' => $painting->id])->orderBy(['isMain' => SORT_DESC])->all();
?>
            <div class="slider-for">
<?php
foreach ($photos as $photo) {
    ?>
                <div>
<?php
echo Html::a(Html::img(Url::to('@web/paintings_photo/preview/') . $photo->filename), Url::to('@web/paintings_photo/original_site/') . $photo->filename, ['data-fancybox' => 'gallery']);
    ?>
                </div>
<?php
}
?>
            </div>

            <div class="slider-nav">
<?php
foreach ($photos as $photo) {
    ?>
                <div>
<?php
echo Html::img(Url::to('@web/paintings_photo/thumb_tiny/') . $photo->filename);
    ?>
                </div>
<?php
}
?>
            </div>
        </div>

        <div class="col-6 col-sm-6 col-lg-5" style="padding-left:1em;">
            <h3>
                <?=Html::encode($painting->name)?>
            </h3>
            <?php
if ($painting->ground->name != '' || $materialsLabel != '') {
    ?>
            <span>
            <?php
if ($painting->ground->name != '' && $materialsLabel != '') {
        echo Html::encode($painting->ground->name) . ', ' . Html::encode($materialsLabel);
    } else if ($painting->ground->name != '') {
        echo Html::encode($painting->ground->name);
    } else if ($materialsLabel != '') {
        echo Html::encode($materialsLabel);
    }
    ?>
            </span>
            <?php
}
if ($dateLabel != '') {
    ?>
            <br / >
            <span>
                <?=Html::encode($dateLabel)?>
</span>
            <?php
}
if ($sizeLabel != '') {
    ?>
            <br / >
            <span>
                <?=Html::encode($sizeLabel)?>
</span>
            <?php
}
?>
        </div>
    </div>

<?php
if ($painting->description != '') {
    ?>
    <br /><br />

    <div class="row">
        <div class="col-lg-12">
            <p class="text-justify">
                <?=$painting->description?>
            </p>
        </div>
    </div>
<?php
}
?>

<?php
if (!Yii::$app->user->isGuest) {
    ?>
    <br /><br />

    <div class="row">
        <div class="col-lg-12 border border-3 border-info rounded">
            <b>Комментарии</b>
            <p class="text-justify">
                <?=$painting->authorComments->comments?>
            </p>

            <b>Затраты материалов</b>
            <p class="text-justify">
                <?=$painting->authorComments->material_costs?>
            </p>

            <b>Затраты времени</b>
            <p class="text-justify">
                <?=$painting->authorComments->time_costs?>
            </p>
        </div>
    </div>
<?php
}
?>

</div>

<br /><br />

<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="/slick/slick.min.js"></script>

<script type="text/javascript">
 $('.slider-for').slick({
  slidesToShow: 1,
  arrows: false,
  fade: true,
  //asNavFor: '.slider-nav',
  //variableWidth: true,
  adaptiveHeight: false
});
$('.slider-nav').slick({
  //slidesToShow: 4,
  //slidesToScroll: 1,
  infinite: false,
  asNavFor: '.slider-for',
  dots: false,
  arrows: true,
  centerMode: false,
  focusOnSelect: false,
  variableWidth: true
});

$('.slider-nav .slick-slide').on('click', function (event) {
    var index = $(this).data('slickIndex');
    $(".slider-nav .slick-slide").removeClass("slick-current");
    $(".slider-nav .slick-slide:eq(" + index + ")").addClass("slick-current");
    $(".slider-nav .slick-slide").removeClass("slick-active");
    $(".slider-nav .slick-slide:eq(" + index + ")").addClass("slick-active");

    $('.slider-for').slick('slickGoTo', index);
});

</script>
