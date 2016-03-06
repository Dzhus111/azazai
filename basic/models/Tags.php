<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "tags".
 *
 * @property integer $tag_id
 * @property string $tag_name
 * @property integer $events_count
 */
class Tags extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'tags';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['tag_name', 'events_count'], 'required'],
            [['tag_id', 'events_count'], 'integer'],
            [['tag_name'], 'string', 'max' => 255],
            [['tag_name'], 'unique']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tag_id' => 'Tag ID',
            'tag_name' => 'Tag Name',
            'events_count' => 'Events Count',
        ];
    }

    public function getTagsevents()
    {
        return $this->hasOne(TagsEvents::className(), ['tag_id' => 'tag_id']);
    }
    
}
