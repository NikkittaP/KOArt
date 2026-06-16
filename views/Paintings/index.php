<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\ArrayHelper;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ArtGenresSearchPaintings */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Картины';
$this->params['breadcrumbs'][] = $this->title;

//$dataProvider->pagination->pageSize=2;
?>
<div class="intranet">
<div class="paintings-index">

    <h1><?=Html::encode($this->title)?></h1>

    <p>
        <?=Html::a('Добавить картину', ['create'], ['class' => 'btn btn-success'])?>
    </p>

    <h3>Выберите серию для отображения картин в ней:</h3>
    <?php
    echo Html::beginForm(['paintings/index'], 'post');
    echo Html::hiddenInput('isPost', '1');
    $seriesList['-1'] = 'Показать все картины';
    foreach ($series as $key => $value) {
        $seriesList[$key] = $value;
    }
    echo Html::dropDownList('selected_series', $selectedSeries, $seriesList, ['class'=>'nostyle form-control col-sm-4']);
    ?>
    <br />
    <div class="form-group">
        <?= Html::submitButton('Показать', ['class' => 'btn btn-primary']) ?>
    </div>
    <?= Html::endForm() ?>

    <br /><br />

    <?=GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'layout' => "{summary}\n{pager}\n{items}\n{pager}",
    'formatter' => ['class' => 'yii\i18n\Formatter', 'nullDisplay' => '&ndash;'],
    'columns' => [
        [
            'attribute' => 'id',
            'headerOptions' => ['style' => 'max-width: 70px;width: 70px;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;text-align:center;'],
        ],
        [
            'attribute' => 'date',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 100px;text-align:center;'],
            'value' => function ($model) {
                return substr($model->date, 0, 7);
            },
        ],
        [
            'attribute' => 'coverPhoto',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 200px;'],
            'format' => 'raw',
            'value' => function ($model) {
                if ($model->mainPhoto->filename === null) {
                    return null;
                }

                return Html::a(
                    Html::img(Yii::$app->request->BaseUrl . '/paintings_photo/thumb_squared/' . $model->mainPhoto->filename,
                        ['width' => '200px']),
                    ['paintings/show', 'id' => $model->id], ['class' => 'black-link', 'target' => '_blank']);
            },
        ],
        [
            'attribute' => 'name',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
        ],
        //'description:ntext',
        /*
        [
        'attribute' => 'width',
        'contentOptions' => ['style' => 'width: 70px;'],
        ],
        [
        'attribute' => 'height',
        'contentOptions' => ['style' => 'width: 70px;'],
        ],
         */
        [
            'attribute' => 'size',
            'headerOptions' => ['style' => 'max-width: 110px;overflow:auto;white-space: normal;word-wrap: break-word;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 110px;text-align:center;'],
            'value' => function ($model) {
                if (is_numeric($model->width) && is_numeric($model->height)) {
                    return $model->width . 'x' . $model->height;
                } else {
                    return null;
                }

            },
        ],
        [
            'attribute' => 'seriesName',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:150px;'],
            'format' => 'html',
            'value' => function ($model) use ($series) {
                if (count($model->paintingsToSeries) == 0) {
                    return null;
                }

                $list = '';
                foreach ($model->paintingsToSeries as $series_) {
                    $list .= $series[$series_->series_id] . '<br />';
                }
                return $list;
            },
        ],
        [
            'attribute' => 'ground_id',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;'],
            'filter' => false,
            'value' => function ($model) {
                return $model->ground->name;
            },
        ],
        /*
        [
            'attribute' => 'artStyleName',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:70px;'],
            'format' => 'html',
            'value' => function ($model) use ($artStyles) {
                if (count($model->artStylesToPaintings) == 0) {
                    return null;
                }

                $list = '';
                foreach ($model->artStylesToPaintings as $artStyle) {
                    $list .= $artStyles[$artStyle->art_style_id] . '<br />';
                }
                return $list;
            },
        ],
        [
            'attribute' => 'artGenreName',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:70px;'],
            'format' => 'html',
            'value' => function ($model) use ($artGenres) {
                if (count($model->artGenresToPaintings) == 0) {
                    return null;
                }

                $list = '';
                foreach ($model->artGenresToPaintings as $artGenre) {
                    $list .= $artGenres[$artGenre->art_genre_id] . '<br />';
                }
                return $list;
            },
        ],
        */
        [
            'attribute' => 'materials',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:70px;'],
            'format' => 'html',
            'value' => function ($model) use ($materials) {
                if (count($model->materialsToPaintings) == 0) {
                    return null;
                }

                $list = '';
                foreach ($model->materialsToPaintings as $material) {
                    $list .= $materials[$material->material_id] . '<br />';
                }
                return $list;
            },
        ],
        [
            'attribute' => 'isVisible',
            'headerOptions' => ['style' => 'max-width: 150px;width: 150px;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 150px;text-align:center;'],
            'format' => 'raw',
            'value' => function ($model) {
                if ($model->isVisible === null || $model->isVisible === 0) {
                    $out .= '<span>нет</span>';
                } else {
                    $out .= '<span>да</span>';
                }

                return $out;
            },
        ],
        /*
        [
            'attribute' => 'price',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;'],
            'value' => function ($model) {
                $price = $model->getLastPrice($model->id)->value;
                if (isset($price)) {
                    return '$' . $price;
                }

            },
        ],
        */
        [
            'label' => 'Действия',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 220px;text-align:left;'],
            'format' => 'html',
            'value' => function ($model) {
                return Html::a('Обновить данные', ['update', 'id' => $model->id], ['class' => 'profile-link']) .
                '<br /><br /><h3>Работа с фотографиями:</h3>' .
                '&ndash;&nbsp;'.Html::a('Добавить', ['photos/add', 'painting_id' => $model->id], ['class' => 'profile-link']) .
                '<br />' .
                '&ndash;&nbsp;'.Html::a('Выбрать основное', ['photos/selectmain', 'painting_id' => $model->id], ['class' => 'profile-link']) .
                '<br />' .
                '&ndash;&nbsp;'.Html::a('Удалить', ['photos/delete', 'painting_id' => $model->id], ['class' => 'profile-link']);
            },
        ],
    ],
]);?>
</div>
</div>
