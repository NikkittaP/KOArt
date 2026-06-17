<?php

use kartik\file\FileInput;
use kartik\select2\Select2;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

use app\models\Sections;
use app\assets\RichTextAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Series */
/* @var $form yii\widgets\ActiveForm */

RichTextAsset::register($this);
?>

<div class="intranet-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="card border-dark">
        <div class="card-header">
            <h5>Базовая информация</h5>
        </div>
        <div class="card-body">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6, 'class' => 'form-control rich-text-editor'])
                ->hint('Показывается на блог-странице серии. Допустимы: жирный/курсив, абзацы, списки, ссылки.') ?>

            <?php
            if ($model->cover_filename != null) {
                echo Html::img(Yii::$app->request->BaseUrl . '/series_cover/thumb/' . $model->cover_filename,
                ['width' => '100px']);
            }
            ?>

            <?= $form->field($model, 'cover_filename')->fileInput() ?>

            <?= $form->field($model, 'isVisible')->checkbox(['class' => 'intranet_checkbox']) ?>

            <?php
            $sections = ArrayHelper::map(Sections::find()->orderBy('sort ASC')->all(), 'id', 'title');
            echo $form->field($model, 'section_id')->widget(Select2::className(), [
                'data' => $sections,
                'options' => ['placeholder' => '- Выбрать раздел -'],
            ]);
            ?>

            <?= $form->field($model, 'sort_order')->input('number', ['step' => 1])
                ->hint('Порядок серии внутри раздела (меньше — выше). Можно также менять стрелками в списке серий.') ?>
        </div>
    </div>
    <br />

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
