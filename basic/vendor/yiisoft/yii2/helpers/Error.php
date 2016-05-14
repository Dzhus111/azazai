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

    public function showErrorMessage($errorName, $errorMessage){
        $this->error = $errorName;
        $this->message = $errorMessage;
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this);
        exit;
    }
}
    