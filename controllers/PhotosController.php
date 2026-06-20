<?php

namespace app\controllers;

use app\helpers\Img;
use app\models\Paintings;
use app\models\Photos;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Imagine\Filter\Basic\Autorotate;
use Yii;
use yii\filters\AccessControl;
use yii\helpers\Inflector;
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
                'only' => ['add', 'selectmain', 'delete', 'upload', 'resizeImage', 'download-original'],
                'rules' => [
                    [
                        'actions' => ['add', 'selectmain', 'delete', 'upload', 'resizeImage', 'download-original'],
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

        // May be null if no cover has been chosen yet (e.g. right after upload).
        $photoMain = Photos::find()->where(['painting_id' => $painting_id, 'isMain' => 1])->one();
        $photoModel->isMain = $photoMain ? $photoMain->id : null;

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
                    $base = Yii::getAlias('@app') . '/web/paintings_photo/';
                    $webp = Img::webp($photo->filename);
                    // Original master is JPG; all derivatives are WebP.
                    @unlink($base . 'original/' . $photo->filename);
                    @unlink($base . 'original_site/' . $webp);
                    @unlink($base . 'preview/' . $webp);
                    @unlink($base . 'thumb_squared/' . $webp);
                    @unlink($base . 'thumb_tiny/' . $webp);

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
            ->save($output, ['webp_quality' => Img::WEBP_QUALITY]);
    }

    public function actionUpload()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $painting_id = isset($_POST['painting_id']) ? (int) $_POST['painting_id'] : 0;

        if (!isset($_FILES['photos']) || $_FILES['photos']['tmp_name'][0] === '') {
            return ['error' => Yii::t('admin', 'No file received.')];
        }

        $currentPhoto = $_FILES['photos'];
        $tmpFilePath = $currentPhoto['tmp_name'][0];
        $bytes = (int) $currentPhoto['size'][0];

        // 1. Sanity-check the source image (size + resolution + format).
        $error = Img::validate($tmpFilePath, $bytes);
        if ($error !== null) {
            return ['error' => $error];
        }

        $info = @getimagesize($tmpFilePath);
        $isJpeg = Img::isJpeg(isset($info[2]) ? $info[2] : null);

        // 2. The master original is always JPG; derivatives are always WebP.
        $base = Yii::$app->security->generateRandomString(10);
        $originalName = $base . '.jpg';
        $webpName = $base . '.webp';

        $dir = Yii::getAlias('@app') . '/web/paintings_photo/';
        $originalFilePath = $dir . 'original/' . $originalName;
        $originalSiteFilePath = $dir . 'original_site/' . $webpName;
        $previewFilePath = $dir . 'preview/' . $webpName;
        $thumbSquaredFilePath = $dir . 'thumb_squared/' . $webpName;
        $thumbTinyFilePath = $dir . 'thumb_tiny/' . $webpName;

        try {
            $imagine = Image::getImagine();

            if ($isJpeg) {
                // Keep the uploaded JPG byte-for-byte (best fidelity for the master).
                if (!move_uploaded_file($tmpFilePath, $originalFilePath)) {
                    return ['error' => Yii::t('admin', 'Could not save the uploaded file.')];
                }
            } else {
                // PNG / other → convert the master to JPG. The admin warns about this.
                $src = $imagine->open($tmpFilePath);
                $rotate = new Autorotate();
                $rotate->apply($src);
                $src->save($originalFilePath, ['jpeg_quality' => Img::JPEG_QUALITY]);
            }

            // 3. Build WebP derivatives from the stored master.
            $image = $imagine->open($originalFilePath);
            $filterAutorotate = new Autorotate();
            $filterAutorotate->apply($image);

            $image->thumbnail(new Box(2000, 2000))
                ->save($originalSiteFilePath, ['webp_quality' => Img::WEBP_QUALITY]);

            $image->thumbnail(new Box(900, 900))
                ->save($previewFilePath, ['webp_quality' => Img::WEBP_QUALITY]);

            $this->resizeImage($originalFilePath, $thumbSquaredFilePath, 700, 700);

            $image->thumbnail(new Box(100, 100), ImageInterface::THUMBNAIL_OUTBOUND)
                ->save($thumbTinyFilePath, ['webp_quality' => Img::WEBP_QUALITY]);

            $photoModel = new Photos();
            $photoModel->painting_id = $painting_id;
            $photoModel->filename = $originalName;
            $photoModel->isMain = 0;
            $photoModel->save();
        } catch (\Exception $e) {
            // Roll back any partial files so we never leave orphans behind.
            foreach ([$originalFilePath, $originalSiteFilePath, $previewFilePath, $thumbSquaredFilePath, $thumbTinyFilePath] as $f) {
                @unlink($f);
            }
            Yii::error('Photo upload failed: ' . $e->getMessage(), __METHOD__);
            return ['error' => Yii::t('admin', 'Could not process the image. Please try a different file.')];
        }

        return [];
    }

    /**
     * Stream the full-resolution JPG master for a single photo as a download,
     * named after the work it belongs to.
     */
    public function actionDownloadOriginal($id)
    {
        $photo = $this->findModel($id);
        $path = Yii::getAlias('@app') . '/web/paintings_photo/original/' . $photo->filename;

        if (!is_file($path)) {
            throw new NotFoundHttpException('The original file does not exist.');
        }

        $painting = $photo->painting;
        $slug = $painting ? Inflector::slug(Inflector::transliterate((string) $painting->name)) : '';
        $downloadName = ($slug !== '' ? $slug : 'painting') . '-' . $photo->id . '.jpg';

        return Yii::$app->response->sendFile($path, $downloadName);
    }

    protected function findModel($id)
    {
        if (($model = Photos::findOne($id)) !== null) {
            return $model;
        }

        throw new NotFoundHttpException('The requested page does not exist.');
    }
}
