<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;

$this->title = 'Удалить фото к картине #' . $paintingModel->id . ' "' . $paintingModel->name . '"';
$this->params['breadcrumbs'][] = ['label' => 'Картины', 'url' => ['paintings/index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="photos-delete">

    <h1><?=Html::encode($this->title)?></h1>

        <?php
        $form = ActiveForm::begin([]); 
        
        $list = [];
        foreach ($photos as $photo) {
            $list[$photo->id] = "<img src='/photos/thumb/".$photo->filename."' /><br /><br />";
        }
        
        echo  $form->field($photoModel, 'selected[]')->checkboxList($list, [
            'item' => function ($index, $label, $name, $checked, $value) {
                $id = 'selected-'. $index;
                return
                    Html::beginTag('div', ['class' => 'custom-control form-control-lg custom-checkbox', 'style' => 'height: 100%; ']) .
                        Html::checkbox($name, $checked, ['value' => $value, 'id' => $id, 'class'=>'custom-control-input']) .
                        Html::label($label, $id, ['style'=>'padding-left:30px;', 'class' => 'custom-control-label']) . 
                    Html::endTag('div');
            },
        ]);
        ?>

    <div class="form-group">
        <?= Html::submitButton('Удалить', ['class' => 'btn btn-danger', 'data-confirm'=> 'Вы уверены что хотите удалить выбранные фото?']) ?>
    </div>

    <?php
    ActiveForm::end();
    ?>
</div>
