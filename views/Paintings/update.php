<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = 'Update Paintings: ' . $model->name;
$this->params['breadcrumbs'][] = ['label' => 'Paintings', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Update';
?>
<div class="paintings-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
