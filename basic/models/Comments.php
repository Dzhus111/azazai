<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "comments".
 *
 * @property integer $comment_id
 * @property integer $user_id
 * @property integer $event_id
 * @property integer $date
 * @property string $comment_text
 */
class Comments extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'comments';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['user_id', 'event_id', 'comment_text', 'date'], 'required'],
            [['user_id', 'event_id', 'date'], 'integer'],
            [['comment_text'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'comment_id' => 'Comment ID',
            'user_id' => 'User ID',
            'event_id' => 'Event ID',
            'comment_text' => 'Comment Text',
            'date' => 'Comment Date'
        ];
    }
}
