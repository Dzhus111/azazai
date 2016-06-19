<?php
namespace app\controllers;
use Yii;
use yii\helpers\Utils;
use app\models\Events;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Comments;
use app\models\Subscribers;
use yii\helpers\OAuthVK;
use app\models\Tags;
use app\models\TagsEvents;

class EventsController extends Controller
{
    const SUBSCRIBED    = 1;
    const UNSUBSCRIBED  = 0;

    private $userId = null;

    public function actionDetail($id){
        $statusActive = Events::EVENT_STATUS_ENABLED;
        $time = time();
        $id = (int)$id;
        $model = Events::find() ->where("event_id = $id and status = $statusActive and meeting_date > $time")
            ->one();
        $comment = Yii::$app->request->post('comment');
        $userId = Yii::$app->session->get('userId');
        $subscribeAction =  Yii::$app->request->post('subscribe');

        if($subscribeAction){
            $subscribeStatus = $this->subscribe($id,$userId);
            $comments = Comments::find()->where("event_id = $id ORDER BY comment_id DESC");
            $commentsDataProvider = new ActiveDataProvider([
                'query' => $comments,
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]);
            return $this->render('event',
                [   'model' => $model,
                    'commentsDataProvider' => $commentsDataProvider,
                    'subscribeStatus' => $subscribeStatus
                ]);
        }

        if(!empty($comment)) {

            $commentsModel = new Comments();
            $commentsModel->user_id = $userId;
            $commentsModel->comment_text = $comment;
            $commentsModel->date = time();
            $commentsModel->event_id = $id;
            $commentsModel->save(false);

            $comments = Comments::find()->where("event_id = $id ORDER BY comment_id DESC");
            $commentsDataProvider = new ActiveDataProvider([
                'query' => $comments,
                'pagination' => [
                    'pageSize' => 20,
                ],
            ]);
            return $this->render('event', ['commentsDataProvider' => $commentsDataProvider]);
        }


        $comments = Comments::find() ->where("event_id = $id ORDER BY comment_id DESC");
        $commentsDataProvider = new ActiveDataProvider([
            'query' => $comments,
            'pagination' => [
                'pageSize' => 20,
            ],
        ]);

        return $this->render('event', [
            'model' => $model,
            'commentsDataProvider' => $commentsDataProvider,
            'subscribeStatus' => $this->isSubscribed($id)

        ]);
    }

    public function subscribe($eventId, $userId){
        $event = Events::find()->where(['event_id' => $eventId])->one();
        $subscriber = Subscribers::find()->where("event_id = $eventId AND user_id = $userId")->one();

        if($subscriber){
            Yii::$app->db->createCommand
            ("DELETE FROM subscribers WHERE user_id = $userId AND event_id = $eventId")->execute();
            $event->subscribers_count = $event->subscribers_count - 1;
            $event ->update(false);
            return self::UNSUBSCRIBED;
        }

        $model = new Subscribers;
        $model->event_id = $eventId;
        $model->user_id = $userId;
        $model->save();
        $event->subscribers_count = $event->subscribers_count + 1;
        $event ->update(false);
        return self::SUBSCRIBED;
    }

    public  function isSubscribed($eventId){
        $userId = Yii::$app->session->get('userId');
        if(!$userId){
            return 0;
        }
        $subscriber = Subscribers::find()->where("event_id = $eventId AND user_id = $userId")->one();
        if($subscriber){
            return self::SUBSCRIBED;
        }
        return self::UNSUBSCRIBED;
    }

    /**
     * Creates a new Events model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate()
    {
        if(is_null( $this->getUserId())){
            $this->loginVk();
        }

        $userId = $this->getUserId();

        if($userId){
            $model = new Events();
            $error = null;
            if ($model->load(Yii::$app->request->post())) {
                if(empty( $model->meeting_date)){
                    $error = 'blankDate';
                    return $this->render('create', [
                        'model' => $model,
                        'error' => $error
                    ]);
                }
                $post = Yii::$app->request->post();
                $post['Events']['description'];
                $tags = array();

                if(!empty( $post['tags'])){
                    $tags = explode(",",$post['tags']);
                }else{
                    $error = 'blankTags';
                    return $this->render('create', [
                        'model' => $model,
                        'error' => $error
                    ]);
                }

                $dateData = str_replace('/', '-', $model->meeting_date);
                $dateData = str_replace(' ', '   ', $dateData);
                $timestamp = strtotime($dateData);
                if($timestamp <= time()){
                    $error = 'incorrectDate';
                    return $this->render('create', [
                        'model' => $model,
                        'error' => $error
                    ]);
                }

                $model->meeting_date = $timestamp;
                $model->created_date = time();
                $model->subscribers_count = 1;
                $model->user_id = $userId;
                $model->status = 1;
                $model->icon = (int)$post['icon'];
                $model->event_type = $post['type'];
                $model->search_text = $post['Events']['event_name'] . " "
                    . $post['Events']['description'] . " " .
                    str_replace(',', ' ', strtolower($post['tags']));

                try{
                    if($model->save(false)){;
                        $subscribersModel = new Subscribers;
                        $subscribersModel->event_id = $model->event_id;
                        $subscribersModel->user_id = $model->user_id;
                        $subscribersModel->save(false);

                        foreach($tags as $tag){
                            $tag = strtolower($tag);
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

                        return $this->redirect('index');
                    }
                }catch(Exception $e){
                    print_r($e);
                }
            } else {
                return $this->render('create', [
                    'model' => $model,
                ]);
            }
        }
    }

    public function actionEdit($id)
    {
        if(is_null( $this->getUserId())){
            $this->loginVk();
        }

        $userId = $this->getUserId();

        if($userId && is_numeric($id)) {
            $event = Events::findOne((int)$id);
            $event->meeting_date = date('d-m-Y H:i', (int)$event->meeting_date);
            $tags = (new Tags)->getTagsByEvent($id);
            $tagsStr = '';

            foreach ($tags as $tag) {
                $tagsStr .= $tag->tag_name . ',';
            }

            return $this->render('edit_event', [
                'event'     => $event,
                'tagsStr'   => rtrim($tagsStr,',')
            ]);
        }
    }

    /**
     * @return null
     */
    protected function getUserId()
    {
        if(is_null($this->userId)){
            if(Yii::$app->session->get('userId')) {
                $this->userId = Yii::$app->session->get('userId');
            }
        }
        return $this->userId;
    }

    protected function loginVk(){
        if (empty($_GET['code'])) {
            OAuthVK::goToAuth('events/create');
        } else {
            if (!OAuthVK::getToken($_GET['code'])) {
                return false;
            }
        }
        return true;
    }

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

    public function actionList(){
        $statusActive = Events::EVENT_STATUS_ENABLED;
        $queryParams = Yii::$app->request->queryParams;
        $tagCondition = '';
        if(isset($queryParams['tagId'])){
            $tagId = (int)$queryParams['tagId'];
            $tagCondition = "and event_id IN (SELECT event_id FROM tags_events where tag_id = $tagId)";
        }
        $time = time();
        $dbQuery = "status = $statusActive and meeting_date > $time $tagCondition ORDER BY meeting_date ASC";
        $query = Events::find()->where($dbQuery);
        if(!empty($queryParams['q'])){
            $q = $queryParams['q'];
            $q =  preg_replace ("/[^a-zA-ZА-Яа-я0-9\s]/u","",$q);
            $query = Events::find()->where("MATCH(search_text) AGAINST ('$q') and ".$dbQuery);
        }
        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 2,
            ],
        ]);
        $events = $dataProvider->getModels();
        return $this->render('list', ['dataProvider' => $dataProvider]);
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

    public function actionMyEvents(){
        if(is_null( $this->getUserId())){
            $this->loginVk();
        }
        $userId = $this->getUserId();
        $time = time();
        $queryParams = Yii::$app->request->queryParams;
        $mod = 'subscribed';
        $query = '';
        if(!empty($queryParams['mod']) && $queryParams['mod'] == 'created'){
            $mod = 'created';

        }

        if($mod == 'subscribed'){
            $query = Events::find()->where("event_id IN (select event_id from subscribers where user_id = $userId)
            and meeting_date > $time order by meeting_date DESC, event_id");
        }elseif($mod == 'created'){
            $query = Events::find()->where("user_id = $userId  and meeting_date > $time order by meeting_date DESC, event_id");
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 5,
            ],
        ]);
        return $this->render('user_events', ['dataProvider' => $dataProvider]);
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