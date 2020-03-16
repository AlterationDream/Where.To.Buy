<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!IsModuleInstalled("search"))
{
	ShowError(GetMessage("CC_BST_MODULE_NOT_INSTALLED"));
	return;
}

if(!isset($arParams["PAGE"]) || strlen($arParams["PAGE"])<=0)
	$arParams["PAGE"] = "#SITE_DIR#search/index.php";
$arParams["TOP_COUNT"] = intval($arParams["TOP_COUNT"]);
if($arParams["TOP_COUNT"] <= 0)
	$arParams["TOP_COUNT"] = 999999999999;
$query = ltrim($_REQUEST["q"]);
if(
	!empty($query)
	&& ($_REQUEST["ajax_call"] === "y" || $arParams['FULL_SEARCH']=='Y')
	&& (
		!isset($_REQUEST["INPUT_ID"])
		|| $_REQUEST["INPUT_ID"] == $arParams["INPUT_ID"]
	)
	&& CModule::IncludeModule("search")
)
{

	$arResult["ITEMS"] = CSlytekSearch::search($arParams, $query);
	$arResult['QUERY']=$GLOBALS['query'];
	if($arResult["ITEMS"] && $arParams['FULL_SEARCH']!='Y'){
		$params = array(
			"q" => $arResult['QUERY'],
		);
		$url = CHTTP::urlAddParams(
			str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
			,$params
			,array("encode"=>true)
		);
		$arResult["ITEMS"][] = array(
			"ALL" => true,
			"NAME" => GetMessage("CC_BST_ALL_RESULTS"),
			"URL" => $url,
		);
	}
}

$arResult["FORM_ACTION"] = htmlspecialcharsbx(str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"]));

if (
	$_REQUEST["ajax_call"] === "y"
	&& (
		!isset($_REQUEST["INPUT_ID"])
		|| $_REQUEST["INPUT_ID"] == $arParams["INPUT_ID"]
	)
)
{
	$APPLICATION->RestartBuffer();

	if(!empty($query))
		$this->IncludeComponentTemplate('ajax');
	CMain::FinalActions();
	die();
}
else
{
	if($arParams['FULL_SEARCH']=='Y'){
		if(count($arResult["ITEMS"])==1){
			foreach($arResult["ITEMS"] as $item){
				if($item['URL']){
					//LocalRedirect($item['URL']);
					break;
				}

			}
		}
		if($arResult["ITEMS"]){
			$arResult['ELEMENT_FILTER_NAME']='searchItemsFilter';
			$GLOBALS[$arResult['ELEMENT_FILTER_NAME']]=array('ID'=>array_keys($arResult["ITEMS"]));
		}
		
		$itemsWord = CSlytekSearch::GetPadezh( count( $arResult['ITEMS'] ), array('товар', 'товара', 'товаров' ));

		$arResult['TITLE'] = 'По запросу «' . $arResult['QUERY'] . '» ';
		if ( count( $arResult['ITEMS'] ) > 0 )
		{
			$arResult['TITLE'] .= 'найдено ' . count( $arResult['ITEMS'] ) . ' ' . $itemsWord . ':';
		}
		return $arResult;
	}
	else{
		$APPLICATION->AddHeadScript($this->GetPath().'/script.js');
		CUtil::InitJSCore(array('ajax'));
	}
	$this->IncludeComponentTemplate();
}
?>
