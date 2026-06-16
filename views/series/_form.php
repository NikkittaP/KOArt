<?php

use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\widgets\ActiveForm;

/* @var $this yii\web\View */
/* @var $model app\models\Series */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="intranet-form">

    <?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <div class="card border-dark">
        <div class="card-header">
            <h5>Базовая информация</h5>
        </div>
        <div class="card-body">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>

            <?php
            if ($model->cover_filename != null) {
                echo Html::img(Yii::$app->request->BaseUrl . '/series_cover/thumb/' . $model->cover_filename,
                ['width' => '100px']);
            }
            ?>

            <?= $form->field($model, 'cover_filename')->fileInput() ?>

            <?= $form->field($model, 'isVisible')->checkbox(['class' => 'intranet_checkbox']) ?>
        </div>
    </div>
    <br />

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
