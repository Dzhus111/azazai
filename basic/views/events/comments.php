<?php
use yii\widgets\ListView;
use yii\helpers\Html;
use yii\helpers\Url;
?>

<?php
echo ListView::widget([
    'dataProvider' => $dataProvider,
    'itemView' => '_comment',
    'summary'=>'',
]);
?>