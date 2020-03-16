<?
namespace Slytek;
class Catalog {
	static function FormatPrice($price, $replace=false){
		return str_ireplace('руб.', $replace, $price);
	}
	
	function DoIBlockAfterSave($arg1, $arg2 = false, $offers=array()) {
		$ELEMENT_ID = false;
		$IBLOCK_ID = false;
		static $OFFERS_IBLOCK_ID = false;
		static $OFFERS_PROPERTY_ID = false;
		//if (CModule::IncludeModule('currency')) {
			$strDefaultCurrency = 'RUB';//CCurrency::GetBaseCurrency();
		//}
			if($arg1["ID"]>0 && $arg1["IBLOCK_ID"]!=CATALOG_IBLOCK_ID){
				if(!$arg1["IBLOCK_ID"])$arg1["IBLOCK_ID"]=\CIBlockElement::GetIBlockByID($arg1["ID"]);
				if($arg1["IBLOCK_ID"]==OFFERS_IBLOCK_ID){
					\CModule::includeModule('catalog');
					$mxResult = \CCatalogSku::GetProductInfo($arg1["ID"]);
					if (is_array($mxResult))
					{
						$arg1["ID"]=$mxResult['ID'];
						$arg1["IBLOCK_ID"]=$mxResult['IBLOCK_ID'];
					}
				}
			}
			if (is_array($arg2) && $arg2["PRODUCT_ID"] > 0) {
				$rsPriceElement = \CIBlockElement::GetList(
					array(),
					array(
						"ID" => $arg2["PRODUCT_ID"],
					),
					false,
					false,
					array("ID", "IBLOCK_ID")
				);
				if ($arPriceElement = $rsPriceElement->Fetch()) {
					$arCatalog = \CCatalog::GetByID($arPriceElement["IBLOCK_ID"]);
					if (is_array($arCatalog)) {
						if ($arCatalog["OFFERS"] == "Y") {
							$rsElement = \CIBlockElement::GetProperty(
								$arPriceElement["IBLOCK_ID"],
								$arPriceElement["ID"],
								"sort",
								"asc",
								array("ID" => $arCatalog["SKU_PROPERTY_ID"])
							);
							$arElement = $rsElement->Fetch();
							if ($arElement && $arElement["VALUE"] > 0) {
								$ELEMENT_ID = $arElement["VALUE"];
								$IBLOCK_ID = $arCatalog["PRODUCT_IBLOCK_ID"];
								$OFFERS_IBLOCK_ID = $arCatalog["IBLOCK_ID"];
								$OFFERS_PROPERTY_ID = $arCatalog["SKU_PROPERTY_ID"];
							}
						}
						elseif ($arCatalog["OFFERS_IBLOCK_ID"] > 0) {
							$ELEMENT_ID = $arPriceElement["ID"];
							$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
							$OFFERS_IBLOCK_ID = $arCatalog["OFFERS_IBLOCK_ID"];
							$OFFERS_PROPERTY_ID = $arCatalog["OFFERS_PROPERTY_ID"];
						}
						else {
							$ELEMENT_ID = $arPriceElement["ID"];
							$IBLOCK_ID = $arPriceElement["IBLOCK_ID"];
							$OFFERS_IBLOCK_ID = false;
							$OFFERS_PROPERTY_ID = false;
						}
					}

				}
			}
			elseif (is_array($arg1) && $arg1["ID"] > 0 && $arg1["IBLOCK_ID"] > 0  &&  $arg1["IBLOCK_ID"]==CATALOG_IBLOCK_ID) {
				$ELEMENT_ID = $arg1["ID"];
				$IBLOCK_ID = $arg1["IBLOCK_ID"];
				if(!$OFFERS_IBLOCK_ID && !$OFFERS_PROPERTY_ID && !$offers){
					$arOffers = \CIBlockPriceTools::GetOffersIBlock($arg1["IBLOCK_ID"]);
					if (is_array($arOffers)) {
						$OFFERS_IBLOCK_ID = $arOffers["OFFERS_IBLOCK_ID"];
						$OFFERS_PROPERTY_ID = $arOffers["OFFERS_PROPERTY_ID"];
					}
				}
			}
			if($IBLOCK_ID!=CATALOG_IBLOCK_ID && (!$OFFERS_IBLOCK_ID || ($OFFERS_IBLOCK_ID && $OFFERS_IBLOCK_ID!=OFFERS_IBLOCK_ID)))return;

			if ($ELEMENT_ID) {
				if($offers){
					$arProductID=$offers;
				}
				else if ($OFFERS_IBLOCK_ID) {
					$rsOffers = \CIBlockElement::GetList(
						array(),
						array(
							"IBLOCK_ID" => $OFFERS_IBLOCK_ID,
							"PROPERTY_" . $OFFERS_PROPERTY_ID => $ELEMENT_ID,
						),
						false,
						false,
						array("ID")
					);
					while ($arOffer = $rsOffers->Fetch()) {
						$arProductID[] = $arOffer["ID"];
					}

					if (!is_array($arProductID)) {
						$arProductID = array($ELEMENT_ID);
					}

				} else {
					$arProductID = array($ELEMENT_ID);
				}

				$minPrice = false;
				$maxPrice = false;
				$discountPrice = 0;

				foreach($arProductID as $id){
					$arPrice = \CCatalogProduct::GetOptimalPrice($id, 1, array(2), 'N', array(), 's1');
					$arPrice=$arPrice['RESULT_PRICE'];
					if ($strDefaultCurrency != $arPrice['CURRENCY'] && \CModule::IncludeModule('currency')) {
						$arPrice["DISCOUNT_PRICE"] = \CCurrencyRates::ConvertCurrency($arPrice["DISCOUNT_PRICE"], $arPrice["CURRENCY"], $strDefaultCurrency);
						$arPrice["DISCOUNT"] = \CCurrencyRates::ConvertCurrency($arPrice["DISCOUNT"], $arPrice["CURRENCY"], $strDefaultCurrency);
					}
					$PRICE = $arPrice["DISCOUNT_PRICE"];
					if ($discountPrice === false || $discountPrice < $arPrice["DISCOUNT"]) {
						$discountPrice = $arPrice["DISCOUNT"];
					}
					if ($minPrice === false || $minPrice > $PRICE) {
						$minPrice = $PRICE;
					}

					if ($maxPrice === false || $maxPrice < $PRICE) {
						$maxPrice = $PRICE;
					}
				}

				\CIBlockElement::SetPropertyValuesEx(
					$ELEMENT_ID,
					$IBLOCK_ID,
					array(
						"MINIMUM_PRICE" => ceil($minPrice),
						"DISCOUNT" => ceil($discountPrice),
					//	"MAXIMUM_PRICE" => ceil($maxPrice)
					)
				);

			}
		}

		function convertAll($intIBlockID, $filter=false) {
			\CModule::includeModule('iblock');
			$args=array();
			$arFilter=array('IBLOCK_ID' => $intIBlockID, 'ACTIVE'=>'Y');
			if($filter){
				$arFilter=array_merge($arFilter, $filter);
			}
			$rsElements = \CIBlockElement::GetList(array(), $arFilter, false, false, array('ID', 'IBLOCK_ID'));
			while ($arFields = $rsElements->GetNext()) {
				$arg1 = array();
				$arg1["ID"] = $arFields['ID'];
				$arg1["IBLOCK_ID"] = $intIBlockID;
				$args[]=$arg1;
				$offers[$arg1["ID"]]=array();
			}
			if($offers){
				$arOffers = \CIBlockPriceTools::GetOffersIBlock($intIBlockID);
				if (is_array($arOffers)) {
					$OFFERS_IBLOCK_ID = $arOffers["OFFERS_IBLOCK_ID"];
					$OFFERS_PROPERTY_ID = $arOffers["OFFERS_PROPERTY_ID"];
					$rsOffers = \CIBlockElement::GetList(
						array(),
						array(
							'ACTIVE'=>'Y',
							"IBLOCK_ID" => $OFFERS_IBLOCK_ID,
							"PROPERTY_".$OFFERS_PROPERTY_ID => array_keys($offers)
						),
						false,
						false,
						array("ID", 'IBLOCK_ID', "PROPERTY_" . $OFFERS_PROPERTY_ID)
					);
					while ($arOffer = $rsOffers->Fetch()) {
						$offers[$arOffer["PROPERTY_".$OFFERS_PROPERTY_ID]][]=$arOffer['ID'];
					}

				}
			}
			foreach($args as $arg1)
				self::DoIBlockAfterSave($arg1, false, $offers[$arg1["ID"]]);
			return 'CSlytekHandler::convertAll(' . $intIBlockID . ');';

		}

		function convertAllHandler($id=false, $arFields=false){
		//set_time_limit(0);
			self::convertAll(CATALOG_IBLOCK_ID);
		}
		public function makeSmartUrl($items, $url, $apply = true, $applyControlId = false, $delControlId = false, $alone=false)
		{
			$smartParts = array();

			if ($apply)
			{
				foreach($items as $PID => $arItem)
				{
					$smartPart = array();
				//Prices
					if ($arItem["PRICE"] && $delControlId!="price-".$arItem["URL_ID"] && !$alone)
					{
						if (strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0)
							$smartPart["from"] = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
						if (strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0)
							$smartPart["to"] = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
					}

					if ($smartPart)
					{
						array_unshift($smartPart, "price-".$arItem["URL_ID"]);

						$smartParts[] = $smartPart;
					}
				}

				foreach($items as $PID => $arItem)
				{
					$smartPart = array();
					if ($arItem["PRICE"])
						continue;

				//Numbers && calendar == ranges
					if (
						(
							$arItem["PROPERTY_TYPE"] == "N"
							|| $arItem["DISPLAY_TYPE"] == "U"
						)
						&& $delControlId!=$arItem["ID"] && !$alone
					)
					{

						if (strlen($arItem["VALUES"]["MIN"]["HTML_VALUE"]) > 0)
							$smartPart["from"] = $arItem["VALUES"]["MIN"]["HTML_VALUE"];
						if (strlen($arItem["VALUES"]["MAX"]["HTML_VALUE"]) > 0)
							$smartPart["to"] = $arItem["VALUES"]["MAX"]["HTML_VALUE"];
					}
					else
					{
						foreach($arItem["VALUES"] as $key => $ar)
						{
							if (
								(
									($ar["CHECKED"] && !$alone)
									|| $ar["CONTROL_ID"] === $applyControlId
								)
								&& strlen($ar["URL_ID"])
								&& ($ar["CONTROL_ID"] !== $delControlId)
							)
							{
								$smartPart[] = $ar["URL_ID"];
							}
						}
					}

					if ($smartPart)
					{
						if ($arItem["CODE"])
							array_unshift($smartPart, toLower($arItem["CODE"]));
						else
							array_unshift($smartPart, $arItem["ID"]);

						$smartParts[] = $smartPart;
					}
				}
			}

			if (!$smartParts)
				$smartParts[] = array("clear");

			return str_replace("#SMART_FILTER_PATH#", implode("/", self::encodeSmartParts($smartParts)), $url);
		}
		public function encodeSmartParts($smartParts)
		{
			foreach ($smartParts as &$smartPart)
			{
				$urlPart = "";
				foreach ($smartPart as $i => $smartElement)
				{
					if (!$urlPart)
						$urlPart .= $smartElement;
					elseif ($i == 'from' || $i == 'to')
						$urlPart .= '-'.$i.'-'.$smartElement;
					elseif ($i == 1)
						$urlPart .= '-is-'.$smartElement;
					else
						$urlPart .= '-or-'.$smartElement;
				}
				$smartPart = $urlPart;
			}
			unset($smartPart);
			return $smartParts;
		}
		public function findAndSet($arFields, $id, $find=false){
			if($arFields['CLASS_ID']=='CondBsktFldProduct' || $arFields['CLASS_ID']=='CondIBElement'){
				if($find && $arFields['DATA']['value'])return $arFields['DATA']['value'];
				elseif($id && is_array($arFields['DATA']['value']))$arFields['DATA']['value']=array($id);
				elseif($id)$arFields['DATA']['value']=$id;
			}
			if($arFields['CHILDREN']){
				foreach($arFields['CHILDREN'] as $k=>$arChild){
					if($find){
						$res_id = self::findAndSet($arChild, $id, $find);
						if($res_id>0)return $res_id;
					}
					elseif($id)$arFields['CHILDREN'][$k]=self::findAndSet($arChild, $id);
				}
			}
			if($id)return $arFields;
		}
		public function everyDayAction($find_id=false){
			\CModule::IncludeModule('sale');
			if($find_id){
				$discount_filter=array(
					'ACTIVE'=>'Y',
					'>ACTIVE_TO'=>new \Bitrix\Main\Type\DateTime(),
					'<=ACTIVE_FROM'=>new \Bitrix\Main\Type\DateTime(),
					'XML_ID'=>'EVERY_DAY_ACTION'
				);
			}else{
				$discount_filter=array(
					'<=ACTIVE_TO'=>new \Bitrix\Main\Type\DateTime(),
					'XML_ID'=>'EVERY_DAY_ACTION'
				);
			}
			$db_res = \Bitrix\Sale\Internals\DiscountTable::getList(
				array(
					'filter'=>$discount_filter
				)
			);
			$arProductDiscounts = $db_res->fetch();
			if(!$arProductDiscounts)return 'CSlytekHandler::everyDayAction();';
			$start_id=self::findAndSet($arProductDiscounts['ACTIONS_LIST'], false, true);
			if($find_id)return $start_id;

			\CModule::IncludeModule('iblock');
			$arOrder=Array('ID'=>'ASC'); $arNav=Array("nTopCount"=>1); $arSelect=Array("ID", 'IBLOCK_ID');
			$filter=Array("IBLOCK_ID"=>IntVal(CATALOG_IBLOCK_ID), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y", 
				'PROPERTY_ARTICLE_OF_DAY'=>'Y', 'ARTICLE_OF_DAY_TODAY'=>'Y');
			if($start_id>0){
				$filter['>ID']=$start_id;
			}
			$res = \CIBlockElement::GetList($arOrder, $filter, false, $arNav, $arSelect);
			$arItem = $res->GetNext();
			if(!$arItem['ID']){
				$filter['>ID']=0;
				$res = \CIBlockElement::GetList($arOrder, $filter, false, $arNav, $arSelect);
				$arItem = $res->GetNext();
			}
			if($arItem['ID']>0){
				$arUpdate=array(
					'ACTIVE'=>'Y',
					'ACTIVE_FROM' => new \Bitrix\Main\Type\DateTime(date('d.m.Y').' 00:00:00'),
					'ACTIVE_TO' => new \Bitrix\Main\Type\DateTime(date('d.m.Y', strtotime('+1 days')).' 00:00:00'),
					'CONDITIONS'=>self::findAndSet($arProductDiscounts['CONDITIONS_LIST'], $arItem['ID']),
					'ACTIONS'=>self::findAndSet($arProductDiscounts['ACTIONS_LIST'], $arItem['ID']),
				);
			}elseif($arProductDiscounts['ACTIVE']=='Y'){
				$arUpdate['ACTIVE']='N';
			}
			if($arUpdate){
				\CSaleDiscount::Update($arProductDiscounts['ID'], $arUpdate);
			}
			return '\Slytek\Catalog::everyDayAction();';
		}
		function getHistoryBasket(){
			static $basket = array();
			if(!$basket){
				\CModule::includeModule('sale');
				$uid = self::getCookieUserID();
				if(!$uid)return;
				$ids=array();
				$res = \Bitrix\Sale\Internals\BasketTable::getList(array(
					'filter'=>array('FUSER_ID'=>$uid, '!ORDER_ID'=>false)
				));
				while($item = $res->fetch()){
					$ids[]=$item['PRODUCT_ID'];
					$basket[$item['ID']]=$item;
				}
				if($ids){
					\CModule::includeModule('iblock');
					$files=array();
					$res = \CIBlockElement::GetList(Array(), Array("ID"=>$ids, "ACTIVE"=>"Y"), false, false, Array("ID", "NAME", "DETAIL_PICTURE", 'DETAIL_PAGE_URL'));
					while($arItem = $res->GetNext())
					{
						if($arItem['DETAIL_PICTURE']){
							$files[]=$arItem['DETAIL_PICTURE'];
						}
						$items[$arItem['ID']]=$arItem;
					}
				}
				if($files){
					$files=Media::picture(array(
						'MORE_PHOTO'=>$files,
						'TYPE'=>'GALLERY'
					));
				}
				foreach($basket as $id=>$item){
					if($items[$item['PRODUCT_ID']]){
						if($items[$item['PRODUCT_ID']]['DETAIL_PICTURE'] && $files[$items[$item['PRODUCT_ID']]['DETAIL_PICTURE']]){
							$items[$item['PRODUCT_ID']]['DETAIL_PICTURE']=$files[$items[$item['PRODUCT_ID']]['DETAIL_PICTURE']];
						}
						$basket[$id]['IBLOCK']=$items[$item['PRODUCT_ID']];
					}
				}
			}
			return $basket;
		}
		function getBasket($params, $prop=array()){
			\CModule::includeModule('sale');
			if(!$params['select'])$params['select']=array();
			if(!$params['filter'])$params['filter']=array();
			if(!$params['runtime'])$params['runtime']=array();
			if($prop){
				static $runtime = array();
				if(!$runtime)
				{
					$runtime=array(
						'BASKET_PROP' => array(
							'data_type' => \Bitrix\Main\Entity\Base::compileEntity(
								'BASKET_PROP',
								array(
									'BASKET_ID' => ['data_type' => 'integer'],
									'CODE' => ['data_type' => 'string'],
									'VALUE' => ['data_type' => 'string'],
								),
								array(
									'table_name' => 'b_sale_basket_props',
								)
							)->getDataClass(),
							'reference' => array(
								'=this.ID' => 'ref.BASKET_ID'
							),
							'join_type' => 'left'
						),
					);
				}
				$params['select'] = array_merge($params['select'], array('ID', 'BASKET_PROP.BASKET_ID', 'BASKET_PROP.CODE', 'BASKET_PROP.VALUE'));
				$params['filter'] = array_merge($params['filter'], array('BASKET_PROP.CODE'=> $prop['CODE'],'BASKET_PROP.VALUE'=> $prop['VALUE']));
				$params['runtime'] = array_merge($params['runtime'], $runtime);
				
			}
			return \Bitrix\Sale\Internals\BasketTable::getList($params);
		}
	}
	?>