<?php
use yii\widgets\ListView;
use yii\helpers\Html;
use yii\widgets\ActiveForm;
use yii\helpers\Url;
?>

<?php $pjax = \yii\widgets\Pjax::begin();?>
<div class="tags-list">
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