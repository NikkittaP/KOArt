<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */

$this->title = Yii::t('admin', 'Add work');
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['index']) ?></div>
        <h1><?= Html::encode($this->title) ?></h1>
    </div>
</div>

<?= $this->render('_form', ['model' => $model]) ?>
