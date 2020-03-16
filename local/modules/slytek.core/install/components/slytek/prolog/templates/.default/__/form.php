<?
//if(intval($_REQUEST['ID'])==0)die();
define('ELEMENT_ID', intval($_REQUEST['ID']));
CModule::includeModule('iblock');
$res = CIBlockElement::GetList(Array(), Array("ID"=>IntVal(ELEMENT_ID), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y"), false, false, Array("ID", 'IBLOCK_ID', 'NAME'));
if($ob = $res->GetNextElement())
{
	$arFields=$ob->GetFields();
	$arFields['PROPERTIES']=$ob->GetProperties();
	if($arFields['PROPERTIES']['SHOW_FORM']['VALUE']!='Y'){
		die();
	}
}
$USER_FIELD_MANAGER = new CUserTypeManager();
$skip =array('UF_PAGE');
//if($arFields['PROPERTIES']['UPLOAD_FILE']['VALUE']!='Y')$skip[]='UF_FILE';
$arProps= $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_'.Atv::$forms['BLOCK_ID'], 0, 'ru');

if ($_POST['add_review']) {

	$vals=array();
	foreach($arProps as $arProp){
		$vals[$arProp['FIELD_NAME']]='';
	}
	//$vals['UF_PAGE']=ELEMENT_ID;
	$vals['UF_DATE']=ConvertTimeStamp(time(), 'FULL');
	if($_FILES){
		$file_ar=$_FILES["REVIEW"];
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
	foreach ($_POST['REVIEW'] as $NAME => $val) {
		$vals[htmlspecialcharsbx($NAME)] = htmlspecialcharsbx($val);
	}
	$arFields['FORM_DATA']='';
	foreach ($vals as $key => $value) {
		if($key=='UF_PAGE')continue;
		$arFields['FORM_DATA'].=$arProps[$key]['EDIT_FORM_LABEL'].': '.$value.'<br>';
	}
	$id = CSlytekHandler::HLAdd(Atv::$forms['BLOCK_ID'], $vals);
	if($id){
		$vals['NAME']=$arFields['NAME'];
		$vals['FORM_DATA']=$arFields['FORM_DATA'];
		$vals['URL']='/bitrix/admin/highloadblock_row_edit.php?ENTITY_ID='.Atv::$forms['BLOCK_ID'].'&ID='.$id.'&lang=ru';
		CEvent::Send("FEEDBACK_FORM", SITE_ID, $vals);
	}
}
unset($arProps['UF_DATE']);
?>
<?if($id):?>
<center>
	<div class="title-sm">Спасибо!</div>
	<p>Ваше отзыв принят!</p>
	<a class="u-link" href="javascript:void(0)" rel="modal:close">Закрыть</a>
</center>
<?else:?>
<div class="reviews-modal" id="reviews-modal" data-js="reviews-modal">
	<center>
		<div class="page-title">Оставить отзыв о магазине</div>
		<form class="feedback-form" method="post" enctype="multipart/form-data">
			<input type="hidden" name="ajax_get" value="<?=htmlspecialchars($_REQUEST['ajax_get'])?>">
			<input type="hidden" name="add_review" value="Y">
			<input type="hidden" name="ID" value="<?=ELEMENT_ID?>">

			<?
			$consent_fields=array();
			foreach($arProps as $arProp):
				if(in_array($arProp['FIELD_NAME'], $skip))continue;
				$consent_fields[]=$arProp['EDIT_FORM_LABEL'];
				if($arProp['FIELD_NAME']=='UF_NAME')$arProp['VALUE']=$USER->GetFullName();
				elseif($arProp['FIELD_NAME']=='UF_EMAIL')$arProp['VALUE']=$USER->GetEmail();
				if($arProp['SETTINGS']['ROWS']>1)$arProp['USER_TYPE_ID']='customhtml';

				$block_label=$arProp['HELP_MESSAGE'];
				if($arProp['USER_TYPE_ID']!='integer'):?>
					<div class="form__label">
						<div class="form__label-title"><?=$arProp['EDIT_FORM_LABEL']?></div>
						<div class="form__label-input">
							<?endif?>
							<?switch ($arProp['USER_TYPE_ID']):

							case 'iblock_element':
							?>
							<label><?=$arProp['EDIT_FORM_LABEL']?><?if($arProp['MANDATORY']=='Y'):?><span class="necessary">*</span><?endif?></label>
							<select class="<?if($arProp['MANDATORY']=='Y'):?>required<?endif?>" name="REVIEW[<?=$arProp['FIELD_NAME']?>]">
								<option value="">(не выбрано)</option>
								<?
								CModule::includeModule('iblock');
								$res = CIBlockElement::GetList(Array(), Array("IBLOCK_ID"=>IntVal($arProp['SETTINGS']['IBLOCK_ID']), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y"), false, false, Array("ID", 'IBLOCK_ID', 'NAME'));
								while($arFields = $res->GetNext())
								{
									?>
									<option value="<?=$arFields['ID']?>" <?=ELEMENT_ID==$arFields['ID']?'selected':''?>><?=$arFields['NAME']?></option>
									<?
								}
								?>
							</select>
							<?
							break;
							case 'file':
							?>
							<label><?=$arProp['EDIT_FORM_LABEL']?><?if($arProp['MANDATORY']=='Y'):?><span class="necessary">*</span><?endif?></label>
							<input type="file" class="<?if($arProp['MANDATORY']=='Y'):?>required<?endif?>" name="REVIEW[<?=$arProp['FIELD_NAME']?>]"/>
							<?
							break;
							case 'customhtml2':
							?>
							<textarea class="input <?if($arProp['MANDATORY']=='Y'):?>required<?endif?>" name="REVIEW[<?=$arProp['FIELD_NAME']?>]" placeholder="<?=$arProp['EDIT_FORM_LABEL']?><?if($arProp['MANDATORY']=='Y'):?>*<?endif?>"></textarea>
							<?
							break;
							case 'integer':
							?>
							<div class="form__rating">
								<div class="rating" data-js="rating">
									<div class="rating__head">
										<div class="rating__title">ваша оценка магазина</div>
										<div class="rating__balls" data-js="rating-balls">0 <span>баллов</span></div>
									</div>
									<div class="rating__list">
										<input class="rating__input" type="hidden" name="REVIEW[<?=$arProp['FIELD_NAME']?>]" value="10" />
										<div class="rating__circle" data-js="rating-circle"></div>
										<?for($i=1; $i<=10; $i++):?>
										<div class="rating__item <?=$i==10?'is-active':''?>" data-js="rating-item"><span><?=$i?></span></div>
										<?endfor?>
									</div>
								</div>
							</div>
							<?
							break;
							default:
							?>
							<input class="input <?if($arProp['MANDATORY']=='Y'):?>required<?endif?>" value="<?=$arProp['VALUE']?>" type="text" name="REVIEW[<?=$arProp['FIELD_NAME']?>]">
							<?
						endswitch?>
						<?if($arProp['USER_TYPE_ID']!='integer'):?>
					</div>
				</div>
				<?endif?>
				<?endforeach?>
				<div class="form__label">
					<div class="form__label-title"></div>
					<div class="form__label-input">
						<button class="btn" type="submit">Отправить</button>
						<div class="form__policy"><?$APPLICATION->IncludeComponent(
							"bitrix:main.userconsent.request", 
							"", 
							array(
								"AUTO_SAVE" => "Y",
								"ID" => 1,
								"IS_CHECKED" => "N",
								"IS_LOADED" => "Y",
								"INPUT_NAME" => "ACCEPT",
								"REPLACE" => array(
									"button_caption" =>'Отправить',
									"fields" => $consent_fields,
								),
								"COMPONENT_TEMPLATE" => ".default"
							),
							false
						);?></div>
					</div>
				</div>
			</form>
		</center>
	</div>
</div>
<?endif?>