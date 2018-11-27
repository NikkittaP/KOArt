<?php
use kartik\icons\Icon;
use yii\helpers\Html;
use yii\widgets\LinkPager;
use yii\helpers\VarDumper;
use app\models\Materials;

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
            $size_string = '';
            if (is_numeric($painting->width) && is_numeric($painting->height))
                $size_string =$painting->width.'x'.$painting->height.'<br />';

            $materials = $painting->materialsToPaintings;
            $material_string = '';
            foreach ($materials as $material) {
                $material_string .= Materials::find()->where(['id' => $material->material_id])->one()->name.', ';
            }
            $material_string = substr($material_string, 0, -2);

            $ground_string = '';
            if (is_numeric($painting->ground_id))
                $ground_string = ' на '.$painting->ground->name;

            echo '
            <div class="masonry-item">';
            echo '
            '.Html::a('
            <div class="painting-group">
                '.Html::img(Yii::$app->request->BaseUrl . '/photos/thumb/' . $painting->mainPhoto->filename, []).'
                <div class="painting-overlay">
                    <h3>'.$painting->name.'</h3>
                    <p>
                    '.$size_string.'
                    '.$material_string.$ground_string.'
                    </p>
                </div>
            </div>
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