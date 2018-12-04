<?php

use kartik\grid\GridView;
use yii\helpers\Html;
use yii\helpers\Url;

/* @var $this yii\web\View */
/* @var $searchModel app\models\search\ArtGenresSearchPaintings */
/* @var $dataProvider yii\data\ActiveDataProvider */

$this->title = 'Статистика по картинам';
$this->params['breadcrumbs'][] = $this->title;

?>
<div class="paintings-stats">

    <h1><?=Html::encode($this->title)?></h1>

    <h5>Количество картин по размерам (см):</h5>
    <table class="table">
        <thead>
            <tr>
                <th>Альбомная ориентация</th>
                <th>Портретная ориентация</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>
                <?php
                foreach ($sizesHorizontalGroups as $groupKey => $groupCount) {
                    echo '<br /><h6>[<b>'.$groupCount.'</b>] Ширина в пределах '.$groupKey.' см<br /></h6>';
                    $floor = explode('-', $groupKey)[0];
                    $ceil = explode('-', $groupKey)[1];
                    foreach ($sizesHorizontal as $key=>$value) {
                        $width = explode('x', $key)[0];
                        if ($width>=$floor && $width<$ceil)
                           echo '&nbsp;&nbsp;&nbsp;[<b>'.$value.'</b>] '.$key.'<br />';
                    }
                }
                ?>
                </td>
                <td>
                <?php
                foreach ($sizesVerticalGroups as $groupKey => $groupCount) {
                    echo '<br /><h6>[<b>'.$groupCount.'</b>] Ширина в пределах '.$groupKey.' см<br /></h6>';
                    $floor = explode('-', $groupKey)[0];
                    $ceil = explode('-', $groupKey)[1];
                    foreach ($sizesVertical as $key=>$value) {
                        $width = explode('x', $key)[0];
                        if ($width>=$floor && $width<$ceil)
                           echo '&nbsp;&nbsp;&nbsp;[<b>'.$value.'</b>] '.$key.'<br />';
                    }
                }
                ?>
                </td>
            </tr>
        </tbody>
    </table>
    <br />
</div>
