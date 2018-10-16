<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Grounds */

$this->title = 'Create Grounds';
$this->params['breadcrumbs'][] = ['label' => 'Grounds', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="grounds-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
