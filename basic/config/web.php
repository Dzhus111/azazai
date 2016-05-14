<?php

$params = require(__DIR__ . '/params.php');

$config = [
    'id' => 'basic',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'aliases' => [
        '@images' => dirname(__DIR__).'/events.net/icon',
    ],
    'components' => [

        
        'request' => [
            'cookieValidationKey' => 'pImaLs8tXTbljqdD9KPPpmWHW-MFEOqp',
        ],
        'urlManager' => [
            'class' => 'yii\web\urlManager',
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'enableStrictParsing' => true,
            'rules' => [
                '/' => 'list/events',
                'api/addComment' => 'api/add-comment',
                'api/getEventById' => 'api/get-event-by-id',
                'api/registerDevice' => 'api/register-device',
                'api/getNotification' => 'api/get-notification',
                'api/searchTags' => 'api/search-tags',
                'api/isSubscribed' => 'api/is-subscribed',
                'api/getEventsByTag' => 'api/get-events-by-tag',
                'api/getTags' => 'api/get-tags',
                'api/getUserEvents' => 'api/get-user-events',
                'api/getCommentsList' => 'api/get-comments-list',
                'api/getSubscribers' => 'api/get-subscribers',
                'api/getEventsList' => 'api/get-events-list',
                'api/cancelEvent' => 'api/cancel-event',
                'api/createEvent' => 'api/create-event',
                'api/editEvent' => 'api/edit-event',
                'api/reportWrongUrl' => 'api/report-wrong-url',
                'api/acceptRequest' => 'api/accept-request',
                'api/denyRequest' => 'api/deny-request',
                'api/getRequests' => 'api/get-requests',
                'api/getIcons' => 'api/get-icons',
                'api/getAllRequests' => 'api/get-all-requests',
                'api/deleteComment' => 'api/delete-comment',
                'api/updateComment' => 'api/update-comment',
                'api/getRequestsCount' => 'api/get-requests-count',
                'list/myEvents' => 'list/my-events',
                'tags' => 'tags/index',
                '<controller:\w+>/<id:\d+>' => '<controller>/view',
                '<controller:\w+>/<action:\w+>/<id:\d+>' => '<controller>/<action>',
                '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
            ],
        ],
        'cache' => [
            'class' => 'yii\caching\FileCache',
        ],
        'user' => [
            'identityClass' => 'app\models\User',
            'enableAutoLogin' => true,
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'mailer' => [
            'class' => 'yii\swiftmailer\Mailer',
            // send all mails to a file by default. You have to set
            // 'useFileTransport' to false and configure a transport
            // for the mailer to send real emails.
            'useFileTransport' => true,
        ],
        'log' => [
            'traceLevel' => YII_DEBUG ? 3 : 0,
            'targets' => [
                [
                    'class' => 'yii\log\FileTarget',
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
        'db' => require(__DIR__ . '/db.php'),
    ],
    'params' => $params,
];

if (YII_ENV_DEV) {
    // configuration adjustments for 'dev' environment
    $config['bootstrap'][] = 'debug';
    $config['modules']['debug'] = 'yii\debug\Module';

    $config['bootstrap'][] = 'gii';
    $config['modules']['gii'] = 'yii\gii\Module';
}

return $config;
