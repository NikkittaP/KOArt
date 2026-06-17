<?php

use yii\helpers\Html;
use kartik\form\ActiveForm;
use yii\helpers\ArrayHelper;
use kartik\number\NumberControl;
use kartik\date\DatePicker;
use kartik\select2\Select2;

use app\models\Grounds;
use app\models\ArtGenres;
use app\models\ArtStyles;
use app\models\Materials;
use app\models\Paintings;
use app\models\Sections;
use app\models\Series;
use app\assets\RichTextAsset;

/* @var $this yii\web\View */
/* @var $model app\models\Paintings */
/* @var $form kartik\form\ActiveForm */

RichTextAsset::register($this);
?>

<?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'author_id')->hiddenInput(['value' => 1])->label(false) ?>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Basic info') ?></h2>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true])->label(Yii::t('admin', 'Title') . ' (RU)') ?>

        <?php if ($model->hasAttribute('name_en')): ?>
            <?= $form->field($model, 'name_en')->textInput(['maxlength' => true])
                ->label(Yii::t('admin', 'Title') . ' (EN)')
                ->hint(Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.')) ?>
        <?php endif; ?>

        <?= $form->field($model, 'description')->textarea(['rows' => 6, 'class' => 'form-control rich-text-editor'])
            ->label(Yii::t('admin', 'Description') . ' (RU)')
            ->hint(Yii::t('admin', 'Shown on the series page, under this work. Allowed: bold/italic, paragraphs, lists, links.')) ?>

        <?php if ($model->hasAttribute('description_en')): ?>
            <?= $form->field($model, 'description_en')->textarea(['rows' => 6, 'class' => 'form-control rich-text-editor'])
                ->label(Yii::t('admin', 'Description') . ' (EN)')
                ->hint(Yii::t('admin', 'Shown on the (English) site; falls back to Russian if empty.')) ?>
        <?php endif; ?>

        <?= $form->field($model, 'isVisible')->checkbox(['class' => 'intranet_checkbox'])->label(Yii::t('admin', 'Visible on the site')) ?>

        <?php
        $items = ArrayHelper::map(Series::find()->all(), 'id', 'name');
        echo $form->field($model, 'seriesName')->widget(Select2::className(), [
            'data' => $items,
            'maintainOrder' => true,
            'options' => ['placeholder' => Yii::t('admin', 'Series…'), 'multiple' => true],
            'pluginOptions' => [
                'tags' => true,
                'tokenSeparators' => [','],
                'maximumInputLength' => 30,
            ],
        ])->label(Yii::t('admin', 'Series'));
        ?>

        <?php
        $sections = ArrayHelper::map(Sections::find()->orderBy('sort ASC')->all(), 'id', 'title');
        echo $form->field($model, 'section_id')->widget(Select2::className(), [
            'data' => $sections,
            'options' => ['placeholder' => Yii::t('admin', '— choose —')],
        ])->label(Yii::t('admin', 'Section'));
        ?>

        <?= $form->field($model, 'sort_order')->input('number', ['step' => 1])
            ->label(Yii::t('admin', 'Order'))
            ->hint(Yii::t('admin', 'Order within the section (lower = higher). Can also be changed with ↑/↓ in the list.')) ?>
    </div>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Dimensions') ?></h2>
        <?php
        $sizesModelHorizontal = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` >= `height`'))->orderBy('width ASC, height ASC')->all();
        $sizesHorizontal = [];
        foreach ($sizesModelHorizontal as $sizeModelHorizontal) {
            $key = $sizeModelHorizontal->width . 'x' . $sizeModelHorizontal->height;
            $sizesHorizontal[$key] = $key;
        }

        $sizesModelVertical = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` < `height`'))->orderBy('width ASC, height ASC')->all();
        $sizesVertical = [];
        foreach ($sizesModelVertical as $sizeModelVertical) {
            $key = $sizeModelVertical->width . 'x' . $sizeModelVertical->height;
            $sizesVertical[$key] = $key;
        }

        echo $form->field($model, 'size_horizontal')->dropdownlist($sizesHorizontal, [
            'prompt' => Yii::t('admin', '— pick a landscape size —'),
        ])->label(Yii::t('admin', 'Existing landscape size'));
        echo '<p style="margin:0 0 14px;color:var(--faint);font-size:12px">' . Yii::t('admin', 'OR') . '</p>';
        echo $form->field($model, 'size_vertical')->dropdownlist($sizesVertical, [
            'prompt' => Yii::t('admin', '— pick a portrait size —'),
        ])->label(Yii::t('admin', 'Existing portrait size'));
        echo '<p style="margin:0 0 14px;color:var(--faint);font-size:12px">' . Yii::t('admin', 'OR enter exact size') . '</p>';

        echo $form->field($model, 'width')->input('number', ['min' => 1, 'max' => 1000, 'step' => 'any', 'placeholder' => Yii::t('admin', '1–1000')])->label(Yii::t('admin', 'Width (cm)'));
        echo $form->field($model, 'height')->input('number', ['min' => 1, 'max' => 1000, 'step' => 'any', 'placeholder' => Yii::t('admin', '1–1000')])->label(Yii::t('admin', 'Height (cm)'));
        ?>
    </div>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Artistic details') ?></h2>
        <?php
        $items = ArrayHelper::map(ArtGenres::find()->all(), 'id', 'name');
        echo $form->field($model, 'artGenreName')->widget(Select2::className(), [
            'data' => $items,
            'maintainOrder' => true,
            'options' => ['placeholder' => Yii::t('admin', 'Genre…'), 'multiple' => true],
            'pluginOptions' => ['tags' => true, 'tokenSeparators' => [','], 'maximumInputLength' => 30],
        ])->label(Yii::t('admin', 'Genres'));

        $items = ArrayHelper::map(ArtStyles::find()->all(), 'id', 'name');
        echo $form->field($model, 'artStyleName')->widget(Select2::className(), [
            'data' => $items,
            'maintainOrder' => true,
            'options' => ['placeholder' => Yii::t('admin', 'Style…'), 'multiple' => true],
            'pluginOptions' => ['tags' => true, 'tokenSeparators' => [','], 'maximumInputLength' => 30],
        ])->label(Yii::t('admin', 'Styles'));

        $items = ArrayHelper::map(Grounds::find()->all(), 'id', 'name');
        echo $form->field($model, 'groundName')->widget(Select2::className(), [
            'data' => $items,
            'options' => ['placeholder' => Yii::t('admin', 'Ground…')],
            'pluginOptions' => ['tags' => true, 'tokenSeparators' => [','], 'maximumInputLength' => 30],
        ])->label(Yii::t('admin', 'Ground'));

        $items = ArrayHelper::map(Materials::find()->all(), 'id', 'name');
        echo $form->field($model, 'materials')->widget(Select2::className(), [
            'data' => $items,
            'maintainOrder' => true,
            'options' => ['placeholder' => Yii::t('admin', 'Materials…'), 'multiple' => true],
            'pluginOptions' => ['tags' => true, 'tokenSeparators' => [','], 'maximumInputLength' => 30],
        ])->label(Yii::t('admin', 'Materials'));
        ?>
    </div>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Sale') ?></h2>
        <?php
        echo $form->field($model, 'price')->widget(NumberControl::classname(), [
            'maskedInputOptions' => ['prefix' => '$ ', 'allowMinus' => false],
            'displayOptions' => ['class' => 'form-control kv-monospace', 'style' => 'width=20%'],
        ])->label(Yii::t('admin', 'Price'));

        echo $form->field($model, 'shopURL')->input('url', ['placeholder' => Yii::t('admin', 'Shop link…')])->label(Yii::t('admin', 'Shop link'));
        ?>
    </div>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Date & place') ?></h2>
        <?php
        echo $form->field($model, 'date')->widget(DatePicker::classname(), [
            'options' => ['placeholder' => Yii::t('admin', 'Date created…')],
            'pluginOptions' => [
                'autoclose' => true,
                'startView' => 'year',
                'minViewMode' => 'months',
                'format' => 'yyyy-mm',
                'endDate' => '+0d',
            ],
        ])->label(Yii::t('admin', 'Date created'));

        echo $form->field($model, 'coordinates')->widget(\msvdev\widgets\mappicker\MapInput::className(), ['service' => 'yandex'])->label(Yii::t('admin', 'Location'));
        ?>
    </div>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Private notes (not shown on the site)') ?></h2>
        <?= $form->field($model, 'authorComments_comments')->textarea(['rows' => 6])->label(Yii::t('admin', 'Notes')) ?>
        <?= $form->field($model, 'authorComments_material_costs')->textarea(['rows' => 3])->label(Yii::t('admin', 'Material costs')) ?>
        <?= $form->field($model, 'authorComments_time_costs')->textarea(['rows' => 3])->label(Yii::t('admin', 'Time spent')) ?>
    </div>

    <div style="margin-top:6px">
        <?= Html::submitButton(Yii::t('admin', 'Save'), ['class' => 'btn accent']) ?>
        <?= Html::a(Yii::t('admin', 'Cancel'), ['index'], ['class' => 'btn ghost']) ?>
    </div>

<?php ActiveForm::end(); ?>
