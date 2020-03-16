<?
if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
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
if($arResult['CONTENT']){
	$banner = $arResult['CONTENT'];
	if($banner['image']):?>
		<div class="banner-shops-container">
			<a <?if($banner['link']):?>href="<?=$banner['link']?>" target="_blank"<?endif?> class="banner guarantee"> 
				<img src="<?=$banner['image']?>"  title="<?=$banner['text']?>">
				<?if($banner['text']):?><div class="shops-banner-text"><?=$banner['text']?></div><?endif?>
			</a>
		</div>
		<?endif;
	} 
	?>