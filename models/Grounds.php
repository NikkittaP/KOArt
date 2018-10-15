<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "grounds".
 *
 * @property int $id
 * @property string $name Название
 *
 * @property Paintings[] $paintings
 */
class Grounds extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'grounds';
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
    public function getPaintings()
    {
        return $this->hasMany(Paintings::className(), ['ground_id' => 'id']);
    }
}
