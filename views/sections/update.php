<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Sections */

$this->title = Yii::t('admin', 'Edit section') . ': ' . $model->title;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Sections'), ['index']) ?></div>
        <h1><?= Html::encode($model->title) ?></h1>
    </div>
</div>

<?= $this->render('_form', ['model' => $model]) ?>
