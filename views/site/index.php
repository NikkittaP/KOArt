<?php
use yii\helpers\Html;
use yii\widgets\LinkPager;

/* @var $this yii\web\View */

$this->title = 'Oskina.Art';
?>

<div class="container">
    <section class="tiles">
        <?php
    foreach ($series as $series_) {
        $styleNum = 7;

        $seriesName = $series_->tr('name');
        $seriesDesc = (string) $series_->tr('description');
        $seriesDescription = mb_strlen($seriesDesc) > 150 ? mb_substr($seriesDesc, 0, 150) . "..." : $seriesDesc;

        echo '
            <article class="style' . $styleNum . '">';
        echo '<span class="image">';
        echo Html::img(Yii::$app->request->BaseUrl . '/series_cover/thumb/' . \app\helpers\Img::webp($series_->cover_filename), []);
        echo '</span>';
        echo '
            ' . Html::a('
            <h2>' . Html::encode($seriesName) . '</h2>
            <div class="content">
                <p>
                    ' . $seriesDescription . '
                </p>
            </div>
            ', ['series/show', 'id' => $series_->id]);
        echo '
            </article>';
    }
    ?>
    </section>

    <br /><br /><br />
    <div style="text-align:center;">
        <?php
    echo LinkPager::widget([
        'pagination' => $pagination,
        'firstPageLabel' => true,
        'lastPageLabel' => true,
        'options' => [
            'class' => 'pagination',
        ],
        'linkContainerOptions' => ['class' => 'page-item'],
        'linkOptions' => ['class' => 'page-link'],
        'disabledListItemSubTagOptions' => ['class' => 'page-link'],
    ]);
    ?>
    </div>
</div>