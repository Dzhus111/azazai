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
        if(SqlUtils::regenerateDb()){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);
            exit;
        }else{
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>false]);
            exit;
        }
    }
    
    public function actionSearchTags(){
        $error = new Error;
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $query = '';
        if($queryParams['query'] || $queryParams['query'] == '0'){
            $query = $queryParams['query'];
        }else{
            $error->error = 'BlankQuery';
            $error->message = 'Query are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        $tags =  array();
        $model = Tags::find()
                    ->where('tag_name LIKE :query')
                    ->addParams([':query'=>"$query%"])
                    ->limit($limit)
                    ->offset($offset)
                    ->all();
        foreach($model as $tag){
           $tags[] = $tag->tag_name;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['Tags'=>$tags], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
         
    }
    
    public function actionIsSubscribed(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $userId = $this->getUserIdByToken($queryParams['token']);
        $subscriber = Subscribers::find()->where(['event_id' => $eventId, 'user_id' => $userId])->one();
        if($subscriber){
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['isSubscribed'=>true], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
            exit;
        }else{
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['isSubscribed'=>false], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
            exit;
        }
    }
    
    public function actionGetEventsByTag(){
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $this->validateTagParam($queryParams);
        $tag = $queryParams['tag'];
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $time = time();
        $timeOut = null;
        $compareSymbol = '>';
        $sortMod = SORT_DESC;
        $mod = $queryParams['mod'];
        if(isset($queryParams['timeOut'])){
            $timeOut = $queryParams['timeOut'];
            
            if($timeOut === 'true'){
                $compareSymbol = '<';
                $sortMod = SORT_ASC;
            }else{
                $error = new Error;
                $error->error = 'InvalidTimeOutValue';
                $error->message = 'TimeOut value must be `true`';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }
        $data = (new \yii\db\Query())
                ->select(['event_id as id', 'event_name as name', 'subscribers_count as subscribersCount',
                 'description', 'user_id as userId', 'address', 'required_people_number as peopleNumber', 
                 'meeting_date as date'])
                ->from('events')
                ->where("MATCH(search_text) AGAINST ('$tag') AND status = 1")
                ->andWhere([$compareSymbol, 'meeting_date', $time])
                ->limit($limit)
                ->offset($offset)
                ->orderBy(['date'=> $sortMod])
                ->all();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( ['Events'=>$data], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                exit; 
    }
    public function actionGetTags(){
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $data = (new \yii\db\Query())
                ->select(['tag_name as tagName', 'events_count as eventsCount'])
                ->from('tags')
                ->limit($limit)
                ->offset($offset)
                ->orderBy(['events_count'=>SORT_DESC])
                ->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['Tags'=>$data], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
                
    } 
    
    public function actionGetCommentsList(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $this->limitAnfOffsetValidator($queryParams);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $data = (new \yii\db\Query())
                ->select(['user_id as userId', 'comment_text as text', 'date'])
                ->from('comments')
                ->where(['event_id' => $eventId])
                ->limit($limit)
                ->offset($offset)
                ->orderBy(['date'=>SORT_DESC])
                ->all();
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['Comments'=>$data], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit; 
    }
    
    public function actionGetUserEvents(){
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $this->validateMod($queryParams);
        $userId = $this->getUserIdByToken($queryParams['token']);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $time = time();
        $timeOut = null;
        $compareSymbol = '>';
        $sortMod = SORT_DESC;
        $mod = $queryParams['mod'];
        if(isset($queryParams['timeOut'])){
            $timeOut = $queryParams['timeOut'];
            
            if($timeOut === 'true'){
                $compareSymbol = '<';
                $sortMod = SORT_ASC;
            }else{
                $error = new Error;
                $error->error = 'InvalidTimeOutValue';
                $error->message = 'TimeOut value must be `true`';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }
        
        if($mod == 'created'){
            $data = (new \yii\db\Query())
                ->select(['event_id as id', 'event_name as name', 'subscribers_count as subscribersCount',
                 'description', 'user_id as userId', 'address', 'required_people_number as peopleNumber', 
                 'meeting_date as date'])
                ->from('events')
                ->where(['user_id' => $userId])
                ->andWhere(['status' => 1])
                ->andWhere([$compareSymbol, 'meeting_date', $time])
                ->limit($limit)
                ->offset($offset)
                ->orderBy(['date'=> $sortMod])
                ->all();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( ['Events'=>$data], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                exit; 
        }elseif($mod == 'subscribed'){
            $data = (new \yii\db\Query())
                ->select(['events.event_id as id', 'events.event_name as name', 'events.subscribers_count as subscribersCount',
                 'events.description', 'events.user_id as userId', 'events.address', 'events.required_people_number as peopleNumber', 
                 'events.meeting_date as date'])
                ->from('events')
                ->innerJoin('subscribers', "subscribers.event_id = events.event_id AND  subscribers.user_id = $userId
                AND events.meeting_date $compareSymbol $time and events.status = 1 and events.status = 1 and events.user_id != $userId", [])
                ->limit($limit)
                ->offset($offset)
                ->orderBy(['events.meeting_date'=> $sortMod])
                ->all();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( ['Events'=>$data], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                exit;
        }
         
    }
    public function actionAddComment(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $this->validateComment($queryParams);
        $userId = $this->getUserIdByToken($queryParams['token']);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $text = htmlentities($queryParams['text']);
        $model = new Comments();
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->comment_text = $text;
        $model->date = time();
        if($model->save(false)){
            $jsonData = ['commentId' => $model->comment_id, 'date' => $model->date];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
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
        $this->isEventIdExist($eventId);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $data = (new \yii\db\Query())
                ->select(['user_id as userId'])
                ->from('subscribers')
                ->where(['event_id' => $eventId])
                ->limit($limit)
                ->offset($offset)
                ->all();
        $usersIds = array();
        foreach ($data as $value){
            $usersIds[] = $value['userId'];
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['Subscribers'=>$usersIds], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;    
        
        
    }
    
    public function actionSubscribe(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $userId = $this->getUserIdByToken($queryParams['token']);
        $eventModel = Events::find()->where(['event_id' => $eventId])->one();
        $tagsString = str_replace($eventModel->event_name.' '.$eventModel->description.' ', '', $eventModel->search_text);
        $tags = explode(' ', $tagsString);
        $subscriber = Subscribers::find()->where(['event_id' => $eventId, 'user_id' => $userId])->one();
        if($subscriber){
            Yii::$app->db->createCommand
            ("DELETE FROM subscribers WHERE user_id = $userId AND event_id = $eventId")->execute();
            $event = Events::find()->where(['event_id' => $eventId])->one();
            $event->subscribers_count = $event->subscribers_count - 1;
            $event ->update(false);
            foreach($tags as $tag){
                $searchTag = Tags::find()->where(['tag_name' => $tag])->one();
                $searchTag->events_count = $searchTag->events_count - 1;
                $searchTag->update(false);
            }
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);
            exit;
        }
        
        
        $model = new Subscribers;
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->save();
        foreach($tags as $tag){
                $searchTag = Tags::find()->where(['tag_name' => $tag])->one();
                $searchTag->events_count = $searchTag->events_count + 1;
                $searchTag->update(false);
            }
        $eventModel->subscribers_count = $eventModel->subscribers_count + 1;
        $eventModel ->update(false);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['success'=>true]);
        exit;
    }
    
    public function actionCancelEvent(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams['id']);
        $id = $queryParams['id'];
        $this->isEventIdExist($id);
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
    {   
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
            $subscribersModel = new Subscribers;
            $subscribersModel->event_id = $model->event_id;
            $subscribersModel->user_id = $model->user_id;
            $subscribersModel->save(false);
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
        elseif(mb_strlen($queryParams['name'], 'UTF-8')<5 || mb_strlen($queryParams['name'], 'UTF-8')>50 ){
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
        elseif(mb_strlen($queryParams['description'], 'UTF-8')<5 || mb_strlen($queryParams['description'], 'UTF-8')>500 ){
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
        elseif(mb_strlen($queryParams['address'], 'UTF-8')<5 || mb_strlen($queryParams['address'], 'UTF-8')>200 ){
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
            $error->error = 'BlankLimit';
            $error->message = 'Limit are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($limit)){
            $error->error = 'NotIntLimit';
            $error->message = 'Limit must be integer';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($offset) || (empty($offset)&& $offset !=0 )){
            $error->error = 'BlankOffset';
            $error->message = 'Offset are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($offset)){
            $error->error = 'NotIntOffset';
            $error->message = 'Offset must be integer';
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
    
    public function validateMod($queryParams){
        $error = new Error;
        if(!isset($queryParams['mod']) || (empty($queryParams['mod'])&& $queryParams['mod'] !='0')){
            $error->error = 'BlankMod';
            $error->message = 'Mod are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }else{
            if($queryParams['mod'] == 'created'){
                return;
            }
            if($queryParams['mod'] == 'subscribed'){
                return;
            }
            $error->error = 'InvalidMod';
            $error->message = 'Mod must be `created` or `subscribed`';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
    
    public function validateTagParam($queryParams){
        $error = new Error;
        if(!isset($queryParams['tag']) || (empty($queryParams['tag'])&& $queryParams['tag'] !='0')){
            $error->error = 'BlankTag';
            $error->message = 'Tag are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
    
    public function isEventIdExist($eventId){
        $error = new Error;
        $event = Events::find()->where(['event_id' => $eventId])->one();
        if($event){
            return;
        }else{
            $error->error = 'InvalidEventId';
            $error->message = 'There are no event with this id';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
}


