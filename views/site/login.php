<?php

/* @var $this yii\web\View */
/* @var $model app\models\LoginForm */

use yii\helpers\Html;

$this->title = Yii::t('admin', 'Sign in');
$errors = array_merge(...array_values($model->getErrors() ?: [[]]));
?>
<div class="alogin-wrap">
    <div class="alogin">
        <div class="wm">Katia Oskina</div>
        <div class="sub"><?= Yii::t('admin', 'Archive · Admin') ?></div>

        <?php if (!empty($errors)): ?>
            <div class="flash danger"><?= Html::encode(reset($errors)) ?></div>
        <?php endif; ?>

        <?= Html::beginForm(['/site/login'], 'post') ?>
            <div class="field">
                <label><?= Yii::t('admin', 'Username') ?></label>
                <?= Html::textInput('LoginForm[username]', $model->username, [
                    'autofocus' => true,
                    'autocomplete' => 'username',
                    'style' => 'width:100%',
                ]) ?>
            </div>
            <div class="field">
                <label><?= Yii::t('admin', 'Password') ?></label>
                <?= Html::passwordInput('LoginForm[password]', '', [
                    'autocomplete' => 'current-password',
                    'style' => 'width:100%',
                ]) ?>
            </div>
            <label class="checkbox">
                <?= Html::checkbox('LoginForm[rememberMe]', $model->rememberMe) ?>
                <?= Yii::t('admin', 'Remember me') ?>
            </label>
            <?= Html::submitButton(Yii::t('admin', 'Sign in'), ['class' => 'btn accent']) ?>
        <?= Html::endForm() ?>
    </div>
</div>
