<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "art_genres".
 *
 * @property int $id
 * @property string $name Название
 *
 * @property ArtGenresToPainting[] $artGenresToPaintings
 */
class ArtGenres extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'art_genres';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtGenresToPaintings()
    {
        return $this->hasMany(ArtGenresToPainting::className(), ['art_genre_id' => 'id']);
    }
}
