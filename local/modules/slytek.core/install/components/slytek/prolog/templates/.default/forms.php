<?
$hid = Aqua::id[FORM_TYPE];
if(!$hid)die();
$arParams = \Slytek\HL::fields($hid);
$skip = array('UF_DATE');
if ($_SERVER["REQUEST_METHOD"] == "POST" && $_POST['AS']=='Y' && check_bitrix_sessid()) {
	$vals=array();
	foreach($arParams['ITEMS'] as $arProp){
		$vals[$arProp['FIELD_NAME']]='';
	}
	//$vals['UF_PAGE']=ELEMENT_ID;
	$vals['UF_DATE']=ConvertTimeStamp(time(), 'FULL');

	if($_FILES){
		$file_ar=$_FILES["FORM"];
		if($file_ar){
			foreach($arProps as $key=>$arProp){
				if($file_ar['name'][$key] && $file_ar['error'][$key]==0){
					$vals[$key]=array(
						'name'=>$file_ar['name'][$key],
						'type'=>$file_ar['type'][$key],
						'tmp_name'=>$file_ar['tmp_name'][$key],
						'error'=>$file_ar['error'][$key],
						'size'=>$file_ar['size'][$key],
					);
				}
			}
		}
	}
	foreach ($_POST['FORM'] as $NAME => $val) {
		$vals[htmlspecialcharsbx($NAME)] = htmlspecialcharsbx($val);
	}
	$arFields['FORM_DATA']='';
	foreach ($vals as $key => $value) {
		if($key=='UF_PAGE')continue;
		$arFields['FORM_DATA'].=$arProps[$key]['EDIT_FORM_LABEL'].': '.$value.'<br>';
	}
	$id = \Slytek\HL::add($hid, $vals);
	if($id){
		$vals['NAME']=$arFields['NAME'];
		$vals['FORM_DATA']=$arFields['FORM_DATA'];
		$vals['URL']='/bitrix/admin/highloadblock_row_edit.php?ENTITY_ID='.$hid.'&ID='.$id.'&lang=ru';
		CEvent::Send("FEEDBACK_FORM", SITE_ID, $vals);
	}
}

?>
<div class="popup__title"><?=$arParams['NAME']?></div>
<div class="popup__content">
	<form class="form" method="post" enctype="multipart/form-data" onsubmit="SlytekHandler.as(this)">
		<?if($id):?>
		<label>Спасибо. Ваше запрос принят!</label>
		<?else:?>
		<?=bitrix_sessid_post()?>
		<input type="hidden" name="ajax_get" value="<?=htmlspecialchars($_REQUEST['ajax_get'])?>">
		<?
		foreach($arParams['ITEMS'] as $arProp):
			if(in_array($arProp['FIELD_NAME'], $skip))continue;
			if($arProp['FIELD_NAME']=='UF_NAME')$arProp['VALUE']=$USER->GetFullName();
			elseif($arProp['FIELD_NAME']=='UF_EMAIL')$arProp['VALUE']=$USER->GetEmail();
			if($arProp['SETTINGS']['ROWS']>1)$arProp['USER_TYPE_ID']='customhtml';

			$placeholder = $arProp['EDIT_FORM_LABEL'].($arProp['MANDATORY']=='Y'?'*':'')
			?>
			<label>
				<?
				switch ($arProp['USER_TYPE_ID']):
					case 'customhtml':
					?>
					<textarea class="<?if($arProp['MANDATORY']=='Y'):?>required<?endif?>" name="FORM[<?=$arProp['FIELD_NAME']?>]" placeholder="<?=$placeholder?>"><?=$arProp['VALUE']?></textarea>
					<?
					break;
					default:
					?>
					<input class="input <?if($arProp['MANDATORY']=='Y'):?>required<?endif?>" name="FORM[<?=$arProp['FIELD_NAME']?>]" value="<?=$arProp['VALUE']?>" placeholder="<?=$placeholder?>" type="text" >
					<?
				endswitch;
				?>
			</label>
			<?
		endforeach;
		?>
		<input class="btn btn--blue" type="submit" name="submit" value="Отправить">
		<?endif?>
	</form>
</div>