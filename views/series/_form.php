<?php

use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use app\models\Sections;
use app\assets\RichTextAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Series */

RichTextAsset::register($this);

$baseUrl = Yii::$app->request->baseUrl;
$sections = ArrayHelper::map(Sections::find()->orderBy('sort ASC')->all(), 'id', 'title');
$err = function ($attr) use ($model) {
    return $model->hasErrors($attr)
        ? '<div class="help-block">' . Html::encode($model->getFirstError($attr)) . '</div>'
        : '';
};
?>
<?= Html::beginForm('', 'post', ['enctype' => 'multipart/form-data']) ?>
<div class="form-grid">
    <div>
        <div class="panel">
            <div class="field <?= $model->hasErrors('name') ? 'has-error' : '' ?>">
                <label><?= Yii::t('admin', 'Name') ?> (RU)</label>
                <?= Html::activeTextInput($model, 'name', ['maxlength' => true, 'style' => 'width:100%']) ?>
                <?= $err('name') ?>
            </div>

            <?php if ($model->hasAttribute('name_en')): ?>
            <div class="field <?= $model->hasErrors('name_en') ? 'has-error' : '' ?>">
                <label><?= Yii::t('admin', 'Name') ?> (EN)</label>
                <?= Html::activeTextInput($model, 'name_en', ['maxlength' => true, 'style' => 'width:100%']) ?>
                <div class="hint"><?= Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.') ?></div>
            </div>
            <?php endif; ?>

            <div class="field">
                <label><?= Yii::t('admin', 'Description') ?> (RU)</label>
                <?= Html::activeTextarea($model, 'description', ['rows' => 6, 'class' => 'rich-text-editor']) ?>
                <div class="hint"><?= Yii::t('admin', 'Shown on the series page. Allowed: bold/italic, paragraphs, lists, links.') ?></div>
            </div>

            <?php if ($model->hasAttribute('description_en')): ?>
            <div class="field">
                <label><?= Yii::t('admin', 'Description') ?> (EN)</label>
                <?= Html::activeTextarea($model, 'description_en', ['rows' => 6, 'class' => 'rich-text-editor']) ?>
                <div class="hint"><?= Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.') ?></div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <div class="field">
                <label><?= Yii::t('admin', 'Cover') ?></label>
                <?php if ($model->cover_filename): ?>
                    <?= Html::img($baseUrl . '/series_cover/thumb/' . $model->cover_filename,
                        ['class' => 'thumb', 'style' => 'width:120px;height:120px;margin-bottom:10px']) ?>
                <?php endif; ?>
                <?= Html::activeFileInput($model, 'cover_filename', ['accept' => 'image/*']) ?>
                <div class="hint"><?= Yii::t('admin', 'Leave empty to keep the current cover.') ?></div>
            </div>

            <div class="field">
                <label><?= Yii::t('admin', 'Section') ?></label>
                <?= Html::activeDropDownList($model, 'section_id', $sections,
                    ['prompt' => Yii::t('admin', '— choose —'), 'style' => 'width:100%']) ?>
            </div>

            <div class="field">
                <label><?= Yii::t('admin', 'Order') ?></label>
                <?= Html::activeInput('number', $model, 'sort_order', ['step' => 1, 'style' => 'width:120px']) ?>
                <div class="hint"><?= Yii::t('admin', 'Order within the section (lower = higher). Can also be changed with ↑/↓ in the list.') ?></div>
            </div>

            <div class="field" style="margin-bottom:0">
                <label class="checkbox" style="display:flex;align-items:center;gap:8px;text-transform:none;letter-spacing:0;color:var(--ink);font-size:13.5px">
                    <?= Html::activeCheckbox($model, 'isVisible', ['label' => null]) ?>
                    <?= Yii::t('admin', 'Visible on the site') ?>
                </label>
            </div>
        </div>
    </div>
</div>

<div style="margin-top:18px">
    <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
    <?= Html::a(Yii::t('admin', 'Cancel'), ['index'], ['class' => 'btn ghost']) ?>
</div>
<?= Html::endForm() ?>
