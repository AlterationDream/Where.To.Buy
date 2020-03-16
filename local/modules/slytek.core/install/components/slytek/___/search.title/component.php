<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

if(!IsModuleInstalled("search"))
{
	ShowError(GetMessage("CC_BST_MODULE_NOT_INSTALLED"));
	return;
}

if(!isset($arParams["PAGE"]) || strlen($arParams["PAGE"])<=0)
	$arParams["PAGE"] = "#SITE_DIR#search/index.php";

$arResult["CATEGORIES"] = array();

$query = ltrim($_POST["q"]);
$type = trim(htmlspecialchars(($_POST["type"])));
$arResult['TYPES']=array('ARTNUMBER', 'NAME', 'DESCRIPTION');
if(
	mb_strlen($query)>2
	&& $_REQUEST["ajax_call"] === "y"
	&& (
		!isset($_REQUEST["INPUT_ID"])
		|| $_REQUEST["INPUT_ID"] == $arParams["INPUT_ID"]
	)
	&& CModule::IncludeModule("search")
)
{
	CUtil::decodeURIComponent($query);

	$arResult["alt_query"] = "";
	if($arParams["USE_LANGUAGE_GUESS"] !== "N")
	{
		$arLang = CSearchLanguage::GuessLanguage($query);
		if(is_array($arLang) && $arLang["from"] != $arLang["to"])
			$arResult["alt_query"] = CSearchLanguage::ConvertKeyboardLayout($query, $arLang["from"], $arLang["to"]);
	}

	$arResult["query"] = $query;
	$arResult["phrase"] = stemming_split($query, LANGUAGE_ID);

	$arParams["NUM_CATEGORIES"] = intval($arParams["NUM_CATEGORIES"]);
	if($arParams["NUM_CATEGORIES"] <= 0)
		$arParams["NUM_CATEGORIES"] = 1;

	$arParams["TOP_COUNT"] = intval($arParams["TOP_COUNT"]);
	if($arParams["TOP_COUNT"] <= 0)
		$arParams["TOP_COUNT"] = 5;

	if(in_array($type, $arResult['TYPES'])){
		CModule::includeModule('iblock');
		$i=0;
		$arResult["CATEGORIES"][$i] = array(
			"TITLE" => '',
			"ITEMS" => array()
		);
		$arFilter=array(
			'IBLOCK_ID'=>2,
			'ACTIVE'=>'Y',
			'ACTIVE_DATE'=>'Y'
		);
		switch($type){
			case 'ARTNUMBER':
			$arFilter['PROPERTY_ARTNUMBER']='%'.$query.'%';
			break;
			case 'NAME':
			$arFilter['NAME']='%'.$query.'%';
			break;
			case 'DESCRIPTION':
			$arFilter['DETAIL_TEXT']='%'.$query.'%';
			break;
		}
		$resElements = CIBlockElement::GetList(Array(), $arFilter, false, array('nTopCount'=>$arParams["TOP_COUNT"]), Array("ID", "IBLOCK_ID", "DETAIL_PAGE_URL", 'NAME', 'PROPERTY_ARTNUMBER'));
		while($arItem = $resElements->GetNext())
		{
			switch($type){
				case 'ARTNUMBER':
				$arItem['NAME'] = $arItem['PROPERTY_ARTNUMBER_VALUE'];
				break;

			}
			$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
				"NAME" => $arItem["NAME"],
				"URL" => htmlspecialcharsbx($arItem["DETAIL_PAGE_URL"]),
				"MODULE_ID" => 'iblock',
				"PARAM1" => 2,
				"PARAM2" => $ar["PARAM2"],
				"ITEM_ID" => $arItem["ID"],
			);
		}
		

	}
	else{
		$arOthersFilter = array("LOGIC"=>"OR");

		for($i = 0; $i < $arParams["NUM_CATEGORIES"]; $i++)
		{
			$bCustom = true;
			if(is_array($arParams["CATEGORY_".$i]))
			{
				foreach($arParams["CATEGORY_".$i] as $categoryCode)
				{
					if ((strpos($categoryCode, 'custom_') !== 0))
					{
						$bCustom = false;
						break;
					}
				}
			}
			else
			{
				$bCustom = (strpos($arParams["CATEGORY_".$i], 'custom_') === 0);
			}

			if ($bCustom)
				continue;

			$category_title = trim($arParams["CATEGORY_".$i."_TITLE"]);
			if(empty($category_title))
			{
				if(is_array($arParams["CATEGORY_".$i]))
					$category_title = implode(", ", $arParams["CATEGORY_".$i]);
				else
					$category_title = trim($arParams["CATEGORY_".$i]);
			}
			if(empty($category_title))
				continue;

			$arResult["CATEGORIES"][$i] = array(
				"TITLE" => htmlspecialcharsbx($category_title),
				"ITEMS" => array()
			);

			$exFILTER = array(
				0 => CSearchParameters::ConvertParamsToFilter($arParams, "CATEGORY_".$i),
			);
			$exFILTER[0]["LOGIC"] = "OR";

			if($arParams["CHECK_DATES"] === "Y")
				$exFILTER["CHECK_DATES"] = "Y";

			$arOthersFilter[] = $exFILTER;

			$j = 0;
			$obTitle = new CSearchTitle;
			$obTitle->setMinWordLength($_REQUEST["l"]);
			if($obTitle->Search(
				$arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"]
				,$arParams["TOP_COUNT"]
				,$exFILTER
				,false
				,$arParams["ORDER"]
			))
			{
				while($ar = $obTitle->Fetch())
				{
					$j++;
					if($j > $arParams["TOP_COUNT"])
					{
						$params = array("q" => $arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"]);

						$url = CHTTP::urlAddParams(
							str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
							,$params
							,array("encode"=>true)
						).CSearchTitle::MakeFilterUrl("f", $exFILTER);

						$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
							"NAME" => GetMessage("CC_BST_MORE"),
							"URL" => htmlspecialcharsex($url),
						);
						break;
					}
					else
					{
						$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
							"NAME" => $ar["NAME"],
							"URL" => htmlspecialcharsbx($ar["URL"]),
							"MODULE_ID" => $ar["MODULE_ID"],
							"PARAM1" => $ar["PARAM1"],
							"PARAM2" => $ar["PARAM2"],
							"ITEM_ID" => $ar["ITEM_ID"],
						);
					}
				}
			}
			/* This code adds not fixed keyboard link to the category
			if($arResult["alt_query"] != "")
			{
				$params = array(
					"q" => $arResult["query"],
					"spell" => 1,
				);

				$url = CHTTP::urlAddParams(
					str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
					,$params
					,array("encode"=>true)
				).CSearchTitle::MakeFilterUrl("f", $exFILTER);

				$arResult["CATEGORIES"][$i]["ITEMS"][] = array(
					"NAME" => GetMessage("CC_BST_QUERY_PROMPT", array("#query#"=>$arResult["query"])),
					"URL" => htmlspecialcharsex($url),
				);
			}
			*/
			if(!$j)
			{
				unset($arResult["CATEGORIES"][$i]);
			}
		}

		if($arParams["SHOW_OTHERS"] === "Y")
		{
			$arResult["CATEGORIES"]["others"] = array(
				"TITLE" => htmlspecialcharsbx($arParams["CATEGORY_OTHERS_TITLE"]),
				"ITEMS" => array(),
			);

			$j = 0;
			$obTitle = new CSearchTitle;
			$obTitle->setMinWordLength($_REQUEST["l"]);
			if($obTitle->Search(
				$arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"]
				,$arParams["TOP_COUNT"]
				,$arOthersFilter
				,true
				,$arParams["ORDER"]
			))
			{
				while($ar = $obTitle->Fetch())
				{
					$j++;
					if($j > $arParams["TOP_COUNT"])
					{
						//it's really hard to make it working
						break;
					}
					else
					{
						$arResult["CATEGORIES"]["others"]["ITEMS"][] = array(
							"NAME" => $ar["NAME"],
							"URL" => htmlspecialcharsbx($ar["URL"]),
							"MODULE_ID" => $ar["MODULE_ID"],
							"PARAM1" => $ar["PARAM1"],
							"PARAM2" => $ar["PARAM2"],
							"ITEM_ID" => $ar["ITEM_ID"],
						);
					}
				}
			}

			if(!$j)
			{
				unset($arResult["CATEGORIES"]["others"]);
			}

		}
	}
	if(!empty($arResult["CATEGORIES"]))
	{
		$arResult["CATEGORIES"]["all"] = array(
			"TITLE" => "",
			"ITEMS" => array()
		);

		$params = array(
			"q" => $arResult["alt_query"]? $arResult["alt_query"]: $arResult["query"],
		);
		$url = CHTTP::urlAddParams(
			str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
			,$params
			,array("encode"=>true)
		);
		$arResult["CATEGORIES"]["all"]["ITEMS"][] = array(
			"NAME" => GetMessage("CC_BST_ALL_RESULTS"),
			"URL" => $url,
		);
		/*
		if($arResult["alt_query"] != "")
		{
			$params = array(
				"q" => $arResult["query"],
				"spell" => 1,
			);

			$url = CHTTP::urlAddParams(
				str_replace("#SITE_DIR#", SITE_DIR, $arParams["PAGE"])
				,$params
				,array("encode"=>true)
			);

			$arResult["CATEGORIES"]["all"]["ITEMS"][] = array(
				"NAME" => GetMessage("CC_BST_ALL_QUERY_PROMPT", array("#query#"=>$arResult["query"])),
				"URL" => htmlspecialcharsex($url),
			);
		}
		*/
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
	$APPLICATION->AddHeadScript($this->GetPath().'/script.js');
	CUtil::InitJSCore(array('ajax'));
	$this->IncludeComponentTemplate();
}
?>
