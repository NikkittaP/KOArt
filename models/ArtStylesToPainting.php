<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "art_styles_to_painting".
 *
 * @property int $id
 * @property int $painting_id Картина
 * @property int $art_style_id Стиль
 *
 * @property ArtStyles $artStyle
 * @property Paintings $painting
 */
class ArtStylesToPainting extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'art_styles_to_painting';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['painting_id', 'art_style_id'], 'required'],
            [['painting_id', 'art_style_id'], 'integer'],
            [['art_style_id'], 'exist', 'skipOnError' => true, 'targetClass' => ArtStyles::className(), 'targetAttribute' => ['art_style_id' => 'id']],
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
            'art_style_id' => 'Стиль',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getArtStyle()
    {
        return $this->hasOne(ArtStyles::className(), ['id' => 'art_style_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPainting()
    {
        return $this->hasOne(Paintings::className(), ['id' => 'painting_id']);
    }
}
