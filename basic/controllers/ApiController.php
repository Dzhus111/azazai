<?php

namespace app\controllers;
use PDO;
use Yii;
use yii\helpers\Error;
use yii\db\Query;
use app\models\Events;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;


class ApiController extends Controller
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

    
    public function actionGetEventsList()
    {   
        $error = new Error;
        $queryParams = Yii::$app->request->queryParams;
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $timeOut = null;
        if(isset($queryParams['timeOut'])){
            $timeOut = $queryParams['timeOut'];
        }
        $time = time();
        if(isset($queryParams['dateFilter'])){
            if(is_numeric($queryParams['dateFilter'])){
                $time = $queryParams['dateFilter'];
            }else{
                $error->error = 'NotIntDateFilter';
                $error->message = 'Date filter must be integer';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }
        $query = "SELECT event_id as id, event_name as name,
         description, user_id as userId, address, required_people_number as peopleNumber, 
         meeting_date as date FROM events WHERE status = 1 AND meeting_date > $time ORDER BY date ASC LIMIT $offset, $limit";
        if($timeOut === 'true'){
            $query = "SELECT event_id as id, event_name as name,
         description, user_id as userId, address, required_people_number as peopleNumber, 
         meeting_date as date FROM events WHERE status = 1 AND meeting_date < $time ORDER BY date DESC LIMIT $offset, $limit";
        }
        if(!isset($limit)){
            $error->error = 'BlankEventListLimit';
            $error->message = 'Event list limit name are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($limit)){
            $error->error = 'NotIntEventListLimit';
            $error->message = 'Event list limit must be integer';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($offset)){
            $error->error = 'BlankEventListOffset';
            $error->message = 'Event list offset name are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($offset)){
            $error->error = 'NotIntEventListOffset';
            $error->message = 'Event list offset must be integer';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        
        $data = Yii::$app->db->createCommand($query)->queryAll();
        $jsonData =['events' => $data];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( $jsonData, JSON_NUMERIC_CHECK );
        exit;
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
    public function actionCreateEvent()
    {   
        $model = new Events();
        $data = array();
        $queryParams = Yii::$app->request->queryParams;

        $this->validateEventsParams($queryParams);
        $data['event_name'] = $queryParams['name'];
        $data['description'] = $queryParams['description'];
        $data['address'] = $queryParams['address'];
        $data['meeting_date'] = (int)$queryParams['date'];
        $data['required_people_number'] = $queryParams['peopleNumber'];
        $data['created_date'] = time();
        $data['status'] = true;
        $data['user_id'] = time();
        $tags = explode(",",$queryParams['tags']);
        $searchText = $data['event_name']." ".$data['description'];
        foreach($tags as $tag){
            $searchText = $searchText." ".$tag;
        }
        $data['search_text'] = $searchText;
        
        $modelData= ['Events' => $data];
        $model->load($modelData);
        if($model->save(false)){
            $jsonData =['success' => true];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($jsonData, JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $jsonData =['success' => false];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($jsonData, JSON_UNESCAPED_UNICODE);
            exit;
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
    
    private function validateEventsParams($queryParams){
        $error = new Error;
        if(!isset($queryParams['name'])){
            $error->error = 'BlankName';
            $error->message = 'Event name are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ]+$/u',$queryParams['name'])){
            $error->error = 'InvalidName';
            $error->message = 'Event name must contain just letters and numbers';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(strlen($queryParams['name'])<5 || strlen($queryParams['name'])>50 ){
            $error->error = 'OutOfRangeError';
            $error->message = 'Event name must contain min 5 characters and max 50 characters';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['description'])){
            $error->error = 'BlankDescriptiont';
            $error->message = 'Event description are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ]+$/u',$queryParams['description'])){
            $error->error = 'InvalidDescription';
            $error->message = 'Event description must contain just letters and numbers';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(strlen($queryParams['description'])<5 || strlen($queryParams['description'])>500 ){
            $error->error = 'OutOfRangeError';
            $error->message = 'Event description must contain min 5 characters and max 500 characters';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['address'])){
            $error->error = 'BlankAddress';
            $error->message = 'Event address  are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(strlen($queryParams['address'])<5 || strlen($queryParams['address'])>200 ){
            $error->error = 'OutOfRangeError';
            $error->message = 'Event address must contain min 5 characters and max 200 characters';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
         elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ]+$/u',$queryParams['description'])){
            $error->error = 'InvalidAddress';
            $error->message = 'Event address must contain just letters and numbers';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['peopleNumber'])){
            $error->error = 'BlankPeopleNumber';
            $error->message = 'Event name are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($queryParams['peopleNumber'])){
            $error->error = 'NotIntPeopleNumber';
            $error->message = 'People number must be integer';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['date'])){
            $error->error = 'BlankDate';
            $error->message = 'Event date are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($queryParams['date'])){
            $error->error = 'NotIntDate';
            $error->message = 'Event date must be integer';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif((int)$queryParams['date']<=time()){
            $error->error = 'InvalidDate';
            $error->message = 'Event date must be bigger than current date';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['tags'])){
            $error->error = 'BlankTags';
            $error->message = 'Event tags are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ]+$/u',$queryParams['description'])){
            $error->error = 'InvalidTags';
            $error->message = 'Event tags must contain just letters and numbers';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
    
}


