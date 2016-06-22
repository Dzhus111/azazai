<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Tags;
use app\models\Events;
use app\helpers\Media;
use yii\helpers\Url;
use dosamigos\datetimepicker\DateTimePicker;

/* @var $this yii\web\View */
/* @var $model app\models\Events */
/* @var $form yii\widgets\ActiveForm */
?>
<?php //if($error == 'incorrectDate'): ?>
<!--    <div>-->
<!--        Дата встречи должна быть не реньше текущей даты-->
<!--    </div>-->
<?php //endif;?>
<?php $images = Media::getIconsList();?>
<?php $imagesDirName = '/icon/';?>

<div class="events-form">
    <?php $form = ActiveForm::begin([
        'action'    => $action,
        'options'   => [
            'id' => 'event-form',
    ]]); ?>

    <?= $form->field($model, 'event_name')->textInput(['maxlength' => true]) ?>
    <div class="form-group field-events-event_type">
        <label class="control-label">Общедоступный</label>
        <input type="radio" value="public" <?php if(!$model->event_type || $model->event_type == Events::EVENT_TYPE_PUBLIC):?>checked="checked"<?php endif;?> name="type" class="form-control">
        <label class="control-label">Приватный</label>
        <input type="radio" value="private" <?php if($model->event_type && $model->event_type == Events::EVENT_TYPE_PRIVATE):?>checked="checked"<?php endif;?> name="type" class="form-control">
    </div>

    <?= $form->field($model, 'description')->textarea(['rows' => 6]) ?>
    <?= $form->field($model, 'address')->textInput() ?>
    <?= $form->field($model, 'required_people_number')->textInput() ?>

    <label>Дата и время встречи</label>
    <?= DateTimePicker::widget([
    'model' => $model,
    'attribute' => 'meeting_date',
    'language' => 'ru',
    'size' => 'ms',
    'clientOptions' => [
        'autoclose' => true,
        'format' => 'dd-mm-yyyy  hh:ii',
        'todayBtn' => true
    ]
    ]);?>

    <div>
        <label>Иконка</label>
        <div>
            <img id="selected-icon" src="/icon/0.png" width="70" height="70" alt="">
        </div>
        <div>
            <span id="icon-status">
                Поменять
            </span>
            <div id="icons-container" style="display: none;">
                <ul>
                    <?php foreach($images as $imageFileName):?>
                        <ol class="add-new-icon" data-value="<?php echo $imageFileName?>">
                            <img src="<?php echo $imagesDirName . $imageFileName?>" alt="" width="70" height="70">
                        </ol>
                    <?php endforeach;?>
                </ul>
            </div>
        </div>
        <input id="icon-input" type="hidden" name="icon" value="0"/>
    </div>

    <?php if(isset($model->event_id) && $model->event_id):?>
        <input type="hidden" value="<?php echo $model->event_id?>" name="id">
    <?php endif;?>

    <label>Теги</label>
    <input id="events-tags" value="<?php echo (isset($tagsStr)) ? $tagsStr : ""?>" type="text" name="tags" class="form-control" data-role="tagsinput"/>
    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>
</div>
