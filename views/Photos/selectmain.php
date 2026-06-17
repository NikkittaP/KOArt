<?php

use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $paintingModel app\models\Paintings */
/* @var $photoModel app\models\Photos */
/* @var $photos app\models\Photos[] */

$this->title = Yii::t('admin', 'Choose cover');
$baseUrl = Yii::$app->request->baseUrl;
$current = $photoModel->isMain;
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['/paintings/index']) ?></div>
        <h1><?= Yii::t('admin', 'Choose cover') ?>: #<?= (int) $paintingModel->id ?> <?= Html::encode($paintingModel->name) ?></h1>
    </div>
</div>

<?= Html::beginForm('', 'post') ?>
<div class="panel">
    <?php if (empty($photos)): ?>
        <p style="color:var(--muted)"><?= Yii::t('admin', 'No photos yet — add some first.') ?></p>
    <?php else: ?>
        <div class="photo-grid">
            <?php foreach ($photos as $photo): ?>
                <label class="photo-pick <?= (int) $current === (int) $photo->id ? 'sel' : '' ?>">
                    <?= Html::radio('Photos[isMain]', (int) $current === (int) $photo->id, ['value' => $photo->id]) ?>
                    <?= Html::img($baseUrl . '/paintings_photo/thumb_squared/' . $photo->filename) ?>
                </label>
            <?php endforeach; ?>
        </div>
        <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
    <?php endif; ?>
    <?= Html::a(Yii::t('admin', 'Back'), ['add', 'painting_id' => $paintingModel->id], ['class' => 'btn ghost']) ?>
</div>
<?= Html::endForm() ?>
