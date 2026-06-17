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
            'name' => 'Название (RU)',
            'name_en' => 'Name (EN)',
        ];
    }

    /**
     * Name in the current UI language: English when the admin is in EN and an
     * English name exists, otherwise the Russian/source name.
     */
    public function displayName()
    {
        if (strncmp(Yii::$app->language, 'en', 2) === 0
            && $this->hasAttribute('name_en') && !empty($this->name_en)) {
            return $this->name_en;
        }
        return $this->name;
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaintings()
    {
        return $this->hasMany(Paintings::className(), ['ground_id' => 'id']);
    }
}
