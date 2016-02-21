<?php
/**
 * Created by PhpStorm.
 * User: dzhus
 * Date: 07.02.16
 * Time: 21:51
 */

use yii\widgets\ActiveForm;
?>
<?php echo Yii::$app->session->getFlash('upload'); ?>
<?php $form = ActiveForm::begin(['options' => ['enctype' => 'multipart/form-data']]) ?>

    <?php echo $form->field($model, 'imageFile')->fileInput() ?>
    <?php echo $form->field($model, 'tag')->textInput()?>

    <button>Submit</button>

<?php ActiveForm::end() ?>