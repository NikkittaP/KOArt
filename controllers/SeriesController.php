<?php

namespace app\controllers;

use app\helpers\RichText;
use app\models\Paintings;
use app\models\PaintingsToSeries;
use app\models\search\SeriesSearch;
use app\models\Series;
use yii\helpers\ArrayHelper;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\data\Pagination;

/**
 * SeriesController implements the CRUD actions for Series model.
 */
class SeriesController extends AdminBaseController
{
    public $adminNav = 'series';

    /** actionShow is the PUBLIC series "blog" page — keep public layout/lang. */
    protected $publicActions = ['show'];

    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'view', 'create', 'update', 'delete', 'move'],
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete', 'move'],
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
                ],
            ],
        ];
    }

    /**
     * Lists all Series models.
     * @return mixed
     */
    public function actionIndex()
    {
        $query = Yii::$app->request->queryParams;

        // Filters come from the URL (GET) so they auto-apply on change (Phase 4b).
        $selectedSection = isset($query['selected_section']) ? (int) $query['selected_section'] : -1;

        $searchModel = new SeriesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams, $selectedSection);
        $dataProvider->sort = false;

        // Global "hide archive" toggle: exclude hidden series when it's on.
        if (\app\helpers\AdminPrefs::hideArchive()) {
            $dataProvider->query->andWhere(['series.isVisible' => 1]);
        }

        $sections = ArrayHelper::map(\app\models\Sections::find()->orderBy('sort ASC')->all(), 'id', 'title');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'sections' => $sections,
            'selectedSection' => $selectedSection,
        ]);
    }

    /**
     * Swaps sort_order with the previous/next series in the same section
     * (mirrors PaintingsController::actionMove).
     */
    public function actionMove($id, $direction, $selected_section = -1)
    {
        $model = $this->findModel($id);

        $siblings = Series::find()
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
     * Displays a single Series model.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Public series "blog" page (Phase 3): back link to its section, title +
     * meta line (year range · technique · N works), description, then
     * full-width images interleaved with per-work description text.
     */
    public function actionShow($id)
    {
        $series = Series::find()->where(['id' => $id])->one();

        if ($series === null)
            throw new NotFoundHttpException('The requested page does not exist.');

        if ((int) $series->isVisible === 0)
            throw new NotFoundHttpException('The requested page does not exist.');

        $paintings = Paintings::find()
            ->joinWith('paintingsToSeries')
            ->where(['paintings_to_series.series_id' => $id, 'paintings.isVisible' => 1])
            ->orderBy(['paintings.sort_order' => SORT_ASC, 'paintings.id' => SORT_ASC])
            ->all();

        $this->layout = '@app/views/layouts/public';
        if ($series->section) {
            $this->view->params['activeNav'] = $series->section->slug;
        }
        // Logged-in owner: "Edit" jumps straight to this series in admin.
        $this->view->params['adminEditUrl'] = ['/series/update', 'id' => $series->id];

        return $this->render('show', [
            'series' => $series,
            'paintings' => $paintings,
        ]);
    }

    /**
     * Creates a new Series model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Series();

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->description = RichText::purify($model->description);
            if ($model->hasAttribute('description_en')) {
                $model->description_en = RichText::purify($model->description_en);
            }
            $model->uploadedCover = UploadedFile::getInstance($model, 'cover_filename');
            if ($model->uploadedCover !== null) {
                if ($model->uploadCover()) {
                    if ($model->save()) {
                        return $this->redirect(['series/index']);
                    }
                }
            } else {
                if ($model->save()) {
                    return $this->redirect(['series/index']);
                }
            }
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Series model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);
        $savedCoverName = $model->cover_filename;

        if (Yii::$app->request->isPost) {
            $model->load(Yii::$app->request->post());
            $model->description = RichText::purify($model->description);
            if ($model->hasAttribute('description_en')) {
                $model->description_en = RichText::purify($model->description_en);
            }
            $model->uploadedCover = UploadedFile::getInstance($model, 'cover_filename');
            if ($model->uploadedCover !== null) {
                if ($model->uploadCover()) {
                    if ($model->save()) {
                        $this->cascadeHideWorks($model);
                        return $this->redirect(['series/index']);
                    }
                }
            } else {
                $model->cover_filename = $savedCoverName;
                if ($model->save()) {
                    $this->cascadeHideWorks($model);
                    return $this->redirect(['series/index']);
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * When a series is archived (hidden), also archive every work in it that is
     * not still shown through some other visible series. Works that appear in
     * another visible series stay published.
     */
    private function cascadeHideWorks(Series $model)
    {
        if ((int) $model->isVisible !== 0) {
            return; // only cascades when the series itself is hidden
        }

        $paintingIds = ArrayHelper::getColumn(
            PaintingsToSeries::find()->where(['series_id' => $model->id])->all(),
            'painting_id'
        );

        foreach (array_unique($paintingIds) as $pid) {
            $inOtherVisibleSeries = PaintingsToSeries::find()
                ->alias('p2s')
                ->innerJoin(['s' => Series::tableName()], 's.id = p2s.series_id')
                ->where(['p2s.painting_id' => $pid, 's.isVisible' => 1])
                ->andWhere(['<>', 'p2s.series_id', $model->id])
                ->exists();

            if (!$inOtherVisibleSeries) {
                Paintings::updateAll(['isVisible' => 0], ['id' => $pid]);
            }
        }
    }

    /**
     * Deletes an existing Series model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Series model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Series the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Series::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
