<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

if(!empty($arResult["ITEMS"])):?>
	<?foreach($arResult["ITEMS"] as $i => $arItem):?>
	<?if($arItem['ALL']):?>
	<div class="ownedmuhaha-search-input-result-item ownedmuhaha-search-input-result-all" data-item>
		<a href="<?echo $arItem["URL"]?>">
			<?echo $arItem["NAME"]?>
		</a>
	</div>
	<?else:?>
	<div class="ownedmuhaha-search-input-result-item" data-item>
		<a href="<?=$arItem['URL']?>">
			<?=$arItem['TITLE_FORMATED']?><?if(isset($arItem['COUNT'])):?> (<?=$arItem['COUNT']?> шт)<?endif?>
		</a>
	</div>
	<?endif;?>
	
	<?endforeach;?>
	<?endif;
	?>