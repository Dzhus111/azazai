<?php
namespace yii\helpers;
class Gsm
{
    //Generic php function to send GCM push notification
    const GOOGLE_API_KEY = 'AIzaSyDMOpnIRD1wcazqUEeco9vN7qnu7ugl8LU';
    public static function sendMessageThroughGSM(array $ids, $message) {
        $url = 'https://android.googleapis.com/gcm/send';
        $fields = array(
            'registration_ids' => $ids,
            'data' => array('data' => array('title' => 'Yo', 'message' => 'Yo, this is a notification', 'icon' => 
			'http://icons.iconarchive.com/icons/yellowicon/game-stars/256/Mario-icon.png')),
        );		
        $headers = array(
            'Authorization: key=' . self::GOOGLE_API_KEY,
            'Content-Type: application/json'
        );
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);	
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($fields));
        $result = curl_exec($ch);				
        curl_close($ch);
        return $result;
    }

}
	