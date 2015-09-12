<?php

namespace app\controllers;

use Yii;
use yii\db\Exception;
use yii\helpers\Url;
use yii\helpers\OAuthVK;
use yii\helpers\Utils;
use app\models\Events;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use yii\web\Session;
use app\models\TagsEvents;
use app\models\Subscribers;
use app\models\Tags;

/**
 * ListController implements the CRUD actions for Events model.
 */
class ListController extends Controller
{
    const STATUS_ACTIVE = 1;
    private $userId = null;

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
            OAuthVK::goToAuth('list/create');
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
     
     public function actionEvents(){
        $statusActive = self::STATUS_ACTIVE;
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

    /**
     * Creates a new Events model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     * @return mixed
     */
    public function actionCreate(){

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
                $tags =  array();
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
                $model->search_text = $post['Events']['event_name'] . " "
                    . $post['Events']['description'] . " " .
                    str_replace(',', ' ', $post['tags']);

                try{
                    if($model->save(false)){;
                        $subscribersModel = new Subscribers;
                        $subscribersModel->event_id = $model->event_id;
                        $subscribersModel->user_id = $model->user_id;
                        $subscribersModel->save(false);

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
