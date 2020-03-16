<?

/*
 * include
 *   /modules/slytek.core
 *   templates/.default/
 */

if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();

$arParams['MAP_ID'] =
(strlen($arParams["MAP_ID"])<=0 || !preg_match("/^[A-Za-z_][A-Za-z01-9_]*$/", $arParams["MAP_ID"])) ? 
'MAP_'.$this->randString() : $arParams['MAP_ID'];

$current_search = $_GET['ys'];

if (($strPositionInfo = $arParams['~MAP_DATA']) && CheckSerializedData($strPositionInfo) && ($arResult['POSITION'] = unserialize($strPositionInfo)))
{
	$arParams['INIT_MAP_LON'] = $arResult['POSITION']['yandex_lon'];
	$arParams['INIT_MAP_LAT'] = $arResult['POSITION']['yandex_lat'];
	$arParams['INIT_MAP_SCALE'] = $arResult['POSITION']['yandex_scale'];
}

CJSCore::Init();


$arParams["IBLOCK_TYPE"] = trim($arParams["IBLOCK_TYPE"]);

$arParams["IBLOCK_ID"] = intval(trim($arParams["IBLOCK_ID"]));

if(!$arParams['IBLOCK_ID'])return;

$arParams['IS_AJAX'] = $_REQUEST['ajax_shops']==='Y';

CModule::includeModule('iblock');
CModule::includeModule('slytek.core');

if(!function_exists('calcKmToPlace')){
	function calcKmToPlace($lat1, $lon1, $lat2, $lon2){
		$R = 6371; // km
		$dLat = deg2rad($lat2-$lat1);
		$dLon = deg2rad($lon2-$lon1);
		$lat1 = deg2rad($lat1);
		$lat2 = deg2rad($lat2);

		$a = sin($dLat/2) * sin($dLat/2) +
		sin($dLon/2) * sin($dLon/2) * cos($lat1) * cos($lat2); 

		$c = 2 * atan2(sqrt($a), sqrt(1-$a)); 
		$d = $R * $c;
		return $d;
	}
}
$arParams['SERVICES'] = $arParams['IBLOCK_ID']==15;

$obCache = new CPHPCache();
if ($obCache->InitCache(0, 'shops_'.$arParams['IBLOCK_ID'], "/iblock/where"))
{
	$arResult = $obCache->GetVars();
}
elseif ($obCache->StartDataCache())
{
	$arResult = array();

	$arResult['RADIUS'] = array();
	foreach(array(1, 5, 25, 50, 100) as $km){
		$arResult['RADIUS'][$km] = array('NAME'=>$km.' км', 'VALUE'=>$km);
	}
	
	$arResult['SECTIONS'] = array();

	$arFilter = array();
	
	if($arParams['IS_AJAX'] && $_REQUEST['geo']){
		$geo = $_REQUEST['geo'];
		$radius = intval($_REQUEST['radius']);
		if(!array_key_exists($radius, $arResult['RADIUS'])){
			unset($radius);
		}
		$geo['coordinates'] = explode(',', $geo['coordinates']);

		if($geo['city'] || $geo['region'] || $geo['country']){
			$find_section = true;
			$filter = array(
				'LOGIC'=>'OR',
			);
			if($geo['city'])$filter[]=array('NAME'=> $geo['city']);
			if($geo['region'])$filter[]=array('NAME'=> $geo['region']);
			if($geo['country'])$filter[]=array('NAME'=> $geo['country']);
		}
		else{
			$filter = array();
		}

		$res = Bitrix\Iblock\SectionTable::getList(array(
			'order'=>array('DEPTH_LEVEL'=>'desc'),
			'filter'=>array(
				'IBLOCK_ID'=>$arParams["IBLOCK_ID"], 
				'ACTIVE'=>'Y', 
				'GLOBAL_ACTIVE'=>'Y',
				$filter
			),
			'select'=>array('ID', 'NAME', 'DEPTH_LEVEL')
		));
		while($item = $res->fetch()){
			$arResult['SECTIONS'][$item['ID']]=$item;
		}
		if($find_section){
			for($i=3; $i>0; $i--){
				foreach($arResult['SECTIONS'] as $arSection){
					if(intval($arSection['DEPTH_LEVEL'])==$i){
						$arResult['SECTION'] = $arSection;
						break(2);
					}
				}
			}
		}
	}
	
	if($arResult['SECTION'] || $geo['coordinates']){
		$arFilter = array('IBLOCK_ID'=>$arParams["IBLOCK_ID"], 'ACTIVE'=>'Y', 'INCLUDE_SUBSECTIONS'=>'Y');
		if($arResult['SECTION']){
			$arFilter['SECTION_ID'] = $arResult['SECTION']['ID'];

			$res = CIBlockElement::GetList(array('NAME'=>'ASC', 'ID'=>'DESC'), $arFilter, false, $navParams, array('ID', 'NAME', 'IBLOCK_ID', 'IBLOCK_SECTION_ID'));
			while($obElement = $res->GetNextElement())
			{
				$item = $obElement->GetFields();
				$item['PROPERTIES'] = $obElement->GetProperties();

				$arItem = array(
					'ID'=>$item['ID'],
					'NAME'=>$item['NAME'],
					'ADDRESS'=>$item['PROPERTIES']['adress']['~VALUE']['TEXT'],
					'PHONE'=>$item['PROPERTIES']['phone']['VALUE'],
					'SCHEDULE'=>$item['PROPERTIES']['center_operation']['~VALUE']['TEXT'],
					'EMAIL'=>$item['PROPERTIES']['email']['VALUE'],
					'SITE'=>$item['PROPERTIES']['site']['VALUE'],
					'CITY'=> $arResult['SECTIONS'][$item['IBLOCK_SECTION_ID']]['NAME'],
					'GEO'=> explode(',' , $item['PROPERTIES']['map']['VALUE']),
					'PAGE'=>'/print/?page='.($arParams['SERVICES']?'services':'where_to_buy').'&id='.$item['ID'],
					'LENGTH'=>999999999,
					'SERVICE'=>$arParams['SERVICES'],
					'RED'=>$item['PROPERTIES']['firm']['VALUE'] && ToLower($item['PROPERTIES']['firm']['VALUE'])!=='нет'
				);
				if($arItem['EMAIL']){
					$emails = array();
					foreach($arItem['EMAIL'] as $k=>$email){
						$email = explode(',', $email);
						foreach($email as $em){
							$emails[]='<a class="point-email" href="mailto:'.$em.'">'.$em.'</a>';
						}
					}
					$arItem['EMAIL'] = implode('; ', $emails);
				}
				if($arItem['PHONE']){
					$phones = array();
					foreach($arItem['PHONE'] as $k=>$phone){
						$phone = explode(',', $phone);
						foreach($phone as $ph){
							$phones[]='<a href="tel:'.preg_replace('/[^0-9+]*?/','',$ph).'">'.$ph.'</a>';
						}
					}
					$arItem['PHONE'] = implode('; ', $phones);
				}
				if($geo && $arItem['GEO']){
					$arItem['LENGTH'] = calcKmToPlace(floatval($arItem['GEO'][0]), floatval($arItem['GEO'][1]), floatval($geo['coordinates'][0]), floatval($geo['coordinates'][1]));
					if($radius>0 && $arItem['LENGTH']>$radius){
						continue;
					}
				}

				$arResult['ITEMS'][]=$arItem;
			}
			$arResult['ITEMS'] = \Slytek\Handler::sort_by_key($arResult['ITEMS'], 'LENGTH', false);
			$items = array();
			foreach($arResult['ITEMS'] as $k=>$item){
				if($item['RED']){
					$items[]=$item;
					unset($arResult['ITEMS'][$k]);
				}
			}
			$arResult['ITEMS'] = array_merge($items, $arResult['ITEMS']);
		}
		else{
			$navParams = array('nTopCount'=>10);
		}
	}
	$obCache->EndDataCache($arResult);
}
$arResult['SERVICES'] = $arParams['IBLOCK_ID']==15;
$arResult['INFO'] = \Slytek\Settings::get($arResult['SERVICES']?'services':'wheretobuy');
//$arResult['CITY'] = 'Москва';
$this->IncludeComponentTemplate($arParams['IS_AJAX']?'ajax':false);
?>