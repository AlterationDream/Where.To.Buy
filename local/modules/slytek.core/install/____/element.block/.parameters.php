<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!CModule::IncludeModule("iblock"))
	return;

$arTypes = CIBlockParameters::GetIBlockTypes();

$arIBlocks=array();
$db_iblock = CIBlock::GetList(array("SORT"=>"ASC"), array("SITE_ID"=>$_REQUEST["site"], "TYPE" => ($arCurrentValues["IBLOCK_TYPE"]!="-"?$arCurrentValues["IBLOCK_TYPE"]:"")));
while($arRes = $db_iblock->Fetch())
	$arIBlocks[$arRes["ID"]] = "[".$arRes["ID"]."] ".$arRes["NAME"];
if($arCurrentValues["IBLOCK_ID"]>0){
	$arElements=array();
	$arFilter = Array("IBLOCK_ID"=>IntVal($arCurrentValues["IBLOCK_ID"]), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
	$res = CIBlockElement::GetList(Array(), $arFilter, false, false, Array('ID', 'NAME'));
	while($arFields = $res->GetNext())
	{
		$arElements[$arFields["ID"]] = "[".$arFields["ID"]."] ".$arFields["NAME"];
	}
}

$arProperty_LNS = array();
if($arCurrentValues['GET_PROPERTIES']=='Y' && $arCurrentValues["IBLOCK_ID"]>0){
	$rsProp = CIBlockProperty::GetList(array("sort"=>"asc", "name"=>"asc"), Array("ACTIVE"=>"Y", "IBLOCK_ID"=>(isset($arCurrentValues["IBLOCK_ID"])?$arCurrentValues["IBLOCK_ID"]:$arCurrentValues["ID"])));
	while ($arr=$rsProp->Fetch())
	{
		$arProperty_LNS[$arr["CODE"]] = "[".$arr["CODE"]."] ".$arr["NAME"];
	}
}


$arComponentParameters = array(
	"GROUPS" => array(
	),
	"PARAMETERS" => array(
		"IBLOCK_TYPE" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_TYPE"),
			"TYPE" => "LIST",
			"VALUES" => $arTypes,
			"DEFAULT" => "news",
			"REFRESH" => "Y",
		),
		"IBLOCK_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("T_IBLOCK_DESC_LIST_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arIBlocks,
			"DEFAULT" => '',
			"ADDITIONAL_VALUES" => "Y",
			"REFRESH" => "Y",
		),
		"ELEMENT_ID" => array(
			"PARENT" => "BASE",
			"NAME" => GetMessage("CP_BND_ELEMENT_ID"),
			"TYPE" => "LIST",
			"VALUES" => $arElements,
			"DEFAULT" => '',
		),
		"CHECK_DATES" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_DESC_CHECK_DATES"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
		"FIELD_CODE" => CIBlockParameters::GetFieldCode(GetMessage("IBLOCK_FIELD"), "DATA_SOURCE"),
		"GET_PROPERTIES" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => 'Get Properties',
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
			"REFRESH" => "Y",
		),
		"GET_IPROPERTIES" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => 'Get IPROPERTY_VALUES',
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "N",
		),
		"PROPERTY_CODE" => array(
			"PARENT" => "DATA_SOURCE",
			"NAME" => GetMessage("T_IBLOCK_PROPERTY"),
			"TYPE" => "LIST",
			"MULTIPLE" => "Y",
			"VALUES" => $arProperty_LNS,
			"ADDITIONAL_VALUES" => "Y",
		),
		"ACTIVE_DATE_FORMAT" => CIBlockParameters::GetDateFormat(GetMessage("T_IBLOCK_DESC_ACTIVE_DATE_FORMAT"), "ADDITIONAL_SETTINGS"),
		"CACHE_TIME"  =>  array("DEFAULT"=>36000000),
		"CACHE_GROUPS" => array(
			"PARENT" => "CACHE_SETTINGS",
			"NAME" => GetMessage("CP_BND_CACHE_GROUPS"),
			"TYPE" => "CHECKBOX",
			"DEFAULT" => "Y",
		),
	),
);

CIBlockParameters::AddPagerSettings(
	$arComponentParameters,
	GetMessage("T_IBLOCK_DESC_PAGER_PAGE"), //$pager_title
	false, //$bDescNumbering
	true, //$bShowAllParam
	true, //$bBaseLink
	$arCurrentValues["PAGER_BASE_LINK_ENABLE"]==="Y" //$bBaseLinkEnabled
);
unset($arComponentParameters["PARAMETERS"]["PAGER_SHOW_ALWAYS"]);
