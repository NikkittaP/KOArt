<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Grounds */

$this->title = 'Update Grounds: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Grounds', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="grounds-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
