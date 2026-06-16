<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\SeriesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Серии';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="intranet">
<div class="series-index">

    <h1><?=Html::encode($this->title)?></h1>
    <?php // echo $this->render('_search', ['model' => $searchModel]); ?>

    <p>
        <?=Html::a('Добавить серию', ['create'], ['class' => 'btn btn-success'])?>
    </p>

    <?=GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'formatter' => ['class' => 'yii\i18n\Formatter', 'nullDisplay' => '&ndash;'],
    'columns' => [
        [
            'attribute' => 'id',
            'headerOptions' => ['style' => 'max-width: 70px;width: 70px;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;text-align:center;'],
        ],
        [
            'attribute' => 'cover_filename',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 200px;'],
            'format' => 'raw',
            'value' => function ($model) {
                if ($model->cover_filename === null) {
                    return null;
                }

                return Html::a(
                    Html::img(Yii::$app->request->BaseUrl . '/series_cover/thumb/' . $model->cover_filename,
                        ['width' => '200px']),
                    ['series/show', 'id' => $model->id], ['class' => 'black-link', 'target' => '_blank']);
            },
        ],
        [
            'attribute' => 'name',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 150px;text-align:left;'],
        ],
        [
            'attribute' => 'description',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
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
        [
            'label' => 'Действия',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'format' => 'html',
            'value' => function ($model) {
                return Html::a('Посмотреть', ['series/view', 'id' => $model->id], ['class' => 'profile-link']) . '<br />' .
                Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'profile-link']);
            },
        ],
    ],
]);?>
</div>
</div>