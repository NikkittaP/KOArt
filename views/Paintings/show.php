<?php

use app\models\Photos;
use yii\helpers\Html;

/* @var $this yii\web\View */
/* @var $painting app\models\Paintings */
/* @var $series app\models\Series */
/* @var $sizeLabel string */
/* @var $dateLabel string */
/* @var $materialsLabel string */

$this->title = $painting->name;
$baseUrl = Yii::$app->request->baseUrl;
$photos = Photos::find()->where(['painting_id' => $painting->id])->orderBy(['isMain' => SORT_DESC])->all();
$comments = $painting->authorComments;
$hasComments = $comments && (trim((string) $comments->comments) !== ''
    || trim((string) $comments->material_costs) !== ''
    || trim((string) $comments->time_costs) !== '');
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Html::a(Yii::t('admin', 'Works'), ['/paintings/index']) ?></div>
        <h1><?= Html::encode($painting->name ?: ('#' . $painting->id)) ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', 'Edit'), ['/paintings/update', 'id' => $painting->id], ['class' => 'btn accent']) ?>
        <?= Html::a(Yii::t('admin', 'Photos'), ['/photos/add', 'painting_id' => $painting->id], ['class' => 'btn ghost']) ?>
    </div>
</div>

<div class="form-grid">
    <div>
        <div class="panel">
            <?php if (empty($photos)): ?>
                <p style="color:var(--muted)"><?= Yii::t('admin', 'No photos yet.') ?></p>
            <?php else: ?>
                <?php foreach ($photos as $i => $photo): ?>
                    <a href="<?= $baseUrl ?>/paintings_photo/original_site/<?= Html::encode($photo->filename) ?>" target="_blank" rel="noopener" style="display:block;margin-bottom:14px">
                        <?= Html::img($baseUrl . '/paintings_photo/preview/' . $photo->filename, [
                            'style' => 'width:100%;height:auto;border-radius:4px;border:1px solid var(--line)',
                        ]) ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <div>
        <div class="panel">
            <h2><?= Yii::t('admin', 'Details') ?></h2>
            <table class="kv">
                <?php
                $rows = [
                    Yii::t('admin', 'Series') => $series->name ?? null,
                    Yii::t('admin', 'Ground') => $painting->ground->name ?? null,
                    Yii::t('admin', 'Materials') => $materialsLabel ?: null,
                    Yii::t('admin', 'Date created') => $dateLabel ?: null,
                    Yii::t('admin', 'Size') => $sizeLabel ?: null,
                ];
                foreach ($rows as $label => $value):
                    if (!$value) continue; ?>
                    <tr>
                        <td style="color:var(--muted);padding:6px 14px 6px 0;white-space:nowrap;vertical-align:top"><?= $label ?></td>
                        <td style="padding:6px 0"><?= Html::encode($value) ?></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td style="color:var(--muted);padding:6px 14px 6px 0;vertical-align:top"><?= Yii::t('admin', 'Visibility') ?></td>
                    <td style="padding:6px 0">
                        <?php if ($painting->isVisible === null || (int) $painting->isVisible === 0): ?>
                            <span class="pill off"><?= Yii::t('admin', 'Archived') ?></span>
                        <?php else: ?>
                            <span class="pill on"><?= Yii::t('admin', 'On site') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<?php if (trim((string) $painting->description) !== ''): ?>
    <div class="panel">
        <h2><?= Yii::t('admin', 'Description') ?></h2>
        <div style="color:var(--soft);line-height:1.7"><?= $painting->description ?></div>
    </div>
<?php endif; ?>

<?php if (!Yii::$app->user->isGuest && $hasComments): ?>
    <div class="panel">
        <h2><?= Yii::t('admin', 'Private notes (not shown on the site)') ?></h2>
        <?php if (trim((string) $comments->comments) !== ''): ?>
            <div class="field"><label><?= Yii::t('admin', 'Notes') ?></label><div style="color:var(--soft)"><?= nl2br(Html::encode($comments->comments)) ?></div></div>
        <?php endif; ?>
        <?php if (trim((string) $comments->material_costs) !== ''): ?>
            <div class="field"><label><?= Yii::t('admin', 'Material costs') ?></label><div style="color:var(--soft)"><?= nl2br(Html::encode($comments->material_costs)) ?></div></div>
        <?php endif; ?>
        <?php if (trim((string) $comments->time_costs) !== ''): ?>
            <div class="field" style="margin-bottom:0"><label><?= Yii::t('admin', 'Time spent') ?></label><div style="color:var(--soft)"><?= nl2br(Html::encode($comments->time_costs)) ?></div></div>
        <?php endif; ?>
    </div>
<?php endif; ?>
