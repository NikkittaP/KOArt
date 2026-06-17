<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $worksTotal int */
/* @var $worksVisible int */
/* @var $worksHidden int */
/* @var $seriesTotal int */
/* @var $seriesVisible int */
/* @var $sectionsTotal int */

$this->title = Yii::t('admin', 'Dashboard');
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Archive') ?></div>
        <h1><?= Yii::t('admin', 'Dashboard') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', '+ Add work'), ['/paintings/create'], ['class' => 'btn accent']) ?>
        <?= Html::a(Yii::t('admin', '+ Add series'), ['/series/create'], ['class' => 'btn ghost']) ?>
    </div>
</div>

<div class="cards">
    <div class="stat">
        <div class="n"><?= $worksTotal ?></div>
        <div class="l"><?= Yii::t('admin', 'Works') ?></div>
        <div class="x">
            <?php if ($hideArchive): ?>
                <?= Yii::t('admin', 'published (archive hidden)') ?>
            <?php else: ?>
                <?= Yii::t('admin', '{v} shown · {h} hidden', ['v' => $worksVisible, 'h' => $worksHidden]) ?>
            <?php endif; ?>
        </div>
    </div>
    <div class="stat">
        <div class="n"><?= $seriesTotal ?></div>
        <div class="l"><?= Yii::t('admin', 'Series') ?></div>
        <div class="x"><?= $hideArchive ? Yii::t('admin', 'published') : Yii::t('admin', '{v} shown', ['v' => $seriesVisible]) ?></div>
    </div>
    <div class="stat">
        <div class="n"><?= $sectionsTotal ?></div>
        <div class="l"><?= Yii::t('admin', 'Sections') ?></div>
        <div class="x"><?= Yii::t('admin', 'navigation') ?></div>
    </div>
</div>

<div class="blocklabel"><span><?= Yii::t('admin', 'Manage') ?></span></div>
<div class="quick">
    <?= Html::a(Yii::t('admin', 'Works'), ['/paintings/index'], ['class' => 'btn ghost']) ?>
    <?= Html::a(Yii::t('admin', 'Series'), ['/series/index'], ['class' => 'btn ghost']) ?>
    <?= Html::a(Yii::t('admin', 'Sections'), ['/sections/index'], ['class' => 'btn ghost']) ?>
    <?= Html::a(Yii::t('admin', 'Genres'), ['/art-genres/index'], ['class' => 'btn ghost']) ?>
    <?= Html::a(Yii::t('admin', 'Grounds'), ['/grounds/index'], ['class' => 'btn ghost']) ?>
    <?= Html::a(Yii::t('admin', 'View site ↗'), ['/site/index'], ['class' => 'btn ghost', 'target' => '_blank']) ?>
</div>
