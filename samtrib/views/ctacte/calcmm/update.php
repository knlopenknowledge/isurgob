<?php

use yii\helpers\Html;
use app\utils\db\Fecha;

/* @var $this yii\web\View */
/* @var $model app\models\ctacte\CalcMm */

$title = 'Modificar Módulo Municipal ';
$this->params['breadcrumbs'][] = ['label' => 'Configuraciones', 'url' => '/samtest/index.php?r=site/config'];
$this->params['breadcrumbs'][] = ['label' => 'Módulos Municipales', 'url' => ['index']];
$this->params['breadcrumbs'][] = $title;

?>
<div class="calc-mm-update">

    <h1><?= Html::encode($title) ?></h1>
       <br>

    <?= $this->render('_form', [
        'model' => $model,
        'consulta' => 3,
    ]) ?>


</div>
