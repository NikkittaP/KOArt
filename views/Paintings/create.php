<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = 'Create Paintings';
$this->params['breadcrumbs'][] = ['label' => 'Paintings', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="paintings-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
