<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
/** @var array $arParams */
/** @var array $arResult */
/** @global CMain $APPLICATION */
/** @global CUser $USER */
/** @global CDatabase $DB */
/** @var CBitrixComponentTemplate $this */
/** @var string $templateName */
/** @var string $templateFile */
/** @var string $templateFolder */
/** @var string $componentPath */
/** @var CBitrixComponent $component */
$this->setFrameMode(true);
if(!$arResult["ITEMS"])return;
?>
<div class="online-stores-content">
<h2>Наши партнерские онлайн-магазины</h2>
<div class="online-stores-list">
<?foreach($arResult["ITEMS"] as $arItem):
	$this->AddEditAction($arItem['ID'], $arItem['EDIT_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_EDIT"));
	$this->AddDeleteAction($arItem['ID'], $arItem['DELETE_LINK'], CIBlock::GetArrayByID($arItem["IBLOCK_ID"], "ELEMENT_DELETE"), array("CONFIRM" => GetMessage('CT_BNL_ELEMENT_DELETE_CONFIRM')));
	?>
	<div class="store-item" id="<?=$this->GetEditAreaId($arItem['ID']);?>">
		<?if($arItem['CODE']):?><a target="_blank" href="<?=$arItem["CODE"]?>"><?endif?>
						<div class="store-img"><img
						src="<?=$arItem["PREVIEW_PICTURE"]["SRC"]?>"
						alt="<?=$arItem["PREVIEW_PICTURE"]["ALT"]?>"
						/></div>
						<div class="store-title"><?=$arItem['NAME']?></div>
		<?if($arItem['CODE']):?></a><?endif?>
	</div>
<?endforeach;?>
</div></div>