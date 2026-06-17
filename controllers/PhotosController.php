<?php

namespace app\controllers;

use app\models\Paintings;
use app\models\Photos;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Filter\Basic\Autorotate;
use Yii;
use yii\filters\AccessControl;
use yii\imagine\Image;
use yii\web\NotFoundHttpException;
use yii\web\Response;

class PhotosController extends AdminBaseController
{
    public $adminNav = 'works';

    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['add', 'selectmain', 'delete', 'upload', 'resizeImage'],
                'rules' => [
                    [
                        'actions' => ['add', 'selectmain', 'delete', 'upload', 'resizeImage'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
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
                    unlink(Yii::getAlias('@app') . '/web/paintings_photo/original/' . $photo->filename);
                    unlink(Yii::getAlias('@app') . '/web/paintings_photo/original_site/' . $photo->filename);
                    unlink(Yii::getAlias('@app') . '/web/paintings_photo/preview/' . $photo->filename);
                    unlink(Yii::getAlias('@app') . '/web/paintings_photo/thumb_squared/' . $photo->filename);
                    unlink(Yii::getAlias('@app') . '/web/paintings_photo/thumb_tiny/' . $photo->filename);

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

    /**
     * Image resize
     * @param string $input Full path to source image
     * @param string $output Full path to result image
     * @param int $width Width of result image
     * @param int $height Height of result image
     */
    public function resizeImage($input, $output, $width, $height)
    {
        $imagine = Image::getImagine();
        $size = new Box($width, $height);
        $mode = ImageInterface::THUMBNAIL_INSET;
        $image = $imagine->open($input);
        
        $filterAutorotate = new Autorotate();
        $filterAutorotate->apply($image);

        $resizeimg = $image->thumbnail($size, $mode);
        $sizeR = $resizeimg->getSize();
        $widthR = $sizeR->getWidth();
        $heightR = $sizeR->getHeight();

        $preserve = $imagine->create($size);
        $startX = $startY = 0;
        if ($widthR < $width) {
            $startX = ($width - $widthR) / 2;
        }
        if ($heightR < $height) {
            $startY = ($height - $heightR) / 2;
        }
        $preserve
            ->paste($resizeimg, new Point($startX, $startY))
            ->save($output);
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
                $newFileName = Yii::$app->security->generateRandomString(10) . "." . strtolower($ext);

                $originalFilePath = Yii::getAlias('@app') . "/web/paintings_photo/original/" . $newFileName;

                $originalSiteFilePath = Yii::getAlias('@app') . "/web/paintings_photo/original_site/" . $newFileName;
                $previewFilePath = Yii::getAlias('@app') . "/web/paintings_photo/preview/" . $newFileName;
                $thumbSquaredFilePath = Yii::getAlias('@app') . "/web/paintings_photo/thumb_squared/" . $newFileName;
                $thumbTinyFilePath = Yii::getAlias('@app') . "/web/paintings_photo/thumb_tiny/" . $newFileName;
               
                if (move_uploaded_file($tmpFilePath, $originalFilePath)) {
                    $imagine = Image::getImagine();
                    $image = $imagine->open($originalFilePath);

                    $filterAutorotate = new Autorotate();
                    $filterAutorotate->apply($image);

                    $image->thumbnail(new Box(2000, 2000))
                        ->save($originalSiteFilePath);
                        
                    $image->thumbnail(new Box(900, 900))
                        ->save($previewFilePath);
                    
                    $this->resizeImage($originalFilePath, $thumbSquaredFilePath, 700, 700);

                    $image->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_OUTBOUND)->save($thumbTinyFilePath);

                    $photoModel = new Photos();
                    $photoModel->painting_id = $painting_id;
                    $photoModel->filename = $newFileName;
                    $photoModel->isMain = 0;
                    $photoModel->save();
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
	public function resizeImage2($input, $output, $width, $height)
    {
        $imagine = Image::getImagine();
        $size = new Box($width, $height);
        $mode = ImageInterface::THUMBNAIL_INSET;
        $resizeimg = $imagine
            ->open($input)
            ->thumbnail($size, $mode);
        $sizeR = $resizeimg->getSize();
        $widthR = $sizeR->getWidth();
        $heightR = $sizeR->getHeight();

        $preserve = $imagine->create($size);
        $startX = $startY = 0;
        if ($widthR < $width) {
            $startX = ($width - $widthR) / 2;
        }
        if ($heightR < $height) {
            $startY = ($height - $heightR) / 2;
        }
        $preserve
            ->paste($resizeimg, new Point($startX, $startY))
            ->save($output);
    }

    public function actionAsd()
    {
        $directory = Yii::getAlias('@app') . "/web/paintings_photo/original/";
        $directory = Yii::getAlias('@app') . "/web/series_cover/original/";
        $images = glob($directory . "/*.png");
		print_r($images);

        foreach ($images as $image) {
            $photo_tiny = Yii::getAlias('@app') . "/web/paintings_photo/thumb_test/" . basename($image);
            $thumb_squared= Yii::getAlias('@app') . "/web/paintings_photo/thumb_squared/" . basename($image);
            $series_cover_thumb= Yii::getAlias('@app') . "/web/series_cover/thumb/" . basename($image);


            if (!file_exists($series_cover_thumb)) {

                    $this->resizeImage2($image, $series_cover_thumb, 700, 700);
					
            //$imagine = Image::getImagine();
            //$image_original = $imagine->open($image);
           // $thumbnail = $image_original->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_OUTBOUND)->save($photo_tiny);
            }
        }
        exit();

    }

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
