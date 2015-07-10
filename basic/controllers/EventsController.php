<?php
namespace app\controllers;

use yii\helpers\Utils;
use app\models\Events;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;

class EventsController extends Controller
{
    public function actionDetail($id){
        $model = Events::find() ->where(['event_id' => $id])
                                ->one();
        return $this->render('event', [
            'model' => $model,
        ]);
    }
}