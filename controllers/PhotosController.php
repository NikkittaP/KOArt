<?php

namespace app\controllers;

use Yii;
use app\models\Photos;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\helpers\VarDumper;

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
      VarDumper::dump( $_FILES, $depth = 10, $highlight = true);

      $array = [
        //'error' => 'An error exception message if applicable',
        'initialPreview' => [
            // initial preview thumbnails for server uploaded files if you want it displayed immediately after upload
        ],
        'initialPreviewConfig' => [
            // configuration for each item in initial preview 
        ],
        'initialPreviewThumbTags' => [
            // initial preview thumbnail tags configuration that will be replaced dynamically while rendering
        ],
        'append' => true // whether to append content to the initial preview (or set false to overwrite)
      ];

      return $this->asJson($array);
    }

    protected function findModel($id)
    {
        if (($model = Photos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
