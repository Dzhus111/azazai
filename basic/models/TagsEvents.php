<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tags_events".
 *
 * @property integer $id
 * @property integer $tag_id
 * @property integer $event_id
 */
class TagsEvents extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tags_events';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['id', 'tag_id', 'event_id'], 'integer']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'tag_id' => 'Tag ID',
            'event_id' => 'Event ID',
        ];
    }
}
