<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

use app\models\Paintings;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ArtGenresSearchPaintings */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Картины';
$this->params['breadcrumbs'][] = $this->title;

//$dataProvider->pagination->pageSize=2;
?>
<div class="paintings-index">

    <h1><?=Html::encode($this->title)?></h1>

    <br />
    <?php
    $sizesModelHorizontal = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` >= `height`'))->orderBy('width ASC, height ASC')->all();
    $sizesHorizontal = [];
    foreach ($sizesModelHorizontal as $sizeModelHorizontal) {
        $key = $sizeModelHorizontal->width.'x'.$sizeModelHorizontal->height;
        if (!array_key_exists($key, $sizesHorizontal))
            $sizesHorizontal[$key] = 1;
        else 
            $sizesHorizontal[$key]++;
    }

    $sizesModelVertical = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` < `height`'))->orderBy('width ASC, height ASC')->all();
    $sizesVertical = [];
    foreach ($sizesModelVertical as $sizeModelVertical) {
        $key = $sizeModelVertical->width.'x'.$sizeModelVertical->height;
        if (!array_key_exists($key, $sizesVertical))
            $sizesVertical[$key] = 1;
        else 
            $sizesVertical[$key]++;
    }
    ?>
    <h5>Количество картин по размерам:</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Альбомная ориентация</th>
                <th>Портретная ориентация</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                <?php
                foreach ($sizesHorizontal as $key=>$value) {
                    echo '[<b>'.$value.'</b>] '.$key.'<br />';
                }
                ?>
                </td>
                <td>
                <?php
                foreach ($sizesVertical as $key=>$value) {
                    echo '[<b>'.$value.'</b>] '.$key.'<br />';
                }
                ?>
                </td>
            </tr>
        </tbody>
    </table>
    <br />

    <p>
        <?=Html::a('Добавить картину', ['create'], ['class' => 'btn btn-success'])?>
    </p>

    <?=GridView::widget([
    'dataProvider' => $dataProvider,
    'filterModel' => $searchModel,
    'layout' => "{summary}\n{pager}\n{items}\n{pager}",
    'formatter' => ['class' => 'yii\i18n\Formatter','nullDisplay' => '&ndash;'],
    'columns' => [
        [
            'attribute' => 'id',
            'headerOptions'=>['style'=>'max-width: 70px;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;text-align:center;'],
        ],
        [
            'attribute' => 'date',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 100px;text-align:center;'],
            'value' => function ($model) {
                return substr($model->date, 0, 7);
            }
        ],
        [
            'attribute' => 'coverPhoto',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 100px;'],
            'format' => 'raw',
            'value' => function ($model) {
                if ($model->mainPhoto->filename === null)
                    return null;
                
                return Html::a(
                    Html::img(Yii::$app->request->BaseUrl . '/photos/thumb/' . $model->mainPhoto->filename,
                    ['width' => '100px']),
                    ['paintings/show', 'id' => $model->id], ['class' => 'black-link', 'target' => '_blank']);
            },
        ],
        [
            'attribute' => 'name',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
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
            'headerOptions'=>['style'=>'max-width: 110px;overflow:auto;white-space: normal;word-wrap: break-word;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 110px;text-align:center;'],
            'value' => function ($model) {
                if (is_numeric($model->width) && is_numeric($model->height))
                    return $model->width.'x'.$model->height;
                else
                    return null;
            },
        ],
        [
            'attribute' => 'ground_id',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;'],
            'filter'=>false,
            'value' => function ($model) {
                return $model->ground->name;
            },
        ],
        [
            'attribute' => 'artStyleName',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:70px;'],
            'format' => 'html',
            'value' => function ($model) use ($artStyles) {
                if (count($model->artStylesToPaintings) == 0)
                    return null;
                
                $list = '';
                foreach ($model->artStylesToPaintings as $artStyle) {
                    $list .= $artStyles[$artStyle->art_style_id] . '<br />';
                }
                return $list;
            },
        ],
        [
            'attribute' => 'artGenreName',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:70px;'],
            'format' => 'html',
            'value' => function ($model) use ($artGenres) {
                if (count($model->artGenresToPaintings) == 0)
                    return null;
                
                $list = '';
                foreach ($model->artGenresToPaintings as $artGenre) {
                    $list .= $artGenres[$artGenre->art_genre_id] . '<br />';
                }
                return $list;
            },
        ],
        [
            'attribute' => 'materials',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:70px;'],
            'format' => 'html',
            'value' => function ($model) use ($materials) {
                if (count($model->materialsToPaintings) == 0)
                    return null;
                
                $list = '';
                foreach ($model->materialsToPaintings as $material) {
                    $list .= $materials[$material->material_id] . '<br />';
                }
                return $list;
            },
        ],
        [
            'attribute' => 'price',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 70px;'],
            'value' => function ($model) {
                $price = $model->getLastPrice($model->id)->value;
                if (isset($price)) {
                    return '$' . $price;
                }

            },
        ],
        [
            'label' => 'Действия',
            'headerOptions'=>['style'=>'vertical-align: middle;'],
            'format' => 'html',
            'value' => function ($model) {
                return  Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'profile-link']).
                        '<br />'.
                        Html::a('Добавить фото', ['photos/add', 'painting_id' => $model->id], ['class' => 'profile-link']).
                        '<br />'.
                        Html::a('Выбрать основное фото', ['photos/selectmain', 'painting_id' => $model->id], ['class' => 'profile-link']).
                        '<br />'.
                        Html::a('Удалить фото', ['photos/delete', 'painting_id' => $model->id], ['class' => 'profile-link']);
            }
        ],
    ],
]);?>
</div>
