<?php 
    use yii\widgets\ListView;
    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use yii\helpers\Url;    
?>
<div class="create-event">
    <a class="create-event-link" href="/list/create">Добавить событие</a>
</div>
<div class="search">
    <div class="events-search">

        <?php $form = ActiveForm::begin([
            'action' => ['events'],
            'method' => 'get',
        ]); ?>

        <input type="text" name="q" />

        <div class="form-group">
            <?= Html::submitButton('Поиск', ['class' => 'btn btn-primary']) ?>
        </div>

        <?php ActiveForm::end(); ?>

    </div>
</div>
<?php $pjax = \yii\widgets\Pjax::begin();?>
<div class="events-list">
    <?php 
        echo ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => '_list',
            'itemOptions' => ['class' => 'item'],
            'pager' => [
                'class' => \darkcs\infinitescroll\InfiniteScrollPager::className(),
                'paginationSelector' => '.pagination',
                'autoStart' => true,
                'pjaxContainer' => $pjax->id,
            ],
            'summary'=>false,
        ]); 
    ?>
</div>
</php \yii\widgets\Pjax::end();?>
<script type="text/javascript">
    $(function(){
        $('.list-view').on('infinitescroll:afterRetrieve', function(){
            $('.pagination').hide();
        });
        $('.list-view').on('infinitescroll:afterStart', function(){
            $('.pagination').hide();
        });

        $('.list-view').on('infinitescroll:afterStop', function(){
            $('.pagination').hide();
        });
    });
</script>