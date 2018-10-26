<?php

use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;

$this->title = 'Добавить фото к картине #' . $paintingModel->id . ' "' . $paintingModel->name . '"';
$this->params['breadcrumbs'][] = ['label' => 'Photos', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="photos-add">

    <h1><?=Html::encode($this->title)?></h1>

    <?php
    echo FileInput::widget([
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
    ]);
    ?>
    <br /><br />
    <?= Html::a('Далее', ['selectmain', 'painting_id' => $paintingModel->id], ['class' => 'btn btn-primary float-right']) ?>
    <br />
</div>
