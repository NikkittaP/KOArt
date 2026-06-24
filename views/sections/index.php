<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $sections app\models\Sections[] */

$this->title = Yii::t('admin', 'Sections');
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Navigation') ?></div>
        <h1><?= Yii::t('admin', 'Sections') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', '+ Add section'), ['create'], ['class' => 'btn accent']) ?>
    </div>
</div>

<p style="color:var(--soft);max-width:640px;margin-bottom:18px;font-size:13.5px">
    <?= Yii::t('admin', 'Sections are the top-level navigation of the public site. Order controls where each appears in the menu (lower = higher).') ?>
</p>

<div class="table-scroll">
<table class="atable">
    <thead>
    <tr>
        <th style="width:70px"><?= Yii::t('admin', 'Order') ?></th>
        <th><?= Yii::t('admin', 'Title') ?></th>
        <th><?= Yii::t('admin', 'Slug') ?></th>
        <th style="width:80px"><?= Yii::t('admin', 'Works') ?></th>
        <th style="width:80px"><?= Yii::t('admin', 'On site') ?></th>
        <th style="width:80px"><?= Yii::t('admin', 'Series') ?></th>
        <th style="width:80px"><?= Yii::t('admin', 'On site') ?></th>
        <th style="width:300px"><?= Yii::t('admin', 'Actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($sections as $section): ?>
        <tr>
            <td><?= (int) $section->sort ?></td>
            <td><strong style="font-weight:400"><?= Html::encode($section->title) ?></strong></td>
            <td><code><?= Html::encode($section->slug) ?></code></td>
            <?php
            $paintingsTotal = (int) $section->getPaintings()->count();
            $paintingsLive = (int) $section->getPaintings()->andWhere(['isVisible' => 1])->count();
            $seriesTotal = (int) $section->getSeries()->count();
            $seriesLive = (int) $section->getSeries()->andWhere(['isVisible' => 1])->count();
            ?>
            <td><?= $paintingsTotal ?></td>
            <td style="color:var(--muted)"><?= $paintingsLive ?></td>
            <td><?= $seriesTotal ?></td>
            <td style="color:var(--muted)"><?= $seriesLive ?></td>
            <td>
                <div class="rowact">
                    <?= Html::a(Yii::t('admin', 'Edit'), ['update', 'id' => $section->id], ['class' => 'btn ghost sm']) ?>
                    <?= Html::a(Yii::t('admin', 'Works in section'), ['/paintings/index', 'selected_section' => $section->id], ['class' => 'btn ghost sm']) ?>
                    <?= Html::a(Yii::t('admin', 'Delete'), ['delete', 'id' => $section->id], [
                        'class' => 'btn danger sm',
                        'data' => [
                            'confirm' => Yii::t('admin', 'Delete section "{name}"? Only possible if it has no works or series.', ['name' => $section->title]),
                            'method' => 'post',
                        ],
                    ]) ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
