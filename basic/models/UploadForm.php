<?php
/**
 * Created by PhpStorm.
 * User: dzhus
 * Date: 07.02.16
 * Time: 21:50
 */

namespace app\models;

use yii\base\Model;
use yii\web\UploadedFile;

class UploadForm extends Model
{
    /**
     * @var UploadedFile
     */
    public $imageFile;
    public $tag;


    public function rules()
    {
        return [
            [['imageFile'], 'file', 'skipOnEmpty' => false, 'extensions' => 'png'],
        ];
    }

    public function upload($tag)
    {
        if ($this->validate()) {
            $mediaModel = new \app\models\Media;
            $mediaModel->tag = $tag;
            if($mediaModel->save(false)){
                $fileName = $mediaModel->mediaId;
                $this->imageFile->saveAs('icon/' .(string)$fileName. '.' . $this->imageFile->extension, true);
                return true;
            }

            return false;

        } else {
            return false;
        }
    }
}