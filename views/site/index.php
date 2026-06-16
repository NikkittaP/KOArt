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

        $seriesDescription = mb_strlen($series_->description) > 150 ? mb_substr($series_->description, 0, 150) . "..." : $series_->description;

        echo '
            <article class="style' . $styleNum . '">';
        echo '<span class="image">';
        echo Html::img(Yii::$app->request->BaseUrl . '/series_cover/thumb/' . $series_->cover_filename, []);
        echo '</span>';
        echo '
            ' . Html::a('
            <h2>' . $series_->name . '</h2>
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