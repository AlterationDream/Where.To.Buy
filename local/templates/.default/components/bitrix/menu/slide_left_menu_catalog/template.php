<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
	<div class="title js-main-left-menu">
		<span>Каталог</span>
		<div class="btn-left-menu ">
			<span></span>
		</div>
	</div>
	<div class="left-menu-list left-menu-list-type-c">
		<?
		$previousLevel = 0;
		foreach($arResult as $arItem):?>
			<?if ($previousLevel && $arItem["DEPTH_LEVEL"] < $previousLevel):?>
				<?=str_repeat("</div></div>", ($previousLevel - $arItem["DEPTH_LEVEL"]));?>
			<?endif?>

			<?if ($arItem["IS_PARENT"]):?>

				<?if ($arItem["DEPTH_LEVEL"] == 1):?>
					<div class="left-menu-item-first-level ">
						<a href="<?=$arItem['LINK']?>" class="left-menu-link-first-level">
							<span><?=$arItem["TEXT"]?></span>
							<span class="icon correct-browser" style="background-image: url('<?=$arItem["UF_IMG"]?>');">
								<?echo $arItem["UF_SVG"]?>
							</span>
						</a>
						<div class="left-menu-list-second-level second-menu-list">
                    <?elseif ($arItem["DEPTH_LEVEL"] == 2):?>
                        <div class="left-menu-item-second-level">
                            <a href="<?=$arItem['LINK']?>" class="left-menu-link-second-level inside"><?=$arItem["TEXT"]?></a>
                            <div class="left-menu-list-third-level type-a">
                    <?else:?>
                        <a href="<?=$arItem['LINK']?>" class="left-menu-link-third-level"><span><?=$arItem['TEXT']?></span></a>
                    <?endif?>

			<?else:?>
				<?if ($arItem["DEPTH_LEVEL"] == 1):?>
					<div class="left-menu-item-first-level <?if($arItem["SELECTED"]):?>active<?endif;?>" >
						<a href="<?=$arItem['LINK']?>" class="left-menu-link-first-level">
							<span><?=$arItem["TEXT"]?></span>
							<span class="icon correct-browser" style="background-image: url('<?=$arItem["UF_IMG"]?>');">
								<?echo $arItem["UF_SVG"]?>
							</span>
						</a>
					</div>
                <?elseif ($arItem["DEPTH_LEVEL"] == 2):?>
                    <div class="left-menu-item-second-level">
                        <a href="<?=$arItem['LINK']?>" class="left-menu-link-second-level"><?=$arItem["TEXT"]?></a>
                    </div>
				<?else:?>
                    <a href="<?=$arItem['LINK']?>" class="left-menu-link-third-level"><span><?=$arItem['TEXT']?></span></a>
                <?endif?>
			<?endif?>

			<?$previousLevel = $arItem["DEPTH_LEVEL"];?>
		<?endforeach?>
        <?if ($previousLevel > 2)://close last item tags?>
            <?=str_repeat("</div>", ($previousLevel-1) );?>
        <?endif?>
		<?if ($previousLevel > 1)://close last item tags?>
			<?=str_repeat("</div>", ($previousLevel-1) );?>
		<?endif?>
	</div>
<?endif;?>