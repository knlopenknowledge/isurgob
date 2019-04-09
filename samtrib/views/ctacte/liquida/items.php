<?php
use yii\helpers\Html;
use app\utils\db\utb;
use \yii\widgets\Pjax;
use app\models\ctacte\Liquida;

//determina si se debe guardar en la tabla temporal (liquidaciones)
$guardarTemporal = isset($guardarTemporal) ? $guardarTemporal : true;

//si no se debe guardar en la tabla temporal, pjax a ejecutar y enviar los datos del item
$selectorPjax = isset($selectorPjax) ? $selectorPjax : '';

if (!isset($liq_id)) $liq_id = 0;
if (!isset($subcta)) $subcta = 0;

echo '<input type="text" name="txopera" id="txopera" style="display:none">';
echo '<input type="text" name="txorden" id="txorden" style="display:none">';

if (isset($opera)) echo "<script>$('#txopera').val('')</script>";
?>
<style>
#EditarItem .modal-content{width:630px;}
</style>

<script>
itemHayError= false;
</script>
<table border='0'>
<tr>
	<td colspan='7'>
		<label>Item: </label>
    	<?php

    		$cond = "item_id in (select item_id from item_vigencia where ".($anio*1000+$cuota);
    		$cond .= " between perdesde and perhasta) ";
    		$cond .= " and Trib_Id=".$trib_id." and tipo not in (2,3,7)";

    		echo Html::dropDownList('dlItem', null, utb::getAux('v_item','item_id',"(nombre || ' - ' || tipo_nom)",0,$cond),
    					['prompt'=>'Seleccionar...', 'class' => 'form-control', 'style' => 'width:300px','id'=>'dlItem',
							'onchange' => '$.pjax.reload({container:"#ActControlesItem",data:{item:$(this).val(),trib:'.$trib_id.',opera:$("#txopera").val()},method:"POST"})']);

    		Pjax::begin(['id' => 'ActControlesItem']);
    			$item_id = intval(Yii::$app->request->post('item', 0));
    			$opera = Yii::$app->request->post('opera', 'Agrega');
    			$trib_id = intval(Yii::$app->request->post('trib', $trib_id));

    			$array = (new Liquida)->CargarItem($item_id,$trib_id,$anio,$cuota);

    			?>
    			<script type="text/javascript">
					$("#txTotalItem").val("");
				<?php

				if (count($array) > 0){
					?>
						$("#lbParam1").html("<?= $array['paramnombre1']; ?>");
						$("#lbParam2").html("<?= $array['paramnombre2']; ?>");
						$("#lbParam3").html("<?= $array['paramnombre3']; ?>");
						$("#lbParam4").html("<?= $array['paramnombre4']; ?>");
						$("#txObsItems").val("<?= $array['obs']; ?>");
					<?php


					if ($array['tcalculo'] == 12){
						?>
							$("#txParam1").val("<?= (new Liquida())->VarSistema($array['paramnombre1']) ?>");
							$("#txParam1").prop("disabled", true);
						<?php

					}else if ($array['tcalculo'] == 15){
						?>
							$("#txParam1").val("<?= (new Liquida())->GetTotalItems_Temp($liq_id); ?>");
							$('#txParam1').prop('disabled', true);
						<?php

					}else{
						?>
						$('#txParam1').prop('disabled', false);
						<?php
					}
				}

				if ($opera == "Agrega" && count($array) > 0){

					if ($array['paramnombre1'] == "" && $array['paramnombre2'] == "" && $array['paramnombre3'] == "" && $array['paramnombre4'] == ""){

						$error = '';
						$monto = (new Liquida())->CalcularItem($error,$item_id,$anio,$cuota,$array['paramcomp1'],$array['paramcomp2'],$array['paramcomp3'],$array['paramcomp4']);
						?>
						$("#txTotalItem").val("<?= $monto; ?>");
						<?php
					}else{
						?>
						$('#txTotalItem').val('')
						<?php
					}
				}

    			?>

    			$("#txParam1").css("visibility",(($("#lbParam1").html()!=="") ? "visible" : "hidden"));
    			$("#txParam2").css("visibility",(($("#lbParam2").html()!=="") ? "visible" : "hidden"));
    			$("#txParam3").css("visibility",(($("#lbParam3").html()!=="") ? "visible" : "hidden"));
    			$("#txParam4").css("visibility",(($("#lbParam4").html()!=="") ? "visible" : "hidden"));
    			$("#dlItem").attr("disabled",($("#txopera").val()!==""));
    			$("#txParam1").attr("disabled",($("#txopera").val()=="Elim"));
    			$("#txParam2").attr("disabled",($("#txopera").val()=="Elim"));
    			$("#txParam3").attr("disabled",($("#txopera").val()=="Elim"));
    			$("#txParam4").attr("disabled",($("#txopera").val()=="Elim"));
    			$("#btCalcularItem").attr("disabled",($("#txopera").val()=="Elim"));
    			</script>
    			<?php

    		Pjax::end();

    		Pjax::begin(['id' => 'Calcular']);
    			if (isset($_POST['item']))
    			{
    				$item_id = $_POST['item'];
    				$param1 = Yii::$app->request->post('param1', 0);
    				$param2 = Yii::$app->request->post('param2', 0);
    				$param3 = Yii::$app->request->post('param3', 0);
    				$param4 = Yii::$app->request->post('param4', 0);
					$venc = Yii::$app->request->post('venc', '');
    				$accion = $_POST['accion'];
    				$liq_id = $_POST['liq'];

    				$opera = ((isset($_POST['opera']) && $_POST['opera'] !== '') ? $_POST['opera'] : 'Agrega');
    				$orden = ((isset($_POST['orden']) && $_POST['orden'] !== '') ? $_POST['orden'] : 0);

    				$error = '';

    				$monto = (new Liquida)->CalcularItem($error,$item_id,$anio,$cuota,$param1,$param2,$param3,$param4);

    				$monto = number_format( $monto, 2, '.', '' );
					$valor_mm = (new Liquida)->CalcularMM($venc);

					echo "<script>$('#txModuloItem').val('".($monto*$valor_mm)."');</script>";
					echo "<script>$('#txTotalItem').val('".$monto."');</script>";

					if ($error !== '')
					{
						echo '<script>mostrarErrores( ["' . $error . '"], "#liquida_items_errorSummary" );</script>';

					}else{

						//Ocultar div errores
						echo '<script>$( "#liquida_items_errorSummary" ).css( "display", "none" );</script>';

						if ($accion == "aceptar")
						{
							$error = '';

							if($guardarTemporal){
								if ($opera == "Agrega") $error = (new Liquida())->NuevoItem($liq_id,$item_id,$param1,$param2,$param3,$param4,$monto,$trib_id,$anio,$cuota,$obj_id,$subcta);
								if ($opera == "Modif") $error = (new Liquida())->ModificarItem($liq_id,$item_id,$orden,$param1,$param2,$param3,$param4,$monto,$trib_id,$anio,$cuota,$obj_id,$subcta);
								if ($opera == "Elim") $error = (new Liquida())->BorrarItem($liq_id,$item_id,$orden,$trib_id,$anio,$cuota,$obj_id,$subcta);

							}

							if ($error != null && $error !== '')
							{
								?>
								<script>
									mostrarErrores( ["<?= $error ?>"], "#liquida_items_errorSummary" );
								itemHayError= true;
								</script>
								<?php

							}else {
								//Ocultar div errores
								echo '<script>$( "#liquida_items_errorSummary" ).css( "display", "none" );</script>';
							}
						}
					}
    			}
    		echo Html::hiddenInput(null, null);
    		Pjax::end();
    	?>
	</td>
</tr>
<tr>
	<td>
		<label id='lbParam1'></label><br>
		<?= Html::input('text', 'txParam1', null, ['class' => 'form-control','id'=>'txParam1', 'maxlength' => 12,  'style'=>'width:90px;']); ?>
	</td>
	<td>
		<label id='lbParam2'></label><br>
		<?= Html::input('text', 'txParam2', null, ['class' => 'form-control','id'=>'txParam2','maxlength' => 12,  'style'=>'width:90px;']); ?>
	</td>
	<td>
		<label id='lbParam3'></label><br>
		<?= Html::input('text', 'txParam3', null, ['class' => 'form-control','id'=>'txParam3','maxlength' => 12,  'style'=>'width:90px;']); ?>
	</td>
	<td>
		<label id='lbParam4'></label><br>
		<?= Html::input('text', 'txParam4', null, ['class' => 'form-control','id'=>'txParam4','maxlength' => 12,  'style'=>'width:90px;']); ?>
	</td>
	<td valign='bottom'>
		<?= Html::Button('Calcular',['class' => 'btn btn-primary', 'id' => 'btCalcularItem', 'onClick' => 'btCalcularAgregar("calcular")']) ?>
	</td>
	<td>
		<label>Módulos</label><br>
		<?= Html::input('text', 'txTotal', null, ['class' => 'form-control','id'=>'txTotalItem','disabled'=>'true', 'style'=>'width:90px;background:#E6E6FA;text-align: right']); ?>
	</td>
	<td>
		<label>Total</label><br>
		<?= Html::input('text', 'txModulo', null, ['class' => 'form-control','id'=>'txModuloItem','disabled'=>'true', 'style'=>'width:90px;background:#E6E6FA;text-align: right']); ?>
	</td>
</tr>
<tr>
	<td colspan='7'>
		<?= Html::textarea('txObsItems', null, ['class' => 'form-control','id'=>'txObsItems','disabled'=>'true','style'=>'width:600px;height:50px;max-width:600px;max-height:100px;background:#E6E6FA']); ?>
	</td>
</tr>
<tr>
	<td colspan='7'>
		<?= Html::Button('Aceptar',['class' => 'btn btn-success', 'onClick' => 'ejecutar();']) ?>
		&nbsp;
		<?= Html::Button('Cancelar',['class' => 'btn btn-primary', 'onClick' => '$("#EditarItem, .window").modal("toggle");']) ?>
	</td>
</tr>
<tr>
	<td colspan='7'>
		<br><div id="error" style="display:none;" class="alert alert-danger alert-dismissable"></div>
	</td>
</tr>
</table>

<div id="liquida_items_errorSummary" class="error-summary" style="display:none;margin-top: 8px;margin-right: 15px">

</div>

<script>

function ejecutar(){

	btCalcularAgregar("aceptar");

	$("#Calcular").on("pjax:complete", function(){

		if(!itemHayError){
			<?php
			if($guardarTemporal){
			?>
			$.pjax.reload({container: "<?= $selectorPjax; ?>",replace: false, push: false, data:{"liq":<?= $liq_id ?>,"venc" : $("#fchvenc").val()},method:"POST"});
			<?php
			} else {
				?>

				item_id = $("#dlItem").val();
				par1 = (($("#txParam1").val()=='') ? 0 : $("#txParam1").val());
				par2 = (($("#txParam2").val()=='') ? 0 : $("#txParam2").val());
				par3 = (($("#txParam3").val()=='') ? 0 : $("#txParam3").val());
				par4 = (($("#txParam4").val()=='') ? 0 : $("#txParam4").val());
				monto = $("#txTotalItem").val();
				item_nom = $("#dlItem option:selected").text();


				$.pjax.reload({
					container : "<?= $selectorPjax ?>",
					type : "GET",
					replace : false,
					push : false,
					data : {
						"param1" : par1,
						"param2" : par2,
						"param3" : par3,
						"param4" : par4,
						"item_id" : item_id,
						"monto" : monto,
						"item_nom" : item_nom
					},
					timeout : 2000
				});
				<?php
			}
		?>

		$("#EditarItem, .window").modal('toggle');
		}

		$("#Calcular").off("pjax:complete");
		itemHayError= false;
	});


}

function btCalcularAgregar(acc)
{
	if ($("#dlItem").val() == 0)
	{
		mostrarErrores( ['Seleccione un Ítem'], "#liquida_items_errorSummary" );

	}else {

		//Ocultar div errores
		$( "#liquida_items_errorSummary" ).css( "display", "none" );

		item_id = $("#dlItem").val();
		par1 = (($("#txParam1").val()=='') ? 0 : $("#txParam1").val());
		par2 = (($("#txParam2").val()=='') ? 0 : $("#txParam2").val());
		par3 = (($("#txParam3").val()=='') ? 0 : $("#txParam3").val());
		par4 = (($("#txParam4").val()=='') ? 0 : $("#txParam4").val());

		$.pjax.reload(
		{
			container:"#Calcular",
			data:{
				item:item_id,
				param1:par1,
				param2:par2,
				param3:par3,
				param4:par4,
				accion:acc,
				opera:$("#txopera").val(),
				orden:$("#txorden").val(),
				liq:<?=$liq_id?>,
				venc:$("#fchvenc").val()
				},
			method:"POST",
			timeout: 2000
		})

	}
}

// funcion que se ejecuta cuando se abre el modal
$('#EditarItem').on('shown.bs.modal', function () {

	//Ocultar div errores
	$( "#liquida_items_errorSummary" ).css( "display", "none" );

	$("#dlItem").attr("disabled",($("#txopera").val()!==""));
	$("#txParam1").attr("disabled",($("#txopera").val()=="Elim"));
	$("#txParam2").attr("disabled",($("#txopera").val()=="Elim"));
	$("#txParam3").attr("disabled",($("#txopera").val()=="Elim"));
	$("#txParam4").attr("disabled",($("#txopera").val()=="Elim"));
	$("#btCalcularItem").attr("disabled",($("#txopera").val()=="Elim"));

	<?php
	if($trib_id > 0){
		?>
		$.pjax.reload({container:"#ActControlesItem",data:{item:$("#dlItem").val(),trib:"<?=$trib_id?>",opera:$("#txopera").val()},method:"POST"});
		<?php
	}
	?>

});


</script>
