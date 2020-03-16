<?if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();?>

<?if (!empty($arResult)):?>
	<div class="left-container" >
		<div class="left-menu-wrapper">
			
			<?$APPLICATION->IncludeComponent("bitrix:menu","slide_left_menu_catalog",Array(
					"ROOT_MENU_TYPE" => "left_a",
					"MAX_LEVEL" => "3",
					"CHILD_MENU_TYPE" => "left_a",
					"USE_EXT" => "Y",
					"DELAY" => "N",
					"ALLOW_MULTI_SELECT" => "Y",
					"MENU_CACHE_TYPE" => "N",
					"MENU_CACHE_TIME" => "3600",
					"MENU_CACHE_USE_GROUPS" => "Y",
					"MENU_CACHE_GET_VARS" => ""
				)
			);?>			
		</div>
	</div>
<?endif?>