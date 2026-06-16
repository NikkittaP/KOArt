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
    public static function tableName()
    {
        return 'sections';
    }

    public function rules()
    {
        return [
            [['slug', 'title'], 'required'],
            [['sort'], 'integer'],
            [['slug'], 'string', 'max' => 64],
            [['title'], 'string', 'max' => 128],
            [['slug'], 'unique'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'slug' => 'Slug (в URL)',
            'title' => 'Название',
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
