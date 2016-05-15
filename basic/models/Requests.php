<?php

namespace app\models;

use Yii;
use app\models\Events;

/**
 * This is the model class for table "subscribers".
 *
 * @property integer $event_id
 * @property integer $user_id
 * @property string $status
 */
class Requests extends \yii\db\ActiveRecord
{

    const REQUEST_STATUS_PENDING = 'pending';
    const REQUEST_STATUS_ACCEPTED = 'accepted';
    const REQUEST_STATUS_DENIED = 'denied';
    public $userId;
    public $eventId;
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'requests';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['event_id', 'user_id', 'status'], 'required'],
            [['event_id', 'user_id'], 'integer'],
            [['status'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'event_id' => 'Event ID',
            'user_id' => 'User ID',
        ];
    }

    public function getAllRequestsForEventsCreator($userId, $limit, $offset){
        return self::find()
            ->joinWith('eventsrequests', true)
            ->where(['events.user_id' => $userId])
            ->andWhere(['requests.status' => self::REQUEST_STATUS_PENDING])
            ->andWhere(['events.status' => Events::EVENT_STATUS_ENABLED])
            ->limit($limit)
            ->offset($offset)
            ->all();
    }

    public function getEventsrequests()
    {
        return $this->hasOne(Events::className(), ['event_id' => 'event_id']);
    }
}

