<?php
namespace app\models;

use Yii;

/**
 * This is the model class for table "media".
 *
 * @property integer $id
 * @property string $mediaId
 * @property string $tag
 */

class  Media extends \yii\db\ActiveRecord
{

    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'media';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['mediaId', 'tag'], 'required'],
            [['mediaId', 'tag'], 'string']
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'tag' => 'Tag',
        ];
    }

    public function getIcons($limit = 100, $offset = 0){
        return $this->find()->limit($limit)->offset($offset)->all();
    }

}