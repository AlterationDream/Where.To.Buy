<?php if (!defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED!==true)die();

$requiredModules = array('highloadblock', 'slytek.core');
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

// hlblock info
$hlblock_id = $arParams['BLOCK_ID'];
$arResult['rows'] = \Slytek\Hl::get($hlblock_id, array(
	'filter'=>array(),
	'picture'=>array('CODE'=>'UF_FILE')
));




$this->IncludeComponentTemplate();