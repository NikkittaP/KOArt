<?php

namespace app\controllers;

use app\helpers\RichText;
use app\models\ArtGenres;
use app\models\ArtGenresToPainting;
use app\models\ArtStyles;
use app\models\ArtStylesToPainting;
use app\models\AuthorComments;
use app\models\Grounds;
use app\models\Materials;
use app\models\MaterialsToPainting;
use app\models\Paintings;
use app\models\PaintingsToSeries;
use app\models\Prices;
use app\models\Sections;
use app\models\search\PaintingsSearch;
use app\models\Series;
use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\NotFoundHttpException;

class PaintingsController extends AdminBaseController
{
    public $adminNav = 'works';

    /** actionWork is the PUBLIC single-work page — keep public layout/lang. */
    protected $publicActions = ['work'];

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'stats', 'view', 'create', 'update', 'delete', 'move', 'bulk-visibility', 'bulk-section', 'bulk-status'],
                'rules' => [
                    [
                        'actions' => ['index', 'stats', 'view', 'create', 'update', 'delete', 'move', 'bulk-visibility', 'bulk-section', 'bulk-status'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                    'move' => ['POST'],
                    'bulk-visibility' => ['POST'],
                    'bulk-section' => ['POST'],
                    'bulk-status' => ['POST'],
                ],
            ],
        ];
    }

    public function actionIndex()
    {
        // Redesigned list (Phase 4b): filters come from the URL (GET) so they
        // auto-apply on change and compose with "Load more"; no POST step.
        $query = Yii::$app->request->queryParams;

        $selectedSeries = isset($query['selected_series']) ? (int) $query['selected_series'] : -1;
        $selectedSection = isset($query['selected_section']) ? (int) $query['selected_section'] : -1;
        $vis = isset($query['vis']) && in_array($query['vis'], ['1', '0'], true) ? $query['vis'] : 'all';
        $status = isset($query['status']) && in_array($query['status'], ['1', '2', '3'], true) ? $query['status'] : 'all';
        $show = isset($query['show']) ? max(24, (int) $query['show']) : 24;

        $searchModel = new PaintingsSearch();
        $dataProvider = $searchModel->search($query, $selectedSeries, $selectedSection);
        $dataProvider->sort = false;

        // Global "hide archive" toggle: exclude hidden works when it's on.
        if (\app\helpers\AdminPrefs::hideArchive()) {
            $dataProvider->query->andWhere(['paintings.isVisible' => 1]);
        }
        // Explicit visibility filter from the toolbar (overrides nothing else).
        if ($vis === '1') {
            $dataProvider->query->andWhere(['paintings.isVisible' => 1]);
        } elseif ($vis === '0') {
            $dataProvider->query->andWhere(['paintings.isVisible' => 0]);
        }
        // Sale/availability status filter (guarded: column may not exist pre-migration).
        if ($status !== 'all' && (new Paintings())->hasAttribute('status')) {
            $dataProvider->query->andWhere(['paintings.status' => (int) $status]);
        }

        // Eager-load the relations rendered per row (thumb, series, notes column).
        $dataProvider->query->with(['mainPhoto', 'paintingsToSeries', 'authorComments']);

        $totalCount = (int) $dataProvider->totalCount;
        $dataProvider->pagination = ['pageSize' => $show, 'page' => 0];

        $materials = ArrayHelper::map(Materials::find()->all(), 'id', 'name');
        $series = ArrayHelper::map(Series::find()->all(), 'id', 'name');
        $sections = ArrayHelper::map(Sections::find()->orderBy('sort ASC')->all(), 'id', 'title');

        return $this->render('index', [
            'dataProvider' => $dataProvider,
            'series' => $series,
            'sections' => $sections,
            'materials' => $materials,
            'selectedSeries' => $selectedSeries,
            'selectedSection' => $selectedSection,
            'vis' => $vis,
            'status' => $status,
            'show' => $show,
            'totalCount' => $totalCount,
            'hasMore' => $totalCount > $show,
        ]);
    }

    /**
     * Swaps sort_order with the previous/next work in the same section so
     * the owner can reorder works without touching the DB. Only meaningful
     * when the grid is filtered to one section (sort_order is otherwise
     * not used for display).
     */
    public function actionMove($id, $direction, $selected_section = -1)
    {
        $model = $this->findModel($id);

        $siblings = Paintings::find()
            ->where(['section_id' => $model->section_id])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        $ids = ArrayHelper::getColumn($siblings, 'id');
        $pos = array_search((int) $id, $ids, true);

        $swapWith = null;
        if ($direction === 'up' && $pos !== false && $pos > 0) {
            $swapWith = $siblings[$pos - 1];
        } elseif ($direction === 'down' && $pos !== false && $pos < count($siblings) - 1) {
            $swapWith = $siblings[$pos + 1];
        }

        if ($swapWith !== null) {
            $a = $model->sort_order;
            $b = $swapWith->sort_order;
            // Equal/zero sort_order values are common on never-reordered
            // rows; fall back to swapping by position so the first click
            // always does something visible.
            if ($a === $b) {
                $model->sort_order = $pos + ($direction === 'up' ? -1 : 1);
                $swapWith->sort_order = $pos;
            } else {
                $model->sort_order = $b;
                $swapWith->sort_order = $a;
            }
            $model->save(false, ['sort_order']);
            $swapWith->save(false, ['sort_order']);
        }

        return $this->redirect(['index', 'selected_section' => $selected_section]);
    }

    /**
     * Bulk action: toggle isVisible for the selected works.
     */
    public function actionBulkVisibility()
    {
        $ids = (array) Yii::$app->request->post('ids', []);
        $visible = (int) Yii::$app->request->post('visible', 0);
        $selectedSection = Yii::$app->request->post('selected_section', -1);

        if (!empty($ids)) {
            Paintings::updateAll(['isVisible' => $visible], ['id' => $ids]);
            Yii::$app->session->setFlash('success', Yii::t('admin', '{n} work(s) updated.', ['n' => count($ids)]));
        }

        return $this->redirect(['index', 'selected_section' => $selectedSection]);
    }

    /**
     * Bulk action: move the selected works to a different section. New
     * works are appended to the end (max sort_order + 1 in the target
     * section) so they don't jump ahead of existing ones.
     */
    public function actionBulkSection()
    {
        $ids = (array) Yii::$app->request->post('ids', []);
        $sectionId = Yii::$app->request->post('section_id');

        if (!empty($ids) && $sectionId !== null && $sectionId !== '') {
            $maxSort = (int) Paintings::find()->where(['section_id' => $sectionId])->max('sort_order');
            foreach (Paintings::findAll($ids) as $painting) {
                $maxSort++;
                $painting->section_id = $sectionId;
                $painting->sort_order = $maxSort;
                $painting->save(false, ['section_id', 'sort_order']);
            }
            Yii::$app->session->setFlash('success', Yii::t('admin', '{n} work(s) moved to another section.', ['n' => count($ids)]));
        }

        return $this->redirect(['index', 'selected_section' => $sectionId ?: -1]);
    }

    /**
     * Bulk action: set the sale/availability status on the selected works.
     */
    public function actionBulkStatus()
    {
        $ids = (array) Yii::$app->request->post('ids', []);
        $status = Yii::$app->request->post('status');
        $selectedSection = Yii::$app->request->post('selected_section', -1);

        if (!empty($ids) && in_array((int) $status, array_keys(Paintings::statuses()), true)) {
            Paintings::updateAll(['status' => (int) $status], ['id' => $ids]);
            Yii::$app->session->setFlash('success', Yii::t('admin', '{n} work(s) status updated.', ['n' => count($ids)]));
        }

        return $this->redirect(['index', 'selected_section' => $selectedSection]);
    }

    public function actionStats()
    {
        $sizesModelHorizontal = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` >= `height`'))->orderBy('width ASC, height ASC')->all();
        $sizesHorizontal = [];
        foreach ($sizesModelHorizontal as $sizeModelHorizontal) {
            $key = $sizeModelHorizontal->width . 'x' . $sizeModelHorizontal->height;
            if (!array_key_exists($key, $sizesHorizontal)) {
                $sizesHorizontal[$key] = 1;
            } else {
                $sizesHorizontal[$key]++;
            }

        }

        $sizesModelVertical = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` < `height`'))->orderBy('width ASC, height ASC')->all();
        $sizesVertical = [];
        foreach ($sizesModelVertical as $sizeModelVertical) {
            $key = $sizeModelVertical->width . 'x' . $sizeModelVertical->height;
            if (!array_key_exists($key, $sizesVertical)) {
                $sizesVertical[$key] = 1;
            } else {
                $sizesVertical[$key]++;
            }

        }

        $sizesHorizontalGroups = [];
        foreach ($sizesHorizontal as $key => $value) {
            $width = explode('x', $key)[0];
            for ($i = 0; $i < 1000; $i = $i + 10) {
                if ($width >= $i && $width < $i + 10) {
                    $groupKey = $i . '-' . ($i + 10);
                    if (!array_key_exists($groupKey, $sizesHorizontalGroups)) {
                        $sizesHorizontalGroups[$groupKey] = 0;
                    }

                    $sizesHorizontalGroups[$groupKey] += $value;

                    break;
                }
            }
        }

        $sizesVerticalGroups = [];
        foreach ($sizesVertical as $key => $value) {
            $width = explode('x', $key)[0];
            for ($i = 0; $i < 1000; $i = $i + 10) {
                if ($width >= $i && $width < $i + 10) {
                    $groupKey = $i . '-' . ($i + 10);
                    if (!array_key_exists($groupKey, $sizesVerticalGroups)) {
                        $sizesVerticalGroups[$groupKey] = 0;
                    }

                    $sizesVerticalGroups[$groupKey] += $value;

                    break;
                }
            }
        }

        return $this->render('stats', [
            'sizesHorizontal' => $sizesHorizontal,
            'sizesHorizontalGroups' => $sizesHorizontalGroups,
            'sizesVertical' => $sizesVertical,
            'sizesVerticalGroups' => $sizesVerticalGroups,
        ]);
    }

    /**
     * PUBLIC single-work page (public layout). This is where long, rich-text
     * descriptions are read — the lightbox only carries a short caption and
     * links here via "Read more". Mirrors SeriesController::actionShow.
     */
    public function actionWork($id)
    {
        $painting = Paintings::find()->where(['id' => $id])->one();

        if ($painting === null || (int) $painting->isVisible === 0) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        // Back target: the work's series (if it belongs to a visible one),
        // otherwise its own section page.
        $series = Series::find()
            ->joinWith('paintingsToSeries')
            ->where(['paintings_to_series.painting_id' => $painting->id])
            ->andWhere(['series.isVisible' => 1])
            ->one();
        $section = $painting->section;

        $this->layout = '@app/views/layouts/public';
        if ($section) {
            $this->view->params['activeNav'] = $section->slug;
        } elseif ($series && $series->section) {
            $this->view->params['activeNav'] = $series->section->slug;
        }
        // Logged-in owner: "Edit" jumps straight to this work in admin.
        $this->view->params['adminEditUrl'] = ['/paintings/update', 'id' => $painting->id];

        return $this->render('work', [
            'painting' => $painting,
            'series' => $series,
            'section' => $section,
        ]);
    }

    public function actionShow($id)
    {
        $series = Series::find()->joinWith('paintingsToSeries')->where(['painting_id' => $id])->one();
        $painting = $this->findModel($id);

        if ($painting->isVisible === 0)
            throw new NotFoundHttpException('The requested page does not exist.');

        if ($series === null)
            throw new NotFoundHttpException('The requested page does not exist.');

        if ($series->isVisible === 0)
            throw new NotFoundHttpException('The requested page does not exist.');

        $sizeLabel = '';
        if (is_numeric($painting->width) && is_numeric($painting->height)) {
            $sizeLabel = $painting->width . \Yii::t('app', 'х') . $painting->height . \Yii::t('app', 'см');
        }

        $dateLabel = '';
        if ($painting->date !== null) {
            $str = substr($painting->date, 0, 7);
            $year = explode('-', $str)[0];
            $month = explode('-', $str)[1];
            if ($month == '01') {
                $month = \Yii::t('app', 'Январь');
            }

            if ($month == '02') {
                $month = \Yii::t('app', 'Феварль');
            }

            if ($month == '03') {
                $month = \Yii::t('app', 'Март');
            }

            if ($month == '04') {
                $month = \Yii::t('app', 'Апрель');
            }

            if ($month == '05') {
                $month = \Yii::t('app', 'Май');
            }

            if ($month == '06') {
                $month = \Yii::t('app', 'Июнь');
            }

            if ($month == '07') {
                $month = \Yii::t('app', 'Июль');
            }

            if ($month == '08') {
                $month = \Yii::t('app', 'Август');
            }

            if ($month == '09') {
                $month = \Yii::t('app', 'Сентябрь');
            }

            if ($month == '10') {
                $month = \Yii::t('app', 'Октябрь');
            }

            if ($month == '11') {
                $month = \Yii::t('app', 'Ноябрь');
            }

            if ($month == '12') {
                $month = \Yii::t('app', 'Декабрь');
            }

            $dateLabel = $month . ' ' . $year;
        }

        $materialsLabel = '';
        if (count($painting->materialsToPaintings) != 0) {
            $materials = ArrayHelper::index(Materials::find()->all(), 'id');
            $i = 0;
            foreach ($painting->materialsToPaintings as $material) {
                $name = isset($materials[$material->material_id]) ? $materials[$material->material_id]->tr('name') : '';
                if ($i != 0) {
                    $materialsLabel .= ', ' . mb_convert_case($name, MB_CASE_LOWER, "UTF-8");
                } else {
                    $materialsLabel .= mb_convert_case($name, MB_CASE_LOWER, "UTF-8");
                }
                $i++;
            }
        }

        return $this->render('show', [
            'painting' => $painting,
            'series' => $series,
            'sizeLabel' => $sizeLabel,
            'dateLabel' => $dateLabel,
            'materialsLabel' => $materialsLabel,
        ]);
    }

    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    public function actionCreate()
    {
        $model = new Paintings();

        if ($model->load(Yii::$app->request->post())) {
            // Inline photos uploaded from the cover-first "Add work" form.
            $uploadedFiles = $this->collectUploadedPhotos();
            $coverIndex = (int) Yii::$app->request->post('cover_index', 0);

            // Pre-fill blank Date / Location from the cover photo's EXIF.
            if (!empty($uploadedFiles)) {
                $coverFile = isset($uploadedFiles[$coverIndex]) ? $uploadedFiles[$coverIndex] : reset($uploadedFiles);
                $meta = \app\helpers\Img::exifMeta($coverFile['tmp']);
                if (($model->date === null || $model->date === '') && !empty($meta['date'])) {
                    $model->date = $meta['date'];
                }
                if (($model->latitude === null || $model->latitude === '') && $meta['lat'] !== null) {
                    $model->latitude = $meta['lat'];
                    $model->longitude = $meta['lng'];
                }
            }

            // Fall back to the device location captured client-side (if still blank).
            if ($model->latitude === null || $model->latitude === '') {
                $dev = (string) Yii::$app->request->post('device_coords', '');
                if (strpos($dev, '@') !== false) {
                    list($dlat, $dlng) = array_pad(explode('@', $dev, 2), 2, '');
                    if (is_numeric($dlat) && is_numeric($dlng)) {
                        $model->latitude = (float) $dlat;
                        $model->longitude = (float) $dlng;
                    }
                }
            }

            // Title is optional — synthesise one from genre + month when left blank.
            if (trim((string) $model->name) === '') {
                $model->name = Paintings::suggestName($model->artGenreName, $model->date);
            }

            // Section is set by the series for works that have one (a work can
            // appear in several sections via several series), so a work with
            // any series has no direct section_id. Only loose works use it.
            if (!empty($model->seriesName)) {
                $model->section_id = null;
            }

            if ($model->date != '' && strlen($model->date) == 7) {
                // Store month-only dates as the 1st (valid date; matches the
                // legacy-date migration). The day is ignored when displaying.
                $model->date .= '-01';
            }
            $model->description = RichText::purify($model->description);
            if ($model->hasAttribute('description_en')) {
                $model->description_en = RichText::purify($model->description_en);
            }
            if ($model->save()) {
                // Серия для картины
                if (!empty($model->seriesName)) {
                    foreach ($model->seriesName as $key => $serName) {
                        if (is_numeric($serName)) {
                            $seriesModel = Series::find()->where(['id' => $serName])->one();
                        } else {
                            $seriesModel = Series::find()->where(['name' => $serName])->one();
                        }

                        if ($seriesModel == null) {
                            $seriesModel = new Series();
                            $seriesModel->name = $serName;
                            $seriesModel->save();
                        }

                        $paintingsToSeriesModel = new PaintingsToSeries();
                        $paintingsToSeriesModel->painting_id = $model->id;
                        $paintingsToSeriesModel->series_id = $seriesModel->id;
                        $paintingsToSeriesModel->save();
                    }
                }

                // Размеры картины
                if ($model->size_horizontal != '') {
                    $model->width = explode('x', $model->size_horizontal)[0];
                    $model->height = explode('x', $model->size_horizontal)[1];
                } else if ($model->size_vertical != '') {
                    $model->width = explode('x', $model->size_vertical)[0];
                    $model->height = explode('x', $model->size_vertical)[1];
                } else {
                    if (is_numeric($model->width) && is_numeric($model->height)) {
                    } else {
                        $model->width = null;
                        $model->height = null;
                    }
                }

                // Основа картины
                if (is_numeric($model->groundName)) {
                    $groundModel = Grounds::find()->where(['id' => $model->groundName])->one();
                } else {
                    $groundModel = Grounds::find()->where(['name' => $model->groundName])->one();
                }
                if ($groundModel === null) {
                    $groundModel = new Grounds();
                    $groundModel->name = $model->groundName;
                    $groundModel->save();
                }
                $model->ground_id = $groundModel->id;

                // Жанр картины
                if (!empty($model->artGenreName)) {
                    foreach ($model->artGenreName as $key => $genreName) {
                        if (is_numeric($genreName)) {
                            $artGenreModel = ArtGenres::find()->where(['id' => $genreName])->one();
                        } else {
                            $artGenreModel = ArtGenres::find()->where(['name' => $genreName])->one();
                        }

                        if ($artGenreModel == null) {
                            $artGenreModel = new ArtGenres();
                            $artGenreModel->name = $genreName;
                            $artGenreModel->save();
                        }

                        $artGenresToPaintingModel = new ArtGenresToPainting();
                        $artGenresToPaintingModel->painting_id = $model->id;
                        $artGenresToPaintingModel->art_genre_id = $artGenreModel->id;
                        $artGenresToPaintingModel->save();
                    }
                }

                // Стиль картины
                if (!empty($model->artStyleName)) {
                    foreach ($model->artStyleName as $key => $styleName) {
                        if (is_numeric($styleName)) {
                            $artStyleModel = ArtStyles::find()->where(['id' => $styleName])->one();
                        } else {
                            $artStyleModel = ArtStyles::find()->where(['name' => $styleName])->one();
                        }

                        if ($artStyleModel == null) {
                            $artStyleModel = new ArtStyles();
                            $artStyleModel->name = $styleName;
                            $artStyleModel->save();
                        }

                        $artStylesToPaintingModel = new ArtStylesToPainting();
                        $artStylesToPaintingModel->painting_id = $model->id;
                        $artStylesToPaintingModel->art_style_id = $artStyleModel->id;
                        $artStylesToPaintingModel->save();
                    }
                }

                // Материалы картины
                if (!empty($model->materials)) {
                    foreach ($model->materials as $key => $material) {
                        if (is_numeric($material)) {
                            $materialModel = Materials::find()->where(['id' => $material])->one();
                        } else {
                            $materialModel = Materials::find()->where(['name' => $material])->one();
                        }

                        if ($materialModel == null) {
                            $materialModel = new Materials();
                            $materialModel->name = $material;
                            $materialModel->save();
                        }

                        $materialsToPaintingModel = new MaterialsToPainting();
                        $materialsToPaintingModel->painting_id = $model->id;
                        $materialsToPaintingModel->material_id = $materialModel->id;
                        $materialsToPaintingModel->save();
                    }
                }

                // Стоимость картины
                $price = new Prices();
                $price->painting_id = $model->id;
                $price->value = $model->price;
                $price->save();

                // Координаты — берём из карты только если автор реально выбрал
                // точку (иначе пустой ввод затёр бы координаты из EXIF/устройства).
                if ($model->coordinates != null && strpos((string) $model->coordinates, '@') !== false) {
                    $parts = explode('@', $model->coordinates);
                    if (isset($parts[0], $parts[1]) && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $model->latitude = $parts[0];
                        $model->longitude = $parts[1];
                    }
                }

                // Комментарии автора
                $authorComments = new AuthorComments();
                $authorComments->painting_id = $model->id;
                $authorComments->comments = $model->authorComments_comments;
                $authorComments->material_costs = $model->authorComments_material_costs;
                $authorComments->time_costs = $model->authorComments_time_costs;
                $authorComments->save();

                $model->save();

                // Store the inline-uploaded photos (cover-first flow). The file
                // at $coverIndex becomes the cover; the rest are extras.
                $savedPhotos = 0;
                foreach ($uploadedFiles as $i => $f) {
                    if (\app\helpers\Img::validate($f['tmp'], $f['size']) !== null) {
                        continue;
                    }
                    $info = @getimagesize($f['tmp']);
                    $isJpeg = \app\helpers\Img::isJpeg(isset($info[2]) ? $info[2] : null);
                    try {
                        $filename = \app\helpers\Img::store($f['tmp'], $isJpeg);
                    } catch (\Exception $e) {
                        Yii::error('Inline photo store failed: ' . $e->getMessage(), __METHOD__);
                        continue;
                    }
                    $photo = new \app\models\Photos();
                    $photo->painting_id = $model->id;
                    $photo->filename = $filename;
                    $photo->isMain = ($i === $coverIndex) ? 1 : 0;
                    $photo->save();
                    $savedPhotos++;
                }

                // Guarantee exactly one cover when photos were saved.
                if ($savedPhotos > 0 && (int) \app\models\Photos::find()->where(['painting_id' => $model->id, 'isMain' => 1])->count() === 0) {
                    $first = \app\models\Photos::find()->where(['painting_id' => $model->id])->orderBy(['id' => SORT_ASC])->one();
                    if ($first) {
                        $first->isMain = 1;
                        $first->save();
                    }
                }

                if ($savedPhotos > 0) {
                    Yii::$app->session->setFlash('success', Yii::t('admin', 'Work added.'));
                    return $this->redirect(['index']);
                }

                // No photo uploaded inline — keep the old "add photos" step.
                return $this->redirect(['photos/add', 'painting_id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Read the inline photo uploads from the create form ($_FILES['photos'],
     * a "photos[]" multi-file field). Returns a list keyed by the original
     * pick order so the posted cover_index still lines up after any skips.
     *
     * @return array<int, array{tmp:string, size:int, name:string}>
     */
    private function collectUploadedPhotos()
    {
        $files = [];
        if (!isset($_FILES['photos']) || !is_array($_FILES['photos']['tmp_name'])) {
            return $files;
        }
        $count = count($_FILES['photos']['tmp_name']);
        for ($i = 0; $i < $count; $i++) {
            $tmp = $_FILES['photos']['tmp_name'][$i];
            $err = isset($_FILES['photos']['error'][$i]) ? (int) $_FILES['photos']['error'][$i] : UPLOAD_ERR_NO_FILE;
            if ($tmp === '' || $err !== UPLOAD_ERR_OK || !is_uploaded_file($tmp)) {
                continue;
            }
            $files[$i] = [
                'tmp' => $tmp,
                'size' => (int) $_FILES['photos']['size'][$i],
                'name' => (string) $_FILES['photos']['name'][$i],
            ];
        }
        return $files;
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            // Title stays optional here too — keep a sensible default if cleared.
            if (trim((string) $model->name) === '') {
                $model->name = Paintings::suggestName($model->artGenreName, $model->date);
            }
            // A work with series has no direct section (set by the series).
            if (!empty($model->seriesName)) {
                $model->section_id = null;
            }
            if ($model->date != '' && strlen($model->date) == 7) {
                // Store month-only dates as the 1st (valid date; matches the
                // legacy-date migration). The day is ignored when displaying.
                $model->date .= '-01';
            }
            $model->description = RichText::purify($model->description);
            if ($model->hasAttribute('description_en')) {
                $model->description_en = RichText::purify($model->description_en);
            }

            if ($model->save()) {
                // Серия для картины.
                // Синхронизацию выполняем ВСЕГДА, даже когда серий не выбрано:
                // если поле очистили, $selectedIDs останется пустым и все
                // прежние связи удалятся (раньше блок целиком пропускался при
                // пустом seriesName, из-за чего серия не снималась с картины).
                $selectedIDs = [];
                if (!empty($model->seriesName)) {
                    foreach ($model->seriesName as $key => $seriesName) {
                        if (is_numeric($seriesName)) {
                            $seriesModel = Series::find()->where(['id' => $seriesName])->one();
                        } else {
                            $seriesModel = Series::find()->where(['name' => $seriesName])->one();
                        }

                        if ($seriesModel == null) {
                            $seriesModel = new Series();
                            $seriesModel->name = $seriesName;
                            $seriesModel->save();
                        }

                        $selectedIDs[] = $seriesModel->id;
                    }
                }

                $savedIDs = PaintingsToSeries::find()->where(['painting_id' => $model->id])->all();
                // Удаляем ненужные записи из БД
                foreach ($savedIDs as $savedID) {
                    if (!in_array($savedID->series_id, $selectedIDs)) {
                        $savedID->delete();
                    }

                }
                $savedIDsArray = ArrayHelper::map(PaintingsToSeries::find()->where(['painting_id' => $model->id])->all(), 'id', 'series_id');
                // Добавляем новые записи из БД
                foreach ($selectedIDs as $selectedID) {
                    if (!in_array($selectedID, $savedIDsArray)) {
                        $paintingsToSeriesModel = new PaintingsToSeries();
                        $paintingsToSeriesModel->painting_id = $model->id;
                        $paintingsToSeriesModel->series_id = $selectedID;
                        $paintingsToSeriesModel->save();
                    }
                }

                // Размеры картины
                if ($model->size_horizontal != '') {
                    $model->width = explode('x', $model->size_horizontal)[0];
                    $model->height = explode('x', $model->size_horizontal)[1];
                } else if ($model->size_vertical != '') {
                    $model->width = explode('x', $model->size_vertical)[0];
                    $model->height = explode('x', $model->size_vertical)[1];
                } else {
                    if (is_numeric($model->width) && is_numeric($model->height)) {
                    } else {
                        $model->width = null;
                        $model->height = null;
                    }
                }

                // Основа картины
                if (is_numeric($model->groundName)) {
                    $groundModel = Grounds::find()->where(['id' => $model->groundName])->one();
                } else {
                    $groundModel = Grounds::find()->where(['name' => $model->groundName])->one();
                }
                if ($groundModel === null) {
                    $groundModel = new Grounds();
                    $groundModel->name = $model->groundName;
                    $groundModel->save();
                }
                $model->ground_id = $groundModel->id;

                // Жанр картины
                if (!empty($model->artGenreName)) {
                    $selectedIDs = [];
                    foreach ($model->artGenreName as $key => $genreName) {
                        if (is_numeric($genreName)) {
                            $artGenreModel = ArtGenres::find()->where(['id' => $genreName])->one();
                        } else {
                            $artGenreModel = ArtGenres::find()->where(['name' => $genreName])->one();
                        }

                        if ($artGenreModel == null) {
                            $artGenreModel = new ArtGenres();
                            $artGenreModel->name = $genreName;
                            $artGenreModel->save();
                        }

                        $selectedIDs[] = $artGenreModel->id;
                    }

                    $savedIDs = ArtGenresToPainting::find()->where(['painting_id' => $model->id])->all();
                    // Удаляем ненужные записи из БД
                    foreach ($savedIDs as $savedID) {
                        if (!in_array($savedID->art_genre_id, $selectedIDs)) {
                            $savedID->delete();
                        }

                    }
                    $savedIDsArray = ArrayHelper::map(ArtGenresToPainting::find()->where(['painting_id' => $model->id])->all(), 'id', 'art_genre_id');
                    // Добавляем новые записи из БД
                    foreach ($selectedIDs as $selectedID) {
                        if (!in_array($selectedID, $savedIDsArray)) {
                            $artGenresToPaintingModel = new ArtGenresToPainting();
                            $artGenresToPaintingModel->painting_id = $model->id;
                            $artGenresToPaintingModel->art_genre_id = $selectedID;
                            $artGenresToPaintingModel->save();
                        }
                    }
                }

                // Стиль картины
                if (!empty($model->artStyleName)) {
                    $selectedIDs = [];
                    foreach ($model->artStyleName as $key => $styleName) {
                        if (is_numeric($styleName)) {
                            $artStyleModel = ArtStyles::find()->where(['id' => $styleName])->one();
                        } else {
                            $artStyleModel = ArtStyles::find()->where(['name' => $styleName])->one();
                        }

                        if ($artStyleModel == null) {
                            $artStyleModel = new ArtStyles();
                            $artStyleModel->name = $styleName;
                            $artStyleModel->save();
                        }

                        $selectedIDs[] = $artStyleModel->id;
                    }

                    $savedIDs = ArtStylesToPainting::find()->where(['painting_id' => $model->id])->all();
                    // Удаляем ненужные записи из БД
                    foreach ($savedIDs as $savedID) {
                        if (!in_array($savedID->art_style_id, $selectedIDs)) {
                            $savedID->delete();
                        }

                    }
                    $savedIDsArray = ArrayHelper::map(ArtStylesToPainting::find()->where(['painting_id' => $model->id])->all(), 'id', 'art_style_id');
                    // Добавляем новые записи из БД
                    foreach ($selectedIDs as $selectedID) {
                        if (!in_array($selectedID, $savedIDsArray)) {
                            $artStylesToPaintingModel = new ArtStylesToPainting();
                            $artStylesToPaintingModel->painting_id = $model->id;
                            $artStylesToPaintingModel->art_style_id = $selectedID;
                            $artStylesToPaintingModel->save();
                        }
                    }
                }

                // Материалы картины
                if (!empty($model->materials)) {
                    $selectedIDs = [];
                    foreach ($model->materials as $key => $material) {
                        if (is_numeric($material)) {
                            $materialModel = Materials::find()->where(['id' => $material])->one();
                        } else {
                            $materialModel = Materials::find()->where(['name' => $material])->one();
                        }

                        if ($materialModel == null) {
                            $materialModel = new Materials();
                            $materialModel->name = $material;
                            $materialModel->save();
                        }

                        $selectedIDs[] = $materialModel->id;
                    }

                    $savedIDs = MaterialsToPainting::find()->where(['painting_id' => $model->id])->all();
                    // Удаляем ненужные записи из БД
                    foreach ($savedIDs as $savedID) {
                        if (!in_array($savedID->material_id, $selectedIDs)) {
                            $savedID->delete();
                        }

                    }
                    $savedIDsArray = ArrayHelper::map(MaterialsToPainting::find()->where(['painting_id' => $model->id])->all(), 'id', 'material_id');
                    // Добавляем новые записи из БД
                    foreach ($selectedIDs as $selectedID) {
                        if (!in_array($selectedID, $savedIDsArray)) {
                            $materialsToPaintingModel = new MaterialsToPainting();
                            $materialsToPaintingModel->painting_id = $model->id;
                            $materialsToPaintingModel->material_id = $selectedID;
                            $materialsToPaintingModel->save();
                        }
                    }
                }

                // Стоимость картины
                $priceRow = Prices::find()->where(['painting_id' => $model->id])->orderBy(['datetime_add' => SORT_DESC])->one();
                $priceOld = $priceRow ? $priceRow->value : null;
                if ($priceOld != $model->price) {
                    $price = new Prices();
                    $price->painting_id = $model->id;
                    $price->value = $model->price;
                    $price->save();
                }

                // Координаты — пустое значение очищает точку, а парсим её
                // только если в строке есть валидные "lat@lng".
                $model->latitude = null;
                $model->longitude = null;
                if (strpos((string) $model->coordinates, '@') !== false) {
                    $parts = explode('@', $model->coordinates);
                    if (isset($parts[0], $parts[1]) && is_numeric($parts[0]) && is_numeric($parts[1])) {
                        $model->latitude = (float) $parts[0];
                        $model->longitude = (float) $parts[1];
                    }
                }

                // Комментарии автора
                $authorComments = AuthorComments::find()->where(['painting_id' => $model->id])->one();
                if ($authorComments === null) {
                    $authorComments = new AuthorComments();
                    $authorComments->painting_id = $model->id;
                }
                $authorComments->comments = $model->authorComments_comments;
                $authorComments->material_costs = $model->authorComments_material_costs;
                $authorComments->time_costs = $model->authorComments_time_costs;
                $authorComments->save();

                $model->save();

                // Photos: handle deletions, cover choice and replacement upload
                // from the unified edit form.
                $this->handlePhotoEdits($model);

                return $this->redirect(['paintings/index']);
            }
        }

        $paintingsToSeries = PaintingsToSeries::find()->where(['painting_id' => $id])->all();
        $model->seriesName = [];
        foreach ($paintingsToSeries as $paintingsToSeries_) {
            $model->seriesName[] = $paintingsToSeries_->series_id;
        }

        $lastPrice = Prices::find()->where(['painting_id' => $id])->orderBy(['datetime_add' => SORT_DESC])->one();
        $model->price = $lastPrice ? $lastPrice->value : null;

        $artGenresToPaintings = ArtGenresToPainting::find()->where(['painting_id' => $id])->all();
        $model->artGenreName = [];
        foreach ($artGenresToPaintings as $artGenreToPainting) {
            $model->artGenreName[] = $artGenreToPainting->art_genre_id;
        }

        $artStylesToPaintings = ArtStylesToPainting::find()->where(['painting_id' => $id])->all();
        $model->artStyleName = [];
        foreach ($artStylesToPaintings as $artStyleToPainting) {
            $model->artStyleName[] = $artStyleToPainting->art_style_id;
        }

        $materialsToPaintings = MaterialsToPainting::find()->where(['painting_id' => $id])->all();
        $model->materials = [];
        foreach ($materialsToPaintings as $materialToPainting) {
            $model->materials[] = $materialToPainting->material_id;
        }

        $model->groundName = $model->ground_id;

        $model->coordinates = $model->latitude . '@' . $model->longitude;

        $authorComments = AuthorComments::find()->where(['painting_id' => $id])->one();
        if ($authorComments !== null) {
            $model->authorComments_comments = $authorComments->comments;
            $model->authorComments_material_costs = $authorComments->material_costs;
            $model->authorComments_time_costs = $authorComments->time_costs;
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Remove the stored master + every WebP derivative for a single photo.
     */
    private function deletePhotoFiles(\app\models\Photos $photo)
    {
        $base = Yii::getAlias('@app') . '/web/paintings_photo/';
        $webp = \app\helpers\Img::webp($photo->filename);
        // Original master is JPG; all derivatives are WebP.
        @unlink($base . 'original/' . $photo->filename);
        @unlink($base . 'original_site/' . $webp);
        @unlink($base . 'preview/' . $webp);
        @unlink($base . 'thumb_squared/' . $webp);
        @unlink($base . 'thumb_tiny/' . $webp);
    }

    /**
     * Handle the photo controls on the unified edit form:
     *   - delete the ticked photos (delete_photo_ids[]),
     *   - replace the cover with a freshly uploaded image (replace_photo) —
     *     a work now keeps a single image, so the upload replaces the current
     *     cover,
     *   - otherwise honour an explicit cover choice (cover_photo_id) among the
     *     legacy multi-photo set,
     *   - and always leave exactly one cover when any photo remains.
     */
    private function handlePhotoEdits(Paintings $model)
    {
        $req = Yii::$app->request;

        // 1) Delete the ticked photos.
        $deleteIds = array_filter(array_map('intval', (array) $req->post('delete_photo_ids', [])));
        if (!empty($deleteIds)) {
            $toDelete = \app\models\Photos::find()
                ->where(['painting_id' => $model->id, 'id' => $deleteIds])
                ->all();
            foreach ($toDelete as $photo) {
                $this->deletePhotoFiles($photo);
                $photo->delete();
            }
        }

        // 2) Replacement upload — becomes the single cover image.
        $replaced = false;
        if (isset($_FILES['replace_photo']) && is_string($_FILES['replace_photo']['tmp_name'] ?? null)
            && $_FILES['replace_photo']['tmp_name'] !== ''
            && (int) ($_FILES['replace_photo']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK
            && is_uploaded_file($_FILES['replace_photo']['tmp_name'])) {

            $tmp = $_FILES['replace_photo']['tmp_name'];
            $bytes = (int) $_FILES['replace_photo']['size'];
            $error = \app\helpers\Img::validate($tmp, $bytes);
            if ($error !== null) {
                Yii::$app->session->setFlash('error', $error);
            } else {
                $info = @getimagesize($tmp);
                $isJpeg = \app\helpers\Img::isJpeg(isset($info[2]) ? $info[2] : null);
                try {
                    $filename = \app\helpers\Img::store($tmp, $isJpeg);
                    // Drop every existing photo: one image per work going forward.
                    foreach (\app\models\Photos::find()->where(['painting_id' => $model->id])->all() as $old) {
                        $this->deletePhotoFiles($old);
                        $old->delete();
                    }
                    $photo = new \app\models\Photos();
                    $photo->painting_id = $model->id;
                    $photo->filename = $filename;
                    $photo->isMain = 1;
                    $photo->save();
                    $replaced = true;
                } catch (\Exception $e) {
                    Yii::error('Replace photo store failed: ' . $e->getMessage(), __METHOD__);
                    Yii::$app->session->setFlash('error', Yii::t('admin', 'Could not process the image. Please try a different file.'));
                }
            }
        }

        // 3) Explicit cover choice among the remaining photos (legacy multi-set).
        if (!$replaced) {
            $coverId = (int) $req->post('cover_photo_id', 0);
            if ($coverId > 0) {
                foreach (\app\models\Photos::find()->where(['painting_id' => $model->id])->all() as $p) {
                    $p->isMain = ((int) $p->id === $coverId) ? 1 : 0;
                    $p->save(false, ['isMain']);
                }
            }
        }

        // 4) Guarantee exactly one cover whenever photos remain.
        $remaining = \app\models\Photos::find()
            ->where(['painting_id' => $model->id])
            ->orderBy(['id' => SORT_ASC])
            ->all();
        if (!empty($remaining)) {
            $mains = array_filter($remaining, function ($p) { return (int) $p->isMain === 1; });
            if (count($mains) === 0) {
                $remaining[0]->isMain = 1;
                $remaining[0]->save(false, ['isMain']);
            } elseif (count($mains) > 1) {
                // Collapse to a single cover if duplicates slipped in.
                $kept = false;
                foreach ($remaining as $p) {
                    if ((int) $p->isMain === 1) {
                        if ($kept) { $p->isMain = 0; $p->save(false, ['isMain']); }
                        $kept = true;
                    }
                }
            }
        }
    }

    protected function findModel($id)
    {
        if (($model = Paintings::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
