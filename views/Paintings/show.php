<?php

use yii\helpers\Html;
use yii\helpers\Url;
use yii\imagine\Image;
use app\models\Photos;

$size = '';
if (is_numeric($painting->width) && is_numeric($painting->height))
    $size = ' ('.$painting->width.'x'.$painting->height.')';
$date = '';
if ($painting->date !== null)
    $date = ', '.Yii::$app->formatter->format($painting->date, 'date');
$this->title = $painting->name.$size.$date;
?>
<div class="container">
    <br /><br />
    <h1><?= Html::encode($this->title) ?></h1>
    <br /><br />

    <div class="container justify-content-center" style="width:80%">
        <?php
        echo newerton\fancybox3\FancyBox::widget([
            'target' => '[data-fancybox]',
            'config' => [
                'loop'              => true,
                'margin'            => [44,0],
                'gutter'            => 30,
                'keyboard'          => true,
                'arrows'            => true,
                'infobar'           => true,
                'toolbar'           => true,
                'buttons' => [
                    'slideShow',
                    'fullScreen',
                    'thumbs',
                    'close'    
                ],
                'idleTime'          => 4,
                'smallBtn'          => 'auto',
                'protect'           => false,
                // Shortcut to make content "modal" - disable keyboard navigtion, hide buttons, etc
                'modal'             => false,
                
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
                'animationEffect'       => "zoom",
                'animationDuration'     => 366,

                // Should image change opacity while zooming
                // If opacity is 'auto', then opacity will be changed if image and thumbnail have different aspect ratios
                'zoomOpacity'           => 'auto',

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
                'transitionEffect'      => "fade",
                'transitionDuration'    => 366,

                // Container is injected into this element
                'parentEl'          => 'body',
            ]
        ]);

        \yii2masonry\yii2masonry::begin([
        'clientOptions' => [
            'columnWidth' => '.masonry-grid-sizer',
            'itemSelector' => '.masonry-item',
            'percentPosition' => true,
            'initLayout' => false,
            'resize' => false
        ]
        ]); 
        
        $photos = Photos::find()->where(['painting_id' => $painting->id])->orderBy(['isMain' => SORT_DESC])->all();
        foreach ($photos as $photo) {
            echo '
            <div class="masonry-item">';
            echo '
            '.Html::a('
            <div class="painting-group">
                '.Html::img(Url::to('@web/photos/thumb/')  . $photo->filename).'
            </div>
            ', Url::to('@web/photos/') . $photo->filename, ['class' => 'black-link', 'data-fancybox' => 'gallery']);
            echo '
            </div>';
        }
        
        \yii2masonry\yii2masonry::end();
        ?>
    </div>

    <br /><br />

    <div class="row">
        <div class="col-lg-2"></div>
        <div class="col-lg-8">
            <p class="text-justify">
                <?=$painting->description?>
            </p>
        </div>
        <div class="col-lg-2"></div>
    </div>

    <br /><br />

    <div class="row">
        <div class="col-lg-2"></div>
        <div class="col-lg-8 border border-info rounded">
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
        <div class="col-lg-2"></div>
    </div>
</div>

<br /><br /><br />