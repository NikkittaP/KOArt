<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Series */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Серии', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="intranet">
<div class="series-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'name',
            'description:ntext',
            [
                'attribute' => 'cover_filename',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->cover_filename === null) {
                        return null;
                    }
    
                    return Html::a(
                        Html::img(Yii::$app->request->BaseUrl . '/series_cover/thumb/' . \app\helpers\Img::webp($model->cover_filename),
                            ['width' => '100px']),
                        ['series/show', 'id' => $model->id], ['class' => 'black-link', 'target' => '_blank']);
                },
            ],
            [
                'attribute' => 'isVisible',
                'format' => 'raw',
                'value' => function ($model) {
                    if ($model->isVisible === null || $model->isVisible === 0) {
                        $out .= '<span>нет</span>';
                    } else {
                        $out .= '<span>да</span>';
                    }
    
                    return $out;
                },
            ]
        ],
    ]) ?>

</div>
</div>