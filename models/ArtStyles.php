<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "art_styles".
 *
 * @property int $id
 * @property string $name Название
 *
 * @property ArtStylesToPainting[] $artStylesToPaintings
 */
class ArtStyles extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'art_styles';
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
    public function getArtStylesToPaintings()
    {
        return $this->hasMany(ArtStylesToPainting::className(), ['art_style_id' => 'id']);
    }
}
