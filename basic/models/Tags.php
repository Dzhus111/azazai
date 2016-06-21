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

    public function getTagsEvents()
    {
        return $this->hasOne(TagsEvents::className(), ['tag_id' => 'tag_id']);
    }

    public function getTagsByEvent($eventId)
    {
        return self::find()
            ->joinWith(['tagsEvents' => function ($query) {
                $query->select('event_id');
            }], true, 'RIGHT JOIN')
            ->where(['tags_events.event_id' => $eventId])
            ->all();
    }

    public function updateTags($tagsStr, $event)
    {
        $tags = explode(",", $tagsStr);
        $existTags = self::find()->where(['tag_name'=>$tags])->all();

        if(!empty($existTags)){
            $existsTagIds = array();
            $existsTagNames = array();

            foreach ($existTags as $existTag){
                $existsTagNames[] = $existTag->tag_name;
                $existsTagIds[] = $existTag->tag_id;
            }

            $assignedTags = TagsEvents::find()->where(['event_id' => $event->event_id])->all();

            $_assignedTags = array();

            foreach($assignedTags as $_tag){
                $_assignedTags[] = $_tag->tag_id;
            }

            $excessTags = array_diff($_assignedTags, $existsTagIds);
            $needToAssign = array_diff($existsTagIds, $_assignedTags);

            if(!empty($excessTags)){
                self::updateAllCounters(['events_count' => -1], ['tag_id' => $excessTags]);
                TagsEvents::deleteAll('tag_id IN (' . implode(',', $excessTags). ') AND event_id = '.$event->event_id);
            }

            if(!empty($needToAssign)){
                Tags::updateAllCounters(['events_count' => 1], ['tag_id' => $needToAssign]);

                foreach($needToAssign as $addedTag){
                    $_tagsEvents = new TagsEvents;
                    $_tagsEvents->tag_id = $addedTag;
                    $_tagsEvents->event_id = $event->event_id;
                    $_tagsEvents->save(false);
                }

            }

            $notExistsTagsNames = array_diff($tags, $existsTagNames);

            if(!empty($notExistsTagsNames)){
                foreach($notExistsTagsNames as $newTag){
                    $tagsEvents = new TagsEvents;
                    $tagsModel = new Tags();
                    $tagsModel->tag_name = $newTag;
                    $tagsModel->events_count = 1;
                    $tagsModel->save(false);
                    $tagsEvents->tag_id = $tagsModel->tag_id;
                    $tagsEvents->event_id = $event->event_id;
                    $tagsEvents->save(false);
                }
            }

        }else{
            $assignedTags = TagsEvents::find()->where(['event_id' => $event->event_id])->all();

            $_assignedTags = array();

            foreach($assignedTags as $_tag){
                $_assignedTags[] = $_tag->tag_id;
            }

            if(!empty($_assignedTags)){
                self::updateAllCounters(['events_count' => -1], ['tag_id' => $_assignedTags]);

                TagsEvents::deleteAll('tag_id IN (' . implode(',', $_assignedTags). ') AND event_id = '.$event->event_id);
            }

            foreach($tags as $newTag){
                $tagsEvents = new TagsEvents;
                $tagsModel = new Tags();
                $tagsModel->tag_name = $newTag;
                $tagsModel->events_count = 1;
                $tagsModel->save(false);
                $tagsEvents->tag_id = $tagsModel->tag_id;
                $tagsEvents->event_id = $event->event_id;
                $tagsEvents->save(false);
            }
        }
    }
}
