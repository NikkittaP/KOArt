<?php

use kartik\file\FileInput;
use yii\helpers\Html;
use yii\helpers\Url;
use yii\web\View;

/* @var $this yii\web\View */
/* @var $paintingModel app\models\Paintings */
/* @var $photos app\models\Photos[] */

$this->title = Yii::t('admin', 'Manage photos');
$baseUrl = Yii::$app->request->baseUrl;

// Ordered id list that seeds the hidden field the form submits.
$orderCsv = implode(',', array_map(function ($p) { return (int) $p->id; }, $photos));

// Which photo is the cover right now (falls back to the first one).
$coverId = 0;
foreach ($photos as $p) {
    if ((int) $p->isMain === 1) { $coverId = (int) $p->id; break; }
}
if ($coverId === 0 && !empty($photos)) {
    $coverId = (int) $photos[0]->id;
}
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['/paintings/index']) ?></div>
        <h1><?= Yii::t('admin', 'Manage photos') ?>: #<?= (int) $paintingModel->id ?> <?= Html::encode($paintingModel->name) ?></h1>
    </div>
    <div class="actions">
        <?= Html::a('← ' . Yii::t('admin', 'Back to work'), ['/paintings/update', 'id' => $paintingModel->id], ['class' => 'btn ghost']) ?>
    </div>
</div>

<div class="panel">
    <h2><?= Yii::t('admin', 'Add photos') ?></h2>
    <?= FileInput::widget([
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
            'maxFileCount' => 20,
        ],
        'pluginEvents' => [
            // Reload once the whole batch is stored so the grid below shows the
            // new photos (they are appended to the end of the order).
            'filebatchuploadcomplete' => 'function(){ window.location.reload(); }',
        ],
    ]) ?>
    <p class="ph-hint" style="margin-top:12px">
        <?= Yii::t('admin', 'JPG and PNG are accepted (PNG is converted to JPG automatically). Max {mb} MB and {px} px on the longer side.', [
            'mb' => 15,
            'px' => 8000,
        ]) ?>
    </p>
</div>

<?= Html::beginForm('', 'post', ['id' => 'photo-manage-form']) ?>
<div class="panel">
    <h2><?= Yii::t('admin', 'Order, cover & delete') ?></h2>

    <?php if (empty($photos)): ?>
        <p style="color:var(--muted)"><?= Yii::t('admin', 'No photos yet — add some above.') ?></p>
    <?php else: ?>
        <p style="color:var(--faint);font-size:12.5px;margin:0 0 12px">
            <?= Yii::t('admin', 'Drag the cards to set the order shown on the work page. Pick one cover (used for thumbnails), and tick any photo to delete it.') ?>
        </p>
        <p style="color:var(--faint);font-size:12.5px;margin:-6px 0 12px">
            <?= Yii::t('admin', 'Tick the photos that go into the section PDF portfolio. If nothing is ticked, the cover is used.') ?>
        </p>

        <input type="hidden" name="order" id="photo-order" value="<?= Html::encode($orderCsv) ?>">

        <div class="photo-sort" id="photo-sort">
            <?php foreach ($photos as $photo): ?>
                <div class="photo-sort-item<?= (int) $photo->id === $coverId ? ' sel' : '' ?>" draggable="true" data-id="<?= (int) $photo->id ?>">
                    <span class="drag-handle" title="<?= Yii::t('admin', 'Drag to reorder') ?>">⠿</span>
                    <?= Html::img($baseUrl . '/paintings_photo/thumb_squared/' . \app\helpers\Img::webp($photo->filename)) ?>
                    <?= Html::a(Yii::t('admin', 'Original'), ['download-original', 'id' => $photo->id], ['class' => 'photo-dl', 'title' => Yii::t('admin', 'Download full-resolution original')]) ?>
                    <div class="photo-sort-controls">
                        <?php if ($photo->hasAttribute('in_portfolio')): ?>
                        <label class="pc-pf" title="<?= Yii::t('admin', 'In portfolio') ?>">
                            <?= Html::checkbox('portfolio_photo_ids[]', (int) $photo->in_portfolio === 1, ['value' => $photo->id]) ?>
                            <?= Yii::t('admin', 'In portfolio') ?>
                        </label>
                        <?php endif; ?>
                        <label class="pc-cover" title="<?= Yii::t('admin', 'Cover') ?>">
                            <?= Html::radio('cover_photo_id', (int) $photo->id === $coverId, ['value' => $photo->id]) ?>
                            <?= Yii::t('admin', 'Cover') ?>
                        </label>
                        <label class="pc-del" title="<?= Yii::t('admin', 'Delete') ?>">
                            <?= Html::checkbox('delete_photo_ids[]', false, ['value' => $photo->id]) ?>
                            <?= Yii::t('admin', 'Delete') ?>
                        </label>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
    <?php endif; ?>
    <?= Html::a(Yii::t('admin', 'Back to work'), ['/paintings/update', 'id' => $paintingModel->id], ['class' => 'btn ghost']) ?>
</div>
<?= Html::endForm() ?>

<?php
$css = <<<CSS
.photo-sort{display:grid;grid-template-columns:repeat(auto-fill,minmax(160px,1fr));gap:14px;margin:4px 0 20px}
.photo-sort-item{position:relative;border:2px solid var(--line);border-radius:5px;overflow:hidden;background:#f4f2ec;transition:border-color .15s,box-shadow .15s,opacity .15s}
.photo-sort-item.sel{border-color:var(--accent);box-shadow:0 0 0 2px rgba(154,106,79,.25)}
.photo-sort-item.dragging{opacity:.45}
.photo-sort-item.drop-target{border-color:var(--accent)}
.photo-sort-item>img{width:100%;height:150px;object-fit:cover;display:block}
.photo-sort-item .drag-handle{position:absolute;top:6px;left:6px;z-index:3;cursor:grab;font-size:16px;line-height:1;padding:3px 6px;border-radius:4px;background:rgba(20,18,16,.7);color:#fff;user-select:none}
.photo-sort-item .drag-handle:active{cursor:grabbing}
.photo-sort-controls{display:flex;flex-wrap:wrap}
.photo-sort-controls label{flex:1 1 50%;min-width:0;display:flex;align-items:center;justify-content:center;gap:4px;padding:7px 3px;font-size:10px;letter-spacing:.01em;line-height:1;white-space:nowrap;color:#fff;cursor:pointer;margin:0}
.photo-sort-controls .pc-cover{background:rgba(20,18,16,.82)}
.photo-sort-controls .pc-del{background:rgba(140,38,30,.85)}
.photo-sort-controls .pc-pf{flex-basis:100%;background:rgba(46,84,62,.85)}
.photo-sort-controls input{flex:none;width:13px;height:13px;margin:0;cursor:pointer}
CSS;
$this->registerCss($css);

$js = <<<JS
(function(){
    var list = document.getElementById('photo-sort');
    if (!list) return;
    var orderField = document.getElementById('photo-order');
    var dragged = null;

    function refreshOrder(){
        var ids = [];
        list.querySelectorAll('.photo-sort-item').forEach(function(it){ ids.push(it.getAttribute('data-id')); });
        orderField.value = ids.join(',');
    }

    // Keep the "sel" highlight in sync with the chosen cover radio.
    list.addEventListener('change', function(e){
        if (e.target && e.target.name === 'cover_photo_id'){
            list.querySelectorAll('.photo-sort-item').forEach(function(it){ it.classList.remove('sel'); });
            var host = e.target.closest('.photo-sort-item');
            if (host) host.classList.add('sel');
        }
    });

    list.querySelectorAll('.photo-sort-item').forEach(function(item){
        item.addEventListener('dragstart', function(){ dragged = item; item.classList.add('dragging'); });
        item.addEventListener('dragend', function(){
            item.classList.remove('dragging');
            list.querySelectorAll('.drop-target').forEach(function(it){ it.classList.remove('drop-target'); });
            dragged = null;
            refreshOrder();
        });
        item.addEventListener('dragover', function(e){
            e.preventDefault();
            if (!dragged || dragged === item) return;
            item.classList.add('drop-target');
            var rect = item.getBoundingClientRect();
            var after = (e.clientY - rect.top) > rect.height / 2;
            if (after) { item.after(dragged); } else { item.before(dragged); }
        });
        item.addEventListener('dragleave', function(){ item.classList.remove('drop-target'); });
    });

    document.getElementById('photo-manage-form').addEventListener('submit', refreshOrder);
})();
JS;
$this->registerJs($js, View::POS_END);
?>
