<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

$this->title = 'Выбрать основное фото к картине #' . $paintingModel->id . ' "' . $paintingModel->name . '"';
$this->params['breadcrumbs'][] = ['label' => 'Фото', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="photos-selectmain">

    <h1><?=Html::encode($this->title)?></h1>

        <?php
        $form = ActiveForm::begin([]); 
        
        $list = [];
        foreach ($photos as $photo) {
            $list[$photo->id] = "<img src='/photos/thumb/".$photo->filename."' /><br /><br />";
        }
        
        echo  $form->field($photoModel, 'isMain')->radioList($list, [
            'item' => function ($index, $label, $name, $checked, $value) {
                $id = 'isMain-'. $index;
                return
                    Html::beginTag('div', ['class' => 'custom-control form-control-lg custom-radio', 'style' => 'height: 100%; ']) .
                        Html::radio($name, $checked, ['value' => $value, 'id' => $id, 'class'=>'custom-control-input']) .
                        Html::label($label, $id, ['style'=>'padding-left:30px;', 'class' => 'custom-control-label']) . 
                    Html::endTag('div');
            },
        ]);
        ?>

    <div class="form-group">
        <?= Html::submitButton('Сохранить', ['class' => 'btn btn-success']) ?>
    </div>

    <?php
    ActiveForm::end();
    ?>
</div>
