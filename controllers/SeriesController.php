<?php

namespace app\controllers;

use app\models\Paintings;
use app\models\search\SeriesSearch;
use app\models\Series;

use Yii;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;
use yii\data\Pagination;

/**
 * SeriesController implements the CRUD actions for Series model.
 */
class SeriesController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['index', 'view', 'create', 'update', 'delete'],
                'rules' => [
                    [
                        'actions' => ['index', 'view', 'create', 'update', 'delete'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
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
        $searchModel = new SeriesSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
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
            $model->uploadedCover = UploadedFile::getInstance($model, 'cover_filename');
            if ($model->uploadedCover !== null) {
                if ($model->uploadCover()) {
                    if ($model->save()) {
                        return $this->redirect(['series/index']);
                    }
                }
            } else {
                $model->cover_filename = $savedCoverName;
                if ($model->save()) {
                    return $this->redirect(['series/index']);
                }
            }
        }

        return $this->render('update', [
            'model' => $model,
        ]);
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
/*
    public function actionSet($painting_id)
    {
        $paintingModel = Paintings::find()->where(['id' => $painting_id])->one();
        $series = Series::find()->where(['painting_id' => $painting_id])->all();

        if (count($series) == 1) {
            $seriesModel = $series[0];
        } else if (count($series) > 1) {
            Yii::$app->session->setFlash('warning', "Добавлено несколько серий к картине. Такой функционал пока не реализован.");

            $seriesModel = $series[0];
        }
        
        $series = Series::findAll();

        if (isset($_POST['Photos'])) {
            $id = $_POST['Photos']['isMain'];
            foreach ($photos as $photo) {
                if ($photo->id == $id) {
                    $photo->isMain = 1;
                } else {
                    $photo->isMain = 0;
                }

                $photo->save();
            }

            return $this->redirect(['paintings/index']);
        }

        return $this->render('set', [
            'paintingModel' => $paintingModel,
            'series' => $series,
            'seriesModel' => $seriesModel,
        ]);
    }
*/

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
