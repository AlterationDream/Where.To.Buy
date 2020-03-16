<?
namespace Slytek;
class CatalogSet {
	function removeDiscount($arUpdateFields){
		if($arUpdateFields['ID']<=0)return;
		if($arUpdateFields['IBLOCK_ID']!=SETS_IBLOCK_ID)return;
		\CModule::includeModule('sale');
		$db_res = \CSaleDiscount::GetList(array("SORT" => "ASC"),array("XML_ID" => 'set'.$arUpdateFields['ID'], ),false,false,array());
		if ($arDiscount = $db_res->Fetch())
		{
			\CSaleDiscount::Delete($arDiscount['ID']);
		}
	}
	function DiscountByItem($arUpdateFields){
		if($arUpdateFields['ID']<=0)return;
		if($arUpdateFields['IBLOCK_ID']!=SETS_IBLOCK_ID)return;

		\CModule::includeModule('sale');
		\CModule::includeModule('iblock');
		$res = \CIBlockElement::GetList(Array(), Array("ID"=>IntVal($arUpdateFields['ID'])), false, false, Array("ID", "NAME", "IBLOCK_ID", 'ACTIVE'));
		if($ob = $res->GetNextElement())
		{
			$arItem = $ob->GetFields();
			$arItem['PROPERTIES'] = $ob->GetProperties();
		}
		$pids=$arItem['PROPERTIES']['PRODUCTS']['VALUE'];
		if(!$arItem || !$pids || !$arItem['PROPERTIES']['DISCOUNT']['VALUE'])return;
		$arChildren=array();
		$arChildrenConditions=array();
		foreach($pids as $pid){
			$arChildren[]=Array('CLASS_ID' => 'CondBsktFldProduct','DATA' => Array('logic' => 'Equal','value' => $pid));
			$arChildrenConditions[]=Array(
				'CLASS_ID' => 'CondBsktProductGroup',
				'DATA' => Array('Found' => 'Found','All' => 'AND'),
				'CHILDREN' =>Array(Array('CLASS_ID' => 'CondBsktFldProduct','DATA' => Array('logic' => 'Equal','value' => $pid))
			)
			);
		}
		$arFields=array(
			'LID' => 's1',
			'NAME' => $arItem['NAME'],
			'ACTIVE' => $arItem['ACTIVE'],
			'SORT' => $arItem['SORT'],
			'PRIORITY' => 1,
			'LAST_DISCOUNT' => 'N',
			'LAST_LEVEL_DISCOUNT' => 'N',
			'XML_ID' => 'set'.$arItem['ID'],
			'CONDITIONS' => Array
			(
				'CLASS_ID' => 'CondGroup',
				'DATA' => Array('All' => 'AND','True' => 'True'),
				'CHILDREN' => $arChildrenConditions
			),
			'ACTIONS' => Array(
				'CLASS_ID' => 'CondGroup',
				'DATA' => Array('All' => 'AND'),
				'CHILDREN' => Array(Array('CLASS_ID' => 'ActSaleBsktGrp','DATA' => Array('Type' => 'Discount','Value' => $arItem['PROPERTIES']['DISCOUNT']['VALUE'], 'Unit' => 'Perc', 'Max' => 10000000, 'All' => 'OR', 'True' => 'True'),'CHILDREN' => $arChildren))
			),
			'USER_GROUPS' => Array(2)
		);
		$db_res = \CSaleDiscount::GetList(array("SORT" => "ASC"),array("XML_ID" => 'set'.$arItem['ID'], ),false,false,array());
		if ($arDiscount = $db_res->Fetch())
		{
			\CSaleDiscount::Update($arDiscount['ID'], $arFields);
		}else{
			\CSaleDiscount::Add($arFields);
		}
	}
}
?>