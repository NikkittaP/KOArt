<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\MaterialsSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = Yii::t('admin', 'Materials');
$materials = $dataProvider->getModels();
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Taxonomy') ?></div>
        <h1><?= Yii::t('admin', 'Materials') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', '+ Add material'), ['create'], ['class' => 'btn accent']) ?>
    </div>
</div>

<div class="table-scroll">
<table class="atable">
    <thead>
    <tr>
        <th style="width:60px">ID</th>
        <th><?= Yii::t('admin', 'Name (RU)') ?></th>
        <th><?= Yii::t('admin', 'Name (EN)') ?></th>
        <th style="width:200px"><?= Yii::t('admin', 'Actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($materials as $m): ?>
        <tr>
            <td><?= (int) $m->id ?></td>
            <td><?= Html::encode($m->name) ?></td>
            <td><?= ($m->hasAttribute('name_en') && $m->name_en) ? Html::encode($m->name_en) : '<span style="color:var(--faint)">—</span>' ?></td>
            <td>
                <div class="rowact">
                    <?= Html::a(Yii::t('admin', 'Edit'), ['update', 'id' => $m->id], ['class' => 'btn ghost sm']) ?>
                    <?= Html::a(Yii::t('admin', 'Delete'), ['delete', 'id' => $m->id], [
                        'class' => 'btn danger sm',
                        'data' => ['confirm' => Yii::t('admin', 'Delete this item?'), 'method' => 'post'],
                    ]) ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
</div>
