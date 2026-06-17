<?php

namespace app\models;

use Yii;

/**
 * Model for table "sections" — the navigation dimension.
 *
 * @property int $id
 * @property string $slug
 * @property string $title
 * @property int $sort
 *
 * @property Paintings[] $paintings
 * @property Series[] $series
 */
class Sections extends \yii\db\ActiveRecord
{
    use BilingualTrait;

    public static function tableName()
    {
        return 'sections';
    }

    public function rules()
    {
        $rules = [
            [['slug', 'title'], 'required'],
            [['sort'], 'integer'],
            [['description'], 'string'],
            [['slug'], 'string', 'max' => 64],
            [['title'], 'string', 'max' => 128],
            [['slug'], 'unique'],
        ];
        if ($this->hasAttribute('title_en')) {
            $rules[] = [['title_en'], 'string', 'max' => 128];
            $rules[] = [['description_en'], 'string'];
            $rules[] = [['title_en', 'description_en'], 'default', 'value' => null];
        }
        return $rules;
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug (в URL)',
            'title' => 'Название',
            'title_en' => 'Title (EN)',
            'description' => 'Описание (интро раздела)',
            'description_en' => 'Description (EN)',
            'sort' => 'Порядок',
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getPaintings()
    {
        return $this->hasMany(Paintings::className(), ['section_id' => 'id']);
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getSeries()
    {
        return $this->hasMany(Series::className(), ['section_id' => 'id']);
    }
}
