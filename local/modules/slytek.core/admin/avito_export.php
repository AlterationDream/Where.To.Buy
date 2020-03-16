<?
/**
* @global CMain $APPLICATION
* @global CUser $USER
* */
if($_REQUEST['ajax_tree']){
	include 'helpers/ajax.php';
}
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");
if (!$USER->CanDoOperation('cache_control'))
{
	$APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
include 'helpers/sections.php';
include 'helpers/xml.php';

global $arSections;
global $arLocations;
function getOptions($mode, $name, $value){
	global $arSections;
	global $arLocations;
	switch ($mode) {
		case 'City':
		if(!$name){
			reset($arLocations);
			$name = key($arLocations);
		}
		$options = array_keys($arLocations[$name]['cities']);
		break;
		case 'Subway':
		if(!$name){
			reset($arLocations);
			$name = key($arLocations);
		}
		
		if($arLocations[$name]['subways']){
			$options=$arLocations[$name]['subways'];
		}
		else{
			foreach($arLocations as $region){
				if(!$name){
					reset($region['cities']);
					$name = key($region['cities']);
				}

				if($region['cities'][$name]['subways']){
					$options=$region[$name]['subways'];
				}
			}
		}

		break;
		case 'District':
		if(!$name){
			reset($arLocations);
			$name = key($arLocations);
		}
		
		if($arLocations[$name]['disctricts']){
			$options=$arLocations[$name]['disctricts'];
		}
		else{
			foreach($arLocations as $region){
				if(!$name){
					reset($region['cities']);
					$name = key($region['cities']);
				}
				if($region['cities'][$name]['disctricts']){
					$options=$region[$name]['disctricts'];
				}
			}
		}
		break;
		case 'TypeId':
		if(!$name){
			reset($arSections);
			$name = key($arSections);
		}
		echo $name;
		if($arSections[$name]){
			$options=$arSections[$name];
		}
		break;
	}
	$k=0;
	?><option value="">Не выбрано</option><?
	foreach($options as $key=>$section){
		$val=$k==$key?$section:$key;
		?><option <?=$value==$val?'selected':''?> value="<?=$val?>"><?=$section?></option><?
		$k++;
	}
}
if($_REQUEST['ajax_refresh'] && $_REQUEST['ajax_mode']){
	$GLOBALS['APPLICATION']->RestartBuffer();
	getOptions($_REQUEST['ajax_mode'], $_REQUEST['ajax_name'], $_REQUEST['ajax_value']);
	die();
	echo json_encode($arSec);
}

IncludeModuleLangFile(__FILE__);
global $DB;
CModule::IncludeModule('slytek.core');
CModule::IncludeModule('iblock');
$aTabs = array(
	array("DIV" => "main_settings", "TAB" => 'Настройки', "ICON"=>"", "TITLE"=>'Экспорт на Авито'),
	array("DIV" => "filters", "TAB" => 'Товары', "ICON"=>"", "TITLE"=>'Экспорт на Авито'),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;

$arParams=array(
	'AllowEmail'=>array(
		'title'=>'Возможность написать сообщение по объявлению через сайт — одно из значений списка',
		'type'=>'select',
		'options'=>array('Да', 'Нет')
	),
	'ManagerName'=>array(
		'title'=>'Имя менеджера, контактного лица компании по данному объявлению',
	),
	'ContactPhone'=>array(
		'title'=>'Контактный телефон ',
	),
	'Region'=>array(
		'title'=>'Регион, в котором находится объект объявления',
		'type'=>'select',
		'options'=>array_keys($arLocations),
		'mode'=>'Region'
	),
	'City'=>array(
		'title'=>'Город или населенный пункт, в котором находится объект объявления',
		'type'=>'select',
		'mode'=>'City',
		'parent'=>'Region'
	),
	'Subway'=>array(
		'title'=>'Ближайшая станция метро',
		'type'=>'select',
		'mode'=>'Subway',
		'parent'=>'Region'
	),
	'District'=>array(
		'title'=>'Район города',
		'type'=>'select',
		'mode'=>'District',
		'parent'=>'City'
	)
	
);
$arProductParams=array(
	'Category'=> array(
		'title'=>'Категория объявлений',
		'type'=>'select',
		'mode'=>'Category',
		'name'=>'avito[rows][<%=ID%>][Category]',
		'options'=>array_keys($arSections)
	),
	'TypeId'=> array(
		'title'=>'Подкатегория товара',
		'type'=>'select',
		'mode'=>'TypeId',
		'name'=>'avito[rows][<%=ID%>][TypeId]',
		'parent'=>'Category'
	)
);
global $arLocations;

if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST["Update"]=="Y" && check_bitrix_sessid())
{

	file_put_contents(__DIR__.'/../settings/avito_settings.dat', serialize($_REQUEST['avito']));

	LocalRedirect($APPLICATION->GetCurPage()."?LID=".$SITE_ID."&lang=".LANGUAGE_ID);
}   
$APPLICATION->SetTitle('Экспорт на Авито');  
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
CJSCore::Init(array('jquery'));
CBitrixComponent::includeComponentClass('bitrix:catalog.section');
//$script_path=CatalogSectionComponent::getSettingsScript('/bitrix/components/bitrix/catalog.section', 'filter_conditions');
//$GLOBALS['APPLICATION']->AddHeadScript($script_path);
$js_params=array('iblockId'=>2);
$js_mess=array('invalid'=>'');
include 'helpers/js.php';
function formatProps($arProp,$name, $arParams){
	if(!$arProp['name'])$arProp['name']='avito[settings]['.$name.']';
	switch ($arProp['type']) {
		case 'select':
		?>
		<select name="<?=$arProp['name']?>" data-mode="<?=$arProp['mode']?>" data-value="<?=$arProp['value']?>">

			<?if($arProp['options']):?>
			<?$k=0;foreach($arProp['options'] as $title=>$arOption):?>
			<?if(is_array($arOption)):?>
			<optgroup label="<?=$title?>">
				<?$k2=0;foreach($arOption as $name2=>$arOption2):
				$value2=$k2==$key?$arOption2:$name2;?>
				<option <?=$arProp['value']==$value2?'selected':''?> value="<?=$value2?>"><?=$arOption2?></option>
				<?$k2++;
			endforeach?>
		</optgroup>
		<?else:
		$value=$k==$title?$arOption:$title;?>
		<option <?=$arProp['value']==$value?'selected':''?> value="<?=$value?>"><?=$arOption?></option>
		<?endif;?>
		<?$k++;endforeach?>
		<?else:?>
		<?
		getOptions($arProp['mode'], $arParams[$arProp['parent']]['value'], $arProp['value']);
		?>
		<?endif?>
	</select>
	<?
	break;

	default:
	?>
	<input type="text" name="<?=$arProp['name']?>" value="<?=$arProp['value']?>">
	<?
	break;
}
}
$arValues=unserialize(file_get_contents(__DIR__.'/../settings/avito_settings.dat'));
foreach($arParams as $name=>$arProp){
	$arParams[$name]['value'] = $arValues['settings'][$name];
}
foreach($arParams as $name=>$arProp){
	$arParams[$name]['value'] = $arValues['settings'][$name];
}
?>
<style type="text/css">
.remove-td {
	width: 34px;
	padding-left: 10px;
}
.product-props{
	width: 200px;
}
select[data-mode]{
	width: 200px;
}
</style>
<form method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>" class="custom-inputs">
	<?=bitrix_sessid_post()?>
	<input type="hidden" name="Update" value="Y">
	<script type="text/template" data-skip-moving="true" id="row-template">
		<tr data-id="<%=ID%>">
			<td class="product-props"><?foreach($arProductParams as $name=>$arProp):?>
				<div><?=$arProp['title']?></div>
				<div>
					<?
					formatProps($arProp, $name, $arProductParams);
					?>
				</div>
			
			<?endforeach?></td>
			<td>
				<input type="hidden" class="input-row" name="avito[rows][<%=ID%>][value]" value="">
			</td>
			<td class="remove-td"><input type="button" title="+" onclick="removeRow(this);return false;" value="X"></td>
		</tr>
	</script>
	<?  
	$tabControl->Begin();
	$tabControl->BeginNextTab(); 
	?>
	<?foreach($arParams as $name=>$arProp):
	
	?>
	<tr>
		<td><?=$arProp['title']?></td>
		<td>
			<?
			formatProps($arProp, $name, $arParams);
			?>
		</td>
	</tr>
	<?endforeach?>
	<?
	$tabControl->BeginNextTab(); 
	?>

	<?foreach($arValues['rows'] as $k=>$row):?>
	<tr data-id="<?=$k?>">
		<?
		$arProductParamsCurrent=$arProductParams;
		foreach($arProductParamsCurrent as $name=>$arProp){
			$arProductParamsCurrent[$name]['value'] = $row[$name];
		}
		?><td class="product-props"><?
		foreach($arProductParamsCurrent as $name=>$arProp):
			$arProp['name']=str_ireplace('<%=ID%>', $k, $arProp['name']);
			?>
			<div><?=$arProp['title']?></div>
				
				<div>
					<?
					formatProps($arProp, $name,$arProductParamsCurrent);
					?>
				</div>
			
			<?endforeach?>
			</td><td>
				<input type="hidden" class="input-row" name="avito[rows][<?=$k?>][value]" value='<?=$row['value']?>'>
			</td>
			<td class="remove-td"><input type="button" title="+" onclick="removeRow(this);return false;" value="X"></td>
		</tr>
		<?endforeach?>
		<tr class="last-row"><td colspan="<?=count($arProductParams)+1?>"><input type="button" title="+" onclick="addRow();return false;" value="Добавить выборку товаров"></td></tr>
		<?

		$tabControl->Buttons();
		?>
		<input type="submit" name="apply" value="<?echo GetMessage("admin_lib_edit_save")?>" title="<?echo GetMessage("admin_lib_edit_savey_title")?>" class="adm-btn-save">
		<?
		$tabControl->End();
		$tabControl->ShowWarnings("form1", $message);
		?>
	</form>


	<?
	if(!defined('ADMIN_PAGE'))require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
	?>
