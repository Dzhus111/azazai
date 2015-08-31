<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "events".
 *
 * @property integer $event_id
 * @property string $event_name
 * @property string $description
 * @property integer subscribers_count
 * @property string $address
 * @property integer $status
 * @property integer $required_people_number
 * @property integer $created_date
 * @property integer $meeting_date
 * @property string $search_text
 * @property integer $user_id
 */
class Events extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'events';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_name', 'address', 'status','description', 'subscribers_count', 'required_people_number', 'created_date', 'meeting_date', 'user_id'], 'required'],
            [['description', 'search_text'], 'string'],
            [['required_people_number', 'created_date', 'user_id', 'status'], 'integer'],
            [['event_name'], 'string', 'max' => 255]
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'subscribers_count' => "Количество подписавшихся",
            'event_id' => 'Event ID',
            'event_name' => 'Название',
            'description' => 'Описание',
            'address' => 'Место встречи (адрес)',
            'required_people_number' => 'Необходимое количество людей',
            'created_date' => 'Created Date',
            'meeting_date' => 'Дата и время встречи',
            'status' => 'Status',
            'search_text' => 'Search Text',
            'user_id' => 'User ID',
        ];
    }
}
