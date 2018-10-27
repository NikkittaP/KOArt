<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = 'Добавить картину';
$this->params['breadcrumbs'][] = ['label' => 'Картины', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paintings-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>
    <br /><br /><br />
</div>
