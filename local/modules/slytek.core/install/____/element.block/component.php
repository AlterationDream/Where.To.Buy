<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var CBitrixComponent $this */
/** @var array $arParams */
/** @var array $arResult */
/** @var string $componentPath */
/** @var string $componentName */
/** @var string $componentTemplate */
/** @global CDatabase $DB */
/** @global CUser $USER */
/** @global CMain $APPLICATION */

use Bitrix\Main\Context,
Bitrix\Main\Type\DateTime,
Bitrix\Main\Loader,
Bitrix\Iblock;

CPageOption::SetOptionString("main", "nav_page_in_session", "N");

if(!isset($arParams["CACHE_TIME"]))
	$arParams["CACHE_TIME"] = 36000000;

$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);
if(strlen($arParams["IBLOCK_TYPE"])<=0)
	$arParams["IBLOCK_TYPE"] = "news";

$arParams["ELEMENT_ID"] = intval($arParams["~ELEMENT_ID"]);
if($arParams["ELEMENT_ID"]==0)return;

$arParams["CHECK_DATES"] = $arParams["CHECK_DATES"]!="N";
if(!is_array($arParams["FIELD_CODE"]))
	$arParams["FIELD_CODE"] = array();
foreach($arParams["FIELD_CODE"] as $key=>$val){
	if(!$val)
		unset($arParams["FIELD_CODE"][$key]);
}
if(!is_array($arParams["PROPERTY_CODE"]))
	$arParams["PROPERTY_CODE"] = array();
foreach($arParams["PROPERTY_CODE"] as $k=>$v){
	if($v==="")
		unset($arParams["PROPERTY_CODE"][$k]);
}
$arParams["ACTIVE_DATE_FORMAT"] = trim($arParams["ACTIVE_DATE_FORMAT"]);
if(strlen($arParams["ACTIVE_DATE_FORMAT"])<=0)
	$arParams["ACTIVE_DATE_FORMAT"] = $DB->DateFormatToPHP(CSite::GetDateFormat("SHORT"));

$arParams["DISPLAY_TOP_PAGER"] = $arParams["DISPLAY_TOP_PAGER"]=="Y";
$arParams["DISPLAY_BOTTOM_PAGER"] = $arParams["DISPLAY_BOTTOM_PAGER"]!="N";
$arParams["PAGER_TITLE"] = trim($arParams["PAGER_TITLE"]);
$arParams["PAGER_SHOW_ALWAYS"] = $arParams["PAGER_SHOW_ALWAYS"]!="N";
$arParams["PAGER_TEMPLATE"] = trim($arParams["PAGER_TEMPLATE"]);
$arParams["PAGER_SHOW_ALL"] = $arParams["PAGER_SHOW_ALL"]!=="N";

if($arParams["DISPLAY_TOP_PAGER"] || $arParams["DISPLAY_BOTTOM_PAGER"])
{
	$arNavParams = array(
		"nPageSize" => 1,
		"bShowAll" => $arParams["PAGER_SHOW_ALL"],
	);
	$arNavigation = CDBResult::GetNavParams($arNavParams);
}
else
{
	$arNavParams = null;
	$arNavigation = false;
}

if (empty($arParams["PAGER_PARAMS_NAME"]) || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["PAGER_PARAMS_NAME"]))
{
	$pagerParameters = array();
}
else
{
	$pagerParameters = $GLOBALS[$arParams["PAGER_PARAMS_NAME"]];
	if (!is_array($pagerParameters))
		$pagerParameters = array();
}


if($this->startResultCache(false, array(($arParams["CACHE_GROUPS"]==="N"? false: $USER->GetGroups()),$bUSER_HAVE_ACCESS, $arNavigation, $pagerParameters)))
{

	if(!Loader::includeModule("iblock"))
	{
		$this->abortResultCache();
		ShowError(GetMessage("IBLOCK_MODULE_NOT_INSTALLED"));
		return;
	}

	$arFilter = array(
		"IBLOCK_LID" => SITE_ID,
		"IBLOCK_ACTIVE" => "Y",
		"ACTIVE" => "Y",
		"CHECK_PERMISSIONS" => "Y",
		"IBLOCK_TYPE" => $arParams["IBLOCK_TYPE"],
		"SHOW_HISTORY" => $arParams["SHOW_WORKFLOW"]? "Y": "N",
	);
	if($arParams["CHECK_DATES"])
		$arFilter["ACTIVE_DATE"] = "Y";
	if(intval($arParams["IBLOCK_ID"]) > 0)
		$arFilter["IBLOCK_ID"] = $arParams["IBLOCK_ID"];

	$arSelect = array_merge($arParams["FIELD_CODE"], array(
		"ID",
		"NAME",
		"IBLOCK_ID",
		"IBLOCK_SECTION_ID",
		"DETAIL_TEXT",
		"DETAIL_TEXT_TYPE",
		"PREVIEW_TEXT",
		"PREVIEW_TEXT_TYPE",
		"DETAIL_PICTURE",
		"TIMESTAMP_X",
		"ACTIVE_FROM",
		"LIST_PAGE_URL",
		"DETAIL_PAGE_URL",
	));
	$bGetProperty = $arParams['GET_PROPERTIES']=='Y';

	$arFilter["ID"] = $arParams["ELEMENT_ID"];

	$rsElement = CIBlockElement::GetList(array(), $arFilter, false, array('nTopCount'=>1), $arSelect);
	if($obElement = $rsElement->GetNextElement())
	{
		$arResult = $obElement->GetFields();

		$arResult["NAV_RESULT"] = new CDBResult;
		if(($arResult["DETAIL_TEXT_TYPE"]=="html") && (strstr($arResult["DETAIL_TEXT"], "<BREAK />")!==false))
			$arPages=explode("<BREAK />", $arResult["DETAIL_TEXT"]);
		elseif(($arResult["DETAIL_TEXT_TYPE"]!="html") && (strstr($arResult["DETAIL_TEXT"], "&lt;BREAK /&gt;")!==false))
		$arPages=explode("&lt;BREAK /&gt;", $arResult["DETAIL_TEXT"]);
		else
			$arPages=array();
		$arResult["NAV_RESULT"]->InitFromArray($arPages);
		$arResult["NAV_RESULT"]->NavStart($arNavParams);
		if(count($arPages)==0)
		{
			$arResult["NAV_RESULT"] = false;
		}
		else
		{
			$navComponentParameters = array();
			if ($arParams["PAGER_BASE_LINK_ENABLE"] === "Y")
			{
				$pagerBaseLink = trim($arParams["PAGER_BASE_LINK"]);
				if ($pagerBaseLink === "")
					$pagerBaseLink = $arResult["DETAIL_PAGE_URL"];

				if ($pagerParameters && isset($pagerParameters["BASE_LINK"]))
				{
					$pagerBaseLink = $pagerParameters["BASE_LINK"];
					unset($pagerParameters["BASE_LINK"]);
				}

				$navComponentParameters["BASE_LINK"] = CHTTP::urlAddParams($pagerBaseLink, $pagerParameters, array("encode"=>true));
			}

			$arResult["NAV_STRING"] = $arResult["NAV_RESULT"]->GetPageNavStringEx(
				$navComponentObject,
				$arParams["PAGER_TITLE"],
				$arParams["PAGER_TEMPLATE"],
				$arParams["PAGER_SHOW_ALWAYS"],
				$this,
				$navComponentParameters
			);
			/** @var CBitrixComponent $navComponentObject */
			$arResult["NAV_CACHED_DATA"] = $navComponentObject->getTemplateCachedData();

			$arResult["NAV_TEXT"] = "";
			while($ar = $arResult["NAV_RESULT"]->Fetch())
				$arResult["NAV_TEXT"].=$ar;
		}

		if(strlen($arResult["ACTIVE_FROM"])>0)
			$arResult["DISPLAY_ACTIVE_FROM"] = CIBlockFormatProperties::DateFormat($arParams["ACTIVE_DATE_FORMAT"], MakeTimeStamp($arResult["ACTIVE_FROM"], CSite::GetDateFormat()));
		else
			$arResult["DISPLAY_ACTIVE_FROM"] = "";
		if($arParams['GET_IPROPERTIES']=='Y'){
			$ipropValues = new Iblock\InheritedProperty\ElementValues($arResult["IBLOCK_ID"], $arResult["ID"]);
			$arResult["IPROPERTY_VALUES"] = $ipropValues->getValues();

			Iblock\Component\Tools::getFieldImageData(
				$arResult,
				array('PREVIEW_PICTURE', 'DETAIL_PICTURE'),
				Iblock\Component\Tools::IPROPERTY_ENTITY_ELEMENT,
				'IPROPERTY_VALUES'
			);
		}
		$arResult["FIELDS"] = array();
		foreach($arParams["FIELD_CODE"] as $code)
			if(array_key_exists($code, $arResult))
				$arResult["FIELDS"][$code] = $arResult[$code];

			if($bGetProperty)
				$arResult["PROPERTIES"] = $obElement->GetProperties();
			$arResult["DISPLAY_PROPERTIES"]=array();
			foreach($arParams["PROPERTY_CODE"] as $pid)
			{
				$prop = &$arResult["PROPERTIES"][$pid];
				if(
					(is_array($prop["VALUE"]) && count($prop["VALUE"])>0)
					|| (!is_array($prop["VALUE"]) && strlen($prop["VALUE"])>0)
				)
				{
					$arResult["DISPLAY_PROPERTIES"][$pid] = CIBlockFormatProperties::GetDisplayValue($arResult, $prop, "news_out");
				}
			}

			$resultCacheKeys = array(
				"ID",
				"IBLOCK_ID",
				"NAV_CACHED_DATA",
				"NAME",
				"IBLOCK_SECTION_ID",
				"IBLOCK",
				"LIST_PAGE_URL", "~LIST_PAGE_URL",
				"SECTION_URL",
				"CANONICAL_PAGE_URL",
				"SECTION",
				"IPROPERTY_VALUES",
				"TIMESTAMP_X",
			);

			$this->setResultCacheKeys($resultCacheKeys);

			$this->includeComponentTemplate();
		}
		else
		{
			$this->abortResultCache();
		}
	}

	if(isset($arResult["ID"]))
	{
		$arTitleOptions = null;
		if(Loader::includeModule("iblock"))
		{

			if($USER->IsAuthorized())
			{
				if(
					$APPLICATION->GetShowIncludeAreas()
				)
				{
					$arReturnUrl = array(
						"add_element" => CIBlock::GetArrayByID($arResult["IBLOCK_ID"], "DETAIL_PAGE_URL"),
						"delete_element" => (
							empty($arResult["SECTION_URL"])?
							$arResult["LIST_PAGE_URL"]:
							$arResult["SECTION_URL"]
						),
					);

					$arButtons = CIBlock::GetPanelButtons(
						$arResult["IBLOCK_ID"],
						$arResult["ID"],
						$arResult["IBLOCK_SECTION_ID"],
						Array(
							"RETURN_URL" => $arReturnUrl,
							"SECTION_BUTTONS" => false,
						)
					);

					if($APPLICATION->GetShowIncludeAreas())
						$this->addIncludeAreaIcons(CIBlock::GetComponentMenu($APPLICATION->GetPublicShowMode(), $arButtons));
				}
			}
		}

		$this->setTemplateCachedData($arResult["NAV_CACHED_DATA"]);

		return $arResult["ID"];
	}
	else
	{
		return 0;
	}