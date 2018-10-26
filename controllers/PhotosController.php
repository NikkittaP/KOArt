<?php

namespace app\controllers;

use app\models\Photos;
use app\models\Paintings;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;
use yii\imagine\Image;
use Imagine\Image\Box;

class PhotosController extends Controller
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

    public function actionAdd($painting_id)
    {
        $paintingModel = Paintings::find()->where(['id' => $painting_id])->one();
        return $this->render('add', [
            'paintingModel' => $paintingModel,
        ]);
    }

    public function actionSelectmain($painting_id)
    {
        $paintingModel = Paintings::find()->where(['id' => $painting_id])->one();
        $photos = Photos::find()->where(['painting_id' => $painting_id])->all();
        $photoModel = new Photos();

        if (isset($_POST['Photos']))
        {
            $id = $_POST['Photos']['isMain'];
            foreach($photos as $photo)
            {
                if ($photo->id == $id)
                    $photo->isMain = 1;
                else 
                    $photo->isMain = 0;

                $photo->save();
            }

            return $this->redirect(['paintings/index']);
        }

        return $this->render('selectmain', [
            'paintingModel' => $paintingModel,
            'photos' => $photos,
            'photoModel' => $photoModel,
        ]);
    }

    public function actionUpload()
    {
        $painting_id = $_POST['painting_id'];

        $result = [];
        if (isset($_POST) && isset($_FILES['photos'])) {
            $currentPhoto = $_FILES['photos'];
            $tmpFilePath = $currentPhoto['tmp_name'][0];

            if ($tmpFilePath != "") {
                $shortname = $currentPhoto['name'][0];
                $size = $currentPhoto['size'][0];
                $ext = substr(strrchr($shortname, '.'), 1);
                $newFileName = Yii::$app->security->generateRandomString(10) . "." . $ext;
                $newFilePath = Yii::getAlias('@app') . "/web/photos" . "/" . $newFileName;
                $newThumbFilePath = Yii::getAlias('@app') . "/web/photos/thumb/" . $newFileName;
                if (move_uploaded_file($tmpFilePath, $newFilePath)) {
                    if (!file_exists(Yii::getAlias('@app') . "/web/photos/thumb/")) {
                        mkdir(Yii::getAlias('@app') . "/web/photos/thumb/", 0777, true);
                    }

                    $imagine = Image::getImagine();
                    $image = $imagine->open($newFilePath);
                    $image->thumbnail(new Box(300, 300))
                    ->save($newThumbFilePath, ['quality' => 70]);
                   
                    $photoModel = new Photos();
                    $photoModel->painting_id = $painting_id;
                    $photoModel->filename = $newFileName;
                    $photoModel->isMain = 0;
                    $photoModel->save();

                    /*
                    $result = [
                        'initialPreview' => [
                            "<img src='/photos/". $newFileName ."' class='file-preview-image' alt='". $newFileName ."' title='". $newFileName ."'>"
                            // initial preview thumbnails for server uploaded files if you want it displayed immediately after upload
                        ],
                        'initialPreviewConfig' => [
                            "{ caption: '".$newFileName."', width: '120px', url: 'http://localhost/avatar/delete', key: 100, extra: {id: 100}}",
                            // configuration for each item in initial preview
                        ],
                        'initialPreviewThumbTags' => [
                            "{ '{CUSTOM_TAG_NEW}': ' ', '{CUSTOM_TAG_INIT}': '<span class=\'custom-css\'>CUSTOM MARKUP</span>' }"
                            // initial preview thumbnail tags configuration that will be replaced dynamically while rendering
                        ],
                        'append' => true, // whether to append content to the initial preview (or set false to overwrite)
                    ];
                    */
                } else {
                    $result = [
                        'error' => "Не удалось загрузить файл!",
                    ];
                }
            }
        }

        Yii::$app->response->format = trim(Response::FORMAT_JSON);
        return $result;
    }

    protected function findModel($id)
    {
        if (($model = Photos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
