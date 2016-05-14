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
 * @property integer $event_type
 * @property string $icon
 */
class Events extends \yii\db\ActiveRecord
{
    const EVENT_STATUS_ENABLED = 1;
    const EVENT_STATUS_DISABLED = 0;
    const EVENT_TYPE_PUBLIC = 'public';
    const EVENT_TYPE_PRIVATE = 'private';
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
            [['event_name', 'address', 'status','description', 'subscribers_count', 'required_people_number', 'created_date', 'meeting_date', 'user_id', 'event_type', 'icon'], 'required'],
            [['description', 'search_text', 'icon'], 'string'],
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
            'icon' => 'Иконка',
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

    /**
     * @return  array
     */
    public function getUserEvents($userId){
        $ids = array();
        $events = self::findAll(['user_id' => $userId]);
        return $events;
    }

    public function getAllRequestsForEventsCreator($userId, $limit, $offset){
        return self::find()
            ->joinWith(['eventsrequests' => function ($query) {
                $query->select('user_id as userId');
            }], true, 'RIGHT JOIN')
            ->where(['events.user_id' => $userId])
            ->andWhere(['events.status' => self::EVENT_STATUS_ENABLED])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function getEventsrequests()
    {
        return $this->hasOne(Requests::className(), ['event_id' => 'event_id']);
    }
}
