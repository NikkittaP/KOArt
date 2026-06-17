<?php

namespace app\models\search;

use app\models\Paintings;
use yii\base\Model;
use yii\data\ActiveDataProvider;

/**
 * PaintingsSearch represents the model behind the search form of `app\models\Paintings`.
 */
class PaintingsSearch extends Paintings
{
    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['id', 'author_id', 'width', 'height', 'ground_id'], 'integer'],
            [['name', 'description', 'shopURL', 'date', 'datetime_add', 'datetime_update'], 'safe'],
            [['latitude', 'longitude'], 'number'],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function scenarios()
    {
        // bypass scenarios() implementation in the parent class
        return Model::scenarios();
    }

    /**
     * Creates data provider instance with search query applied
     *
     * @param array $params
     *
     * @return ActiveDataProvider
     */
    public function search($params, $series_id = -1, $section_id = -1)
    {
        if ($series_id != -1) {
            $query = Paintings::find()->joinWith('paintingsToSeries')->where(['series_id' => $series_id])->indexBy('id');
        } elseif ($section_id != -1) {
            // Filtering by section: order by the manual sort_order so the
            // admin grid matches what visitors will see, and reorder arrows
            // (Phase 4) operate on a stable, predictable list.
            $query = Paintings::find()->where(['section_id' => $section_id])->orderBy(['sort_order' => SORT_ASC, 'id' => SORT_ASC]);
        } else {
            $query = Paintings::find()->orderBy('id DESC');
        }
        // add conditions that should always apply here

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        // grid filtering conditions
        $query->andFilterWhere([
            'id' => $this->id,
            'width' => $this->width,
            'height' => $this->height
        ]);
        
        $query->andFilterWhere(['like', 'name', $this->name])
            ->andFilterWhere(['like', 'date', $this->date]);

        return $dataProvider;
    }
}
