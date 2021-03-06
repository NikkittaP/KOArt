<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = 'Обновить данные по картине #' . $model->id . ' "' . $model->name.'"';
$this->params['breadcrumbs'][] = ['label' => 'Картины', 'url' => ['index']];
$this->params['breadcrumbs'][] = ['label' => $model->name, 'url' => ['view', 'id' => $model->id]];
$this->params['breadcrumbs'][] = 'Обновить';
?>
<div class="paintings-update">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    <br /><br /><br />
</div>
