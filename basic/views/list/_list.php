<?php 
    use yii\helpers\Url;
?>
<div class="event-element" style="border: solid 1px black; margin-bottom: 20px;">

    <a href="http://events.net/events/detail?id=<?php echo $model->event_id;?>">
        <?php echo $model->event_name; ?><br />
    </a>
    <p><?php echo $model->description; ?></p>
    <p>Адрес: <?php echo $model->address; ?></p>
    <p>Люди: <?php echo $model->subscribers_count; ?>/<?php echo $model->required_people_number; ?></p>
    <p>Дата: <?php echo $model->meeting_date; ?></p>

</div>


