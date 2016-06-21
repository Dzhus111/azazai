<?php
/**
 * Created by PhpStorm.
 * User: dzhus
 * Date: 03.04.16
 * Time: 16:58
 */

namespace app\helpers;
use yii\base\Exception;
use yii\helpers\Error;
use app\models\Events;

class DataValidator
{
    public function validateDataParameter($params, $key , $isInt = false, $maxStrLen = null, $minStrLen = null){
        try {
            if (empty($params[$key])) {
                (new Error())->showErrorMessage('blank' . ucfirst($key), $key . ' field is blank');
            }
            if($isInt){
                if(!is_numeric($params[$key])){
                    (new Error())->showErrorMessage('notInt' .  ucfirst($key), $key . ' must be integer');
                }
                return (int)$params[$key];
            }
        }catch(Exception $e){
            echo $e->getMessage();
            exit;
        }

        if($maxStrLen){

        }
        return $params[$key];
    }

}