<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "photos".
 *
 * @property int $id
 * @property int $painting_id Картина
 * @property string $filename Название файла
 * @property int $isMain Является главной
 *
 * @property Paintings $painting
 */
class Photos extends \yii\db\ActiveRecord
{
    public $selected;

    public static function tableName()
    {
        return 'photos';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['painting_id', 'filename'], 'required'],
            [['painting_id', 'isMain'], 'integer'],
            [['filename'], 'string', 'max' => 255],
            [['painting_id'], 'exist', 'skipOnError' => true, 'targetClass' => Paintings::className(), 'targetAttribute' => ['painting_id' => 'id']],
            [['selected'], 'safe'],
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
            'filename' => 'Название файла',
            'isMain' => '',
            'selected' => '',
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
