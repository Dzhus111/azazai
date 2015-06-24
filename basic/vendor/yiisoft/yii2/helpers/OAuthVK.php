<?php
namespace yii\helpers;
class OAuthVK {

    const APP_ID = 4966821; //ID приложения
    const APP_SECRET = '8XyPLv1r7jOx6ovlVeCO'; //Защищенный ключ
    const URL_CALLBACK = 'http://events.net/list/vkontakte'; //URL сайта до этого скрипта-обработчика 
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
    public static function goToAuth()
    {
        Utils::redirect(self::URL_AUTHORIZE .
            '?client_id=' . self::APP_ID .
            '&scope=offline' .
            '&redirect_uri=' . urlencode(self::URL_CALLBACK) .
            '&response_type=code');
    }
    
    public static function getUserIdToken($token){
        
        $url ='https://api.vk.com/method/users.get?access_token='.$token;
            
        if (!($res = @file_get_contents($url))) {
            return false;
        }

        $user = json_decode($res);

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

        return $user->uid;
    }
    
    public static function getToken() {
        $url = self::URL_ACCESS_TOKEN .
            '?client_id=' . self::APP_ID .
            '&client_secret=' . self::APP_SECRET .
            '&code=' . $_GET['code'] .
            '&redirect_uri=' . urlencode(self::URL_CALLBACK);

        if (!($res = @file_get_contents($url))) {
            return false;
        }

        $res = json_decode($res);
        if (empty($res->access_token) || empty($res->user_id)) {
            return false;
        }
        session_start();
        $_SESSION['token'] = $res->access_token;
        $_SESSION['userId'] = $res->user_id;;

        return true;
    }

    /**
     * Если данных недостаточно, то посмотрите что можно ещё запросить по этой ссылке
     * @url https://vk.com/pages.php?o=-1&p=getProfiles
     */
     
     
    public static function getUser() {
        session_start();
        if (!$_SESSION['userId']) {
            return false;
        }

        $url = self::URL_GET_PROFILES.
            '?uid=' . $_SESSION['userId'] .
            '&access_token=' . $_SESSION['token'];

        if (!($res = @file_get_contents($url))) {
            return false;
        }

        $user = json_decode($res);

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
}