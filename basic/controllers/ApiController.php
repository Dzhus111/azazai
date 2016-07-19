<?php

namespace app\controllers;
use Yii;
use yii\helpers\OAuthVK;
use yii\helpers\SqlUtils;
use yii\helpers\Error;
use yii\helpers\GSM;
use app\helpers\DataValidator;
use app\helpers\Data;
use yii\helpers\ArrayHelper;
use yii\db\Query;
use app\models\Events;
use app\models\Users;
use app\models\Comments;
use app\models\Tags;
use app\models\TagsEvents;
use app\models\Subscribers;
use app\models\Requests;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;


class ApiController extends Controller
{
    const DEFAULT_MEDIA_ID = 0;

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
    
    public function actionGetEventById(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $id = $queryParams['id'];
        $this->isEventIdExist($id);
        $model = Events::find()->where(['event_id' => $id])->one();
        $jsondata = [
                        'id' => $model->event_id, 
                        'name' => $model->event_name, 
                        'subscribersCount' => $model->subscribers_count,
                        'description' => $model->description,
                        'userId' => $model->user_id,
                        'address' => $model->address,
                        'peopleNumber' => $model->required_people_number,
                        'date' => $model->meeting_date,
                        'isPrivate' => ($model->event_type == Events::EVENT_TYPE_PRIVATE) ? 1 : 0
                     ];
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($jsondata, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
    }
    
    public function actionRegisterDevice(){
        $queryParams = Yii::$app->request->queryParams;
        $userId = $this->getUserIdByToken($queryParams);
        $deviceId = '';
        $newDeviceId = '';
        if(isset($queryParams['deviceId']) && !empty($queryParams['deviceId'])){
            $deviceId = $queryParams['deviceId'];
        }else{
            $error = new Error;
            $error->error = 'BlankDeviceId';
            $error->message = 'Device id are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        $rowToUpdate = Users::find()->where("device_id = '$deviceId'")->one();
        if($rowToUpdate){
            if($rowToUpdate->user_id != $userId){
                        $rowToUpdate->user_id = $userId;
                        $rowToUpdate->device_id = $deviceId;
                        if($rowToUpdate->update(false)){
                            header('Content-Type: application/json; charset=utf-8');
                            echo json_encode(['success' => true]);
                            exit;
                        }
            }
        }
        if(isset($queryParams['newDeviceId']) && !empty($queryParams['newDeviceId'])){
            $newDeviceId = $queryParams['newDeviceId'];
            $rowToUpdate_2 = Users::find()->where("device_id = '$newDeviceId'")->one();
            if($rowToUpdate_2){
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'NoDataToUpdate']);
                    exit;
            }
            if($rowToUpdate){
                $rowToUpdate->device_id = $newDeviceId;
                if($rowToUpdate->update(false)){
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => true]);
                    exit;
                }else{
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['error' => 'NoDataToUpdate']);
                    exit;
                }
                
            }else{
                $model = new Users;
                $model->user_id = $userId;
                $model->device_id = $newDeviceId;
                $model->save(false);
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => true]);
                exit;
            }
        }
        if($rowToUpdate){
            $error = new Error;
            $error->error = 'DublicatedId';
            $error->message = 'There is already data for this device id in data base';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        $model = new Users;
        $model->user_id = $userId;
        $model->device_id = $deviceId;
        $model->save(false);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true]);
        exit;
    }
    
    public function actionGetNotification(){
        $queryParams = Yii::$app->request->queryParams;
        $ids = array();
        if(isset($qininueryParams['id'])){
            $ids[] = $queryParams['id'];
        }else{
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['error'=>'BlankId']);
            exit;
        }
        $test = Gsm::sendMessageThroughGSM($ids, 'Test Message');
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['result'=>$test]);
        exit;
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
        $ignoreTags = array();
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $query = '';
        if($queryParams['ignore']){
            if(!preg_match('/^[\p{L}0-9,]+$/u',$queryParams['ignore']) && $queryParams['ignore'] != '0'){
                $error->error = 'InvalidTags';
                $error->message = 'Event tags must contain just letters and numbers';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
            $ignoreTags = explode(",",$queryParams['ignore']);
            $limit += count($ignoreTags);
        }
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
                    ->where('tag_name LIKE :query and events_count != 0')
                    ->addParams([':query'=>"$query%"])
                    ->limit($limit)
                    ->offset($offset)
                    ->orderBy(['events_count'=> SORT_DESC])
                    ->all();
        foreach($model as $tag){
            if(in_array($tag->tag_name, $ignoreTags)){
                continue;
            }
           $tags[] = $tag->tag_name;
        }
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['Tags'=>$tags], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
         
    }
    
    public function actionIsSubscribed(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $userId = $this->getUserIdFromReques($queryParams);
        $response = 'none';
        $request = Requests::find()->where(['event_id' => $eventId, 'user_id' => $userId])->one();
        if($request){
            if($request->status === Requests::REQUEST_STATUS_ACCEPTED){
                $response = 'subscribed';
            }else{
                $response = $request->status;
            }
        }else{
            $subscriber = Subscribers::find()->where(['event_id' => $eventId, 'user_id' => $userId])->one();

            if($subscriber){
                $response = 'subscribed';
            }
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['isSubscribed'=>$response], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
        exit;
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
        $tagsData = Tags::find()->where(['tag_name' => $tag])->one();
        if($tagsData){
            $tagId = $tagsData->tag_id;
        }else{
            $tagId = -1;
        }
        
        $data = (new \yii\db\Query())
                ->select(['event_id as id', 'icon', 'event_name as name', 'subscribers_count as subscribersCount',
                    'description', 'user_id as userId', 'address', 'required_people_number as peopleNumber',
                    '`events`.`meeting_date` as date ', "IF(event_type = 'private' , true, false) as isPrivate"])
                ->from('events')
                ->where(" event_id IN (SELECT event_id FROM tags_events where tag_id = $tagId) AND status = 1")
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
                ->where('events_count != 0')
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
        $this->validateEventId($queryParams);
        $this->limitAnfOffsetValidator($queryParams);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];

        $data = (new \yii\db\Query())
                ->select(['user_id as userId', 'comment_id as id', 'comment_text as text', 'date'])
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
        $userId = $this->getUserIdFromReques($queryParams);
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
                ->select(['event_id as id', 'icon', 'event_name as name', 'subscribers_count as subscribersCount',
                    'description', 'user_id as userId', 'address', 'required_people_number as peopleNumber',
                    '`events`.`meeting_date` as date', "IF(event_type = 'private' , true, false) as isPrivate"])
                ->from('events')
                ->where(['user_id' => $userId])
                ->andWhere(['status' => 1])
                ->andWhere([$compareSymbol, 'meeting_date', $time])
                ->limit($limit)
                ->offset($offset)
                ->orderBy(['events.meeting_date'=> $sortMod])
                ->all();
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( ['Events'=>$data], JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
                exit; 
        }elseif($mod == 'subscribed'){
            $data = (new \yii\db\Query())
                ->select(['events.event_id as id', 'events.icon', 'events.event_name as name', 'events.subscribers_count as subscribersCount',
                 'events.description', 'events.user_id as userId', 'events.address', 'events.required_people_number as peopleNumber',
                    '`events`.`meeting_date` as date', "IF(event_type = 'private' , true, false) as isPrivate"])
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
        $this->validateEventId($queryParams);
        $this->validateComment($queryParams);
        $userId = $this->getUserIdByToken($queryParams);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $text = htmlentities($queryParams['text']);
        $model = new Comments();
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->comment_text = $text;
        $model->date = time();
        if($model->save(false)){
            $jsonData = ['userId' => $userId, 'date' => $model->date, 'text' => $model->comment_text, 'id' => $model->comment_id];
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode($jsonData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK);
            $event = Events::find()->where(['event_id' => $eventId])->one();
            $users = Users::find()->where(['user_id' => $event->user_id])->one();
            if($users){
                Gsm::sendMessageThroughGSM(array($users->device_id),
                    ['comment' => array('eventId' => intval($eventId), 'text' => $text, 'userId' => intval($userId))]);
            }
            exit;
        }else{
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['error'=>'SaveError']);
            exit;
        }
    }

    public function actionDeleteComment()
    {
        $queryParams = Yii::$app->request->queryParams;
        $userId = $this->getUserIdByToken($queryParams);
        $commentId = (new DataValidator())->validateDataParameter($queryParams, 'commentId');
        $comment = Comments::find()->where(['comment_id' => $commentId, 'user_id' => $userId])->one();
        if($comment){
            if($comment->delete()) {
                Data::returnApiData(['success' => true]);
            }
            Data::returnApiData(['success' => false]);
        }else{
            (new Error())->showErrorMessage('CommentNotFound', 'Comment with this commentId and userId not found');
        }
    }

    public function actionUpdateComment()
    {
        $queryParams = Yii::$app->request->queryParams;
        $userId = $this->getUserIdByToken($queryParams);
        $commentId = (new DataValidator())->validateDataParameter($queryParams, 'commentId');
        $this->validateComment($queryParams);
        $text = htmlentities($queryParams['text']);
        $comment = Comments::find()->where(['comment_id' => $commentId, 'user_id' => $userId])->one();
        if($comment){
            $comment->comment_text = $text;
            if($comment->save(false)){
                Data::returnApiData(['success' => true]);
            }
            Data::returnApiData(['success' => false]);
        }else{
            (new Error())->showErrorMessage('CommentNotFound', 'Comment with this commentId and userId not found');
        }
    }
    
    public function actionGetSubscribers()
    {
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
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
        $this->validateEventId($queryParams);
        $eventId = $queryParams['id'];
        $this->isEventIdExist($eventId);
        $userId = $this->getUserIdByToken($queryParams);
        $event = Events::find()->where(['event_id' => $eventId])->one();
        $users = Users::find()->where(['user_id' => $event->user_id])->all();
        if($event->user_id == $userId){
            $error = new Error;
            $error->error = "SubscribeError";
            $error->message = 'Event creator can not subscribe or unsubscribe';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }

        $pendingStatus = Requests::REQUEST_STATUS_PENDING;
        $subscriber = Subscribers::find()->where("event_id = $eventId AND user_id = $userId")->one();
        $requestOfSubscriber = Requests::find()->where("event_id = $eventId AND user_id = $userId")->one();

        if($subscriber || $requestOfSubscriber){
            if($subscriber){
                Yii::$app->db->createCommand
                ("DELETE FROM subscribers WHERE user_id = {$userId} AND event_id = {$eventId}")->execute();
                $event->subscribers_count = $event->subscribers_count - 1;
                $event ->update(false);

                if($users){
                    Gsm::sendMessageThroughGSM(array($users->device_id),
                        ['unsubscribe' => ['eventId' => $eventId, 'userId' => $userId]]);
                }
            }

            if($requestOfSubscriber && $requestOfSubscriber->status == $pendingStatus){
                Yii::$app->db->createCommand
                ("DELETE FROM requests WHERE user_id = {$userId} AND event_id = {$eventId} AND status = '{$pendingStatus}'")->execute();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);
            exit;
        }

        if($event->event_type == Events::EVENT_TYPE_PRIVATE){

            if($requestOfSubscriber){
                $requestOfSubscriber->status =  Requests::REQUEST_STATUS_PENDING;
                $requestOfSubscriber->update(false);
            }else{
                $requestModel= new Requests;
                $requestModel->event_id = $eventId;
                $requestModel->user_id = $userId;
                $requestModel->status = Requests::REQUEST_STATUS_PENDING;
                $requestModel->save();
            }

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);

            if($users){
                Gsm::sendMessageThroughGSM(array($users->device_id),
                    ['subscribeRequest' => ['eventId' => $eventId, 'userId' => $userId]]);
            }
            exit;
        }

        $model = new Subscribers;
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->save();
        $event->subscribers_count = $event->subscribers_count + 1;
        $event ->update(false);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['success'=>true]);

        if($users){
            Gsm::sendMessageThroughGSM(array($users->device_id),
                ['subscribe' => ['eventId' => $eventId, 'userId' => $userId]]);
        }
        exit;
    }

    public function  actionAcceptRequest(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $id = $queryParams['id'];
        $creatorId = $this->getUserIdByToken($queryParams);
        $userId = (new DataValidator())->validateDataParameter($queryParams, 'userId');
        $this->checkEventForCreatorId($creatorId, $id);
        $users = Users::find()->where(['user_id' => $userId])->all();
        $event = Events::find()->where(['event_id' => $id])->one();

        $request = Requests::find()->where("event_id = $id AND user_id = $userId")->one();

        if($request && $request->status == Requests::REQUEST_STATUS_PENDING){
            $model = new Subscribers;
            $model->event_id = $id;
            $model->user_id = $userId;
            $model->save();
            $event->subscribers_count = $event->subscribers_count + 1;
            $request ->status = Requests::REQUEST_STATUS_ACCEPTED;

            $event ->update(false);
            $request->update(false);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);

            if($users){
                Gsm::sendMessageThroughGSM(array($users->device_id),
                    ['aceptedRequest' => ['eventId' => $id]]);
            }
            exit;

        }else{
            $error = new Error;
            $error->error = 'InvalidRequest';
            $error->message = 'There are no pending request with this useId';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }

    }

    public function checkEventForCreatorId($creatorId, $eventId){
        $event = Events::find()->where(['event_id' => $eventId, 'user_id' => $creatorId])->one();
        if($event){
            return true;
        }else{
            $error = new Error;
            $error->error = 'EventNotFount';
            $error->message = 'There are no event with this event id and this event creator id';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }

    public function actionDenyRequest(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $creatorId = $this->getUserIdByToken($queryParams);
        $userId = (new DataValidator())->validateDataParameter($queryParams, 'userId');
        $id = $queryParams['id'];
        $this->checkEventForCreatorId($creatorId, $id);
        $users = Users::find()->where(['user_id' => $userId])->all();
        $request = Requests::find()->where("event_id = $id AND user_id = $userId")->one();

        if($request && $request->status == Requests::REQUEST_STATUS_PENDING){
            $request ->status = Requests::REQUEST_STATUS_DENIED;
            $request->update(false);

            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( ['success'=>true]);

            if($users){
                Gsm::sendMessageThroughGSM(array($users->device_id),
                    ['deniedRequest' => ['eventId' => $id]]);
            }
            exit;

        }else{
            $error = new Error;
            $error->error = 'InvalidRequest';
            $error->message = 'There are no pending request with this useId';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }

    public function actionGetAllRequests(){
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $userId = $this->getUserIdFromReques($queryParams);
        $eventsModel = new Requests();
        $requests = $eventsModel->getAllRequestsForEventsCreator($userId, $limit, $offset);
        $data = array();

        foreach($requests as $request){
            $data[] = array(
                'userId' => $request->user_id,
                'event' => array(
                    'id'                => $request->eventsrequests->event_id,
                    'userId'            => $request->eventsrequests->user_id,
                    'name'              => $request->eventsrequests->event_name,
                    'icon'              => $request->eventsrequests->icon,
                    'description'       => $request->eventsrequests->description,
                    'peopleNumber'      => $request->eventsrequests->required_people_number,
                    'subscribersCount'  => $request->eventsrequests->subscribers_count,
                    'date'              => $request->eventsrequests->meeting_date,
                    'address'           => $request->eventsrequests->address
            ));
        }

        $jsonData = array('Requests' => $data);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( $jsonData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK );
        exit;
    }

    public function actionGetRequestsCount(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $id = $queryParams['id'];

        if($this->isEventIdExist($id)){
            $count = Requests::find()->where(['event_id' => $id])->count();
            Data::returnApiData(['result' => $count]);
        };
    }


    public function actionGetRequests(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $id = $queryParams['id'];
        $this->isEventIdExist($id);

        $requests = Requests::find()
            ->where(['event_id' => $id])->limit($limit)->offset($offset)->all();
        $data = array();

        foreach($requests as $request){
            $data[] = $request->user_id;
        }

        $jsonData = array('Requests' => $data);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( $jsonData, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK );
        exit;
    }
    
    public function actionCancelEvent(){
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $id = $queryParams['id'];
        $this->isEventIdExist($id);
        $model = Events::find()->where(['event_id' => $id])->one();
        $model->status = 0;

        if(TagsEvents::find()->where(['event_id' => $id])->one()){
            Yii::$app->db->createCommand
            ("UPDATE tags SET events_count = events_count - 1  WHERE tag_id IN 
                (SELECT tag_id FROM tags_events WHERE event_id = $id )")->execute();
            Yii::$app->db->createCommand
            ("DELETE FROM tags_events WHERE event_id = $id")->execute();
            if(!$model->update(false)){
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( ['error'=>'DeleteError']);
                exit;
            }
        }
        
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( ['success'=>true]);
        $users = Users::find()->where("user_id IN (SELECT user_id FROM subscribers WHERE event_id = $id)")->all();
        $ids = [];
        foreach($users as $user){
            $ids[] = $user->device_id;
        }
        Gsm::sendMessageThroughGSM($ids, ['cancelEventId' => intval($id)]);
        exit;
    }

    public function actionGetEventTags()
    {
        $queryParams = Yii::$app->request->queryParams;
        $this->validateEventId($queryParams);
        $eventId = $queryParams['id'];
        $tags = (new Tags())->getTagsByEvent($eventId);
        $jsonData = ['Tags' => []];

        foreach ($tags as $row) {
            $jsonData['Tags'][] = [
                'tagId'         => $row->tag_id,
                'tagName'       => $row->tag_name,
                'eventsCount'   => $row->events_count,
            ];
        }

        Data::returnApiData($jsonData);
    }
        
    public function actionGetEventsList()
    {
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $timeOut = false;
        $time = time();
        $query = null;
        $searchQuery = null;
        $order = SORT_ASC;
        $dataFilter = (isset($queryParams['dateFilter'])) ? (new DataValidator())->validateDataParameter($queryParams, 'dateFilter', true) : null;
        $data =  (new \yii\db\Query())
            ->select(['events.event_id as id', 'events.icon', 'events.event_name as name', 'events.subscribers_count as subscribersCount',
                'events.description', 'events.user_id as userId', 'events.address', 'events.required_people_number as peopleNumber',
                '`events`.`meeting_date` as date ', "IF(event_type = 'private' , true, false) as isPrivate"])
            ->from('events')
            ->where(['status' => Events::EVENT_STATUS_ENABLED]);
        
        if(isset($queryParams['timeOut']) && $queryParams['timeOut']){
            $timeOut = $queryParams['timeOut'];
            $order = SORT_DESC;
        }

        if(!empty($queryParams['query']) && $searchQuery != '0'){
            $searchQuery = str_replace(',', ' ', $queryParams['query']);
            $data->andWhere("MATCH (search_text) AGAINST ('" . $searchQuery . "' IN BOOLEAN MODE)");
        }

        if($dataFilter && $timeOut){
            $data ->andWhere('meeting_date >= ' . $dataFilter . ' AND meeting_date <= ' .  $time);
        }elseif($timeOut){
            $data ->andWhere('meeting_date < ' . $time);
        }elseif($dataFilter){
            $endOfDateFilter = $dataFilter + ((60*60*24));
            $data ->andWhere('meeting_date >= ' . $time . ' AND meeting_date < ' .  $endOfDateFilter);
        }else{
            $data ->andWhere('meeting_date >= ' . $time);
        }

        $data = $data
            ->limit($limit)
            ->offset($offset)
            ->orderBy(['events.meeting_date'=> $order])
            ->all();
        $jsonData = ['Events' => $data];
        Data::returnApiData($jsonData);
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

    public function actionGetIcons(){
        $queryParams = Yii::$app->request->queryParams;
        $this->limitAnfOffsetValidator($queryParams);
        $limit= $queryParams['limit'];
        $offset = $queryParams['offset'];
        $jsonData = array();
        $iconModel = new \app\models\Media;
        $icons = $iconModel->getIcons($limit, $offset);

        if($icons){
            $jsonData = \yii\helpers\ArrayHelper::toArray( $icons);
        }

        header('Content-Type: application/json; charset=utf-8');
        echo json_encode( array('Icons' => $jsonData), JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK );
        exit;
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
        $userId = $this->getUserIdByToken($queryParams);
        
        $data['user_id'] = $userId;
        $data['subscribers_count'] = 1;
        $data['event_name'] = $queryParams['name'];
        $data['event_type'] = $queryParams['type'];
        $data['description'] = $queryParams['description'];
        $data['address'] = $queryParams['address'];
        $data['icon'] = (!empty($queryParams['icon'])) ? (string)$queryParams['icon'] : self::DEFAULT_MEDIA_ID ;
        $data['meeting_date'] =     (int)$queryParams['date'];
        $data['required_people_number'] = $queryParams['peopleNumber'];
        $data['created_date'] = time();
        $data['status'] = true;
        $tagsString = strtolower($queryParams['tags']);
        $tags = explode(",", $tagsString);
        $searchText = $data['event_name']." ".$data['description']." ".str_replace(',', ' ', $tagsString);
        foreach($tags as $tag){
            if(mb_strlen($tag, 'UTF-8') < 3 || mb_strlen($tag, 'UTF-8') > 20 ){
                $error =  new Error;
                $error->error = 'OutOfRangeError';
                $error->message = 'Event tag must contain min 3 characters and max 20 characters';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }else{
                $searchText = $searchText." ".$tag;
            }
        }
        $data['search_text'] = $searchText;
        $modelData = ['Events'=>$data];
        
        
        $model->load($modelData);
        if($model->save(false)){
            $subscribersModel = new Subscribers;
            $subscribersModel->event_id = $model->event_id;
            $subscribersModel->user_id = $model->user_id;
            $subscribersModel->save(false);
            $jsonData =['id' => $model->event_id];
            
            foreach($tags as $tag){
                $searchTag = Tags::find()->where(['tag_name' => $tag])->one();
                if($searchTag == null){
                    $tagsEvents = new TagsEvents;
                    $tagsModel = new Tags();
                    $tagsModel->tag_name = $tag;
                    $tagsModel->events_count = 1;
                    $tagsModel->save(false);
                    $tagsEvents->tag_id = $tagsModel->tag_id;
                    $tagsEvents->event_id = $model->event_id;
                    $tagsEvents->save(false);               
                }else{
                    $tagsEvents = new TagsEvents;
                    $searchTag->events_count = $searchTag->events_count + 1;
                    $searchTag->update(false);
                    $tagsEvents->tag_id = $searchTag->tag_id;
                    $tagsEvents->event_id = $model->event_id;
                    $tagsEvents->save(false);
                }
            }
            
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

    public function actionEditEvent(){
        $queryParams = Yii::$app->request->queryParams;
        $this->verifyParams($queryParams);
        $userId = $this->getUserIdByToken($queryParams);
        $this->validateEventId($queryParams);
        $eventId = (int)$queryParams['id'];
        $event = $this->getEventById($eventId);
        $updateSearch = false;

        if($event){
            if(isset($queryParams['name'])){
                $event->event_name = $queryParams['name'];
                $updateSearch = true;
            }

            if(isset($queryParams['description'])){
                $event->description = $queryParams['description'];
                $updateSearch = true;
            }

            if(isset($queryParams['icon'])){
                $event->icon = $queryParams['icon'];
            }

            if(isset($queryParams['address'])){
                $event->address = $queryParams['address'];
            }

            if(isset($queryParams['type'])){
                $event->event_type = $queryParams['type'];
            }

            if(isset($queryParams['peopleNumber'])){
                $event->required_people_number = $queryParams['peopleNumber'];
            }

            if(isset($queryParams['date'])){
                $event->meeting_date = $queryParams['date'];
            }

            if(isset($queryParams['tags'])){
                $updateSearch = true;
                $tags = explode(",",$queryParams['tags']);
                $existTags = Tags::find()->where(['tag_name'=>$tags])->all();
                if(!empty($existTags)){
                    $existsTagIds = array();
                    $existsTagNames = array();


                    foreach ($existTags as $existTag){
                        $existsTagNames[] = $existTag->tag_name;
                        $existsTagIds[] = $existTag->tag_id;
                    }

                    $assignedTags = TagsEvents::find()->where(['event_id' => $eventId])->all();

                    $_assignedTags = array();

                    foreach($assignedTags as $_tag){
                        $_assignedTags[] = $_tag->tag_id;
                    }

                    $excessTags = array_diff($_assignedTags, $existsTagIds);
                    $needToAssign = array_diff($existsTagIds, $_assignedTags);

                    if(!empty($excessTags)){
                        Tags::updateAllCounters(['events_count' => -1], ['tag_id' => $excessTags]);
                        TagsEvents::deleteAll('tag_id IN (' . implode(',', $excessTags). ') AND event_id = '.$eventId);
                    }

                    if(!empty($needToAssign)){
                        Tags::updateAllCounters(['events_count' => 1], ['tag_id' => $needToAssign]);

                        foreach($needToAssign as $addedTag){
                            $_tagsEvents = new TagsEvents;
                            $_tagsEvents->tag_id = $addedTag;
                            $_tagsEvents->event_id = $event->event_id;
                            $_tagsEvents->save(false);
                        }

                    }

                    $notExistsTagsNames = array_diff($tags, $existsTagNames);

                    if(!empty($notExistsTagsNames)){
                        foreach($notExistsTagsNames as $newTag){
                            $tagsEvents = new TagsEvents;
                            $tagsModel = new Tags();
                            $tagsModel->tag_name = $newTag;
                            $tagsModel->events_count = 1;
                            $tagsModel->save(false);
                            $tagsEvents->tag_id = $tagsModel->tag_id;
                            $tagsEvents->event_id = $event->event_id;
                            $tagsEvents->save(false);
                        }
                    }

                }else{
                    $assignedTags = TagsEvents::find()->where(['event_id' => $eventId])->all();

                    $_assignedTags = array();

                    foreach($assignedTags as $_tag){
                        $_assignedTags[] = $_tag->tag_id;
                    }

                    if(!empty($_assignedTags)){
                        Tags::updateAllCounters(['events_count' => -1], ['tag_id' => $_assignedTags]);

                        TagsEvents::deleteAll('tag_id IN (' . implode(',', $_assignedTags). ') AND event_id = '.$eventId);
                    }

                    foreach($tags as $newTag){
                        $tagsEvents = new TagsEvents;
                        $tagsModel = new Tags();
                        $tagsModel->tag_name = $newTag;
                        $tagsModel->events_count = 1;
                        $tagsModel->save(false);
                        $tagsEvents->tag_id = $tagsModel->tag_id;
                        $tagsEvents->event_id = $event->event_id;
                        $tagsEvents->save(false);
                    }
                }
            }


            if($updateSearch){
                $eventTags = Tags::find()->joinWith('tagsevents')
                    ->where(['tags_events.event_id' => $eventId])
                    ->all();
                $tagsData = array();

                foreach($eventTags as $eventTag){
                    $tagsData[] = $eventTag->tag_name;
                }

                $searchText = $event->event_name . " " . $event->description . " " . implode(' ', $tagsData);
                $event->search_text = $searchText;
            }

            $event->update(false);
        }

    }

    public function actionReportWrongUrl() {
        $query = Yii::$app->request->queryParams;
        $db= Yii::$app->db;
        $db->createCommand()->insert("flyingdogreport", $query);
        header('Content-Type: application/json; charset=utf-8');
        echo "{success: true}";
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

    public function addRequest($eventId, $userId){

    }

    public function updateRequest($eventId, $userId, $status = Requests::REQUEST_STATUS_ACCEPTED){

    }
    
    private function validateEventsParams($queryParams, $additionalFields = array()){
        $error = new Error;
        if(!isset($queryParams['name']) || empty($queryParams['name'])){
            $error->error = 'BlankName';
            $error->message = 'Event name are required';
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
        if(!isset($queryParams['peopleNumber']) || (empty($queryParams['peopleNumber'])&&(int)$queryParams['peopleNumber'] !== 0)){
            $error->error = 'BlankPeopleNumber';
            $error->message = 'People number are required';
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
        elseif(((int)$queryParams['peopleNumber'] > 0 && (int)$queryParams['peopleNumber'] < 2 )  || ((int)$queryParams['peopleNumber'] < 0 && (int)$queryParams['peopleNumber'] !== -1) || (int)$queryParams['peopleNumber'] === 0){
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
        elseif(!preg_match('/^[\p{L}0-9,]+$/u',$queryParams['tags'])){
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
        if(!isset($queryParams['type']) || empty($queryParams['type'])){
            $error->error = 'BlankEventType';
            $error->message = 'Event type are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }elseif($queryParams['type'] != Events::EVENT_TYPE_PRIVATE &&$queryParams['type'] != Events::EVENT_TYPE_PUBLIC){
            $error->error = 'InvalidEventType';
            $error->message = 'Event type must be `public` or `private`';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!empty($additionalFields)){
            foreach($additionalFields as $field){
                if(!isset($queryParams[$field])){
                    $error->error = 'Blank'.$field;
                    $error->message = $field.' are required';
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode( $error);
                    exit;
                }
            }
        }
    }
    
    public function validateEventId($queryParams){

        $error = new Error;
        if(!isset($queryParams['id']) || (empty($queryParams['id']) && $queryParams['id'] !='0' )){
            $error->error = 'BlankEventId';
            $error->message = 'Event id are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }elseif(!is_numeric($queryParams['id'])){
            $error->error = 'NotIntEventId';
            $error->message = 'Event id must be intereg';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }
    
    public function getUserIdByToken($queryParams){
        $error = new Error;
        if(!isset($queryParams['token']) || empty($queryParams['token'])){
            $error->error = 'BlankToken';
            $error->message = 'token  are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        $userId = OAuthVK::getUserIdToken($queryParams['token']);
        if(!$userId){
            $error = new Error;
            $error->error = 'InvalidToken';
            $error->message = 'token must be valid';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        return $userId;
    }
    
    public function limitAnfOffsetValidator($queryParams){
        $error = new Error;

        if(!isset($queryParams['limit']) || (empty($queryParams['limit'])&& $queryParams['limit'] !='0' )){
            $error->error = 'BlankLimit';
            $error->message = 'Limit are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($queryParams['limit'])){
            $error->error = 'NotIntLimit';
            $error->message = 'Limit must be integer';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        if(!isset($queryParams['offset']) || (empty($queryParams['offset'])&& $queryParams['offset'] !=0 )){
            $error->error = 'BlankOffset';
            $error->message = 'Offset are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
        elseif(!is_numeric($queryParams['offset'])){
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
            $error->error = 'BlankCommentText';
            $error->message = 'text are required';
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
        $event = Events::find()->where(['event_id' => $eventId])->one();
        if($event){
            return true;
        }else{
            $error = new Error;
            $error->error = 'InvalidEventId';
            $error->message = 'There are no event with this id';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }

    public function getUserIdFromReques($queryParams){
        $error = new Error;
        if(empty($queryParams['userId'])){
            $error->error = 'BlankUserId';
            $error->message = 'UserId are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }

        return (int)$queryParams['userId'];
    }

    private function getEventById($eventId){
        $event = Events::find()->where(['event_id' => $eventId])->one();
        if($event){
            return $event;
        }else{
            $error = new Error;
            $error->error = 'InvalidEventId';
            $error->message = 'There are no event with this id';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }
    }

    private function verifyParams($queryParams){
        $error = new Error;

        if(isset($queryParams['name'])){
            if(mb_strlen($queryParams['name'], 'UTF-8')<5 || mb_strlen($queryParams['name'], 'UTF-8')>50 ){
                $error->error = 'OutOfRangeError';
                $error->message = 'Event name must contain min 5 characters and max 50 characters';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }

        if(isset($queryParams['description'])){
            if(mb_strlen($queryParams['description'], 'UTF-8')<5 || mb_strlen($queryParams['description'], 'UTF-8')>500){
                $error->error = 'OutOfRangeError';
                $error->message = 'Event description must contain min 5 characters and max 500 characters';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }

        if(isset($queryParams['address'])){
            if(mb_strlen($queryParams['address'], 'UTF-8')<5 || mb_strlen($queryParams['address'], 'UTF-8')>200){
                $error->error = 'OutOfRangeError';
                $error->message = 'Event address must contain min 5 characters and max 200 characters';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }

        if(isset($queryParams['peopleNumber'])){
            $peopleNumber = (int)$queryParams['peopleNumber'];
            if(!is_numeric($peopleNumber)){
                $error->error = 'NotIntPeopleNumber';
                $error->message = 'People number must be integer';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            } elseif(($peopleNumber > 0 && $peopleNumber < 2 )  || ($peopleNumber < 0 && $peopleNumber !== -1) || $peopleNumber ===0){
                $error->error = 'InvalidPeopleNumber';
                $error->message = "People number should be 2 or bigger";
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }

        if(isset($queryParams['date'])){
            if(!is_numeric($queryParams['date'])){
                $error->error = 'NotIntDate';
                $error->message = 'Event date must be integer';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            } elseif((int)$queryParams['date']<=time()){
                $error->error = 'InvalidDate';
                $error->message = 'Event date must be bigger than current date';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }

        if(isset($queryParams['tags'])){
            if(!preg_match('/^[\p{L}0-9,]+$/u',$queryParams['tags'])){
                $error->error = 'InvalidTags';
                $error->message = 'Event tags must contain just letters and numbers';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }

        if(!isset($queryParams['token']) || empty($queryParams['token'])){
            $error->error = 'BlankToken';
            $error->message = 'Token  are required';
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode( $error);
            exit;
        }

        if(isset($queryParams['type'])){
            if($queryParams['type'] != Events::EVENT_TYPE_PRIVATE &&$queryParams['type'] != Events::EVENT_TYPE_PUBLIC){
                $error->error = 'InvalidEventType';
                $error->message = 'Event type must be `public` or `private`';
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode( $error);
                exit;
            }
        }
    }
}


