<?php 
    use yii\widgets\ListView;
    use yii\helpers\Html;
    use yii\widgets\ActiveForm;
    use yii\helpers\Url;    
?>
<div class="search">
    <div class="events-search">

    <?php $form = ActiveForm::begin([
        'action' => ['events'],
        'method' => 'get',
    ]); ?>

    <input type="text" name="q" />

    <div class="form-group">
        <?= Html::submitButton('Search', ['class' => 'btn btn-primary']) ?>
    </div>

    <?php ActiveForm::end(); ?>

</div>
</div>
<div class="events-list">
    <?php 
        echo ListView::widget([
            'dataProvider' => $dataProvider,
            'itemView' => '_list',
            'summary'=>'',
        ]); 
    ?>
</div>