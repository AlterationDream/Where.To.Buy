<?
class CSlytekSearch {
	
	public function search_init($arParams, $query, $exParams=false){
		CModule::IncludeModule('search');
		$query = preg_replace('/[\/\_\-]/', ' ', $query);
		if($exParams=='TRANSLIT' || $exParams=='TRANSLIT_STEMMING')
		{
			$arLang = CSearchLanguage::GuessLanguage($query);
			if(is_array($arLang) && $arLang["from"] != $arLang["to"]){
				$query = CSearchLanguage::ConvertKeyboardLayout($query, $arLang["from"], $arLang["to"]);
			}
		}
		$GLOBALS['query']=$query;
		$tags=$query;
		$arFilter = array(
			"CHECK_DATES" => 'Y',
			"MODULE_ID" => 'iblock',
			"SITE_ID" => SITE_ID,
		);
		if($arParams['TAGS']){
			$arFilter['TAGS']=$tags;
		}else{
			$arFilter['QUERY']='"'.$query.'"';
		}
		$aSort=array("CUSTOM_RANK"=>"DESC", "TITLE_RANK"=>"DESC", "RANK"=>"DESC", "DATE_CHANGE"=>"DESC");
		
		$exFILTER = CSearchParameters::ConvertParamsToFilter($arParams, "");
		if($exParams=='STEMMING' || $exParams=='TRANSLIT_STEMMING'){
			$exFILTER["STEMMING"] = false;
		}
		else{
			$exFILTER["STEMMING"] = true;
		}
		if(is_array($arParams['FILTER'])){
			$arFilter=array_merge($arFilter, $arParams['FILTER']);
		}
		$obSearch = new CSearch();
		$obSearch->SetOptions(array(
			"ERROR_ON_EMPTY_STEM" => $arParams["RESTART"] != "Y",
			"NO_WORD_LOGIC" => true,
		));
		$obSearch->Search($arFilter, $aSort, $exFILTER);
		
		return $obSearch;
	}
	public function search($arParams, $query, $exParams=false){
		CModule::IncludeModule('search');
		CModule::IncludeModule('iblock');
		CUtil::decodeURIComponent($query);
		if(mb_strlen($query)<3)return;		
		$obSearch=self::search_init($arParams, $query, $exParams);

		$items = array();
		$error_n = $obSearch->errorno;
		$error_text = $obSearch->error;

		if($obSearch->errorno==0)
		{
			$obSearch->NavStart(1, false);
			$ar = $obSearch->GetNext();
			if(!$ar){
				if(!$exParams){
					if($arParams["RESTART"] == "Y" && $obSearch->Query->bStemming && $exParams!='STEMMING' && $exParams!='TRANSLIT_STEMMING')
					{
						$restart = true;
						$items = self::search($arParams, $query, 'STEMMING');		
					}
					if($arParams["USE_LANGUAGE_GUESS"] !== "N" && (!$restart || ($restart && !$items)) && $exParams!='TRANSLIT'&& $exParams!='TRANSLIT_STEMMING'){
						$restart = true;
						$items = self::search($arParams, $query, 'TRANSLIT');
					}
					if(
						$arParams["USE_LANGUAGE_GUESS"] !== "N" && 
						$arParams["RESTART"] == "Y" && 
						(!$restart || ($restart && !$items)) && 
						$exParams!='TRANSLIT_STEMMING' && 
						$exParams!='STEMMING' &&
						$exParams!='TRANSLIT'
					){
						$restart = true;
						$last = true;
						$items = self::search($arParams, $query, 'TRANSLIT_STEMMING');
					}
				}
				
				return $items;
			}
			else
			{
				$obSearch=self::search_init($arParams, $query, $exParams);
				$obSearch->NavStart($arParams["TOP_COUNT"], false);
				$j=0;
				$findCatalog=array();
				while($ar = $obSearch->GetNext())
				{
					$j++;
					if($j > $arParams["TOP_COUNT"])
					{
						break;
					}
					$items[$ar['ITEM_ID']]=$ar;
				}				
			}
		}
		return $items;

	}
	function GetPadezh($number, $after) {
		if(!is_array($after)){
			$after=explode(',', $after);
			foreach($after as $k=>$val){
				$after[$k]=trim($val);
			}
		}
		$cases = array(2, 0, 1, 1, 1, 2);
		return $after[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
	}
}

?>