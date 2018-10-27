<?php

use kartik\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ArtGenresSearchPaintings */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Картины';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paintings-index">

    <h1><?=Html::encode($this->title)?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?=Html::a('Добавить картину', ['create'], ['class' => 'btn btn-success'])?>
    </p>

    <?=GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => '&ndash;'],
    'columns' => [
        [
            'attribute' => 'id',
            'contentOptions' => ['style' => 'width: 70px;text-align:center'],
        ],
        [
            'attribute' => 'date',
            'contentOptions' => ['style' => 'width: 100px;'],
        ],
        [
            'attribute' => 'coverPhoto',
            'format' => 'html',
            'value' => function ($model) {
                return Html::img(Yii::$app->request->BaseUrl . '/photos/thumb/' . $model->mainPhoto->filename,
                    ['width' => '100px']);
            },
            'contentOptions' => ['style' => 'width: 100px;'],
        ],
        [
            'attribute' => 'name',
        ],
        //'description:ntext',
        [
            'attribute' => 'width',
            'contentOptions' => ['style' => 'width: 70px;'],
        ],
        [
            'attribute' => 'height',
            'contentOptions' => ['style' => 'width: 70px;'],
        ],
        [
            'attribute' => 'ground_id',
            'value' => function ($model) {
                return $model->ground->name;
            },
            'contentOptions' => ['style' => 'width: 70px;'],
        ],
        [
            'attribute' => 'artStyleName',
            'format' => 'html',
            'value' => function ($model) use ($artStyles) {
                $list = '';
                foreach ($model->artStylesToPaintings as $artStyle) {
                    $list .= $artStyles[$artStyle->art_style_id] . '<br />';
                }
                return $list;
            },
            'contentOptions' => ['style' => 'padding:2px;width:70px;']
        ],
        [
            'attribute' => 'artGenreName',
            'format' => 'html',
            'value' => function ($model) use ($artGenres) {
                $list = '';
                foreach ($model->artGenresToPaintings as $artGenre) {
                    $list .= $artGenres[$artGenre->art_genre_id] . '<br />';
                }
                return $list;
            },
            'contentOptions' => ['style' => 'padding:2px;width:70px;']
        ],
        [
            'attribute' => 'materials',
            'format' => 'html',
            'value' => function ($model) use ($materials) {
                $list = '';
                foreach ($model->materialsToPaintings as $material) {
                    $list .= $materials[$material->material_id] . '<br />';
                }
                return $list;
            },
            'contentOptions' => ['style' => 'padding:2px;width:70px;']
        ],
        [
            'attribute' => 'price',
            'value' => function ($model) {
                $price = $model->getLastPrice($model->id)->value;
                if (isset($price)) {
                    return '$' . $price;
                }

            },
            'contentOptions' => ['style' => 'width: 70px;'],
        ],
    ],
]);?>
</div>
