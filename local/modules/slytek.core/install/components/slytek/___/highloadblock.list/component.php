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

/** @global CIntranetToolbar $INTRANET_TOOLBAR */
global $INTRANET_TOOLBAR;
$requiredModules = array('highloadblock');
foreach ($requiredModules as $requiredModule)
{
	if (!CModule::IncludeModule($requiredModule))
	{
		ShowError(GetMessage("F_NO_MODULE"));
		return 0;
	}
}

use Bitrix\Highloadblock as HL;
use Bitrix\Main\Entity;

if($this->startResultCache(false, array($arParams)))
{
	// hlblock info
	$hlblock_id = $arParams['BLOCK_ID'];
	if (empty($hlblock_id))
	{
		ShowError(GetMessage('HLBLOCK_LIST_NO_ID'));
		return 0;
	}
	$hlblock = HL\HighloadBlockTable::getById($hlblock_id)->fetch();
	if (empty($hlblock))
	{
		ShowError(GetMessage('HLBLOCK_LIST_404'));
		return 0;
	}


	$entity = HL\HighloadBlockTable::compileEntity($hlblock);
	$fields = $GLOBALS['USER_FIELD_MANAGER']->GetUserFields('HLBLOCK_'.$hlblock['ID'], 0, LANGUAGE_ID);

	$sortId = 'ID';
	$sortType = 'DESC';
	if (isset($arParams['SORT_FIELD']) && isset($fields[$arParams['SORT_FIELD']]))
	{
		$sortId = $arParams['SORT_FIELD'];
	}

	if (isset($arParams['SORT_ORDER']) && in_array($arParams['SORT_ORDER'], array('ASC', 'DESC'), true))
	{
		$sortType = $arParams['SORT_ORDER'];
	}

	if (isset($arParams['ROWS_PER_PAGE']) && $arParams['ROWS_PER_PAGE']>0)
	{
		$pagenId = isset($arParams['PAGEN_ID']) && trim($arParams['PAGEN_ID']) != '' ? trim($arParams['PAGEN_ID']) : 'page';
		$perPage = intval($arParams['ROWS_PER_PAGE']);
		$nav = new \Bitrix\Main\UI\PageNavigation($pagenId);
		$nav->allowAllRecords(true)
		->setPageSize($perPage)
		->initFromUri();
	}
	else
	{
		$arParams['ROWS_PER_PAGE'] = 0;
	}

	$mainQuery = new Entity\Query($entity);
	$mainQuery->setSelect(array('*'));
	$mainQuery->setOrder(array($sortId => $sortType));

	if (
		isset($arParams['FILTER_NAME']) &&
		!empty($arParams['FILTER_NAME']) &&
		preg_match('/^[A-Za-z_][A-Za-z01-9_]*$/', $arParams['FILTER_NAME']))
	{
		global ${$arParams['FILTER_NAME']};
		$filter = ${$arParams['FILTER_NAME']};
		if (is_array($filter))
		{
			$mainQuery->setFilter($filter);
		}
	}

	if ($perPage > 0)
	{
		$mainQueryCnt = $mainQuery;
		$result = $mainQueryCnt->exec();
		$result = new CDBResult($result);
		$nav->setRecordCount($result->selectedRowsCount());
		$arResult['nav_object'] = $nav;
		unset($mainQueryCnt, $result);

		$mainQuery->setLimit($nav->getLimit());
		$mainQuery->setOffset($nav->getOffset());
	}

	$result = $mainQuery->exec();
	$result = new CDBResult($result);


	$rows = array();
	$tableColumns = array();
	$arFiles = array();
	while ($row = $result->fetch())
	{
		foreach($row as $name=>$value){
			if($fields[$name]['USER_TYPE_ID']=='file'){
				$arFiles[$value]=array();
			}
		}
		$arResult["ITEMS"][] = $row;
	}
	if($arFiles){
		$res = Bitrix\Main\FileTable::getList(array(
			'filter'=>array(
				'ID'=>array_keys($arFiles)
			)
		));
		while($file = $res->fetch()){
			$file["SRC"] = CFile::GetFileSRC($file);
			$arFiles[$file['ID']]=$file;
		}
	}
	foreach ($arResult["ITEMS"] as $k=>$arItem)
	{
		foreach($arItem as $name=>$value){
			if($fields[$name]['USER_TYPE_ID']=='file' && $arFiles[$value]){
				$arItem[$name]=$arFiles[$value];
			}
		}
		$arResult["ITEMS"][$k]=$arItem;
	}
	$arResult["FIELDS"]=$fields;
	$this->includeComponentTemplate();
}

