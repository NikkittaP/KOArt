<?php

namespace app\models;

use Yii;
use yii\db\ActiveRecord;
use yii\behaviors\TimestampBehavior;
use yii\db\Expression;

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
            [['painting_id', 'value'], 'required'],
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

    public function behaviors()
    {
        return [
            [
                'class' => TimestampBehavior::className(),
                'attributes' => [
                    ActiveRecord::EVENT_BEFORE_INSERT => ['datetime_add'],
                ],
                // если вместо метки времени UNIX используется datetime:
                'value' => new Expression('NOW()'),
            ],
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
