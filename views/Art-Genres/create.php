<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\ArtGenres */

$this->title = 'Create Art Genres';
$this->params['breadcrumbs'][] = ['label' => 'Art Genres', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
?>
<div class="art-genres-create">

    <h1><?= Html::encode($this->title) ?></h1>

    <?= $this->render('_form', [
        'model' => $model,
    ]) ?>

</div>
