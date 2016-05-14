<?php
/**
 * Created by PhpStorm.
 * User: dzhus
 * Date: 10.04.16
 * Time: 0:29
 */

namespace app\helpers;


class Data
{
    public static function returnApiData($jsonData){
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( $jsonData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK );
        exit;
    }
}