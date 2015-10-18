<?php
    use yii\helpers\Url;
    use yii\widgets\Pjax;
    use yii\helpers\Html;
?>
<?php $id = Yii::$app->getRequest()->getQueryParam('id')?>
<div class="custom-event">
    <?php echo $model->event_name; ?><br />
    <p><?php echo $model->description; ?></p>
    <p>Адрес: <?php echo $model->address; ?></p>
    <p>Люди: <?php echo $model->subscribers_count; ?>/<?php echo $model->required_people_number; ?></p>
    <p>Дата: <?php echo $model->meeting_date; ?></p>
    <p>User: <?php echo $model->user_id; ?></p>
</div>

<div class="subscribe-container">
    <?php $subscribeBtnText = 'Пойду' ?>
    <?php if($subscribeStatus === 1): ?>
        <?php $subscribeBtnText = 'Я передумал(а)' ?>
        <div> Вы указали что пойдете на это событие</div>
    <?php endif;?>
    <?php echo Html::beginForm(['events/detail?id='.$id], 'post', ['data-pjax' => '', 'class' => 'form-inline']); ?>
    <?php echo Html::hiddenInput('subscribe', 1); ?>
    <?php echo Html::submitButton("$subscribeBtnText", ['class' => 'btn btn-lg btn-primary', 'name' => 'subscribeButton']) ?>
    <?php echo Html::endForm() ?>
</div>
<?php Pjax::begin(); ?>
<div class="comments-container">
    <p>Комментарии</p>
    <?php if(Yii::$app->session->get('userId')):?>
    <div class="add-comment-form">
        <?= Html::beginForm(['events/detail?id='.$id], 'post', ['data-pjax' => '', 'class' => 'form-inline']); ?>
        <?= Html::input('text', 'comment', '', ['class' => 'form-control']) ?>
        <?= Html::submitButton('Добавить комментарий', ['class' => 'btn btn-lg btn-primary', 'name' => 'commentButton']) ?>
        <?= Html::endForm() ?>
    </div>
    <?php endif;?>
        <div class="comments-list">
            <?echo $this->render( 'comments', ['dataProvider' => $commentsDataProvider]); ?>
        </div>
</div>
<?php Pjax::end(); ?>