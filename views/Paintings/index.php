<?php

use yii\helpers\Html;
use yii\grid\GridView;
use yii\helpers\VarDumper;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ArtGenresSearchPaintings */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Картины';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paintings-index">

    <h1><?= Html::encode($this->title) ?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?= Html::a('Добавить картину', ['create'], ['class' => 'btn btn-success']) ?>
    </p>

    <?= GridView::widget([
        'dataProvider' => $dataProvider,
        'filterModel' => $searchModel,
        'columns' => [
            ['class' => 'yii\grid\SerialColumn'],

            [
                'attribute' => 'id',
                'contentOptions'=>['style'=>'width: 70px;']
            ],
            [
                'attribute' => 'coverPhoto',
                'value' => function($model) { return $model->mainPhoto->filename; },
            ],
            'name',
            'description:ntext',
            [
                'attribute' => 'width',
                'contentOptions'=>['style'=>'width: 70px;']
            ],
            [
                'attribute' => 'height',
                'contentOptions'=>['style'=>'width: 70px;']
            ],
            //'ground_id',
            //'shopURL',
            //'date',
            //'latitude',
            //'longitude',
            //'datetime_add',
            //'datetime_update',

            ['class' => 'yii\grid\ActionColumn'],
        ],
    ]); ?>
</div>
