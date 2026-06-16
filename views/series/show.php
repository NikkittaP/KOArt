<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;

use kartik\icons\Icon;

use app\models\Materials;

/* @var $this yii\web\View */

//$this->title = \Yii::t('app', 'Серия работ').' "'.$series->name.'"';
$this->title = $series->name;
$this->params['breadcrumbs'][] = $this->title;
?>

<div class="container">
    <h1><?=Html::encode($this->title)?></h1>

    <p class="text-justify">
        <?=Html::encode($series->description);?>
    </p>

    <section class="tiles">
        <?php
        foreach ($paintings as $painting) {
            $styleNum = 8;
        
            $size_string = '';
            if (is_numeric($painting->width) && is_numeric($painting->height))
                $size_string =$painting->width.'x'.$painting->height;

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
            <article class="style'.$styleNum.'">';
            echo '<span class="image2">';
            echo Html::img(Yii::$app->request->BaseUrl . '/paintings_photo/thumb_squared/' . $painting->mainPhoto->filename, []);
            echo '</span>';
            echo '
            '.Html::a('', ['paintings/show', 'id' => $painting->id]);
            /*
            echo '
            <br />
            <span>'.$painting->name.', '.$size_string.'
            <br />
            '.$material_string.$ground_string.'</span>
            */
            echo '</article>';
        }
        ?>
    </section>

    <br /><br /><br />
    <div style="text-align:center;">
        <?php
        echo LinkPager::widget([
            'pagination' => $pagination,
            'firstPageLabel' => true,
            'lastPageLabel' => true,
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