<?php

namespace app\controllers;

use app\models\ArtGenres;
use app\models\ArtGenresToPainting;
use app\models\ArtStyles;
use app\models\ArtStylesToPainting;
use app\models\Grounds;
use app\models\Materials;
use app\models\MaterialsToPainting;
use app\models\Paintings;
use app\models\Prices;
use app\models\search\PaintingsSearch;
use Yii;
use yii\filters\VerbFilter;
use yii\helpers\ArrayHelper;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

/**
 * PaintingsController implements the CRUD actions for Paintings model.
 */
class PaintingsController extends Controller
{
    /**
     * {@inheritdoc}
     */
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['POST'],
                ],
            ],
        ];
    }

    /**
     * Lists all Paintings models.
     * @return mixed
     */
    public function actionIndex()
    {
        $searchModel = new PaintingsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        $artStyles = ArrayHelper::map(ArtStyles::find()->all(), 'id', 'name');
        $artGenres = ArrayHelper::map(ArtGenres::find()->all(), 'id', 'name');
        $materials = ArrayHelper::map(Materials::find()->all(), 'id', 'name');

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
            'artStyles' => $artStyles,
            'artGenres' => $artGenres,
            'materials' => $materials,
        ]);
    }

    /**
     * Displays a single Paintings model.
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
     * Creates a new Paintings model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Paintings();

        //VarDumper::dump( Yii::$app->request->post(), $depth = 10, $highlight = true);
        //exit();
        if ($model->load(Yii::$app->request->post()) && $model->save()) {

            // Основа картины
            $groundModel = Grounds::find()->where(['name' => $model->groundName])->one();
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

            $model->save();

            return $this->redirect(['photos/add', 'painting_id' => $model->id]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Updates an existing Paintings model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     * @throws NotFoundHttpException if the model cannot be found
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->id]);
        }

        return $this->render('update', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes an existing Paintings model.
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
     * Finds the Paintings model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Paintings the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Paintings::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
