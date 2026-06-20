<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $model app\models\Authors */

$this->title = Yii::t('admin', 'About');

$err = function ($attr) use ($model) {
    return $model->hasErrors($attr)
        ? '<div class="help-block">' . Html::encode($model->getFirstError($attr)) . '</div>'
        : '';
};
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Site content') ?></div>
        <h1><?= Yii::t('admin', 'About') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', 'View page'), ['/site/about'], ['class' => 'btn ghost', 'target' => '_blank', 'rel' => 'noopener']) ?>
    </div>
</div>

<p style="color:var(--soft);max-width:640px;margin-bottom:18px;font-size:13.5px">
    <?= Yii::t('admin', 'The text of the public About page. The English site shows the English text and falls back to Russian when it is empty.') ?>
</p>

<div class="panel" style="max-width:640px">
    <?= Html::beginForm('', 'post') ?>

    <div class="field <?= $model->hasErrors('name') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Author name') ?></label>
        <?= Html::activeTextInput($model, 'name', ['maxlength' => true]) ?>
        <div class="hint"><?= Yii::t('admin', 'Author name (not shown on the page heading, kept for reference).') ?></div>
        <?= $err('name') ?>
    </div>

    <div class="field <?= $model->hasErrors('biography') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Biography') ?> (RU)</label>
        <?= Html::activeTextarea($model, 'biography', ['rows' => 8]) ?>
        <div class="hint"><?= Yii::t('admin', 'Plain text. Leave a blank line between paragraphs.') ?></div>
        <?= $err('biography') ?>
    </div>

    <?php if ($model->hasAttribute('biography_en')): ?>
    <div class="field <?= $model->hasErrors('biography_en') ? 'has-error' : '' ?>">
        <label><?= Yii::t('admin', 'Biography') ?> (EN)</label>
        <?= Html::activeTextarea($model, 'biography_en', ['rows' => 8]) ?>
        <div class="hint"><?= Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.') ?></div>
        <?= $err('biography_en') ?>
    </div>
    <?php endif; ?>

    <div class="field">
        <label><?= Yii::t('admin', 'Portrait') ?></label>
        <div class="hint" style="margin-top:0">
            <?= Yii::t('admin', 'The photo is a file, not a database field. To change it, replace this image:') ?>
            <code>web/about_photo/about.jpg</code>
        </div>
        <img src="<?= Url::to('@web/about_photo/about.jpg') ?>" alt=""
             style="margin-top:10px;max-width:160px;height:auto;border-radius:6px">
    </div>

    <div class="field" style="margin-bottom:0">
        <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
    </div>

    <?= Html::endForm() ?>
</div>
