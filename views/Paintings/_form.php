<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use kartik\number\NumberControl;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="paintings-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'author_id')->hiddenInput(['value'=> 1])->label(false); ?>

    <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

    <?php
    echo $form->field($model, 'width')->
    input('number', ['min'=>1, 'max'=> 1000, 'step'=>1, 'placeholder'=>'Ширина от 1 до 1000']); ?>

    <?= $form->field($model, 'height')->textInput() ?>

    <?= $form->field($model, 'ground_id')->textInput() ?>

    <?= $form->field($model, 'shopURL')->textInput(['maxlength' => true]) ?>

    <?= $form->field($model, 'date')->textInput() ?>

    <?= $form->field($model, 'latitude')->textInput() ?>

    <?= $form->field($model, 'longitude')->textInput() ?>

    <?= $form->field($model, 'datetime_add')->textInput() ?>

    <?= $form->field($model, 'datetime_update')->textInput() ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
