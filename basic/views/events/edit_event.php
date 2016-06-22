<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Events */

$this->title = 'Обновление события';
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/bootstrap-tagsinput.js',  ['position' => yii\web\View::POS_END]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/add-new-form.js',  ['position' => yii\web\View::POS_END]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/bootstrap-tagsinput.css');
?>

<div class="events-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', [
        'model'     => $event,
        'tagsStr'   => $tagsStr,
        'action'    => '/events/editSave',
//        'eventId'   => $event->event_id
//        'errors' => $errors
    ]) ?>
</div>