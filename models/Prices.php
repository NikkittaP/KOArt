<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "prices".
 *
 * @property int $id
 * @property int $painting_id Картина
 * @property string $value Стоимость
 * @property string $datetime_add Дата и время добавления
 *
 * @property Paintings $painting
 */
class Prices extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'prices';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['painting_id', 'value', 'datetime_add'], 'required'],
            [['painting_id'], 'integer'],
            [['value'], 'number'],
            [['datetime_add'], 'safe'],
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
            'value' => 'Стоимость',
            'datetime_add' => 'Дата и время добавления',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPainting()
    {
        return $this->hasOne(Paintings::className(), ['id' => 'painting_id']);
    }
}
