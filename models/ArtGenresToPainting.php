<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "art_genres_to_painting".
 *
 * @property int $id
 * @property int $painting_id Картина
 * @property int $art_genre_id Жанр
 *
 * @property ArtGenres $artGenre
 * @property Paintings $painting
 */
class ArtGenresToPainting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'art_genres_to_painting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['painting_id', 'art_genre_id'], 'required'],
            [['painting_id', 'art_genre_id'], 'integer'],
            [['art_genre_id'], 'exist', 'skipOnError' => true, 'targetClass' => ArtGenres::className(), 'targetAttribute' => ['art_genre_id' => 'id']],
            [['painting_id'], 'exist', 'skipOnError' => true, 'targetClass' => Paintings::className(), 'targetAttribute' => ['painting_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'painting_id' => 'Картина',
            'art_genre_id' => 'Жанр',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtGenre()
    {
        return $this->hasOne(ArtGenres::className(), ['id' => 'art_genre_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPainting()
    {
        return $this->hasOne(Paintings::className(), ['id' => 'painting_id']);
    }
}
