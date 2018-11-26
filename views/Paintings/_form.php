<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\number\NumberControl;
use kartik\date\DatePicker;
//use dosamigos\selectize\SelectizeDropDownList;
use kartik\select2\Select2;

use app\models\Grounds;
use app\models\ArtGenres;
use app\models\ArtStyles;
use app\models\Materials;
use app\models\Paintings;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */
/* @var $form yii\widgets\ActiveForm */
?>

<div class="paintings-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'author_id')->hiddenInput(['value'=> 1])->label(false); ?>
    <div class="card border-dark">
        <div class="card-header">
            <h5>Базовая информация</h5>
        </div>
        <div class="card-body">
            <?= $form->field($model, 'name')->textInput(['maxlength' => true]) ?>

            <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
        </div>
    </div>
    <br />

    <div class="card border-dark">
        <div class="card-header">
            <h5>Размеры</h5>
        </div>
        <div class="card-body">
            <?php
            $sizesModelHorizontal = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` >= `height`'))->orderBy('width ASC, height ASC')->all();
            $sizesHorizontal = [];
            foreach ($sizesModelHorizontal as $sizeModelHorizontal) {
                $key = $sizeModelHorizontal->width.'x'.$sizeModelHorizontal->height;
                $sizesHorizontal[$key] = $key;
            }

            $sizesModelVertical = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` < `height`'))->orderBy('width ASC, height ASC')->all();
            $sizesVertical = [];
            foreach ($sizesModelVertical as $sizeModelVertical) {
                $key = $sizeModelVertical->width.'x'.$sizeModelVertical->height;
                $sizesVertical[$key] = $key;
            }

            echo $form->field($model, 'size_horizontal')->dropdownlist($sizesHorizontal, [
                'prompt'=>'- Выбрать альбомный размер -'
            ]);
            echo '<b>ИЛИ</b><br /><br />';
            echo $form->field($model, 'size_vertical')->dropdownlist($sizesVertical, [
                'prompt'=>'- Выбрать портретный размер -'
            ]);
            echo '<b>ИЛИ</b><br /><br />';
            /*
            $sizeCount = [];
            foreach ($sizesModel as $sizeModel) {
                if (is_numeric($sizeModel->width) && is_numeric($sizeModel->height))
                {
                    $key = $sizeModel->width.'x'.$sizeModel->height;
                    if (!array_key_exists($key, $sizeCount))
                        $sizeCount[$key] = 0;

                    $sizeCount[$key]++;
                }
            }

            arsort($sizeCount);

            $sizes = [];
            foreach ($sizeCount as $key => $value)
            {
                $sizes[$key] = $key;
            }
            */
            ?>

            <?php
            echo $form->field($model, 'width')->
            input('number', ['min'=>1, 'max'=> 1000, 'step'=>'any', 'placeholder'=>'Длина от 1 до 1000']);
            ?>

            <?php
            echo $form->field($model, 'height')->
            input('number', ['min'=>1, 'max'=> 1000, 'step'=>'any', 'placeholder'=>'Высота от 1 до 1000']);
            ?>
        </div>
    </div>
    <br />

    <div class="card border-dark">
        <div class="card-header">
            <h5>Художественная информация</h5>
        </div>
        <div class="card-body">
            <?php
            $items =  ArrayHelper::map(ArtGenres::find()->all(), 'id', 'name');
            echo $form->field($model, 'artGenreName')->widget(Select2::className(), [
                'data' => $items,
                'maintainOrder' => true,
                'options' => [
                    'placeholder' => 'Жанр картины ...',
                    'multiple' => true
                ],
                'pluginOptions' => [
                    'tags' => true,
                    'tokenSeparators' => [','],
                    'maximumInputLength' => 30
                ],
            ]);
            ?>

            <?php
            $items =  ArrayHelper::map( ArtStyles ::find()->all(), 'id', 'name');
            echo $form->field($model, 'artStyleName')->widget(Select2::className(), [
                'data' => $items,
                'maintainOrder' => true,
                'options' => ['placeholder' => 'Стиль картины ...', 'multiple' => true],
                'pluginOptions' => [
                    'tags' => true,
                    'tokenSeparators' => [','],
                    'maximumInputLength' => 30
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
                    'tokenSeparators' => [','],
                    'maximumInputLength' => 30
                ],
            ]);
            ?>

            <?php
            $items =  ArrayHelper::map( Materials ::find()->all(), 'id', 'name');
            echo $form->field($model, 'materials')->widget(Select2::className(), [
                'data' => $items,
                'maintainOrder' => true,
                'options' => ['placeholder' => 'Использованные материалы ...', 'multiple' => true],
                'pluginOptions' => [
                    'tags' => true,
                    'tokenSeparators' => [','],
                    'maximumInputLength' => 30
                ],
            ]);
            ?>
        </div>
    </div>
    <br />

    <div class="card border-dark">
        <div class="card-header">
            <h5>Продажа</h5>
        </div>
        <div class="card-body">
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
        </div>
    </div>
    <br />

    <div class="card border-dark">
        <div class="card-header">
            <h5>Дата и место</h5>
        </div>
        <div class="card-body">
            <?php
            echo $form->field($model, 'date')->widget(DatePicker::classname(), [
                'options' => ['placeholder' => 'Дата создания ...'],
                'pluginOptions' => [
                    'autoclose'=>true,
                    'startView'=>'year',
                    'minViewMode'=>'months',
                    'format' => 'yyyy-mm',
                    'endDate' => '+0d'
                ]
            ]);
            ?>

            <?php
            echo $form->field($model, 'coordinates')->widget(\msvdev\widgets\mappicker\MapInput::className(), ['service' => 'yandex']);
            ?>
        </div>
    </div>
    <br />

    <div class="card border-info">
        <div class="card-header bg-info text-white">
            <h5>Скрытые комментарии от автора</h5>
        </div>
        <div class="card-body text-info">

        <?= $form->field($model, 'authorComments_comments')->textarea(['rows' => 6]) ?>
        <?= $form->field($model, 'authorComments_material_costs')->textarea(['rows' => 3]) ?>
        <?= $form->field($model, 'authorComments_time_costs')->textarea(['rows' => 3]) ?>
            
        </div>
    </div>
    <br />

    <div class="form-group">
        <?= Html::submitButton('Далее', ['class' => 'btn btn-primary float-right']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
