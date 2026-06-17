<?php

use yii\helpers\Html;
use app\assets\RichTextAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Sections */

RichTextAsset::register($this);

$err = function ($attr) use ($model) {
    return $model->hasErrors($attr)
        ? '<div class="help-block">' . Html::encode($model->getFirstError($attr)) . '</div>'
        : '';
};
?>
<div class="panel" style="max-width:640px">
    <?= Html::beginForm('', 'post') ?>

    <div class="field <?= $model->hasErrors('title') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Title') ?> (RU)</label>
        <?= Html::activeTextInput($model, 'title', ['maxlength' => true]) ?>
        <?= $err('title') ?>
    </div>

    <?php if ($model->hasAttribute('title_en')): ?>
    <div class="field <?= $model->hasErrors('title_en') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Title') ?> (EN)</label>
        <?= Html::activeTextInput($model, 'title_en', ['maxlength' => true]) ?>
        <div class="hint"><?= Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.') ?></div>
        <?= $err('title_en') ?>
    </div>
    <?php endif; ?>

    <div class="field <?= $model->hasErrors('slug') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Slug') ?></label>
        <?= Html::activeTextInput($model, 'slug', ['maxlength' => true]) ?>
        <div class="hint"><?= Yii::t('admin', 'Used in the section URL, e.g. "picturebooks". Latin letters, digits, hyphen.') ?></div>
        <?= $err('slug') ?>
    </div>

    <div class="field <?= $model->hasErrors('sort') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Order') ?></label>
        <?= Html::activeInput('number', $model, 'sort', ['step' => 1, 'style' => 'width:120px']) ?>
        <div class="hint"><?= Yii::t('admin', 'Position in the navigation (lower = higher).') ?></div>
        <?= $err('sort') ?>
    </div>

    <div class="field">
        <label><?= Yii::t('admin', 'Intro text') ?> (RU)</label>
        <?= Html::activeTextarea($model, 'description', ['rows' => 4, 'class' => 'rich-text-editor']) ?>
        <div class="hint"><?= Yii::t('admin', 'Short intro shown under the section title on the site.') ?></div>
    </div>

    <?php if ($model->hasAttribute('description_en')): ?>
    <div class="field">
        <label><?= Yii::t('admin', 'Intro text') ?> (EN)</label>
        <?= Html::activeTextarea($model, 'description_en', ['rows' => 4, 'class' => 'rich-text-editor']) ?>
        <div class="hint"><?= Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.') ?></div>
    </div>
    <?php endif; ?>

    <div class="field" style="margin-bottom:0">
        <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
        <?= Html::a(Yii::t('admin', 'Cancel'), ['index'], ['class' => 'btn ghost']) ?>
    </div>

    <?= Html::endForm() ?>
</div>
