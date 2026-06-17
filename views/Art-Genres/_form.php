<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\ArtGenres */

$err = function ($attr) use ($model) {
    return $model->hasErrors($attr)
        ? '<div class="help-block">' . Html::encode($model->getFirstError($attr)) . '</div>'
        : '';
};
?>
<div class="panel" style="max-width:560px">
    <?= Html::beginForm('', 'post') ?>

    <div class="field <?= $model->hasErrors('name') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Name (RU)') ?></label>
        <?= Html::activeTextInput($model, 'name', ['maxlength' => true]) ?>
        <?= $err('name') ?>
    </div>

    <?php if ($model->hasAttribute('name_en')): ?>
    <div class="field <?= $model->hasErrors('name_en') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Name (EN)') ?></label>
        <?= Html::activeTextInput($model, 'name_en', ['maxlength' => true]) ?>
        <div class="hint"><?= Yii::t('admin', 'Optional. Shown when the admin is in English; falls back to the Russian name.') ?></div>
        <?= $err('name_en') ?>
    </div>
    <?php endif; ?>

    <div class="field" style="margin-bottom:0">
        <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
        <?= Html::a(Yii::t('admin', 'Cancel'), ['index'], ['class' => 'btn ghost']) ?>
    </div>

    <?= Html::endForm() ?>
</div>
