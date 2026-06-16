<?php

/* @var $this yii\web\View */

use yii\helpers\Html;
use yii\helpers\Url;

$this->title = \Yii::t('app', 'Об авторе');
$this->params['breadcrumbs'][] = $this->title;
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
        'modal' => false,

        'image' => [
            'preload' => "auto",
        ],

        'animationEffect' => "zoom",
        'animationDuration' => 366,

        'zoomOpacity' => 'auto',

        'transitionEffect' => "fade",
        'transitionDuration' => 366,

        'parentEl' => 'body',
    ],
]);
?>

<div class="container">

    <h1 style="text-align:justify">Екатерина Оськина</h1>

    <div class="row">
        <div class="col-12 col-sm-4 col-lg-4">
            <div class="slider-for">
<?php
$aboutPhotos = [
    '001_2016-08.jpg',
    '002_2019-03.jpg',
    '003_2019-08.jpg',
    '004_2018-02.jpg',
];
foreach ($aboutPhotos as $aboutPhoto) {
    ?>
                <div>
<?php
echo Html::a(Html::img(Url::to('@web/about_photo/') . $aboutPhoto), Url::to('@web/about_photo/') . $aboutPhoto, ['data-fancybox' => 'gallery']);
    ?>
                </div>
<?php
}
?>
            </div>

            <div class="slider-nav">
<?php
foreach ($aboutPhotos as $aboutPhoto) {
    ?>
                <div>
<?php
echo Html::img(Url::to('@web/about_photo/thumb_tiny/') . $aboutPhoto);
    ?>
                </div>
<?php
}
?>
            </div>
        </div>
        <div class="col-12 col-sm-8 col-lg-8" style="padding-left:1em;">
            <p style="text-align:justify">Художник-живописец, училась в МГАХИ им. В.И. Сурикова, закончила магистратуру в Италии, работает в и классических и современных техниках. Пишет маслом, акрилом и акварелью, работает в смешанных техниках. Участвует в выставках и арт-резиденциях в России и за рубежом. На проходящей сейчас выставке в Адмиралтействе музея-заповедника В. Д. Поленова представлены картины и этюды, написанные в разное время года во время поездок по России. Серия работ, написанная на Баренцевом море, зимние этюды с Кавказа, пейзажи, написанные в небольшой деревне в Нижегородской области и в Подмосковье.</p>
        </div>
    </div>

</div>


<script type="text/javascript" src="//code.jquery.com/jquery-1.11.0.min.js"></script>
<script type="text/javascript" src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
<script type="text/javascript" src="/slick/slick.min.js"></script>

<script type="text/javascript">
 $('.slider-for').slick({
  slidesToShow: 1,
  arrows: false,
  fade: true,
  adaptiveHeight: false
});
$('.slider-nav').slick({
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