<?
namespace Slytek;
class Discount{
	function getLogic($logic){
		switch ($logic){
			case 'Equal':return '=';
			case 'Not':return '!';
			case 'Great':return '>';
			case 'Less':return '<';
			case 'EqGr':return '>=';
			case 'EqLs':return '<=';
			case 'Contain':return '?';
			case 'NotCont':return '!?';
		}

	}
	function getField($field){
		switch ($field){
			case 'CondIBElement':return 'ID';
			case 'CondIBIBlock':return 'IBLOCK_ID';
			case 'CondIBSection':return 'SECTION_ID';
			case 'CondIBXmlID':return 'XML_ID';
			case 'CondIBDateActiveFrom':return 'DATE_ACTIVE_FROM ';
			case 'CondIBDateActiveTo':return 'DATE_ACTIVE_TO';
			case 'CondIBPreviewText':return 'PREVIEW_TEXT';
			case 'CondIBDetailText':return 'DETAIL_TEXT';
			case 'CondIBDateCreate':return 'DATE_CREATE';
			case 'CondIBCreatedBy':return 'CREATED_BY';
			case 'CondIBTimestampX':return 'TIMESTAMP_X';
			case 'CondIBModifiedBy':return 'MODIFIED_BY';
			case 'CondIBTags':return 'TAGS';
			case 'CondCatQuantity':return 'CATALOG_QUANTITY';
			case 'CondCatWeight':return 'CATALOG_WEIGHT';
			default:
			return ToUpper(str_ireplace('CondIB', '', $field));
			break;
		}

	}
	function checkConditions($arContition){
		$ok=true;
		switch ($arAction['CLASS_ID']){
			case 'GifterCondIBElement':
			case 'GiftCondGroup':
			$ok = false;
			break;
			case 'CondGroup':
			case 'CondBsktProductGroup':
			foreach ($arAction['CHILDREN'] as $arChild){
				$ok = self::checkConditions($arChild);
			}
			break;
		}
		return $ok;
	}
	function checkActions(&$arAction){
		$ok=true;
		switch ($arAction['CLASS_ID']){
			case 'GifterCondIBElement':
			case 'GiftCondGroup':
			$ok = false;
			break;
			case 'CondGroup':
			case 'CondBsktProductGroup':
			foreach ($arAction['CHILDREN'] as $arChild){
				$ok = self::checkActions($arChild);
			}
			break;
		}
		return $ok;
	}
	function parseCondition(&$arContition, &$arParams=array(), &$arFilter=array()){
		$arCurrentFilter=array();
		switch ($arContition['CLASS_ID']){
			case 'CondGroup':
			case 'CondBsktProductGroup':
			case 'ActSaleBsktGrp':
			case 'ActSaleSubGrp':
			$arCurrentarams=array('NOT'=>$arParams['NOT']);
			$arCurrentFilter['LOGIC']=$arContition['DATA']['All'];
			if($arContition['DATA']['True']=='False')$arCurrentarams['NOT']=!$arCurrentarams['NOT'];
			foreach ($arContition['CHILDREN'] as $arChild){
				self::parseCondition($arChild, $arCurrentarams, $arCurrentFilter);
			}
			break;
			default:
			$name=false;

			if(stripos($arContition['CLASS_ID'],'CondIBProp')!==false){
				$arProp=explode(':', $arContition['CLASS_ID']);
				$arCurrentFilter['IBLOCK_ID']=$arProp[1];
				$name='PROPERTY_'.$arProp[2];
			}
			else if(stripos($arContition['CLASS_ID'], 'CondIB')!==false){
				$name=self::getField($arContition['CLASS_ID']);
			}
			if($name)
				$arCurrentFilter[($arParams['NOT']?'!':'').(self::getLogic($arContition['DATA']['logic'])).($name)]=self::getValue($arContition['DATA']['value'], $name);

		}
		if(
			($arCurrentFilter['LOGIC'] && count($arCurrentFilter)==1) 
		){
			$arCurrentFilter=false;
		}
		if($arCurrentFilter){
			$arFilter[]=$arCurrentFilter;
		}
		return $arFilter;
	}
	function getValue($val, $type){
		switch($type){
			case 'DATE_ACTIVE_FROM':
			case 'DATE_ACTIVE_TO':
			case 'DATE_CREATE':
			case 'TIMESTAMP_X':
			return ConvertTimeStamp($val + \CTimeZone::GetOffset(), 'FULL');
			default:
			return $val;
		}

	}
	public static function getDiscountsItems(){
		\CModule::includeModule('sale');
		$ar=array();
		global $USER;
		global $DB;
		$arGroups=$USER->GetUserGroupArray();

		$db_res = \CSaleDiscount::GetList(array("SORT" => "ASC"),array("ACTIVE" => 'Y',  "!>ACTIVE_FROM" => $DB->FormatDate(date("Y-m-d H:i:s"), 
			"YYYY-MM-DD HH:MI:SS",
			\CSite::GetDateFormat("FULL")),
		"!<ACTIVE_TO" => $DB->FormatDate(date("Y-m-d H:i:s"), 
			"YYYY-MM-DD HH:MI:SS", 
			\CSite::GetDateFormat("FULL")),
	),false,false, array('ID', 'USER_GROUPS'));
		while ($arDiscount = $db_res->Fetch())
		{
			$couponIterator=\Bitrix\Sale\Internals\DiscountCouponTable::getList(array(
				'select' => array('ID'),
				'filter' => array('=DISCOUNT_ID' => $arDiscount['ID'])
			));
			if ($coupon = $couponIterator->fetch()){

				continue;
			}
			$discounts[$arDiscount['ID']][]=$arDiscount['USER_GROUPS'];
		}
		foreach ($discounts as $id => $groups) {
			$ok = false;
			if($groups && is_array($groups)){
				if(in_array(2, $groups)){
					$ok = true;
				}
			}else{
				$ok = true;
			}
			if($ok){
				$ids = self::getDiscountItems($id);
				if(is_array($ids)){
					$ar = array_merge($ar, $ids);
				}
			}
		}

		return $ar;
	}
	public static function getDiscountItems($id){
		global $DB;
		static $arDiscountElementID = array();
		static $arDiscountSectionID = array();
		static $arResult = array();
		$arDiscounts = array();
		global $USER;
		\CModule::includeModule('iblock');
		\CModule::includeModule('catalog');
		if(!$arResult){
			$dbProductDiscounts = \CSaleDiscount::GetList(
				array("SORT" => "ASC"),
				array(
					"ID" => $id,
				),
				false,
				false,
				array("ID", "SITE_ID", "ACTIVE", "ACTIVE_FROM", "ACTIVE_TO",
					"RENEWAL", "NAME", "SORT", "MAX_DISCOUNT", "VALUE_TYPE",
					"VALUE", "CURRENCY", "PRODUCT_ID", "SECTION_ID", 'CONDITIONS', 'ACTIONS')
			);
			while ($arItem = $dbProductDiscounts->Fetch())
			{
				if($arItem['PRODUCT_ID'])$arItem['PRODUCT_ID']=array($arItem['PRODUCT_ID']);
				if($arItem['SECTION_ID'])$arItem['SECTION_ID']=array($arItem['SECTION_ID']);
				if($arDiscount){
					if($arItem['PRODUCT_ID'])$arDiscount['PRODUCT_ID']=array_merge($arDiscount['PRODUCT_ID'], $arItem['PRODUCT_ID']);
					if($arItem['SECTION_ID'])$arDiscount['SECTION_ID']=array_merge($arDiscount['SECTION_ID'], $arItem['SECTION_ID']);
				}
				else $arDiscount=$arItem;
			}
		}
		
			
		if($arDiscount && self::checkConditions(unserialize($arDiscount['CONDITIONS'])) && self::checkActions(unserialize($arDiscount['ACTIONS']))){
			$arFilter=array();

			if($arDiscount['ACTIONS']){
				
				$arFilter=self::parseCondition(unserialize($arDiscount['ACTIONS']));
			}else{
				if($arDiscount['PRODUCT_ID']){
					$arFilter['ID']=$arDiscount['PRODUCT_ID'];
				}
				if($arDiscount['SECTION_ID']){
					$arFilter['SECTION_ID']=$arDiscount['SECTION_ID'];
					$arFilter['INCLUDE_SUBSECTIONS']='Y';
				}
			}
			
			if($arFilter){
				$arFilter['ACTIVE']='Y';
				$arFilter['IBLOCK_ID']=array(2,3);
				$res = \CIBlockElement::GetList(Array(), $arFilter, false, false, array('ID'));
				while($arFields = $res->GetNext())
				{
					$mxResult = \CCatalogSku::GetProductInfo(
						$arFields['ID']
					);
					if (is_array($mxResult))
					{
						$return[]=$mxResult['ID'];
					}
					else
					{
						$return[]=$arFields['ID'];
					}
					
				}

			}

			return $return;
		}
	}
	function handler($id, $arFields=false){
		$ids=self::getDiscountItems($id);
		foreach($ids as $id){
			Catalog::DoIBlockAfterSave(array('ID'=>$id), false);
		}
	}
}
?>