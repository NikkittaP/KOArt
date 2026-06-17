<?php

use yii\grid\GridView;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\SeriesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $sections array id => title */
/* @var $selectedSection int */

$this->title = 'Серии';
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="intranet">
<div class="series-index">

    <h1><?=Html::encode($this->title)?></h1>

    <p>
        <?=Html::a('Добавить серию', ['create'], ['class' => 'btn btn-success'])?>
    </p>

    <h3>Фильтр / сортировка:</h3>
    <?php
    echo Html::beginForm(['series/index'], 'post');
    echo Html::hiddenInput('isPost', '1');
    ?>
    <div class="row">
        <div class="col-sm-5">
            <label>Раздел (для сортировки серий — выберите раздел)</label>
            <?php
            $sectionList = ['-1' => 'Все разделы'];
            foreach ($sections as $key => $value) {
                $sectionList[$key] = $value;
            }
            echo Html::dropDownList('selected_section', $selectedSection, $sectionList, ['class' => 'nostyle form-control']);
            ?>
        </div>
    </div>
    <br />
    <div class="form-group">
        <?= Html::submitButton('Показать', ['class' => 'btn btn-primary']) ?>
    </div>
    <?= Html::endForm() ?>

    <br />

    <?php if ((int) $selectedSection !== -1): ?>
        <p class="text-muted">
            Список отсортирован по порядку раздела — стрелками ↑/↓ можно менять порядок
            (именно в этом порядке серии будут показаны на сайте).
        </p>
    <?php endif; ?>

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
            'attribute' => 'section_id',
            'label' => 'Раздел',
            'headerOptions' => ['style' => 'vertical-align: middle;'],
            'contentOptions' => ['style' => 'width:130px;'],
            'filter' => false,
            'value' => function ($model) use ($sections) {
                return $model->section_id && isset($sections[$model->section_id]) ? $sections[$model->section_id] : null;
            },
        ],
        [
            'attribute' => 'isVisible',
            'headerOptions' => ['style' => 'max-width: 150px;width: 150px;text-align:center;vertical-align: middle;'],
            'contentOptions' => ['style' => 'width: 150px;text-align:center;'],
            'format' => 'raw',
            'value' => function ($model) {
                $out = '';
                if ($model->isVisible === null || $model->isVisible === 0) {
                    $out .= '<span>нет</span>';
                } else {
                    $out .= '<span>да</span>';
                }

                return $out;
            },
        ],
        [
            'label' => 'Порядок',
            'headerOptions' => ['style' => 'vertical-align: middle;width:70px;text-align:center;'],
            'contentOptions' => ['style' => 'width:70px;text-align:center;'],
            'format' => 'raw',
            'visible' => (int) $selectedSection !== -1,
            'value' => function ($model) use ($selectedSection) {
                return Html::a('↑', ['series/move', 'id' => $model->id, 'direction' => 'up', 'selected_section' => $selectedSection],
                        ['class' => 'profile-link', 'data' => ['method' => 'post'], 'title' => 'Выше']) .
                    '&nbsp;' .
                    Html::a('↓', ['series/move', 'id' => $model->id, 'direction' => 'down', 'selected_section' => $selectedSection],
                        ['class' => 'profile-link', 'data' => ['method' => 'post'], 'title' => 'Ниже']);
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
