<?php

namespace app\controllers;

use app\models\Photos;
use Yii;
use yii\filters\VerbFilter;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

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

    public function actionUpload()
    {
        $result = [];
        if (isset($_POST) && isset($_FILES['Paintings'])) {
            $key = array_keys($_FILES['Paintings']['tmp_name']['photo_upload'])[0];
            $tmpFilePath = $_FILES['Paintings']['tmp_name']['photo_upload'][$key];

            if ($tmpFilePath != "") {
                $key = array_keys($_FILES['Paintings']['name']['photo_upload'])[0];
                $shortname = $_FILES['Paintings']['name']['photo_upload'][$key];
                $key = array_keys($_FILES['Paintings']['size']['photo_upload'])[0];
                $size = $_FILES['Paintings']['size']['photo_upload'][$key];
                $ext = substr(strrchr($shortname, '.'), 1);
                $newFileName = Yii::$app->security->generateRandomString(10) . "." . $ext;
                //if (move_uploaded_file($tmpFilePath, Helper::UPLOAD_FOLDER . '/' . Helper::TEMP_FOLDER . '/' . $newFileName)) {
                if (move_uploaded_file($tmpFilePath, Yii::getAlias('@app') . "/photos" . "/" . $newFileName)) {
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
        } else {
            /*
            $result = [
                'error' => "Все файлы уже были загружены",
            ];
            */
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
