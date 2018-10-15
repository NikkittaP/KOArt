<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "author_comments".
 *
 * @property int $id
 * @property int $painting_id
 * @property string $comments Комментарии
 * @property string $material_costs Затраты материалов
 * @property string $time_costs Затраты времени
 *
 * @property Paintings $painting
 */
class AuthorComments extends \yii\db\ActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return 'author_comments';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['painting_id'], 'required'],
            [['painting_id'], 'integer'],
            [['comments', 'material_costs', 'time_costs'], 'string'],
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
            'painting_id' => 'Painting ID',
            'comments' => 'Комментарии',
            'material_costs' => 'Затраты материалов',
            'time_costs' => 'Затраты времени',
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
