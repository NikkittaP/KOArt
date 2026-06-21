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

<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]); ?>

    <?= $form->field($model, 'author_id')->hiddenInput(['value' => 1])->label(false) ?>

    <?php if ($model->isNewRecord): ?>
    <div class="panel" id="photo-panel">
        <h2><?= Yii::t('admin', 'Photos') ?></h2>

        <div class="ph-up">
            <label class="ph-drop" id="ph-drop">
                <input type="file" id="ph-input" name="photos[]" accept="image/*" multiple>
                <div class="ph-drop-empty" id="ph-empty">
                    <span class="ph-drop-title"><?= Yii::t('admin', 'Drop a photo here, or click to choose') ?></span>
                    <span class="ph-drop-sub"><?= Yii::t('admin', 'The first photo is the cover. You can add more — they stay smaller.') ?></span>
                </div>
                <div class="ph-cover" id="ph-cover" hidden>
                    <img id="ph-cover-img" alt="">
                    <span class="ph-cover-badge"><?= Yii::t('admin', 'Cover') ?></span>
                </div>
            </label>

            <div class="ph-thumbs" id="ph-thumbs" hidden></div>

            <p class="ph-hint">
                <?= Yii::t('admin', 'Click a thumbnail to make it the cover.') ?><br>
                <?= Yii::t('admin', 'JPG and PNG are accepted (PNG is converted to JPG automatically). Max {mb} MB and {px} px on the longer side.', [
                    'mb' => 15,
                    'px' => 8000,
                ]) ?>
            </p>
        </div>

        <input type="hidden" name="cover_index" id="ph-cover-index" value="0">
        <input type="hidden" name="device_coords" id="ph-device-coords" value="">
    </div>
    <?php endif; ?>

    <div class="panel">
        <h2><?= Yii::t('admin', 'Basic info') ?></h2>

        <?= $form->field($model, 'name')->textInput(['maxlength' => true])
            ->label(Yii::t('admin', 'Title') . ' (RU)')
            ->hint(Yii::t('admin', 'Optional — leave blank and a default (genre + month) is filled in automatically.')) ?>

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
        // Section sits above Series: it's the higher-level grouping. It applies
        // only to "loose" works (not in any series). When a work has series,
        // the section is set by the series, so this field is locked (see the
        // script at the bottom) and section_id is cleared server-side.
        $sections = ArrayHelper::map(Sections::find()->orderBy('sort ASC')->all(), 'id', 'title');
        echo $form->field($model, 'section_id')->widget(Select2::className(), [
            'data' => $sections,
            'options' => ['placeholder' => Yii::t('admin', '— choose —')],
        ])->label(Yii::t('admin', 'Section'))
          ->hint(Yii::t('admin', 'Used only for works that are not in a series — otherwise the section is set by the series.'));

        // Series. With "Hide archive" on, drop archived series from the list,
        // but keep any already linked to this work so editing won't unlink them.
        $seriesQuery = Series::find();
        if (\app\helpers\AdminPrefs::hideArchive()) {
            $selectedSeriesIds = is_array($model->seriesName)
                ? array_values(array_filter($model->seriesName, 'is_numeric'))
                : [];
            $seriesQuery->andWhere([
                'or',
                ['isVisible' => 1],
                ['id' => $selectedSeriesIds ?: [0]],
            ]);
        }
        $items = ArrayHelper::map($seriesQuery->all(), 'id', 'name');
        echo $form->field($model, 'seriesName')->widget(Select2::className(), [
            'data' => $items,
            'maintainOrder' => true,
            'options' => ['placeholder' => Yii::t('admin', 'Series…'), 'multiple' => true],
            'pluginOptions' => [
                'tags' => true,
                'tokenSeparators' => [','],
                'maximumInputLength' => 30,
            ],
        ])->label(Yii::t('admin', 'Series'))
          ->hint(Yii::t('admin', 'A work in a series is placed by the series — add it to several series to show it in several sections.'));
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
        if ($model->hasAttribute('status')) {
            echo $form->field($model, 'status')->dropDownList(Paintings::statuses())
                ->label(Yii::t('admin', 'Status'))
                ->hint(Yii::t('admin', 'Availability of the original. Admin-only; not shown on the public site.'));
        }

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
        ])->label(Yii::t('admin', 'Date created'))
          ->hint($model->isNewRecord ? Yii::t('admin', 'Filled automatically from the photo when available; you can override it.') : '');

        echo $form->field($model, 'coordinates')->widget(\msvdev\widgets\mappicker\MapInput::className(), ['service' => 'yandex'])
            ->label(Yii::t('admin', 'Location'))
            ->hint($model->isNewRecord ? Yii::t('admin', 'Filled automatically from the photo when available; you can override it.') : '');
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

<?php if ($model->isNewRecord): ?>
    <?php $this->registerJsFile('@web/js/painting-form.js', ['position' => \yii\web\View::POS_END]); ?>
<?php endif; ?>

<?php
// Section ↔ Series are mutually exclusive: once a series is chosen, the work's
// section comes from the series, so lock & clear the Section field. Purely
// cosmetic — the server also clears section_id when series are present.
$this->registerJs(<<<'JS'
(function () {
  var series = jQuery('#paintings-seriesname');
  if (!series.length) return;
  var section = jQuery('#paintings-section_id');
  var sectionField = jQuery('.field-paintings-section_id');
  function sync() {
    var locked = (series.val() || []).length > 0;
    if (locked) { section.val(null); }
    section.prop('disabled', locked).trigger('change.select2');
    sectionField.toggleClass('is-locked', locked);
  }
  series.on('change', sync);
  sync();
})();
JS
);
?>
