<?php

use yii\helpers\Html;
use yii\widgets\DetailView;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Картины', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paintings-view">

    <h1><?= Html::encode($this->title) ?></h1>

    <p>
        <?= Html::a('Обновить', ['update', 'id' => $model->id], ['class' => 'btn btn-primary']) ?>
    </p>

    <?= DetailView::widget([
        'model' => $model,
        'attributes' => [
            'id',
            'author.name',
            'name',
            'description:ntext',
            'width',
            'height',
            'ground.name',
            'shopURL',
            'date',
            'latitude',
            'longitude',
            'datetime_add',
            'datetime_update',
        ],
    ]) ?>

</div>
