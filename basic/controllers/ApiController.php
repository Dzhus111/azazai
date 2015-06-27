<?php

namespace app\controllers;
use Yii;
use yii\helpers\OAuthVK;
use yii\helpers\SqlUtils;
use yii\helpers\Error;
use yii\db\Query;
use app\models\Events;
use app\models\Comments;
use app\models\Tags;
use app\models\Subscribers;
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

    public function actionDb(){
        
      
        SqlUtils::createEventsTable();
        
    }
    
    public function actionAddComment(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $this->validateComment($queryParams);
        $userId = $this->getUserIdByToken($queryParams['token']);
        $eventId = $queryParams['id'];
        $text = htmlentities($queryParams['text']);
        $model = new Comments();
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->comment_text = $text;
        if($model->save(false)){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);
            exit;
        }else{
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>false]);
            exit;
        }
}
    
    
    public function actionGetSubscribers(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $this->limitAnfOffsetValidator($queryParams);
        $eventId = $queryParams['id'];
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $data = (new \yii\db\Query())
                ->select(['user_id'])
                ->from('subscribers')
                ->where(['event_id' => $eventId])
                ->limit($limit)
                ->offset($offset)
                ->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['subscribers'=>$data]);
        exit;    
        
        
    }
    
    public function actionSubscribe(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $eventId = $queryParams['id'];
        $userId = $this->getUserIdByToken($queryParams['token']);
        $model = new Subscribers;
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->save();
        $event = Events::find()->where(['event_id' => $eventId])->one();
        $event->subscribers_count = $event->subscribers_count + 1;
        $event ->update(false);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['success'=>true]);
        exit;
    }
    
    public function actionCancelEvent(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $id = $queryParams['id'];
        $model = Events::find()->where(['event_id' => $id])->one();
        $model->status = 0;
        if($model->update(false)){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['succsess' => 'true']);
            exit;
        }else{
            $error->error = 'NotFindId';
            $error->message = 'Event id is not finded';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);
            exit;
    }
        
    public function actionGetEventsList()
    {   
        $error = new Error;
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $timeOut = null;
        $query = null;
        $seqrchQuery = $queryParams['query'];
        if(isset($queryParams['timeOut'])){
            $timeOut = $queryParams['timeOut'];
        }
        $time = time();
        
        $query = "SELECT event_id as id, event_name as name, subscribers_count as subscribersCount,
                 description, user_id as userId, address, required_people_number as peopleNumber, 
                 meeting_date as date FROM events WHERE status = 1 AND meeting_date > $time ORDER BY date ASC LIMIT $offset, $limit";
        if($timeOut === 'true'){
            $query = "SELECT event_id as id, event_name as name, subscribers_count as subscribersCount,
                 description, user_id as userId, address, required_people_number as peopleNumber, 
                 meeting_date as date FROM events WHERE status = 1 AND meeting_date < $time ORDER BY date DESC LIMIT $offset, $limit";
        }
        if($queryParams['query']){
            if(!empty($queryParams['query'])&&$seqrchQuery != '0'){
                $comma = strpos($seqrchQuery, ',');
                if($comma){
                    $seqrchQuery = str_replace(',', ' ', $seqrchQuery);
                }
                $query = "SELECT event_id as id, event_name as name, subscribers_count as subscribersCount,
                 description, user_id as userId, address, required_people_number as peopleNumber, 
                 meeting_date as date FROM events WHERE MATCH(search_text) AGAINST ('$seqrchQuery') AND status = 1 AND meeting_date > $time ORDER BY date DESC LIMIT $offset, $limit";
            }else{
                 $query = "SELECT event_id as id, event_name as name, subscribers_count as subscribersCount,
                 description, user_id as userId, address, required_people_number as peopleNumber, 
                 meeting_date as date FROM events WHERE status = 1 AND meeting_date > $time ORDER BY date ASC LIMIT $offset, $limit";
            }
        }
        if(isset($queryParams['dateFilter'])){
            if(empty($queryParams['dateFilter']) && $queryParams['dateFilter'] != '0'){
                $error->error = 'BlankFilter';
                $error->message = 'Filter fild is blank';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
            if(is_numeric($queryParams['dateFilter'])){
                $filterDate = (int)$queryParams['dateFilter'];
                $endOfdateFilter = $filterDate + (60*60*24);
                
                $query = "SELECT event_id as id, event_name as name, subscribers_count as subscribersCount,
                 description, user_id as userId, address, required_people_number as peopleNumber, 
                 meeting_date as date FROM events WHERE meeting_date >= $filterDate AND 
                 meeting_date <= $endOfdateFilter AND status = 1  ORDER BY date ASC LIMIT $offset, $limit";
                
            }else{
                $error->error = 'NotIntDateFilter';
                $error->message = 'Date filter must be integer';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }
        
        
        
        
        $data = Yii::$app->db->createCommand($query)->queryAll();
        $jsonData =['events' => $data];
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( $jsonData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK );
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
    {   session_start();
        $model = new Events();
        $data = array();
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventsParams($queryParams);
        $userId = OAuthVK::getUserIdToken($queryParams['token']);
       
        if(!$userId){
            $error = new Error;
            $error->error = 'InvalidToken';
            $error->message = 'Token must be valid';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        
        $data['user_id'] = $userId;
        $data['subscribers_count'] = 1;
        $data['event_name'] = $queryParams['name'];
        $data['description'] = $queryParams['description'];
        $data['address'] = $queryParams['address'];
        $data['meeting_date'] = (int)$queryParams['date'];
        $data['required_people_number'] = $queryParams['peopleNumber'];
        $data['created_date'] = time();
        $data['status'] = true;
        $tags = explode(",",$queryParams['tags']);
        $searchText = $data['event_name']." ".$data['description'];
        foreach($tags as $tag){
            $searchText = $searchText." ".$tag;
        }
        $data['search_text'] = $searchText;
        $modelData = ['Events'=>$data];
        foreach($tags as $tag){
            $searchTag = Tags::find()->where(['tag_name' => $tag])->one();
            if($searchTag == null){
                $tagsModel = new Tags();
                $tagsModel->tag_name = $tag;
                $tagsModel->events_count = 1;
                $tagsModel->save(false);
            }else{
                $searchTag->events_count = $searchTag->events_count + 1;
                $searchTag->update(false);
            }
        }
        
        $model->load($modelData);
        if($model->save(false)){
            $jsonData =['id' => $model->event_id];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($jsonData, JSON_UNESCAPED_UNICODE);
            exit;
        }else{
            $jsonData =['error' => "Can't create event"];
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
        if(!isset($queryParams['name']) || empty($queryParams['name'])){
            $error->error = 'BlankName';
            $error->message = 'Event name are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ,]+$/u',$queryParams['name'])){
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
        if(!isset($queryParams['description']) || empty($queryParams['description'])){
            $error->error = 'BlankDescriptiont';
            $error->message = 'Event description are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ,]+$/u',$queryParams['description'])){
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
        if(!isset($queryParams['address']) || empty($queryParams['address'])){
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
         elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ,]+$/u',$queryParams['address'])){
            $error->error = 'InvalidAddress';
            $error->message = 'Event address must contain just letters and numbers';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['peopleNumber']) || (empty($queryParams['peopleNumber'])&&(int)$queryParams['peopleNumber'] !== 0)){
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
        elseif((int)($queryParams['peopleNumber']) === 0 || $queryParams['peopleNumber'] == 1){
            $error->error = 'InvalidPeopleNumber';
            $error->message = "People number should be 2 or bigger";
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['date']) || empty($queryParams['date'])){
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
        if(!isset($queryParams['tags']) || empty($queryParams['tags'])){
            $error->error = 'BlankTags';
            $error->message = 'Event tags are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!preg_match('/^[а-яА-ЯёЁa-zA-Z0-9 ,]+$/u',$queryParams['tags'])){
            $error->error = 'InvalidTags';
            $error->message = 'Event tags must contain just letters and numbers';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['token']) || empty($queryParams['token'])){
            $error->error = 'BlankToken';
            $error->message = 'Token  are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
    
    public function validateEventId($id){
        $error = new Error;
        if(!isset($id) || (empty($id)&&$id !='0' )){
            $error->error = 'BlankEventId';
            $error->message = 'Event id are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }elseif(!is_numeric($id)){
            $error->error = 'NotIntEventId';
            $error->message = 'Event id must be intereg';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
    
    public function getUserIdByToken($token){
        $error = new Error;
        if(!isset($token) || empty($token)){
            $error->error = 'BlankToken';
            $error->message = 'Token  are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        $userId = OAuthVK::getUserIdToken($token);
        if(!$userId){
            $error = new Error;
            $error->error = 'InvalidToken';
            $error->message = 'Token must be valid';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        return $userId;
    }
    
    public function limitAnfOffsetValidator($queryParams){
        $error = new Error;
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        if(!isset($limit) || (empty($limit)&& $limit !='0' )){
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
        if(!isset($offset) || (empty($offset)&& $offset !=0 )){
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
    }
    
    public function validateComment($queryParams){
        $error = new Error;
        if(!isset($queryParams['text']) || (empty($queryParams['text'])&& $queryParams['text'] !='0')){
            $error->error = 'BlankComment';
            $error->message = 'Comment are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(strlen($queryParams['text'])<1 || strlen($queryParams['text'])>200 ){
            $error->error = 'OutOfRangeError';
            $error->message = 'Comment must contain min 1 characters and max 200 characters';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
}


