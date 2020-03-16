<?
if($_REQUEST['action']=='ADD2BASKET' && $_REQUEST['id']>0){
	CModule::includeModule('catalog');
	CModule::includeModule('iblock');
	$id = intval($_REQUEST['id']);
	$arProps=array();
	$res = CIBlockElement::GetList(Array(), Array( "ID"=>$id), false, array('nTopCount'=>1), Array("ID", "IBLOCK_ID", "NAME"));
	if($ob = $res->GetNextElement()){ 
		$arItem = $ob->GetFields();  
		$arItem['PROPERTIES'] = $ob->GetProperties();
		foreach(Icon::CART_PROPS as $prop){
			if($arItem['PROPERTIES'][$prop]['VALUE']){
				$arProps[]=array("NAME" => $arItem['PROPERTIES'][$prop]['NAME'], "CODE" => $arItem['PROPERTIES'][$prop]['CODE'], "VALUE" => $arItem['PROPERTIES'][$prop]['VALUE']);
			}
		}
	}
	$q=intval($_REQUEST['quantity']);
	if($q<1)$q=1;
	Add2BasketByProductID(intval($_REQUEST['id']), $q, $arProps);
	define('ACTION_TYPE', 'basket');	
}
elseif(($_REQUEST['action']=='deletebasket' || $_REQUEST['action']=='delay') && $_REQUEST['id']>0){
	CModule::includeModule('sale');
	$dbBasketItems = CSaleBasket::GetList(
		array(
			"NAME" => "ASC",
			"ID" => "ASC"
		),
		array(
			"FUSER_ID" => CSaleBasket::GetBasketUserID(),
			"LID" => SITE_ID,
			"ID" => intval($_REQUEST['id']),
			"ORDER_ID" => "NULL"
		),
		false,
		false,
		array("ID")
	);
	while ($arItem = $dbBasketItems->Fetch())
	{
		if($_REQUEST['action']=='delay'){
			CSaleBasket::Update($arItem['ID'], array(
				'DELAY'=>'Y',
			));
		}else{
			CSaleBasket::Delete($arItem['ID']);
		}
		CSaleBasket::Delete($arItem['ID']);
	}
}
elseif($_REQUEST['action']=='quantity' && $_REQUEST['id']>0 && $_REQUEST['quantity']>0 ){
	CModule::includeModule('sale');
	$quantity=intval($_REQUEST['quantity']);
	$dbBasketItems = CSaleBasket::GetList(
		array(
			"NAME" => "ASC",
			"ID" => "ASC"
		),
		array(
			"FUSER_ID" => CSaleBasket::GetBasketUserID(),
			"LID" => SITE_ID,
			"ID" => intval($_REQUEST['id']),
			"ORDER_ID" => "NULL"
		),
		false,
		false,
		array("ID")
	);
	while ($arItem = $dbBasketItems->Fetch())
	{
		CSaleBasket::Update($arItem['ID'], array(
			'QUANTITY'=>$quantity,
		));
		echo $arItem['ID'];
	}
}
if($_REQUEST['action']=='LIKE' || $_REQUEST['action']=='DISLIKE'){
	$ID=intval($_REQUEST['id']);
	if ($ID > 0 && \Bitrix\Main\Loader::includeModule('slytek.likeit')) {
		if($_REQUEST['action']=='LIKE')
			$arResult['STATUS'] = Slytek\Likeit\LikeTable::setLike($ID);
		else $arResult['STATUS'] = Slytek\Likeit\LikeTable::setDisLike($ID);
	}
	
}
else if($_REQUEST['action']=='ADD_TO_COMPARE_LIST' || $_REQUEST['action']=='DELETE_FROM_COMPARE_LIST'){
	$APPLICATION->IncludeComponent(
		"bitrix:catalog.compare.list", 
		"empty", 
		array(
			"ACTION_VARIABLE" => "action",
			"AJAX_MODE" => "N",
			"AJAX_OPTION_ADDITIONAL" => "",
			"AJAX_OPTION_HISTORY" => "N",
			"AJAX_OPTION_JUMP" => "N",
			"AJAX_OPTION_STYLE" => "N",
			"COMPARE_URL" => "/compare/",
			"DETAIL_URL" => "",
			"IBLOCK_ID" => "2",
			"IBLOCK_TYPE" => "catalog",
			"NAME" => "CATALOG_COMPARE_LIST",
			"POSITION" => "top left",
			"POSITION_FIXED" => "N",
			"PRODUCT_ID_VARIABLE" => "id",
			"COMPONENT_TEMPLATE" => "compare"
		),
		false
	);
	define('ACTION_TYPE', 'compare');
}else if($_REQUEST['action']=='FAVORITE'){
	$APPLICATION->IncludeComponent(
		"slytek:favorites.add",
		"",
		Array(),
		false
	);
	define('ACTION_TYPE', 'favorite');
}
include 'messages.php';
?>