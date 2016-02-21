<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use app\models\LoginForm;
use app\models\ContactForm;
use app\models\UploadForm;
use yii\web\UploadedFile;

class AdminController extends Controller
{
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout'],
                'rules' => [
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
        ];
    }

    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    public function actionIndex()
    {
        return $this->render('index');
    }

    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    public function actionContact()
    {
        $model = new ContactForm();
        if ($model->load(Yii::$app->request->post()) && $model->contact(Yii::$app->params['adminEmail'])) {
            Yii::$app->session->setFlash('contactFormSubmitted');

            return $this->refresh();
        } else {
            return $this->render('contact', [
                'model' => $model,
            ]);
        }
    }

    public function actionAbout()
    {
        return $this->render('about');
    }

    public function actionIcons(){
        $model = new UploadForm();
        return $this->render('upload', ['model' => $model]);
    }

    public function actionUpload()
    {
        $model = new UploadForm();
        $postData = Yii::$app->request->post();
        $tag = (isset($postData['UploadForm']['tag'])) ? $postData['UploadForm']['tag'] : null;

        if (Yii::$app->request->isPost && !is_null($tag)) {
            $model->imageFile = UploadedFile::getInstance($model, 'imageFile');
            if ($model->upload($tag)) {
                return $this->goToUploadPage ($model, 'File is uploaded successfully');
            }
        }
        return $this->goToUploadPage ($model);

    }

    private function goToUploadPage($model, $message = ''){
        if(!empty($message)){
            Yii::$app->getSession()->setFlash('upload', $message);
        }
        return $this->render('upload', ['model' => $model]);
    }
}
