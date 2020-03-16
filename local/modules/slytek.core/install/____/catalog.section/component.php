<?
$rand=$this->randString();
$arParams['RAND_STRING']=$rand;
if($_REQUEST['ajax-component']=='Y' && $rand==$_REQUEST['component'])$APPLICATION->RestartBuffer();
else echo '<div data-component="'.$rand.'">';
$GLOBALS['filter_'.$rand]=array();
if($arParams['FILTER_PROPERTY']=='HISTORY_VIEWS'){
	CModule::includeModule('catalog');
	CModule::includeModule('sale');
	$viewedIterator = Bitrix\Catalog\CatalogViewedProductTable::getList(array(
		'select' => array('PRODUCT_ID', 'ELEMENT_ID'),
		'filter' => array('=FUSER_ID' => (int)CSaleBasket::GetBasketUserID(false), '=SITE_ID' => SITE_ID),
		'order' => array('DATE_VISIT' => 'DESC'),
		'limit' => $arParams['PAGE_ELEMENT_COUNT']
	));
	while ($viewedProduct = $viewedIterator->fetch())
	{
		$GLOBALS['filter_'.$rand]['ID'][]=$viewedProduct['ELEMENT_ID'];
	}
}
else if($arParams['FILTER_PROPERTY']){
	$GLOBALS['filter_'.$rand]=array(
		'LOGIC'=>'AND',
		array('!PROPERTY_'.$arParams['FILTER_PROPERTY']=>false),
		array('!PROPERTY_'.$arParams['FILTER_PROPERTY']=>0), 
		array('!PROPERTY_'.$arParams['FILTER_PROPERTY']=>'false'), 
		array('!PROPERTY_'.$arParams['FILTER_PROPERTY']=>'N')
	);
}
if($_REQUEST['ids_'.$rand]){
	$ids=json_decode(($_REQUEST['ids_'.$rand]), true);
	$GLOBALS['filter_'.$rand]['!ID']=$ids;
	$arParams['IDS']=$_REQUEST['ids_'.$rand];
}
if($arParams['FILTER_NAME'] && $GLOBALS[$arParams['FILTER_NAME']]){
	$GLOBALS['filter_'.$rand]=array_merge($GLOBALS['filter_'.$rand], $GLOBALS[$arParams['FILTER_NAME']]); 
}
$arParams['FILTER_NAME']='filter_'.$rand;
if($arParams['BLOCK_TITLE']){
	$arParams['BLOCK_TITLE']=htmlspecialchars_decode($arParams['BLOCK_TITLE']);
}
if(defined('AJAX_PAGE') || $_REQUEST['ajax_get']){
	$arParams['AJAX_PAGE']=true;
}

$this->includeComponentTemplate();

if($_REQUEST['ajax-component']=='Y'&& $rand==$_REQUEST['component'] && $arParams['NODIE']!='Y')die();
elseif($_REQUEST['ajax-component']=='Y'&& $rand==$_REQUEST['component'] && $arParams['NODIE']=='Y')define('MUST_DIE', 1);
else echo '</div>';    

?>