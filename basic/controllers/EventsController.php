<?php
namespace app\controllers;
use Yii;
use yii\helpers\Utils;
use app\models\Events;
use app\controllers\ListController;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Comments;
use app\models\Users;
use app\models\Subscribers;

class EventsController extends Controller
{
    const SUBSCRIBED = 1;
    const UNSUBSCRIBED = 0;
    public function actionDetail($id){
        $statusActive = ListController::STATUS_ACTIVE;
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

}