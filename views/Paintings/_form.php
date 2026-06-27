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
        <h2><?= Yii::t('admin', 'Photo') ?></h2>

        <div class="ph-up">
            <label class="ph-drop" id="ph-drop"
                   data-max-mb="15"
                   data-err-large="<?= Html::encode(Yii::t('admin', 'Image "{name}" is too large — maximum is {max} MB.', ['max' => 15])) ?>"
                   data-err-type="<?= Html::encode(Yii::t('admin', 'File "{name}" is not an image and was skipped.')) ?>">
                <input type="file" id="ph-input" name="photos[]" accept="image/*">
                <div class="ph-drop-empty" id="ph-empty">
                    <span class="ph-drop-title"><?= Yii::t('admin', 'Drop a photo here, or click to choose') ?></span>
                    <span class="ph-drop-sub"><?= Yii::t('admin', 'One photo per work. This is the cover shown everywhere.') ?></span>
                </div>
                <div class="ph-cover" id="ph-cover" hidden>
                    <img id="ph-cover-img" alt="">
                    <span class="ph-cover-badge"><?= Yii::t('admin', 'Cover') ?></span>
                </div>
            </label>

            <div class="ph-thumbs" id="ph-thumbs" hidden></div>

            <p class="ph-hint">
                <?= Yii::t('admin', 'JPG and PNG are accepted (PNG is converted to JPG automatically). Max {mb} MB and {px} px on the longer side.', [
                    'mb' => 15,
                    'px' => 8000,
                ]) ?>
            </p>
            <p class="ph-error" id="ph-error" role="alert" hidden></p>
        </div>

        <input type="hidden" name="cover_index" id="ph-cover-index" value="0">
        <input type="hidden" name="device_coords" id="ph-device-coords" value="">
    </div>
    <?php else: ?>
    <?php
        // Unified photo management on the edit form. A work now has a single
        // image, but legacy works may still have several — they are all shown
        // here with delete checkboxes (and a cover radio when there is a
        // choice). Uploading a new photo replaces the current cover.
        $photos = \app\models\Photos::find()
            ->where(['painting_id' => $model->id])
            ->orderBy(['isMain' => SORT_DESC, 'id' => SORT_ASC])
            ->all();
        $mainPhoto = null;
        foreach ($photos as $p) {
            if ((int) $p->isMain === 1) { $mainPhoto = $p; break; }
        }
        if ($mainPhoto === null && !empty($photos)) {
            $mainPhoto = $photos[0];
        }
        $baseUrl = Yii::$app->request->baseUrl;
    ?>
    <div class="panel" id="photo-panel">
        <h2><?= Yii::t('admin', 'Photo') ?></h2>

        <div class="ph-replace">
            <div class="ph-replace-now">
                <span class="ph-replace-label"><?= Yii::t('admin', 'Current') ?></span>
                <?php if ($mainPhoto): ?>
                    <img id="ph-now-img" src="<?= $baseUrl . '/paintings_photo/thumb_squared/' . \app\helpers\Img::webp($mainPhoto->filename) ?>" alt="">
                <?php else: ?>
                    <span class="thumb ph" id="ph-now-img" style="display:inline-block"></span>
                <?php endif; ?>
            </div>

            <span class="ph-replace-arrow" id="ph-replace-arrow" hidden>→</span>

            <div class="ph-replace-next" id="ph-replace-next" hidden>
                <span class="ph-replace-label"><?= Yii::t('admin', 'After upload') ?></span>
                <img id="ph-next-img" alt="">
            </div>
        </div>

        <label class="ph-drop ph-drop-replace" id="ph-replace-drop"
               data-max-mb="15"
               data-err-large="<?= Html::encode(Yii::t('admin', 'Image "{name}" is too large — maximum is {max} MB.', ['max' => 15])) ?>"
               data-err-type="<?= Html::encode(Yii::t('admin', 'File "{name}" is not an image and was skipped.')) ?>">
            <input type="file" id="ph-replace-input" name="replace_photo" accept="image/*">
            <div class="ph-drop-empty">
                <span class="ph-drop-title"><?= Yii::t('admin', 'Drop a new photo here, or click to choose') ?></span>
                <span class="ph-drop-sub"><?= Yii::t('admin', 'Uploading replaces the current image.') ?></span>
            </div>
        </label>

        <p class="ph-hint">
            <?= Yii::t('admin', 'JPG and PNG are accepted (PNG is converted to JPG automatically). Max {mb} MB and {px} px on the longer side.', [
                'mb' => 15,
                'px' => 8000,
            ]) ?>
        </p>
        <p class="ph-error" id="ph-replace-error" role="alert" hidden></p>

        <?php if (count($photos) > 1): ?>
            <h3 style="margin:18px 0 6px;font-size:13px;color:var(--muted)"><?= Yii::t('admin', 'All photos of this work') ?></h3>
            <p style="color:var(--faint);font-size:12px;margin:0 0 8px">
                <?= Yii::t('admin', 'This work has several photos (legacy). Pick the cover and tick any you want to delete.') ?>
            </p>
            <div class="photo-grid">
                <?php foreach ($photos as $photo): ?>
                    <div class="photo-pick <?= (int) $photo->isMain === 1 ? 'sel' : '' ?>">
                        <?= Html::img($baseUrl . '/paintings_photo/thumb_squared/' . \app\helpers\Img::webp($photo->filename)) ?>
                        <label class="photo-cover-pick" title="<?= Yii::t('admin', 'Cover') ?>">
                            <?= Html::radio('cover_photo_id', (int) $photo->isMain === 1, ['value' => $photo->id]) ?>
                            <?= Yii::t('admin', 'Cover') ?>
                        </label>
                        <label class="photo-del-pick" title="<?= Yii::t('admin', 'Delete') ?>">
                            <?= Html::checkbox('delete_photo_ids[]', false, ['value' => $photo->id]) ?>
                            <?= Yii::t('admin', 'Delete') ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($mainPhoto): ?>
            <label class="ph-del-single">
                <?= Html::checkbox('delete_photo_ids[]', false, ['value' => $mainPhoto->id]) ?>
                <?= Yii::t('admin', 'Delete the current photo') ?>
            </label>
        <?php endif; ?>
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
        // Стандартные размеры берём из легко расширяемого файла-конфига
        // (config/canvas_sizes.php) — без таблицы в БД. Из каждого размера
        // строим и альбомный (ширина ≥ высота), и портретный вариант.
        // Значение опции — "ШИРИНАxВЫСОТА" (его ждёт разбор в контроллере),
        // подпись — понятная, например "A4 (29.7 × 21 см)".
        $standardSizes = require Yii::getAlias('@app') . '/config/canvas_sizes.php';
        $fmtNum = function ($n) {
            return rtrim(rtrim(number_format((float) $n, 2, '.', ''), '0'), '.');
        };
        $sizesHorizontal = [];
        $sizesVertical = [];
        foreach ($standardSizes as $sizeName => $dims) {
            $short = $fmtNum($dims[0]);
            $long = $fmtNum($dims[1]);
            // Альбомная ориентация: ширина = длинная сторона.
            $sizesHorizontal[$long . 'x' . $short] = $sizeName . ' (' . $long . ' × ' . $short . ' см)';
            // Портретная ориентация: ширина = короткая сторона.
            $sizesVertical[$short . 'x' . $long] = $sizeName . ' (' . $short . ' × ' . $long . ' см)';
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
        // BS4 build of the picker defaults to FontAwesome icons, which aren't
        // loaded here — supply inline-SVG icons so the calendar/clear glyphs show.
        $calIcon = '<svg class="kv-dp-icon" xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>';
        $clearIcon = '<svg class="kv-dp-icon" xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>';
        echo $form->field($model, 'date')->widget(DatePicker::classname(), [
            'pickerIcon' => $calIcon,
            'removeIcon' => $clearIcon,
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

        $coordHint = $model->isNewRecord
            ? Yii::t('admin', 'Search a city, click the map, or let it fill from the photo / your device.')
            : Yii::t('admin', 'Search a city or click the map to set the location.');
        ?>
        <div class="field field-paintings-coordinates">
            <label><?= Yii::t('admin', 'Location') ?></label>
            <div class="mappick">
                <input type="text" class="mappick-search" placeholder="<?= Yii::t('admin', 'Search a city or address…') ?>" autocomplete="off">
                <div class="mappick-map" id="paintings-map"></div>
                <?= Html::activeHiddenInput($model, 'coordinates', ['id' => 'paintings-coordinates']) ?>
                <div class="mappick-bar">
                    <button type="button" class="btn ghost sm mappick-clear"><?= Yii::t('admin', 'Clear location') ?></button>
                    <span class="mappick-status"></span>
                </div>
            </div>
            <div class="hint"><?= Html::encode($coordHint) ?></div>
        </div>
        <?php
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

<?php
// Location picker: Leaflet + OpenStreetMap (no API key). Search is via the
// free Nominatim geocoder. Loaded on both create and edit.
$this->registerCssFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.css');
$this->registerJsFile('https://unpkg.com/leaflet@1.9.4/dist/leaflet.js', ['position' => \yii\web\View::POS_HEAD]);
$this->registerJsFile('@web/js/map-picker.js', ['position' => \yii\web\View::POS_END]);
?>

<?php if ($model->isNewRecord): ?>
    <?php // exif-js reads the photo's GPS client-side so the marker can be pre-placed. ?>
    <?php $this->registerJsFile('https://cdnjs.cloudflare.com/ajax/libs/exif-js/2.3.0/exif.min.js', ['position' => \yii\web\View::POS_END]); ?>
    <?php $this->registerJsFile('@web/js/painting-form.js', ['position' => \yii\web\View::POS_END]); ?>
<?php else: ?>
    <?php
    // Edit-form replace preview: when a new file is picked, show it next to the
    // current image so the author sees what it will be replaced with.
    $this->registerJs(<<<'JS'
(function () {
  // Stop the browser from opening an image that's dropped anywhere on the page.
  window.addEventListener('dragover', function (e) { e.preventDefault(); });
  window.addEventListener('drop', function (e) {
    // Only swallow real file drops; let normal interactions through otherwise.
    if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
      e.preventDefault();
      if (drop) handleFiles(e.dataTransfer.files);
    }
  });

  var input = document.getElementById('ph-replace-input');
  if (!input) return;
  var drop = document.getElementById('ph-replace-drop');
  var nextWrap = document.getElementById('ph-replace-next');
  var nextImg = document.getElementById('ph-next-img');
  var arrow = document.getElementById('ph-replace-arrow');
  var errorEl = document.getElementById('ph-replace-error');
  var ds = (drop && drop.dataset) ? drop.dataset : {};
  var maxMb = parseFloat(ds.maxMb) || 15;
  var maxBytes = maxMb * 1024 * 1024;
  var msgLarge = ds.errLarge || 'Image "{name}" is too large — maximum is {max} MB.';
  var msgType = ds.errType || 'File "{name}" is not an image and was skipped.';
  var url = null;

  function showError(msg) {
    if (!errorEl) return;
    if (!msg) { errorEl.hidden = true; errorEl.textContent = ''; return; }
    errorEl.textContent = msg;
    errorEl.hidden = false;
  }

  function preview(file) {
    if (url) { URL.revokeObjectURL(url); url = null; }
    if (!file) { nextWrap.hidden = true; arrow.hidden = true; return; }
    url = URL.createObjectURL(file);
    nextImg.src = url;
    nextWrap.hidden = false;
    arrow.hidden = false;
  }

  // Validate a dropped/picked file, then push it into the real input so it
  // submits with the form (and show the side-by-side preview).
  function handleFiles(fileList) {
    var file = fileList && fileList[0];
    if (!file) return;
    if (!file.type || file.type.indexOf('image/') !== 0) {
      showError(msgType.replace('{name}', file.name || '?'));
      return;
    }
    if (file.size > maxBytes) {
      showError(msgLarge.replace('{name}', file.name || '?').replace('{max}', maxMb));
      return;
    }
    showError('');
    if (typeof DataTransfer !== 'undefined') {
      var dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;
    }
    preview(file);
  }

  input.addEventListener('change', function () {
    var file = input.files && input.files[0];
    // Re-validate native picks too, so oversize files get a clear message.
    if (file && (file.type.indexOf('image/') !== 0 || file.size > maxBytes)) {
      handleFiles(input.files);
      // handleFiles already showed the error; clear the bad pick.
      if (typeof DataTransfer !== 'undefined') { input.value = ''; preview(null); }
      return;
    }
    showError('');
    preview(file);
  });

  if (drop) {
    ['dragenter', 'dragover'].forEach(function (ev) {
      drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('drag'); });
    });
    ['dragleave', 'dragend'].forEach(function (ev) {
      drop.addEventListener(ev, function () { drop.classList.remove('drag'); });
    });
    drop.addEventListener('drop', function (e) {
      e.preventDefault();
      e.stopPropagation();
      drop.classList.remove('drag');
      if (e.dataTransfer && e.dataTransfer.files && e.dataTransfer.files.length) {
        handleFiles(e.dataTransfer.files);
      }
    });
  }
})();
JS
    );
    ?>
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
