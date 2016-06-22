<?php

use yii\helpers\Html;


/* @var $this yii\web\View */
/* @var $model app\models\Events */

$this->title = 'Создайте событие';
$this->params['breadcrumbs'][] = ['label' => 'Events', 'url' => ['index']];
$this->params['breadcrumbs'][] = $this->title;
//$this->registerJsFile(Yii::$app->request->baseUrl . '/js/bootstrap-tagsinput-angular.js',  ['position' => yii\web\View::POS_END]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/bootstrap-tagsinput.js',  ['position' => yii\web\View::POS_END]);
$this->registerJsFile(Yii::$app->request->baseUrl . '/js/add-new-form.js',  ['position' => yii\web\View::POS_END]);
$this->registerCssFile(Yii::$app->request->baseUrl . '/css/bootstrap-tagsinput.css');
?>

<script type="text/javascript">
    $(function(){
        $('#test').tagsinput('refresh');
    });

</script>
<div class="events-create">
    <h1><?= Html::encode($this->title) ?></h1>
    <?= $this->render('_form', [
        'model'     => $model,
        'action'    => '/events/create'
//        'errors' => $errors
    ]) ?>
</div>
