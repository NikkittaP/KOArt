<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $paintingModel app\models\Paintings */
/* @var $photoModel app\models\Photos */
/* @var $photos app\models\Photos[] */

$this->title = Yii::t('admin', 'Delete photos');
$baseUrl = Yii::$app->request->baseUrl;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['/paintings/index']) ?></div>
        <h1><?= Yii::t('admin', 'Delete photos') ?>: #<?= (int) $paintingModel->id ?> <?= Html::encode($paintingModel->name) ?></h1>
    </div>
</div>

<?= Html::beginForm('', 'post') ?>
<div class="panel">
    <?php if (empty($photos)): ?>
        <p style="color:var(--muted)"><?= Yii::t('admin', 'No photos yet.') ?></p>
    <?php else: ?>
        <p style="color:var(--muted);font-size:12.5px;margin-bottom:6px"><?= Yii::t('admin', 'Tick the photos you want to delete.') ?></p>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?>
                <label class="photo-pick">
                    <?= Html::checkbox('Photos[selected][]', false, ['value' => $photo->id]) ?>
                    <?= Html::img($baseUrl . '/paintings_photo/thumb_squared/' . $photo->filename) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?= Html::submitButton(Yii::t('admin', 'Delete selected'), [
            'class' => 'btn danger',
            'data' => ['confirm' => Yii::t('admin', 'Delete the selected photos?')],
        ]) ?>
    <?php endif; ?>
    <?= Html::a(Yii::t('admin', 'Back'), ['selectmain', 'painting_id' => $paintingModel->id], ['class' => 'btn ghost']) ?>
</div>
<?= Html::endForm() ?>
