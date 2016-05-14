<?php
/**
 * Created by PhpStorm.
 * User: dzhus
 * Date: 05.05.16
 * Time: 23:25
 */

namespace app\helpers;
use yii;


class Media
{
    public static function getIconsList(){
        $mediaDir = Yii::getAlias('@images');
        $images = scandir($mediaDir);
        $imagesArray = [];
        foreach ($images as $fileName){
            $ext = substr($fileName, strrpos($fileName, '.') + 1);
            if(in_array($ext, array("jpg","jpeg","png","gif"))){
                $imagesArray[] = $fileName;
            }
        }
        return $imagesArray;
    }
}