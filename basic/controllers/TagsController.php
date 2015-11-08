<?php
namespace app\controllers;
use Yii;
use yii\helpers\Utils;
use app\models\Tags;
use app\controllers\ListController;
use app\models\EventsModel;
use yii\web\Controller;
use yii\web\NotFoundHttpException;
use yii\filters\VerbFilter;
use yii\data\ActiveDataProvider;
use app\models\Comments;
use app\models\Users;
use app\models\Subscribers;

class TagsController extends Controller
{
    public function actionIndex(){
        $dbQuery = "events_count > 0 order by events_count DESC, tag_id";
        $query = Tags::find()->where($dbQuery);

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'pageSize' => 5,
            ],

        ]);
        $tags = $dataProvider->getModels();

        return $this->render('list', ['dataProvider' => $dataProvider, 'tags'=>$tags]);
    }
}