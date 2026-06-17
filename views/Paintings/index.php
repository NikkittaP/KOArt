<?php

use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $dataProvider yii\data\ActiveDataProvider */
/* @var $series array id => name */
/* @var $sections array id => title */
/* @var $materials array id => name */
/* @var $selectedSeries int */
/* @var $selectedSection int */
/* @var $vis string all|1|0 */
/* @var $show int */
/* @var $totalCount int */
/* @var $hasMore bool */

$this->title = Yii::t('admin', 'Works');
$models = $dataProvider->getModels();
$baseUrl = Yii::$app->request->baseUrl;
$ordering = (int) $selectedSection !== -1;

// Current filters, for building the "Load more" link.
$searchName = Yii::$app->request->get('PaintingsSearch')['name'] ?? '';
$filters = ['index'];
if ($selectedSeries !== -1) $filters['selected_series'] = $selectedSeries;
if ($selectedSection !== -1) $filters['selected_section'] = $selectedSection;
if ($vis !== 'all') $filters['vis'] = $vis;
if ($searchName !== '') $filters['PaintingsSearch']['name'] = $searchName;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Archive') ?></div>
        <h1><?= Yii::t('admin', 'Works') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', '+ Add work'), ['create'], ['class' => 'btn accent']) ?>
    </div>
</div>

<!-- Filter bar (GET, auto-applies on change) -->
<?= Html::beginForm(['index'], 'get', ['class' => 'filterbar', 'id' => 'works-filter']) ?>
    <div class="fg">
        <label><?= Yii::t('admin', 'Section') ?></label>
        <?= Html::dropDownList('selected_section', $selectedSection,
            [-1 => Yii::t('admin', 'All sections')] + $sections,
            ['onchange' => 'this.form.submit()']) ?>
    </div>
    <div class="fg">
        <label><?= Yii::t('admin', 'Series') ?></label>
        <?= Html::dropDownList('selected_series', $selectedSeries,
            [-1 => Yii::t('admin', 'All series')] + $series,
            ['onchange' => 'this.form.submit()']) ?>
    </div>
    <div class="fg">
        <label><?= Yii::t('admin', 'Visibility') ?></label>
        <?= Html::dropDownList('vis', $vis, [
            'all' => Yii::t('admin', 'All'),
            '1' => Yii::t('admin', 'Published'),
            '0' => Yii::t('admin', 'Archived'),
        ], ['onchange' => 'this.form.submit()']) ?>
    </div>
    <div class="fg" style="flex:1;min-width:160px">
        <label><?= Yii::t('admin', 'Search by name') ?></label>
        <?= Html::textInput('PaintingsSearch[name]', Yii::$app->request->get('PaintingsSearch')['name'] ?? '',
            ['placeholder' => Yii::t('admin', 'Type and press Enter'), 'style' => 'width:100%']) ?>
    </div>
    <?php if ($selectedSeries !== -1 || $selectedSection !== -1 || $vis !== 'all' || !empty(Yii::$app->request->get('PaintingsSearch')['name'])): ?>
        <a class="btn ghost sm" href="<?= Url::to(['index']) ?>"><?= Yii::t('admin', 'Reset') ?></a>
    <?php endif; ?>
<?= Html::endForm() ?>

<p style="color:var(--muted);font-size:12.5px;margin:0 0 14px">
    <?= Yii::t('admin', 'Showing {n} of {t}', ['n' => count($models), 't' => $totalCount]) ?>
    <?php if ($ordering): ?>
        · <?= Yii::t('admin', 'Sorted by section order — use ↑/↓ to reorder (this is the order shown on the site).') ?>
    <?php else: ?>
        · <?= Yii::t('admin', 'Pick a section above to reorder works with ↑/↓.') ?>
    <?php endif; ?>
</p>

<!-- Bulk actions + list (POST) -->
<?= Html::beginForm(['bulk-visibility'], 'post', ['id' => 'works-form']) ?>
<?= Html::hiddenInput('selected_section', $selectedSection) ?>

<div class="bulkbar">
    <b><?= Yii::t('admin', 'With selected:') ?></b>
    <button type="submit" class="btn ghost sm" formaction="<?= Url::to(['bulk-visibility']) ?>" name="visible" value="1"><?= Yii::t('admin', 'Show on site') ?></button>
    <button type="submit" class="btn ghost sm" formaction="<?= Url::to(['bulk-visibility']) ?>" name="visible" value="0"><?= Yii::t('admin', 'Hide (archive)') ?></button>
    <span style="color:var(--faint)">|</span>
    <?= Yii::t('admin', 'Move to section:') ?>
    <?= Html::dropDownList('section_id', null, $sections, ['prompt' => Yii::t('admin', '— choose —'), 'style' => 'width:auto']) ?>
    <button type="submit" class="btn ghost sm" formaction="<?= Url::to(['bulk-section']) ?>"><?= Yii::t('admin', 'Move') ?></button>
</div>

<div class="table-scroll">
<table class="atable">
    <thead>
    <tr>
        <th style="width:34px"><input type="checkbox" data-check-all aria-label="Select all"></th>
        <th style="width:64px">ID</th>
        <th style="width:96px"></th>
        <th><?= Yii::t('admin', 'Name') ?></th>
        <th style="width:130px"><?= Yii::t('admin', 'Section') ?></th>
        <th style="width:140px"><?= Yii::t('admin', 'Series') ?></th>
        <th style="width:220px"><?= Yii::t('admin', 'Notes') ?></th>
        <th style="width:90px"><?= Yii::t('admin', 'Size') ?></th>
        <th style="width:110px"><?= Yii::t('admin', 'Visibility') ?></th>
        <?php if ($ordering): ?><th style="width:60px"><?= Yii::t('admin', 'Order') ?></th><?php endif; ?>
        <th style="width:230px"><?= Yii::t('admin', 'Actions') ?></th>
    </tr>
    </thead>
    <tbody id="works-tbody">
    <?php foreach ($models as $m): ?>
        <?php
        $hidden = ($m->isVisible === null || (int) $m->isVisible === 0);
        $thumb = ($m->mainPhoto && $m->mainPhoto->filename)
            ? $baseUrl . '/paintings_photo/thumb_squared/' . $m->mainPhoto->filename : null;
        $seriesNames = [];
        foreach ($m->paintingsToSeries as $p2s) {
            if (isset($series[$p2s->series_id])) $seriesNames[] = $series[$p2s->series_id];
        }
        $sizeLabel = (is_numeric($m->width) && is_numeric($m->height)) ? $m->width . '×' . $m->height : '';
        $noteModel = $m->authorComments;
        $note = $noteModel ? trim((string) $noteModel->comments) : '';
        ?>
        <tr class="<?= $hidden ? 'is-hidden' : '' ?>">
            <td><input type="checkbox" name="ids[]" value="<?= (int) $m->id ?>"></td>
            <td><?= (int) $m->id ?></td>
            <td>
                <?php if ($thumb): ?>
                    <?= Html::a(Html::img($thumb, ['class' => 'thumb', 'width' => 84, 'height' => 84]),
                        ['show', 'id' => $m->id]) ?>
                <?php else: ?>
                    <span class="thumb" style="display:inline-block"></span>
                <?php endif; ?>
            </td>
            <td><?= Html::encode($m->name) ?></td>
            <td><?= isset($sections[$m->section_id]) ? Html::encode($sections[$m->section_id]) : '<span style="color:var(--faint)">—</span>' ?></td>
            <td><?= $seriesNames ? Html::encode(implode(', ', $seriesNames)) : '<span style="color:var(--faint)">—</span>' ?></td>
            <td>
                <?php if ($note === ''): ?>
                    <span style="color:var(--faint)">—</span>
                <?php elseif (mb_strlen($note) <= 70): ?>
                    <span style="color:var(--soft)"><?= Html::encode($note) ?></span>
                <?php else: ?>
                    <details class="note"><summary><span class="trunc"><?= Html::encode(mb_substr($note, 0, 70)) ?>…</span></summary><div class="full"><?= nl2br(Html::encode($note)) ?></div></details>
                <?php endif; ?>
            </td>
            <td><?= $sizeLabel ?: '<span style="color:var(--faint)">—</span>' ?></td>
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
                    <?= Html::a(Yii::t('admin', 'Photos'), ['photos/add', 'painting_id' => $m->id], ['class' => 'btn ghost sm']) ?>
                    <?= Html::a(Yii::t('admin', 'Cover'), ['photos/selectmain', 'painting_id' => $m->id], ['class' => 'btn ghost sm']) ?>
                </div>
            </td>
        </tr>
    <?php endforeach; ?>
    <?php if (empty($models)): ?>
        <tr><td colspan="<?= $ordering ? 11 : 10 ?>" style="text-align:center;color:var(--muted);padding:34px"><?= Yii::t('admin', 'Nothing here yet.') ?></td></tr>
    <?php endif; ?>
    </tbody>
</table>
</div>
<?= Html::endForm() ?>

<?php if ($hasMore): ?>
    <div style="text-align:center;margin-top:22px">
        <a id="loadmore" class="btn ghost" data-tbody="#works-tbody"
           href="<?= Url::to(array_merge($filters, ['show' => $show + 24])) ?>"><?= Yii::t('admin', 'Load more') ?></a>
    </div>
<?php endif; ?>
