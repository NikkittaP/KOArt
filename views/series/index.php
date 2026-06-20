<?php

use yii\helpers\Html;
use yii\helpers\Url;
use app\helpers\Img;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\SeriesSearch */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $sections array id => title */
/* @var $selectedSection int */

$this->title = Yii::t('admin', 'Series');
$models = $dataProvider->getModels();
$baseUrl = Yii::$app->request->baseUrl;
$ordering = (int) $selectedSection !== -1;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Archive') ?></div>
        <h1><?= Yii::t('admin', 'Series') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', '+ Add series'), ['create'], ['class' => 'btn accent']) ?>
    </div>
</div>

<?= Html::beginForm(['index'], 'get', ['class' => 'filterbar']) ?>
    <div class="fg">
        <label><?= Yii::t('admin', 'Section') ?></label>
        <?= Html::dropDownList('selected_section', $selectedSection,
            [-1 => Yii::t('admin', 'All sections')] + $sections,
            ['onchange' => 'this.form.submit()']) ?>
    </div>
    <?php if ($ordering): ?>
        <a class="btn ghost sm" href="<?= Url::to(['index']) ?>"><?= Yii::t('admin', 'Reset') ?></a>
    <?php endif; ?>
<?= Html::endForm() ?>

<p style="color:var(--muted);font-size:12.5px;margin:0 0 14px">
    <?php if ($ordering): ?>
        <?= Yii::t('admin', 'Sorted by section order — use ↑/↓ to reorder (this is the order shown on the site).') ?>
    <?php else: ?>
        <?= Yii::t('admin', 'Pick a section above to reorder series with ↑/↓.') ?>
    <?php endif; ?>
</p>

<div class="table-scroll">
<table class="atable">
    <thead>
    <tr>
        <th style="width:64px">ID</th>
        <th style="width:160px"></th>
        <th><?= Yii::t('admin', 'Name') ?></th>
        <th style="width:130px"><?= Yii::t('admin', 'Section') ?></th>
        <th style="width:110px"><?= Yii::t('admin', 'Visibility') ?></th>
        <?php if ($ordering): ?><th style="width:60px"><?= Yii::t('admin', 'Order') ?></th><?php endif; ?>
        <th style="width:200px"><?= Yii::t('admin', 'Actions') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php foreach ($models as $m): ?>
        <?php
        $hidden = ($m->isVisible === null || (int) $m->isVisible === 0);
        $cover = $m->cover_filename ? $baseUrl . '/series_cover/thumb/' . Img::webp($m->cover_filename) : null;
        ?>
        <tr class="<?= $hidden ? 'is-hidden' : '' ?>">
            <td><?= (int) $m->id ?></td>
            <td>
                <?php if ($cover): ?>
                    <?= Html::a(Html::img($cover, ['class' => 'thumb', 'alt' => Html::encode($m->name), 'loading' => 'lazy']),
                        ['show', 'id' => $m->id], ['target' => '_blank']) ?>
                <?php else: ?>
                    <span class="thumb ph"></span>
                <?php endif; ?>
            </td>
            <td><?= Html::encode($m->name) ?></td>
            <td><?= isset($sections[$m->section_id]) ? Html::encode($sections[$m->section_id]) : '<span style="color:var(--faint)">—</span>' ?></td>
            <td>
                <?php if ($hidden): ?>
                    <span class="pill off"><?= Yii::t('admin', 'Archived') ?></span>
                <?php else: ?>
                    <span class="pill on"><?= Yii::t('admin', 'On site') ?></span>
                <?php endif; ?>
            </td>
            <?php if ($ordering): ?>
                <td>
                    <span class="ord">
                        <?= Html::a('↑', ['move', 'id' => $m->id, 'direction' => 'up', 'selected_section' => $selectedSection], ['data' => ['method' => 'post'], 'title' => Yii::t('admin', 'Up')]) ?>
                        <?= Html::a('↓', ['move', 'id' => $m->id, 'direction' => 'down', 'selected_section' => $selectedSection], ['data' => ['method' => 'post'], 'title' => Yii::t('admin', 'Down')]) ?>
                    </span>
                </td>
            <?php endif; ?>
            <td>
                <div class="rowact">
                    <?= Html::a(Yii::t('admin', 'Edit'), ['update', 'id' => $m->id], ['class' => 'btn ghost sm']) ?>
                    <?= Html::a(Yii::t('admin', 'Open ↗'), ['show', 'id' => $m->id], ['class' => 'btn ghost sm', 'target' => '_blank']) ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($models)): ?>
        <tr><td colspan="<?= $ordering ? 7 : 6 ?>" style="text-align:center;color:var(--muted);padding:34px"><?= Yii::t('admin', 'Nothing here yet.') ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
