<?php

namespace app\controllers;

use Yii;
use yii\helpers\OAuthVK;
use yii\helpers\Utils;
use app\models\Events;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;

/**
 * ListController implements the CRUD actions for Events model.
 */
class ListController extends Controller
{
    public function behaviors()
    {
        return [
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'delete' => ['post'],
                ],
            ],
        ];
    }

    /**
     * Lists all Events models.
     * @return mixed
     */
     
     public function actionVk(){
         session_start();
        if (!empty($_GET['error'])) {
    // Пришёл ответ с ошибкой. Например, юзер отменил авторизацию.
    die($_GET['error']);
} elseif (empty($_GET['code'])) {
    // Самый первый запрос
    OAuthVK::goToAuth();
} else {
    /*
     * На данном этапе можно проверить зарегистрирован ли у вас ВК-юзер с id = OAuthVK::$userId
     * Если да, то можно просто авторизовать его и не запрашивать его данные.
     */
    //echo $_SESSION['token'].'<br/>';
    $user = OAuthVK::getUser();
    print_r($user);
    /*
     * Вот и всё - мы узнали основные данные авторизованного юзера.
     * $user в этом примере состоит из трёх полей: uid, first_name, last_name.
     * Делайте с ними что угодно - регистрируйте, авторизуйте, ругайте...
     */
}
     }
     
    public function actionIndex()
    {   
       
        $searchModel = new EventsModel();
        $dataProvider = $searchModel->search(Yii::$app->request->queryParams);

        return $this->render('index', [
            'searchModel' => $searchModel,
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Events model.
     * @param integer $id
     * @return mixed
     */
    public function actionView($id)
    {
        return $this->render('view', [
            'model' => $this->findModel($id),
        ]);
    }

    /**
     * Creates a new Events model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        $model = new Events();

        if ($model->load(Yii::$app->request->post())) {
            var_dump(Yii::$app->request->post());
            exit();
            $dateData = str_replace('/', '-', $model->meeting_date);
            $dateData = str_replace(' ', '   ', $dateData);
            $timestamp = strtotime($dateData);
            $model->meeting_date = $timestamp;
            $model->created_date = time();
            try{
                if($model->save(false)){
                return $this->redirect(['view', 'id' => $model->event_id]);
                }else var_dump($model->meeting_date);
            }catch(Exception $e){
                var_dump($e);
            }
        } else {
            return $this->render('create', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Updates an existing Events model.
     * If update is successful, the browser will be redirected to the 'view' page.
     * @param integer $id
     * @return mixed
     */
    public function actionUpdate($id)
    {
        $model = $this->findModel($id);

        if ($model->load(Yii::$app->request->post()) && $model->save()) {
            return $this->redirect(['view', 'id' => $model->event_id]);
        } else {
            return $this->render('update', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Deletes an existing Events model.
     * If deletion is successful, the browser will be redirected to the 'index' page.
     * @param integer $id
     * @return mixed
     */
    public function actionDelete($id)
    {
        $this->findModel($id)->delete();

        return $this->redirect(['index']);
    }

    /**
     * Finds the Events model based on its primary key value.
     * If the model is not found, a 404 HTTP exception will be thrown.
     * @param integer $id
     * @return Events the loaded model
     * @throws NotFoundHttpException if the model cannot be found
     */
    protected function findModel($id)
    {
        if (($model = Events::findOne($id)) !== null) {
            return $model;
        } else {
            throw new NotFoundHttpException('The requested page does not exist.');
        }
    }
}
