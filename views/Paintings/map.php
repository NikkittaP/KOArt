<?php

use yii\helpers\Html;
use yii\helpers\Json;

/* @var $this yii\web\View */
/* @var $points array list of {id,name,lat,lng,thumb,section,series,date,visible,editUrl,viewUrl} */

$this->title = Yii::t('admin', 'Map');
$count = count($points);

// i18n strings needed by the client-side script.
$i18n = [
    'edit' => Yii::t('admin', 'Edit'),
    'view' => Yii::t('admin', 'View on site'),
    'noPhoto' => Yii::t('admin', 'No photo'),
    'section' => Yii::t('admin', 'Section'),
    'series' => Yii::t('admin', 'Series'),
    'date' => Yii::t('admin', 'Date created'),
    'archived' => Yii::t('admin', 'Archived'),
];
?>
<div class="apagehead">
    <div>
        <div class="crumb"><?= Yii::t('admin', 'Archive') ?></div>
        <h1><?= Yii::t('admin', 'Map') ?></h1>
    </div>
    <div class="actions">
        <?= Html::a(Yii::t('admin', 'Works'), ['index'], ['class' => 'btn ghost']) ?>
    </div>
</div>

<p style="color:var(--muted);font-size:12.5px;margin:0 0 14px">
    <?= Yii::t('admin', '{n} works with a geotag', ['n' => $count]) ?>
    · <?= Yii::t('admin', 'Click a cluster to zoom in; click a marker for details.') ?>
</p>

<?php if ($count === 0): ?>
    <div class="panel" style="text-align:center;color:var(--muted);padding:40px">
        <?= Yii::t('admin', 'No works have a geotag yet. Add a location to a work to see it here.') ?>
    </div>
<?php else: ?>
    <div id="works-map" class="works-map"></div>
    <script type="application/json" id="works-map-data"><?= Json::encode($points) ?></script>
    <script type="application/json" id="works-map-i18n"><?= Json::encode($i18n) ?></script>
<?php endif; ?>

<?php
if ($count > 0) {
    // Leaflet + Leaflet.markercluster (both from unpkg, no API key). Leaflet is
    // the same version already used by the location picker on the work form.
    $this->registerCssFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
    $this->registerCssFile('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.css');
    $this->registerCssFile('https://unpkg.com/leaflet.markercluster@1.5.3/dist/MarkerCluster.Default.css');
    $this->registerJsFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', ['position' => \yii\web\View::POS_HEAD]);
    $this->registerJsFile('https://unpkg.com/leaflet.markercluster@1.5.3/dist/leaflet.markercluster.js', ['position' => \yii\web\View::POS_HEAD]);
    $this->registerJsFile('@web/js/paintings-map.js', ['position' => \yii\web\View::POS_END]);
}
?>
