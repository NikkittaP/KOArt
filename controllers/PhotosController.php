<?php

namespace app\controllers;

use app\models\Paintings;
use app\models\Photos;
use Imagine\Image\Box;
use Yii;
use yii\imagine\Image;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PhotosController extends Controller
{
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

        if (count($photos) == 0) {
            Yii::$app->session->setFlash('warning', "Сначала необходимо добавить хотя бы 1 фото");
            return $this->redirect(['photos/add', 'painting_id' => $painting_id]);
        } else if (count($photos) == 1) {
            foreach ($photos as $photo) {
                $photo->isMain = 1;
                $photo->save();
            }

            Yii::$app->session->setFlash('warning', "Единственное фото назначено основным");
            return $this->redirect(['paintings/index']);
        }

        $photoModel = new Photos();

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

        $photoMain = Photos::find()->where(['painting_id' => $painting_id, 'isMain' => 1])->one();
        $photoModel->isMain = $photoMain->id;

        return $this->render('selectmain', [
            'paintingModel' => $paintingModel,
            'photos' => $photos,
            'photoModel' => $photoModel,
        ]);
    }

    public function actionDelete($painting_id)
    {
        $paintingModel = Paintings::find()->where(['id' => $painting_id])->one();
        $photos = Photos::find()->where(['painting_id' => $painting_id])->all();
        $photoModel = new Photos();

        if (isset($_POST['Photos'])) {
            $ids = $_POST['Photos']['selected'];
            foreach ($photos as $photo) {
                if (in_array($photo->id, $ids)) {
                    unlink(Yii::getAlias('@app') . '/web/photos/thumb/' . $photo->filename);
                    unlink(Yii::getAlias('@app') . "/web/photos/" . $photo->filename);

                    $photo->delete();
                }
            }

            return $this->redirect(['photos/selectmain', 'painting_id' => $paintingModel->id]);
        }

        return $this->render('delete', [
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

    /*
    public function actionFiltersource()
    {
        $uploadedDir = Yii::getAlias('@app') . "/web/photos" . "/";
        $sourceDir = Yii::getAlias('@app') . "/web/photos" . "/src" . "/";

        $existing = [];

        if ($handle = opendir($uploadedDir)) {
            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $file_parts = pathinfo($entry);

                    if ($file_parts['extension'] == "jpg") {
                        //echo "$entry"."<br />";
                        $imagine = Image::getImagine();
                        $image = $imagine->open($uploadedDir . $entry);
                        $size = $image->getSize();
                        $width = $size->getWidth();
                        $height = $size->getHeight();
                        $filesize = filesize($uploadedDir . $entry);
                        //echo $width.'x'.$height."<br />";
                        //echo $filesize."<br />";

                        $existing[$entry]['width'] = $width;
                        $existing[$entry]['height'] = $height;
                        $existing[$entry]['filesize'] = $filesize;
                    }
                }
            }
            closedir($handle);
        }

        if ($handle = opendir($sourceDir)) {
            if (!file_exists($sourceDir . 'used/')) {
                mkdir($sourceDir . 'used/', 0777, true);
            }

            while (false !== ($entry = readdir($handle))) {
                if ($entry != "." && $entry != "..") {
                    $file_parts = pathinfo($entry);

                    if ($file_parts['extension'] == "jpg") {
                        $imagine = Image::getImagine();
                        $image = $imagine->open($sourceDir . $entry);
                        $size = $image->getSize();
                        $width = $size->getWidth();
                        $height = $size->getHeight();
                        $filesize = filesize($sourceDir . $entry);

                        foreach ($existing as $name => $data) {
                            if ($data['width'] == $width &&
                                $data['height'] == $height &&
                                $data['filesize'] == $filesize) {
                                rename($sourceDir . $entry, $sourceDir . 'used/' . $entry);
                                break;
                            }
                        }
                    }
                }
            }
            closedir($handle);
        }
    }
    */

    protected function findModel($id)
    {
        if (($model = Photos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
