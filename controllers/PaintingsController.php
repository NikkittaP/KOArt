<?php

namespace app\controllers;

use app\models\ArtGenres;
use app\models\ArtGenresToPainting;
use app\models\ArtStyles;
use app\models\ArtStylesToPainting;
use app\models\AuthorComments;
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

class PaintingsController extends Controller
{
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

    public function actionIndex()
    {
        $searchModel = new PaintingsSearch();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);
        $dataProvider->sort = false;

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

    public function actionStats()
    {
        $sizesModelHorizontal = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` >= `height`'))->orderBy('width ASC, height ASC')->all();
        $sizesHorizontal = [];
        foreach ($sizesModelHorizontal as $sizeModelHorizontal) {
            $key = $sizeModelHorizontal->width.'x'.$sizeModelHorizontal->height;
            if (!array_key_exists($key, $sizesHorizontal))
                $sizesHorizontal[$key] = 1;
            else 
                $sizesHorizontal[$key]++;
        }
    
        $sizesModelVertical = Paintings::find()->select(['width', 'height'])->where(new \yii\db\Expression('`width` < `height`'))->orderBy('width ASC, height ASC')->all();
        $sizesVertical = [];
        foreach ($sizesModelVertical as $sizeModelVertical) {
            $key = $sizeModelVertical->width.'x'.$sizeModelVertical->height;
            if (!array_key_exists($key, $sizesVertical))
                $sizesVertical[$key] = 1;
            else 
                $sizesVertical[$key]++;
        }

        $sizesHorizontalGroups = [];
        foreach ($sizesHorizontal as $key=>$value) {
            $width = explode('x', $key)[0];
            for($i=0; $i<1000; $i=$i+10)
            {
                if ($width >= $i && $width < $i+10)
                {
                    $groupKey = $i.'-'.($i+10);
                    if (!array_key_exists($groupKey, $sizesHorizontalGroups))
                        $sizesHorizontalGroups[$groupKey] = 0;

                    $sizesHorizontalGroups[$groupKey] += $value;

                    break;
                }
            }
        }

        $sizesVerticalGroups = [];
        foreach ($sizesVertical as $key=>$value) {
            $width = explode('x', $key)[0];
            for($i=0; $i<1000; $i=$i+10)
            {
                if ($width >= $i && $width < $i+10)
                {
                    $groupKey = $i.'-'.($i+10);
                    if (!array_key_exists($groupKey, $sizesVerticalGroups))
                        $sizesVerticalGroups[$groupKey] = 0;

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
        return $this->render('show', [
            'painting' => $this->findModel($id),
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
            if ($model->save()) {
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

            if ($model->save()) {
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
