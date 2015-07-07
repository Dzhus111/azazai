<?php use yii\helpers\Url;?>
<div style="border: solid 1px black;">

<a href="http://events.net/events/detail?id=<?php echo $model->event_id;?>">

<?php echo $model->event_name; ?><br />
<?php echo $model->description; ?><br />
</a>
</div>


