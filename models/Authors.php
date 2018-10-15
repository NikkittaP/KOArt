<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "authors".
 *
 * @property int $id
 * @property string $name Имя
 * @property string $biography Биография
 *
 * @property Paintings[] $paintings
 */
class Authors extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'authors';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['name'], 'required'],
            [['biography'], 'string'],
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
            'name' => 'Имя',
            'biography' => 'Биография',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaintings()
    {
        return $this->hasMany(Paintings::className(), ['author_id' => 'id']);
    }
}
