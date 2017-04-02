<?php
namespace yii\helpers;
use yii\web\Session;
use Yii;

class OAuthVK {

    const APP_ID = 4966821; //ID приложения
    const APP_SECRET = '8XyPLv1r7jOx6ovlVeCO'; //Защищенный ключ
    const URL_CALLBACK = 'http://azazai.net/events/create'; //URL сайта до этого скрипта-обработчика
    const URL_ACCESS_TOKEN = 'https://oauth.vk.com/access_token';
    const URL_AUTHORIZE = 'https://oauth.vk.com/authorize';
    const URL_GET_PROFILES = 'https://api.vk.com/method/getProfiles';

    public static $token;
    public static $userId;
    public static $userData;

    private static function printError($error) {
        echo '#' . $error->error_code . ' - ' . $error->error_msg;
    }

    /**
     * @url https://vk.com/dev/auth_sites
     */
    public static function goToAuth($path)
    {
        Utils::redirect(self::URL_AUTHORIZE .
            '?client_id=' . self::APP_ID .
            '&scope=offline' .
            '&redirect_uri=' . urldecode(self::URL_CALLBACK) .
            '&response_type=code&display=popup');
    }
    
    public static function getUserIdToken($token){
        
        $url ='https://api.vk.com/method/users.get?access_token='.$token;

        $user = self::getVkApiResponce($url);

        if (!empty($user->error)) {
            return false;
        }

        if (empty($user->response[0])) {
            return false;
        }

        $user = $user->response[0];
        if (empty($user->uid) || empty($user->first_name) || empty($user->last_name)) {
            return false;
        }
        $session = new Session;
        $session->open();
        $session['userId'] = $user->user_id;
        return $user->uid;
    }
    
    public static function getToken($code) {
        $url = self::URL_ACCESS_TOKEN .
            '?client_id=' . self::APP_ID .
            '&client_secret=' . self::APP_SECRET .
            '&code=' .$code .
            '&redirect_uri=' . urlencode('http://azazai.net/events/create');

        $result = self::getVkApiResponce($url);

        if (empty($result->access_token) || empty($result->user_id)) {
            return false;
        }

        $session = new Session;
        $session->open();
        $session['token'] = $result->access_token;
        $session['userId'] = $result->user_id;
        return  true;
    }

    public static function getUser() {

        $url = self::URL_GET_PROFILES.
            '?uid=' . $_SESSION['userId'] .
            '&access_token=' . $_SESSION['token'];

        $user = self::getVkApiResponce($url);

        if (!empty($user->error)) {
            self::printError($user->error);
            return false;
        }

        if (empty($user->response[0])) {
            return false;
        }

        $user = $user->response[0];
        if (empty($user->uid) || empty($user->first_name) || empty($user->last_name)) {
            return false;
        }

        return $user;
    }

    public static function getVkApiResponce($url) {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_URL, $url);

        $result = json_decode(curl_exec($ch));

        curl_close($ch);

        return $result;
    }
}
