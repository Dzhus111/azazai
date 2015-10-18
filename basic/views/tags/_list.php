<?php
use yii\helpers\Url;
?>
<div class="tag-element" style="border: solid 1px black; margin-bottom: 20px;">
    <a href="http://events.net/list/events?tagId=<?php echo $model->tag_id;?>">
        <p><?php echo $model->tag_name; ?> (<?php echo $model->events_count; ?>)</p><br />
    </a>
</div>

