<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
if(defined('ADMIN_SECTION'))return;
if($GLOBALS['USER']->IsAuthorized() && defined('NEED_AUTH')){
	define('PERSONAL_SECTION', 1);
}
if($GLOBALS['APPLICATION']->GetCurPage(true)==SITE_DIR.'index.php')define('INDEX', true);

include $_SERVER['DOCUMENT_ROOT'].SITE_TEMPLATE_PATH.'/include/head.php';

if($_REQUEST['ajax_get']){
	$componentPage=$_REQUEST['ajax_get'];	
	if(file_exists($_SERVER['DOCUMENT_ROOT'].$this->__template->__folder.'/actions.php')){
		include $_SERVER['DOCUMENT_ROOT'].$this->__template->__folder.'/actions.php';
	}
	switch ($componentPage) {
		case 'counters':
		case 'likes':
		case 'basket':
		case 'wishlist':
		$componentPage='counters';
		break;
		case 'comments':
		if($_REQUEST['ID']>0){
			define('ELEMENT_ID', intval($_REQUEST['ID']));
		}
		break;
		default: 
		if(stripos($componentPage, '-info')!==false){
			define('INFO_TYPE', str_ireplace('-info', '', $componentPage));
			$componentPage='info';
		}
		else if(stripos($componentPage, '-form')!==false){
			define('FORM_TYPE', str_ireplace('-form', '', $componentPage));
			$componentPage='forms';
		}
	}
}
if($componentPage){
	$temp_component = clone $this;
	$temp_component->__templatePage = $componentPage;
	$temp_template = new CBitrixComponentTemplate();
	$temp_template->Init($temp_component);
	$path = $temp_template->__file;
	unset($temp_component, $temp_template);
	if(file_exists($_SERVER['DOCUMENT_ROOT'].$path)){
		$APPLICATION->RestartBuffer();
		CHTTP::SetStatus("200 OK");
		$this->IncludeComponentTemplate($componentPage);
		if(!defined('CONTINUE_LOAD')){
			require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_after.php");
			die();
		}
	}
}