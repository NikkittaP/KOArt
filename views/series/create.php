<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Series */

$this->title = 'Добавить серию';
$this->params['breadcrumbs'][] = ['label' => 'Серии', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="intranet">
<div class="series-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
</div>