<?php

namespace app\controllers;

use Yii;
use yii\helpers\Url;
use yii\helpers\OAuthVK;
use yii\helpers\Utils;
use app\models\Events;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;

/**
 * ListController implements the CRUD actions for Events model.
 */
class ListController extends Controller
{
    const STATUS_ACTIVE = 1;
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
     
     public function actionEvents(){
        $status = self::STATUS_ACTIVE;
        $queryParams = Yii::$app->request->queryParams;
        $time = time();
        $dbQuery = "status = 0 and meeting_date > $time ORDER BY meeting_date ASC";
        $query = Events::find()->where($dbQuery);;
        if(!empty($queryParams['q'])){
            $q = $queryParams['q'];
            $q =  preg_replace ("/[^a-zA-ZА-Яа-я0-9\s]/u","",$q);
            $query = Events::find()->where("MATCH(search_text) AGAINST ('$q') ".$dbQuery);
        }
        $dataProvider = new ActiveDataProvider([
        'query' => $query,
        'pagination' => [
        'pageSize' => 20,
                        ],
                    ]);
        $events = $dataProvider->getModels();
        return $this->render('list', ['dataProvider' => $dataProvider]);
     }
     public function actionVk(){
         session_start();
        if (!empty($_GET['error'])) {
            die($_GET['error']);
        } elseif (empty($_GET['code'])) {
            OAuthVK::goToAuth();
        } 
     }
     
     public function actionVkontakte(){
         session_start();
         OAuthVK::getToken();
         $id = OAuthVK::getUserIdToken($_SESSION['token']);
         if(!$id){
            echo 'error';
         }
         var_dump($id);
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
