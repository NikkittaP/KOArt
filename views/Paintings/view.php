<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Paintings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paintings-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Update', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
        <?= Html::a('Delete', ['delete', 'id' => $model->id], [
            'class' => 'btn btn-danger',
            'data' => [
                'confirm' => 'Are you sure you want to delete this item?',
                'method' => 'post',
            ],
        ]) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'author_id',
            'name',
            'description:ntext',
            'width',
            'height',
            'ground_id',
            'shopURL',
            'date',
            'latitude',
            'longitude',
            'datetime_add',
            'datetime_update',
        ],
    ]) ?>

</div>
