<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = Yii::t('admin', 'Edit work') . ': #' . $model->id;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['index']) ?></div>
        <h1><?= Html::encode($model->name ?: ('#' . $model->id)) ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', 'Photos'), ['/photos/add', 'painting_id' => $model->id], ['class' => 'btn ghost']) ?>
    </div>
</div>

<?= $this->render('_form', ['model' => $model]) ?>
