<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Materials */

$this->title = Yii::t('admin', 'Update material') . ': ' . $model->name;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Materials'), ['index']) ?></div>
        <h1><?= Html::encode($model->name) ?></h1>
    </div>
</div>

<?= $this->render('_form', ['model' => $model]) ?>
