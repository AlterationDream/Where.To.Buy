<?
namespace Slytek;
class Avito
{
	function GetParams(){
		$arParams=unserialize(file_get_contents(__DIR__.'/../settings/avito_settings.dat'));
		foreach($arParams['rows'] as $k=>$row){
			$row['value']=json_decode($row['value'], true);
			$row['value']=self::parseCondition($row['value'], array('INCLUDE_SUBSECTIONS'=>'A', 'SECTION_GLOBAL_ACTIVE'=>'Y'));
			if($row['value'])$arParams['rows'][$k]=$row;
		}
		return $arParams;
	}
	function processText($text){
		return strip_tags($text);
	}
	function clearText($text){
		return preg_replace(array('/Компания[\s\W]*\«Акваклининг[\s\W]*Центр\»[\w\s\W]*212\-07\-[0-9]{0,2}[\w\s\W]*?/is', '/(\+7)?\(495\)[\s]*?212\-07\-[0-9]{0,2}/is'), '', $text);
	}
	function export()
	{
		$arParams=self::GetParams();
		\CModule::includeModule('iblock');
		$domain=(\CMain::IsHttps()?'https://':'http://').$_SERVER['HTTP_HOST'];
		header("Content-Type: text/xml; charset=utf-8");
		$xml='<?xml version="1.0" ?>'."\n";
		$xml.='<Ads formatVersion="3" target="Avito.ru">';
		$arSelect = Array("ID", "NAME", 'IBLOCK_ID', 'DETAIL_TEXT','PREVIEW_PICTURE', 'DETAIL_PICTURE');
		$arFilter = Array("IBLOCK_ID"=>IntVal(2), "ACTIVE"=>"Y", 'CATALOG_AVAILABLE'=>'Y');
		$pic_ids=array();
		$items=array();
		foreach($arParams['rows'] as $k=>$row){
			if(!$row['value'])continue;
			$filter=$arFilter;
			$filter[]=$row['value'];
			$res = \CIBlockElement::GetList(Array(), $filter, false, false, $arSelect);
			while($arItem = $res->GetNext())
			{
				$arItem['PICTURES']=array();
				if($arItem['DETAIL_PICTURE'])$arItem['PICTURES'][]=$arItem['DETAIL_PICTURE'];
				else if($arItem['PREVIEW_PICTURE'])$arItem['PICTURES'][]=$arItem['PREVIEW_PICTURE'];
				$resProps = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], "sort", "asc", array("CODE" => "ARTNUMBER"));
				while ($ob = $resProps->GetNext())
				{
					$arItem['ARTNUMBER']=$ob['VALUE'];
				}
				$resProps = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], "sort", "asc", array("CODE" => "BRAND_REF"));
				while ($ob = $resProps->GetNext())
				{
					$arItem['BRAND']=$ob['VALUE'];
				}
				$resProps = \CIBlockElement::GetProperty($arItem['IBLOCK_ID'], $arItem['ID'], "sort", "asc", array("CODE" => "MORE_PHOTO"));
				while ($ob = $resProps->GetNext())
				{
					$arItem['PICTURES'][] = $ob['VALUE'];
					if(count($arItem['PICTURES'])>=5)break;
				}
				$pic_ids=array_merge($pic_ids, $arItem['PICTURES']);
				$items[$arItem['ID']]=$arItem;
				$row['items'][]=$arItem['ID'];
				$arParams['rows'][$k]=$row;
			}
		}
		if($pic_ids){
			$resFiles = \CFile::GetList(array("ID"=>"asc"), array("ID"=>$pic_ids));
			while($arFile = $resFiles->GetNext())
			{
				$arFile["SRC"] = \CFile::GetFileSRC($arFile);
				
				$files[$arFile['ID']]='<Image url="'.$domain.$arFile['SRC'].'" />';
			}
		}
		$db_res = \CPrice::GetList(array(),array("PRODUCT_ID" => array_keys($items),"CATALOG_GROUP_ID" => 1));
		while($arPrice = $db_res->Fetch()){
			$items[$arPrice['PRODUCT_ID']]['PRICE']=ceil($arPrice['PRICE']);
		}

		foreach($arParams['rows'] as $k=>$row){
			foreach($row['items'] as $item){
				$arItem=$items[$item];
				$xmlPictures='';
				if($arItem['PRICE']<=0)continue;
				if($arItem['PICTURES']){
					foreach($arItem['PICTURES'] as $pid)
					{
						$xmlPictures.=$files[$pid];
					}
				}
				if($xmlPictures){
					$xmlPictures='<Images>'.$xmlPictures.'</Images>';
				}
				$arItem['DETAIL_TEXT']=trim(strip_tags($arItem['DETAIL_TEXT']));
				$arItem['DETAIL_TEXT']=self::clearText($arItem['DETAIL_TEXT']);
				
				if($arItem['DETAIL_TEXT']){
					$arItem['DETAIL_TEXT']=TruncateText($arItem['DETAIL_TEXT'], 2900);
				}else{
					$arItem['DETAIL_TEXT']=$arItem['NAME'];
				}
				if($arItem['ARTNUMBER']){
					$arItem['DETAIL_TEXT']='Артикул: '.$arItem['ARTNUMBER'].".\n ".$arItem['DETAIL_TEXT'];
				}
				$arItem['DETAIL_TEXT']='<![CDATA['.($arItem['DETAIL_TEXT']).']]>';
				
				$is_num=preg_match('/^[0-9-]*$/i', $row['TypeId']);
				$xml.='<Ad>
				<Id>'.$arItem['ID'].'</Id>'.
				(!$is_num?('<AdType>'.($arItem['BRAND']=='acg'?'Товар от производителя':'Товар приобретен на продажу')).'</AdType>':'').
				'<AllowEmail>'.$arParams['settings']['AllowEmail'].'</AllowEmail>
				<ManagerName>'.$arParams['settings']['ManagerName'].'</ManagerName>
				<ContactPhone>'.$arParams['settings']['ContactPhone'].'</ContactPhone>
				<Region>'.$arParams['settings']['Region'].'</Region>
				'.($arParams['settings']['City']?'<City>'.$arParams['settings']['City'].'</City>':'').'
				'.($arParams['settings']['Subway']?'<Subway>'.$arParams['settings']['Subway'].'</Subway>':'').'
				'.($arParams['settings']['District']?'<District>'.$arParams['settings']['District'].'</District>':'').'
				<Category>'.$row['Category'].'</Category>'.   
				($is_num?'<TypeId>'.$row['TypeId'].'</TypeId>':'<GoodsType>'.$row['TypeId'].'</GoodsType>').        
				'<Title><![CDATA['.($arItem['NAME']).']]></Title>
				<Description>'.$arItem['DETAIL_TEXT'].'</Description>
				<Price>'.$arItem['PRICE'].'</Price>'.$xmlPictures.
				'</Ad>';
			}
		}
		$xml.='</Ads>';
		echo $xml;
	}
	protected function parseCondition($condition, $params)
	{
		$result = array();

		if (!empty($condition) && is_array($condition))
		{
			if ($condition['CLASS_ID'] === 'CondGroup')
			{
				if (!empty($condition['CHILDREN']))
				{
					foreach ($condition['CHILDREN'] as $child)
					{
						$childResult = self::parseCondition($child, $params);

						// is group
						if ($child['CLASS_ID'] === 'CondGroup')
						{
							$result[] = $childResult;
						}
						// same property names not overrides each other
						elseif (isset($result[key($childResult)]))
						{
							$fieldName = key($childResult);

							if (!isset($result['LOGIC']))
							{
								$result = array(
									'LOGIC' => $condition['DATA']['All'],
									array($fieldName => $result[$fieldName])
								);
							}

							$result[][$fieldName] = $childResult[$fieldName];
						}
						else
						{
							$result += $childResult;
						}
					}

					if (!empty($result))
					{
						self::parsePropertyCondition($result, $condition, $params);

						if (count($result) > 1)
						{
							$result['LOGIC'] = $condition['DATA']['All'];
						}
					}
				}
			}
			else
			{
				$result += self::parseConditionLevel($condition, $params);
			}
		}

		return $result;
	}

	protected function parseConditionLevel($condition, $params)
	{
		$result = array();

		if (!empty($condition) && is_array($condition))
		{
			$name = self::parseConditionName($condition);
			if (!empty($name))
			{
				$operator = self::parseConditionOperator($condition);
				$value = self::parseConditionValue($condition, $name);
				$result[$operator.$name] = $value;

				if ($name === 'SECTION_ID')
				{
					$result['INCLUDE_SUBSECTIONS'] = isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'N' ? 'N' : 'Y';

					if (isset($params['INCLUDE_SUBSECTIONS']) && $params['INCLUDE_SUBSECTIONS'] === 'A')
					{
						$result['SECTION_GLOBAL_ACTIVE'] = 'Y';
					}

					$result = array($result);
				}
				if ($name === 'ID')
				{
					$result = array($result);
				}
			}
		}

		return $result;
	}

	protected function parseConditionName(array $condition)
	{
		$name = '';
		$conditionNameMap = array(
			'CondIBXmlID' => 'XML_ID',
			'CondIBElement' => 'ID',
			'CondIBSection' => 'SECTION_ID',
			'CondIBDateActiveFrom' => 'DATE_ACTIVE_FROM',
			'CondIBDateActiveTo' => 'DATE_ACTIVE_TO',
			'CondIBSort' => 'SORT',
			'CondIBDateCreate' => 'DATE_CREATE',
			'CondIBCreatedBy' => 'CREATED_BY',
			'CondIBTimestampX' => 'TIMESTAMP_X',
			'CondIBModifiedBy' => 'MODIFIED_BY',
			'CondIBTags' => 'TAGS',
			'CondCatQuantity' => 'CATALOG_QUANTITY',
			'CondCatWeight' => 'CATALOG_WEIGHT'
		);

		if (isset($conditionNameMap[$condition['CLASS_ID']]))
		{
			$name = $conditionNameMap[$condition['CLASS_ID']];
		}
		elseif (strpos($condition['CLASS_ID'], 'CondIBProp') !== false)
		{
			$name = $condition['CLASS_ID'];
		}

		return $name;
	}

	protected function parseConditionOperator($condition)
	{
		$operator = '';

		switch ($condition['DATA']['logic'])
		{
			case 'Equal':
			$operator = '';
			break;
			case 'Not':
			$operator = '!';
			break;
			case 'Contain':
			$operator = '%';
			break;
			case 'NotCont':
			$operator = '!%';
			break;
			case 'Great':
			$operator = '>';
			break;
			case 'Less':
			$operator = '<';
			break;
			case 'EqGr':
			$operator = '>=';
			break;
			case 'EqLs':
			$operator = '<=';
			break;
		}

		return $operator;
	}

	protected function parseConditionValue($condition, $name)
	{
		$value = $condition['DATA']['value'];

		switch ($name)
		{
			case 'DATE_ACTIVE_FROM':
			case 'DATE_ACTIVE_TO':
			case 'DATE_CREATE':
			case 'TIMESTAMP_X':
			$value = ConvertTimeStamp($value, 'FULL');
			break;
		}

		return $value;
	}

	protected function parsePropertyCondition(array &$result, array $condition, $params)
	{
		if (!empty($result))
		{
			$subFilter = array();

			foreach ($result as $name => $value)
			{
				if (!empty($result[$name]) && is_array($result[$name]))
				{
					self::parsePropertyCondition($result[$name], $condition, $params);
				}
				else
				{
					if (($ind = strpos($name, 'CondIBProp')) !== false)
					{
						list($prefix, $iblock, $propertyId) = explode(':', $name);
						$operator = $ind > 0 ? substr($prefix, 0, $ind) : '';
						\CModule::includeModule('catalog');
						$catalogInfo = \CCatalogSku::GetInfoByIBlock($iblock);
						if (!empty($catalogInfo))
						{
							if (
								$catalogInfo['CATALOG_TYPE'] != \CCatalogSku::TYPE_CATALOG
								&& $catalogInfo['IBLOCK_ID'] == $iblock
							)
							{
								$subFilter[$operator.'PROPERTY_'.$propertyId] = $value;
							}
							else
							{
								$result[$operator.'PROPERTY_'.$propertyId] = $value;
							}
						}

						unset($result[$name]);
					}
				}
			}

			if (!empty($subFilter) && !empty($catalogInfo))
			{
				$offerPropFilter = array(
					'IBLOCK_ID' => $catalogInfo['IBLOCK_ID'],
					'ACTIVE_DATE' => 'Y',
					'ACTIVE' => 'Y'
				);

				if ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'Y')
				{
					$offerPropFilter['HIDE_NOT_AVAILABLE'] = 'Y';
				}
				elseif ($params['HIDE_NOT_AVAILABLE_OFFERS'] === 'L')
				{
					$offerPropFilter[] = array(
						'LOGIC' => 'OR',
						'CATALOG_AVAILABLE' => 'Y',
						'CATALOG_SUBSCRIBE' => 'Y'
					);
				}

				if (count($subFilter) > 1)
				{
					$subFilter['LOGIC'] = $condition['DATA']['All'];
					$subFilter = array($subFilter);
				}

				$result['=ID'] = \CIBlockElement::SubQuery(
					'PROPERTY_'.$catalogInfo['SKU_PROPERTY_ID'],
					$offerPropFilter + $subFilter
				);
			}
		}
	}
}
?>