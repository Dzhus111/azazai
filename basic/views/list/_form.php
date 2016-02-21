<?php

use yii\helpers\Html;
use yii\widgets\ActiveForm;
use app\models\Tags;
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
<div class="events-form">

    <?php $form = ActiveForm::begin(); ?>

    <?= $form->field($model, 'event_name')->textInput(['maxlength' => true]) ?>
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
        'format' => 'dd-mm-yyyy   hh:ii',
        'todayBtn' => true
    ]
    ]);?>
    <label>Теги</label>
    <input id="events-tags" type="text" name="tags" class="form-control"/>

    <div class="form-group">
        <?= Html::submitButton($model->isNewRecord ? 'Create' : 'Update', ['class' => $model->isNewRecord ? 'btn btn-success' : 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
