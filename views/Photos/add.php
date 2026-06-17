<?php

use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $paintingModel app\models\Paintings */

$this->title = Yii::t('admin', 'Photos');
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['/paintings/index']) ?></div>
        <h1><?= Yii::t('admin', 'Add photos') ?>: #<?= (int) $paintingModel->id ?> <?= Html::encode($paintingModel->name) ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', 'Choose cover →'), ['selectmain', 'painting_id' => $paintingModel->id], ['class' => 'btn accent']) ?>
    </div>
</div>

<div class="panel">
    <?= FileInput::widget([
        'name' => 'photos[]',
        'options' => [
            'multiple' => true,
            'accept' => 'image/*',
        ],
        'pluginOptions' => [
            'previewFileType' => 'image',
            'uploadUrl' => Url::to(['/photos/upload']),
            'uploadExtraData' => [
                'painting_id' => $paintingModel->id,
            ],
            'maxFileCount' => 10,
        ],
    ]) ?>
    <p class="hint" style="margin-top:14px;color:var(--faint)">
        <?= Yii::t('admin', 'Upload photos, then choose which one is the cover.') ?>
    </p>
</div>

<div>
    <?= Html::a(Yii::t('admin', 'Choose cover →'), ['selectmain', 'painting_id' => $paintingModel->id], ['class' => 'btn ghost']) ?>
    <?= Html::a(Yii::t('admin', 'Delete photos'), ['delete', 'painting_id' => $paintingModel->id], ['class' => 'btn ghost']) ?>
</div>
