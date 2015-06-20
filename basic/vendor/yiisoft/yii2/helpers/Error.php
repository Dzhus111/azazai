<?php
namespace yii\helpers;
class Error
{
    public $error;
    public $message;
    
    public function setErrorName($name){
        $this->error = $name;
    }
    
    public function getErrorName(){
        return $this->error;
    }
}
    