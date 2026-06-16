<?php

/* @var $this yii\web\View */
/* @var $name string */
/* @var $message string */
/* @var $exception Exception */

use yii\helpers\Html;

$this->title = $name;
?>

<div class="container">

    <h1><?= Html::encode($this->title) ?></h1>

    <div class="alert alert-danger">
        <?= nl2br(Html::encode($message)) ?>
    </div>

    <p>
        <?=\Yii::t('app', 'Вышеупомянутая ошибка произошла во время обработки вашего запроса веб-сервером.');?>
    </p>
    <p>
        <?=\Yii::t('app', 'Свяжитесь с нами, если вы считаете, что это ошибка сервера. Спасибо.');?>
    </p>

</div>