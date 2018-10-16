<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "paintings".
 *
 * @property int $id
 * @property int $author_id Автор
 * @property string $name Название
 * @property string $description Описание
 * @property int $width Ширина
 * @property int $height Высота
 * @property int $ground_id Основа
 * @property string $shopURL Ссылка в магазин
 * @property string $date Дата создания
 * @property double $latitude Широта
 * @property double $longitude Долгота
 * @property string $datetime_add Дата и время добавления
 * @property string $datetime_update Дата и время обновления
 *
 * @property ArtGenresToPainting[] $artGenresToPaintings
 * @property ArtStylesToPainting[] $artStylesToPaintings
 * @property AuthorComments[] $authorComments
 * @property MaterialsToPainting[] $materialsToPaintings
 * @property Authors $author
 * @property Grounds $ground
 * @property Photos[] $photos
 * @property Prices[] $prices
 */
class Paintings extends \yii\db\ActiveRecord
{
    public $coverPhoto;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'paintings';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['author_id', 'name'], 'required'],
            [['author_id', 'width', 'height', 'ground_id'], 'integer'],
            [['description'], 'string'],
            [['date', 'datetime_add', 'datetime_update'], 'safe'],
            [['latitude', 'longitude'], 'number'],
            [['name', 'shopURL'], 'string', 'max' => 255],
            [['author_id'], 'exist', 'skipOnError' => true, 'targetClass' => Authors::className(), 'targetAttribute' => ['author_id' => 'id']],
            [['ground_id'], 'exist', 'skipOnError' => true, 'targetClass' => Grounds::className(), 'targetAttribute' => ['ground_id' => 'id']],
            [['coverPhoto'], 'safe'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'author_id' => 'Автор',
            'name' => 'Название',
            'description' => 'Описание',
            'width' => 'Ширина',
            'height' => 'Высота',
            'ground_id' => 'Основа',
            'shopURL' => 'Ссылка в магазин',
            'date' => 'Дата создания',
            'latitude' => 'Широта',
            'longitude' => 'Долгота',
            'datetime_add' => 'Дата и время добавления',
            'datetime_update' => 'Дата и время обновления',
            'coverPhoto' => 'Фото',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtGenresToPaintings()
    {
        return $this->hasMany(ArtGenresToPainting::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtStylesToPaintings()
    {
        return $this->hasMany(ArtStylesToPainting::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthorComments()
    {
        return $this->hasMany(AuthorComments::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterialsToPaintings()
    {
        return $this->hasMany(MaterialsToPainting::className(), ['painting_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getAuthor()
    {
        return $this->hasOne(Authors::className(), ['id' => 'author_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getGround()
    {
        return $this->hasOne(Grounds::className(), ['id' => 'ground_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPhotos()
    {
        return $this->hasMany(Photos::className(), ['painting_id' => 'id']);
    }

    public function getMainPhoto()
    {
        return $this->hasOne(Photos::className(), ['painting_id' => 'id'])->andOnCondition(['isMain' => '1']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPrices()
    {
        return $this->hasMany(Prices::className(), ['painting_id' => 'id']);
    }
}
