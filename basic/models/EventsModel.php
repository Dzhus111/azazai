<?php

namespace app\models;

use Yii;
use yii\base\Model;
use yii\data\ActiveDataProvider;
use app\models\Events;

/**
 * EventsModel represents the model behind the search form about `app\models\Events`.
 */
class EventsModel extends Events
{
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_id', 'required_people_number', 'created_date', 'user_id'], 'integer'],
            [['event_name', 'description', 'required_people_number', 'address', 'status', 'search_text', 'created_date', 'meeting_date'], 'safe'],
        ];
    }

    /**
     * @inheritdoc
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
    public function search($params)
    {
        $query = Events::find();

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
        ]);

        $this->load($params);

        if (!$this->validate()) {
            // uncomment the following line if you do not want to return any records when validation fails
            // $query->where('0=1');
            return $dataProvider;
        }

        $query->andFilterWhere([
            'event_id' => $this->event_id,
            'required_people_number' => $this->required_people_number,
            'created_date' => $this->created_date,
            'meeting _date' => $this->meeting_date,
            'user_id' => $this->user_id,
        ]);

        $query->andFilterWhere(['like', 'event_name', $this->event_name])
            ->andFilterWhere(['like', 'description', $this->description])
            ->andFilterWhere(['like', 'search_text', $this->search_text]);

        return $dataProvider;
    }
}
