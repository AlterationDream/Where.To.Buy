<?php
namespace Slytek\Partnerprices;

use Bitrix\Main,
Bitrix\Main\Loader,
Bitrix\Main\Localization\Loc;
Loc::loadMessages(__FILE__);

class PricesTable extends Main\Entity\DataManager
{
	/**
	 * Returns DB table name for entity.
	 *
	 * @return string
	 */
	public static function getTableName()
	{
		return 'slytek_partner_prices';
	}

	/**
	 * Returns entity map definition.
	 *
	 * @return array
	 */
	public static function getMap()
	{
		return array(
			'ID' => array(
				'data_type' => 'integer',
				'primary' => true,
				'autocomplete' => true,
				'title' => 'ID',
				),
			'ACTIVE' => array(
				'editable' => true,
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => 'ACTIVE',
				),
			'AGREED' => array(
				'editable' => true,
				'data_type' => 'boolean',
				'values' => array('N', 'Y'),
				'title' => 'AGREED',
				),
			'DATE_INSERT' => array(
				'data_type' => 'datetime',
				'required' => true,
				'title' => 'DATE_INSERT',
				),
			'DATE_UPDATE' => array(
				'data_type' => 'datetime',
				'title' => 'DATE_UPDATE',
				),
			'USER_ID' => array(
				'editable' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' => 'USER_ID',
				),
			'CATALOG_GROUP_ID' => array(
				'editable' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' => 'CATALOG_GROUP_ID',
				),
			'PRICE' => array(
				'editable' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' => 'PRICE',
				),
			'ELEMENT_ID' => array(
				'editable' => true,
				'data_type' => 'integer',
				'required' => true,
				'title' =>'ELEMENT_ID',
				),
			);
	}
	/**
	 * Returns validators for COOKIE_USER_ID field.
	 *
	 * @return array
	 */
	public static function onBeforeAdd(\Bitrix\Main\Entity\Event $event)
	{
		$result = new \Bitrix\Main\Entity\EventResult;
		$result->modifyFields(array('DATE_INSERT' => new \Bitrix\Main\Type\DateTime()));
		return $result;
	}
	public static function onBeforeUpdate(\Bitrix\Main\Entity\Event $event)
	{
		$result = new \Bitrix\Main\Entity\EventResult;
		$result->modifyFields(array('DATE_UPDATE' => new \Bitrix\Main\Type\DateTime()));
		return $result;
	}
	public static function OnAfterAdd(\Bitrix\Main\Entity\Event $event)
	{
		return self::OnHandler($event);
	}
	public static function OnAfterUpdate(\Bitrix\Main\Entity\Event $event)
	{
		return self::OnHandler($event);
	}
	public static function getGroupID($userID){
		Loader::includeModule('catalog');
		$arDefault=\CCatalogGroup::GetBaseGroup();
		if(!$userID)return $arDefault['ID'];
		$rsUsers = \CUser::GetList(($by="name"), ($order="asc"), array('ID'=>$userID), array('SELECT'=>array('UF_CITY'), "FIELDS"=>array('ID'))); 
		if($arUser = $rsUsers->GetNext()){
			if($arUser['UF_CITY']>0){
				global $APPLICATION;
				$prices=$APPLICATION->IncludeComponent("slytek:main.include","",Array("AREA_FILE_SHOW" => "prop","PROPERTY_ID" => "regions","SITE_ID" => SITE_ID), false, array('HIDE_ICONS'=>'Y'));
				foreach($prices as $city=>$ar){
					if($ar[$city.'_city']==$arUser['UF_CITY']){
						$dbPriceType = \CCatalogGroup::GetList(
							array("SORT" => "ASC"),
							array("ID" => $ar[$city.'_price'])
							);
						if($arPriceType = $dbPriceType->Fetch())
						{
							return $arPriceType['ID'];
						}
					}
				}
			}else{
				return $arDefault['ID'];
			}
		}
	}
	public static function OnHandler(\Bitrix\Main\Entity\Event $event)
	{
		$arParams = $event->getParameters();
		$dbQuery =self::getList(array("filter" => array('ID'=>$arParams['id']['ID'])));
		if($arPrice = $dbQuery->fetch()){
			if($arPrice['AGREED']=='Y' && $arPrice['ACTIVE']=='Y' && $arPrice['PRICE']>0){
				Loader::includeModule('catalog');
				Loader::includeModule('currency');
				$currency=\CCurrency::GetBaseCurrency();

				$dbQuery2 =self::getList(array("filter" => array('!ID'=>$arPrice['ID'],'ELEMENT_ID'=>$arPrice['ELEMENT_ID'], 'CATALOG_GROUP_ID'=>$arPrice['CATALOG_GROUP_ID'])));
				while($arOther = $dbQuery2->fetch()){
					\Slytek\Partnerprices\PricesTable::update($arOther['ID'], array('AGREED'=>'N'));
				}

				$dbProductPrice = \CPrice::GetListEx(array(),array("PRODUCT_ID" => $arPrice['ELEMENT_ID'], "CATALOG_GROUP_ID" => $arPrice['CATALOG_GROUP_ID']),false,false,array("ID", "CATALOG_GROUP_ID", "PRICE"));
				if ($arOldPrice = $dbProductPrice->Fetch())
				{
					\CPrice::Update($arOldPrice["ID"], array('PRICE'=>$arPrice['PRICE']));
				}else{
					\CPrice::Add(Array(
						"PRODUCT_ID" => $arPrice['ELEMENT_ID'],
						"CATALOG_GROUP_ID" => $arPrice['CATALOG_GROUP_ID'],
						"PRICE" => $arPrice['PRICE'],
						"CURRENCY" => $currency,
						));
				}
			}
		}
		return $event;
	}

}