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
                'only' => ['add', 'selectmain', 'delete', 'manage', 'upload', 'resizeImage', 'download-original'],
                'rules' => [
                    [
                        'actions' => ['add', 'selectmain', 'delete', 'manage', 'upload', 'resizeImage', 'download-original'],
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
     * Single, convenient page to manage the *additional* photos of a work:
     * upload new ones, drag to reorder, pick the cover, and delete. The cover
     * (isMain) is used for thumbnails across the site; the drag order
     * (sort_order) drives how the photos stack on the public work page.
     *
     * GET  → render the sortable grid + uploader.
     * POST → apply deletions, then the new order, then the cover choice, then the portfolio ticks.
     */
    public function actionManage($painting_id)
    {
        $paintingModel = Paintings::find()->where(['id' => $painting_id])->one();
        if ($paintingModel === null) {
            throw new NotFoundHttpException('The requested work does not exist.');
        }

        if (Yii::$app->request->isPost) {
            $req = Yii::$app->request;

            // 1) Delete the ticked photos (files + rows).
            $deleteIds = array_filter(array_map('intval', (array) $req->post('delete_photo_ids', [])));
            if (!empty($deleteIds)) {
                $toDelete = Photos::find()
                    ->where(['painting_id' => $painting_id, 'id' => $deleteIds])
                    ->all();
                foreach ($toDelete as $photo) {
                    $this->deletePhotoFiles($photo);
                    $photo->delete();
                }
            }

            // 2) Apply the drag order. `order` is a comma-separated id list.
            $orderRaw = (string) $req->post('order', '');
            $orderIds = array_filter(array_map('intval', explode(',', $orderRaw)));
            if (!empty($orderIds)) {
                $pos = 0;
                foreach ($orderIds as $pid) {
                    $p = Photos::find()->where(['id' => $pid, 'painting_id' => $painting_id])->one();
                    if ($p !== null) {
                        $p->sort_order = $pos++;
                        $p->save(false, ['sort_order']);
                    }
                }
            }

            // 3) Cover choice, then guarantee exactly one cover remains.
            $coverId = (int) $req->post('cover_photo_id', 0);
            $remaining = Photos::find()
                ->where(['painting_id' => $painting_id])
                ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
                ->all();
            if (!empty($remaining)) {
                $haveCover = false;
                foreach ($remaining as $p) {
                    $isCover = ($coverId > 0 && (int) $p->id === $coverId);
                    if ($isCover) { $haveCover = true; }
                    if ((int) $p->isMain !== ($isCover ? 1 : 0)) {
                        $p->isMain = $isCover ? 1 : 0;
                        $p->save(false, ['isMain']);
                    }
                }
                // No valid cover chosen → make the first photo the cover.
                if (!$haveCover) {
                    $first = $remaining[0];
                    if ((int) $first->isMain !== 1) {
                        $first->isMain = 1;
                        $first->save(false, ['isMain']);
                    }
                }
            }

            // 4) Portfolio selection. Ticked photos go into the section PDF;
            // none ticked is valid and means "use the cover"
            // (Paintings::portfolioPhotos()). Skipped before the migration.
            if (Photos::getTableSchema()->getColumn('in_portfolio') !== null) {
                $portfolioIds = array_map('intval', (array) $req->post('portfolio_photo_ids', []));
                foreach (Photos::find()->where(['painting_id' => $painting_id])->all() as $p) {
                    $want = in_array((int) $p->id, $portfolioIds, true) ? 1 : 0;
                    if ((int) $p->in_portfolio !== $want) {
                        $p->in_portfolio = $want;
                        $p->save(false, ['in_portfolio']);
                    }
                }
            }

            Yii::$app->session->setFlash('success', Yii::t('admin', 'Photos updated.'));
            return $this->redirect(['manage', 'painting_id' => $painting_id]);
        }

        $photos = Photos::find()
            ->where(['painting_id' => $painting_id])
            ->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC])
            ->all();

        return $this->render('manage', [
            'paintingModel' => $paintingModel,
            'photos' => $photos,
        ]);
    }

    /** Remove the stored JPG master + every WebP derivative for one photo. */
    protected function deletePhotoFiles(Photos $photo)
    {
        $base = Yii::getAlias('@app') . '/web/paintings_photo/';
        $webp = Img::webp($photo->filename);
        @unlink($base . 'original/' . $photo->filename);
        @unlink($base . 'original_site/' . $webp);
        @unlink($base . 'preview/' . $webp);
        @unlink($base . 'thumb_squared/' . $webp);
        @unlink($base . 'thumb_tiny/' . $webp);
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

        // 2. Store master (JPG) + WebP derivatives via the shared pipeline.
        try {
            $originalName = Img::store($tmpFilePath, $isJpeg);

            // Append to the end of the current order for this work.
            $maxOrder = (int) Photos::find()
                ->where(['painting_id' => $painting_id])
                ->max('sort_order');

            $photoModel = new Photos();
            $photoModel->painting_id = $painting_id;
            $photoModel->filename = $originalName;
            $photoModel->isMain = 0;
            $photoModel->sort_order = $maxOrder + 1;
            $photoModel->save();
        } catch (\Exception $e) {
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
