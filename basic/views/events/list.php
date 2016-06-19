<?php 
    use yii\widgets\ListView;
    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use yii\helpers\Url;
    $this->registerJsFile( '/js/listing.js' );
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
    <?php $queryParams = Yii::$app->request->queryParams;?>
    <div class="events-list">
        <?php
            echo ListView::widget([
                'dataProvider' => $dataProvider,
                'itemView' => '_list',
                'itemOptions' => ['class' => 'item'],
                'pager' => [
                    'class' => \kop\y2sp\ScrollPager::className(),
                    'noneLeftText' => '',
                    'triggerText' => 'Показать еще',
                    'historyPrev' => '.prev',
                    'triggerTemplate' => '<div class="load-more">{text}</div>'
                ],
                'summary'=>false,
            ]);
        ?>
    </div>
    <?php if (!empty($queryParams['page']) && (int)$queryParams['page'] > 1):?>
        <a class="prev" style="display: none" href="<?php echo Url::current(['page' => (int)$queryParams['page'] - 1])?>"></a>
    <?php endif;?>
</php \yii\widgets\Pjax::end();?>

<script type="text/javascript">
    $(function(){
        EventsList.changeImageWrapperHeight();
        $(window).resize(function(){
           EventsList.changeImageWrapperHeight();
        });
        $(document).ajaxComplete(function(){
            setTimeout("$(window).resize()", 400);
        });
    });
</script>

