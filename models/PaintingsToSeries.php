<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "paintings_to_series".
 *
 * @property int $id
 * @property int $series_id Серия
 * @property int $painting_id Картина
 *
 * @property Paintings $painting
 * @property Series $series
 */
class PaintingsToSeries extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'paintings_to_series';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['series_id', 'painting_id'], 'required'],
            [['series_id', 'painting_id'], 'integer'],
            [['painting_id'], 'exist', 'skipOnError' => true, 'targetClass' => Paintings::className(), 'targetAttribute' => ['painting_id' => 'id']],
            [['series_id'], 'exist', 'skipOnError' => true, 'targetClass' => Series::className(), 'targetAttribute' => ['series_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'series_id' => 'Серия',
            'painting_id' => 'Картина',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPainting()
    {
        return $this->hasOne(Paintings::className(), ['id' => 'painting_id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeries()
    {
        return $this->hasOne(Series::className(), ['id' => 'series_id']);
    }
}
