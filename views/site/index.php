<?php
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\VarDumper;

/* @var $this yii\web\View */

$this->title = 'Katia Oskina Art';
?>
<div class="site-index">
    <div class="jumbotron">
        <?php
        echo Html::a(
            Icon::show('instagram', ['framework' => Icon::FAB, 'class'=>'fa-3x']),
            "https://www.instagram.com/katia.oskina/",
            ['class' => 'black-link', 'target' => '_blank']);
        ?>
        <br /><br />
        <h1>Katerina Oskina Art</h1>
    </div>

    <div class="container justify-content-center" style="width:80%">
        <?php
        \yii2masonry\yii2masonry::begin([
        'clientOptions' => [
            'columnWidth' => '.masonry-grid-sizer',
            'itemSelector' => '.masonry-item',
            'percentPosition' => true,
            'initLayout' => false,
            'resize' => false
        ]
        ]); 
        
        foreach ($paintings as $painting) {
            echo '
            <div class="masonry-item">';
            echo '
            '.Html::a('
                '.Html::img(Yii::$app->request->BaseUrl . '/photos/thumb/' . $painting->mainPhoto->filename, []).'
            ', ['paintings/show', 'id' => $painting->id], ['class' => 'black-link']);
            echo '
            </div>';
        }
        
        \yii2masonry\yii2masonry::end();
        ?>
    </div>

    <br /><br /><br />
    <div class="row justify-content-center">
        <?php
        echo LinkPager::widget([
            'pagination' => $pagination,
            'options' => [
                'class' => 'pagination',
            ],
            'linkContainerOptions' => ['class' => 'page-item'],
            'linkOptions' => ['class' => 'page-link'],
            'disabledListItemSubTagOptions' => ['class' => 'page-link'],
        ]);
        ?>
    </div>
</div>