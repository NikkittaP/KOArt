<?php

namespace app\models;

use app\helpers\Img;
use Imagine\Filter\Basic\Autorotate;
use Imagine\Image\Box;
use Imagine\Image\ImageInterface;
use Imagine\Image\Point;
use Yii;
use yii\imagine\Image;

/**
 * This is the model class for table "series".
 *
 * @property int $id
 * @property string $name Название серии
 * @property string $description Описание серии
 * @property string $cover_filename Название файла обложки
 * @property int $isVisible Отображать на сайте
 *
 * @property PaintingsToSeries[] $paintingsToSeries
 */
class Series extends \yii\db\ActiveRecord
{
    use BilingualTrait;

    public $uploadedCover;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'series';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['cover_filename'], 'string', 'max' => 255],
			[['isVisible'], 'boolean'],
            [['section_id', 'sort_order'], 'integer'],
            [['section_id'], 'exist', 'skipOnError' => true, 'targetClass' => Sections::className(), 'targetAttribute' => ['section_id' => 'id']],
        ];
        if ($this->hasAttribute('name_en')) {
            $rules[] = [['name_en'], 'string', 'max' => 255];
            $rules[] = [['description_en'], 'string'];
            $rules[] = [['name_en', 'description_en'], 'default', 'value' => null];
        }
        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название серии',
            'name_en' => 'Name (EN)',
            'description' => 'Описание серии',
            'description_en' => 'Description (EN)',
            'cover_filename' => 'Файл обложки',
            'isVisible' => 'Отображать на сайте',
            'section_id' => 'Раздел',
            'sort_order' => 'Порядок сортировки',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaintingsToSeries()
    {
        return $this->hasMany(PaintingsToSeries::className(), ['series_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSection()
    {
        return $this->hasOne(Sections::className(), ['id' => 'section_id']);
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

    /**
     * Stores the uploaded cover: a JPG master (PNG/other converted) plus WebP
     * derivatives (700px squared thumb + 2000px site image).
     */
    public function uploadCover()
    {
        $tmpPath = $this->uploadedCover->tempName;

        // Reject oversized / wrong-format covers, mirroring the photo pipeline.
        $error = Img::validate($tmpPath, (int) $this->uploadedCover->size);
        if ($error !== null) {
            $this->addError('cover_filename', $error);
            Yii::$app->session->setFlash('error', $error);
            return false;
        }

        $base = Yii::$app->security->generateRandomString(10);
        $this->cover_filename = $base . '.jpg';
        if (!$this->validate()) {
            return false;
        }

        $info = @getimagesize($tmpPath);
        $isJpeg = Img::isJpeg(isset($info[2]) ? $info[2] : null);

        $originalFilePath = Yii::getAlias('@app') . '/web/series_cover/original/' . $base . '.jpg';
        $thumbFilePath = Yii::getAlias('@app') . '/web/series_cover/thumb/' . $base . '.webp';
        $siteFilePath = Yii::getAlias('@app') . '/web/series_cover/' . $base . '.webp';

        $imagine = Image::getImagine();

        // JPG master (keep JPG uploads as-is; convert anything else).
        if ($isJpeg) {
            $this->uploadedCover->saveAs($originalFilePath);
        } else {
            $src = $imagine->open($tmpPath);
            $rotate = new Autorotate();
            $rotate->apply($src);
            $src->save($originalFilePath, ['jpeg_quality' => Img::JPEG_QUALITY]);
        }

        // WebP derivatives from the master.
        $this->resizeImage($originalFilePath, $thumbFilePath, 700, 700);

        $image = $imagine->open($originalFilePath);
        $rotate = new Autorotate();
        $rotate->apply($image);
        $image->thumbnail(new Box(2000, 2000))
            ->save($siteFilePath, ['webp_quality' => Img::WEBP_QUALITY]);

        return true;
    }
}
