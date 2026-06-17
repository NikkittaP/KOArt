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

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'stats', 'view', 'create', 'update', 'delete', 'move', 'bulk-visibility', 'bulk-section'],
                'rules' => [
                    [
                        'actions' => ['index', 'stats', 'view', 'create', 'update', 'delete', 'move', 'bulk-visibility', 'bulk-section'],
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
            $materials = ArrayHelper::map(Materials::find()->all(), 'id', 'name');
            $i = 0;
            foreach ($painting->materialsToPaintings as $material) {
                if ($i != 0) {
                    $materialsLabel .= ', ' . mb_convert_case($materials[$material->material_id], MB_CASE_LOWER, "UTF-8");
                } else {
                    $materialsLabel .= mb_convert_case($materials[$material->material_id], MB_CASE_LOWER, "UTF-8");
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
            if ($model->date != '' && strlen($model->date) == 7) {
                $model->date .= '-00';
            }
            $model->description = RichText::purify($model->description);
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

                // Координаты
                if ($model->coordinates != null) {
                    $model->latitude = explode('@', $model->coordinates)[0];
                    $model->longitude = explode('@', $model->coordinates)[1];
                }

                // Комментарии автора
                $authorComments = new AuthorComments();
                $authorComments->painting_id = $model->id;
                $authorComments->comments = $model->authorComments_comments;
                $authorComments->material_costs = $model->authorComments_material_costs;
                $authorComments->time_costs = $model->authorComments_time_costs;
                $authorComments->save();

                $model->save();

                return $this->redirect(['photos/add', 'painting_id' => $model->id]);
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post())) {
            if ($model->date != '' && strlen($model->date) == 7) {
                $model->date .= '-00';
            }
            $model->description = RichText::purify($model->description);

            if ($model->save()) {
                // Серия для картины
                if (!empty($model->seriesName)) {
                    $selectedIDs = [];
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
                $priceOld = Prices::find()->where(['painting_id' => $model->id])->orderBy(['datetime_add' => SORT_DESC])->one()->value;
                if ($priceOld != $model->price) {
                    $price = new Prices();
                    $price->painting_id = $model->id;
                    $price->value = $model->price;
                    $price->save();
                }

                // Координаты
                if ($model->coordinates != null) {
                    $model->latitude = explode('@', $model->coordinates)[0];
                    $model->longitude = explode('@', $model->coordinates)[1];
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

                //return $this->redirect(['update', 'id' => $model->id]);
                return $this->redirect(['paintings/index']);
            }
        }

        $paintingsToSeries = PaintingsToSeries::find()->where(['painting_id' => $id])->all();
        $model->seriesName = [];
        foreach ($paintingsToSeries as $paintingsToSeries_) {
            $model->seriesName[] = $paintingsToSeries_->series_id;
        }

        $model->price = Prices::find()->where(['painting_id' => $id])->orderBy(['datetime_add' => SORT_DESC])->one()->value;

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

    protected function findModel($id)
    {
        if (($model = Paintings::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
