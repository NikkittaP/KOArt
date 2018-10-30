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
        <h1>Katia Oskina Art</h1>
        <h1>Kate Oskina Art</h1>
        <h1>Oskina Art</h1>
        <h1>KOArt</h1>
    </div>

    <div class="container-fluid" style="max-width: 1250px;">
        <div class="d-flex flex-row flex-wrap justify-content-center">
            <?php
            $i = 0;
            foreach ($paintings as $painting) {
                if ($i == 4 || $i == 8) {
                    echo '</div>';
                    echo '<div class="d-flex flex-row flex-wrap justify-content-center">';
                }
                echo '<div class="d-flex flex-column">';
                echo Html::a(Html::img(Yii::$app->request->BaseUrl . '/photos/thumb/' . $painting->mainPhoto->filename, ['class' => 'img-fluid']), ['paintings/show', 'id' => $painting->id], ['style' => 'margin: 5px;', 'class' => 'black-link']);
                echo '</div>';

                $i++;
            }
            ?>
        </div>
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