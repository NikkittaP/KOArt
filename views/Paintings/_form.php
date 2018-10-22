<?php

use yii\helpers\Html;
use yii\helpers\Url;
use kartik\form\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\number\NumberControl;
use kartik\date\DatePicker;
//use dosamigos\selectize\SelectizeDropDownList;
use kartik\select2\Select2;
use kartik\file\FileInput;

use app\models\Grounds;
use app\models\ArtGenres;
use app\models\ArtStyles;
use app\models\Materials;

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
    echo $form->field($model, 'photo_upload')->widget(FileInput::classname(), [
        'name' => 'photos[]',
        'options'=>[
            'multiple'=>true
        ],
        'pluginOptions' => [
            'uploadUrl' => Url::to(['/photos/upload']),
            'uploadExtraData' => [
                'album_id' => 20,
                'cat_id' => 'Nature'
            ],
            'maxFileCount' => 10
        ]
    ]);
    ?>

    <?php
    echo $form->field($model, 'width')->
    input('number', ['min'=>1, 'max'=> 1000, 'step'=>1, 'placeholder'=>'Ширина от 1 до 1000']);
    ?>

    <?php
    echo $form->field($model, 'height')->
    input('number', ['min'=>1, 'max'=> 1000, 'step'=>1, 'placeholder'=>'Высота от 1 до 1000']);
    ?>

    <?php
    $items =  ArrayHelper::map( ArtGenres ::find()->all(), 'id', 'name');
    echo $form->field($model, 'artGenreName')->widget(Select2::className(), [
        'data' => $items,
        //'value' => ['red', 'green'], // initial value
        'maintainOrder' => true,
        'options' => ['placeholder' => 'Жанр картины ...', 'multiple' => true],
        'pluginOptions' => [
            'tags' => true,
            'maximumInputLength' => 10
        ],
    ]);
    ?>

    <?php
    $items =  ArrayHelper::map( ArtStyles ::find()->all(), 'id', 'name');
    echo $form->field($model, 'artStyleName')->widget(Select2::className(), [
        'data' => $items,
        //'value' => ['red', 'green'], // initial value
        'maintainOrder' => true,
        'options' => ['placeholder' => 'Стиль картины ...', 'multiple' => true],
        'pluginOptions' => [
            'tags' => true,
            'maximumInputLength' => 10
        ],
    ]);
    ?>

    <?php
    $items =  ArrayHelper::map( Grounds ::find()->all(), 'id', 'name');
    echo $form->field($model, 'groundName')->widget(Select2::className(), [
        'data' => $items,
        'options' => ['placeholder' => 'Основа картины ...'],
        'pluginOptions' => [
            'tags' => true,
            'tokenSeparators' => [',', ' '],
            'maximumInputLength' => 10
        ],
    ]);
    ?>

     <?php
    $items =  ArrayHelper::map( Materials ::find()->all(), 'id', 'name');
    echo $form->field($model, 'materials')->widget(Select2::className(), [
        'data' => $items,
        //'value' => ['red', 'green'], // initial value
        'maintainOrder' => true,
        'options' => ['placeholder' => 'Использованные материалы ...', 'multiple' => true],
        'pluginOptions' => [
            'tags' => true,
            'maximumInputLength' => 10
        ],
    ]);
    ?>

    <?php
    echo $form->field($model, 'price')->widget(NumberControl::classname(), [
        'maskedInputOptions' => [
            'prefix' => '$ ',
            'suffix' => ' ¢',
            'allowMinus' => false
        ],
        'displayOptions' => [
            'class' => 'form-control kv-monospace',
            'style'=> 'width=20%'
        ]
    ]);
    ?>

    <?php
    echo $form->field($model, 'shopURL')->
    input('url', ['placeholder'=>'Ссылка на магазин ...']);
    ?>

    <?php
    echo $form->field($model, 'date')->widget(DatePicker::classname(), [
        'options' => ['placeholder' => 'Дата создания ...'],
        'pluginOptions' => [
            'autoclose'=>true,
            'format' => 'dd-M-yyyy'
        ]
    ]);
    ?>

    <?php
    echo $form->field($model, 'coordinates')->widget(\msvdev\widgets\mappicker\MapInput::className(), ['service' => 'yandex']);
    ?>

    <div class="form-group">
        <?= Html::submitButton('Save', ['class' => 'btn btn-success']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
