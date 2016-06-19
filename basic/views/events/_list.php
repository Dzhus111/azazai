<?php 
    use yii\helpers\Url;
?>
<div class="event-element">
    <div class="people-image-wrapper">
        <div class="people-image-block">
            <img class="people-image"  src="<?php echo Url::home(true)?>images/people.png" alt="" width="70" />
        </div>
       <div class="people-count"><?php echo $model->subscribers_count; ?>/<?php echo $model->required_people_number; ?></div>
    </div>
    <div class="event-element-content">
        <a href="http://events.net/events/detail?id=<?php echo $model->event_id;?>">
            <?php echo $model->event_name; ?><br />
        </a>
        <div><?php echo $model->description; ?></div>
    </div>
    <div class="date-block"><?php echo strftime('%d %b %Y в %H:%M', $model->meeting_date); ?></div>
</div>


