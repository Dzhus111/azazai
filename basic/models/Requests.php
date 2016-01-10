<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "subscribers".
 *
 * @property integer $event_id
 * @property integer $user_id
 * @property string $status
 */
class Requests extends \yii\db\ActiveRecord
{

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
}

