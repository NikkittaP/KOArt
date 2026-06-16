<?php

namespace app\models;

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
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
            [['description'], 'string'],
            [['cover_filename'], 'string', 'max' => 255],
			[['isVisible'], 'boolean'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название серии',
            'description' => 'Описание серии',
            'cover_filename' => 'Файл обложки',
            'isVisible' => 'Отображать на сайте',
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

    public function uploadCover()
    {
        $newFileName = Yii::$app->security->generateRandomString(10) . "." . $this->uploadedCover->extension;
        $this->cover_filename = $newFileName;
        if ($this->validate()) {
            $newFilePath = 'series_cover/original/' . $newFileName;
            $newThumbFilePath = Yii::getAlias('@app') . '/web/series_cover/thumb/' . $newFileName;
            $newSiteFilePath = Yii::getAlias('@app') . '/web/series_cover/' . $newFileName;
            $this->uploadedCover->saveAs($newFilePath);

            $this->resizeImage($newFilePath, $newThumbFilePath, 700, 700);

            $imagine = Image::getImagine();
            $image = $imagine->open($newFilePath);
            $image->thumbnail(new Box(2000, 2000))
                ->save($newSiteFilePath);

            return true;
        } else {
            return false;
        }
    }
}
