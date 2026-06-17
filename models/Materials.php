<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "materials".
 *
 * @property int $id
 * @property string $name Название
 *
 * @property MaterialsToPainting[] $materialsToPaintings
 */
class Materials extends \yii\db\ActiveRecord
{
    use BilingualTrait;

    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'materials';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        $rules = [
            [['name'], 'required'],
            [['name'], 'string', 'max' => 255],
        ];
        // name_en is added by a migration; guard so the model also works before
        // the migration has been applied.
        if ($this->hasAttribute('name_en')) {
            $rules[] = [['name_en'], 'string', 'max' => 255];
            $rules[] = [['name_en'], 'default', 'value' => null];
        }
        return $rules;
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'name' => 'Название',
            'name_en' => 'Name (EN)',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getMaterialsToPaintings()
    {
        return $this->hasMany(MaterialsToPainting::className(), ['material_id' => 'id']);
    }
}
