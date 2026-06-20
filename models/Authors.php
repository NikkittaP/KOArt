<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "authors".
 *
 * @property int $id
 * @property string $name Имя
 * @property string $biography Биография
 * @property string $biography_en Biography (EN)
 *
 * @property Paintings[] $paintings
 */
class Authors extends \yii\db\ActiveRecord
{
    use BilingualTrait;

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
        $rules = [
            [['name'], 'required'],
            [['biography'], 'string'],
            [['name'], 'string', 'max' => 255],
        ];
        // Guarded so the model keeps working before the biography_en migration.
        if ($this->hasAttribute('biography_en')) {
            $rules[] = [['biography_en'], 'string'];
            $rules[] = [['biography_en'], 'default', 'value' => null];
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
            'name' => 'Имя',
            'biography' => 'Биография',
            'biography_en' => 'Biography (EN)',
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
