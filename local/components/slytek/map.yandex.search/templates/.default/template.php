<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$this->setFrameMode(true);

?>
<div class="site-inner shops-page <?=$arResult['SERVICES']?'services-page':''?>">
	<div class="container-page">
		<div class="content-container content-container-catalog shops-page-container">
			<div class="right-wrapper">
				<?$APPLICATION->IncludeComponent("bitrix:menu","left_menu_type_c_simple",Array(
					"ROOT_MENU_TYPE" => "pages_left",
					"MAX_LEVEL" => "3",
					"CHILD_MENU_TYPE" => "pages_left",
					"USE_EXT" => "Y",
					"DELAY" => "N",
					"ALLOW_MULTI_SELECT" => "Y",
					"MENU_CACHE_TYPE" => "N",
					"MENU_CACHE_TIME" => "3600",
					"MENU_CACHE_USE_GROUPS" => "Y",
					"MENU_CACHE_GET_VARS" => ""
				)
				);?><div class="title-grey"><?=$APPLICATION->GetTitle();?></div>
			</div>
			<div class="content-inner-wrapper">
				<?$APPLICATION->IncludeComponent(
					"slytek:main.include",
					"banner_wheretobuy",
					Array(
						"AREA_FILE_SHOW" => "prop",
						"AREA_FILE_SUFFIX" => "inc",
						"EDIT_TEMPLATE" => "",
						"PATH" => "",
						"PROPERTY_ID" => $arResult['SERVICES']?"services->banner":"wheretobuy->banner",
						"SITE_ID" => "s1"
					)
				);?>
				<?if($arResult['INFO']):?>
				<div class="container-right-image service-right-image">
					<?if($arResult['INFO']['image']):?><img src="<?=$arResult['INFO']['image']?>" alt=""><?endif?>
					<?if($arResult['INFO']['title']):?><h1><?=$arResult['INFO']['title']?></h1><?endif?>
					<?if($arResult['INFO']['text']):?><div><?=$arResult['INFO']['text']?></div><?endif?>
				</div>
				<?endif?>

				<form class="shops-form" name="search_form_ShopsMap" >
					<div class="filter-points-item">
						<div class="form-input">
							<label>Введите название населенного пункта
								<input class="city-input" type="text" placeholder="Населенный пункт" name="address" value="<?=$arResult['CITY']?>">
								<div class="shops-yandex-search-results" id="results_ShopsMap"></div>
							</label>
						</div>
						<input type="submit" class="btn-default" value="Найти">	
					</div>	
					<div class="filter-points-item">
						<label>
							Радиус поиска
							<select name="radius" id="" class="js-select-chosen">
								<option value="">(не выбрано)</option>>
								<?foreach($arResult['RADIUS'] as $arItem):?>
								<option value="<?=$arItem['VALUE']?>"><?=$arItem['NAME']?></option>
								<?endforeach?>
							</select>
						</label>
					</div>
				</form>
				<h2 class="shops-count"></h2>
				<div class="shops-map-block">
					
					<div class="shops-block">
						<ul></ul>
					</div>
					<div class="map-block js-yandex-map">
						<div style="width: 100%; height: 100%;">
							<?
							$arParams['ONMAPREADY'] = 'BXWaitForMap_searchShopsMap';
							$APPLICATION->IncludeComponent('slytek:map.yandex.system', '.default', $arParams, null, array('HIDE_ICONS' => 'Y'));
							?>
							<!-- <div class="yandex-map-wrapper js-yandex-map" id="yandex-map"></div> -->
						</div>
					</div>
				</div>

			</div>
			<?if(!$arResult['SERVICES']):?>
			<div class="content-inner-wrapper">
				<?$APPLICATION->IncludeComponent(
					"bitrix:main.include",
					"",
					Array(
						"AREA_FILE_SHOW" => "file",
						"AREA_FILE_SUFFIX" => "inc",
						"EDIT_TEMPLATE" => "",
						"PATH" => "/include/online_stores.php"
					)
				);?>
			</div>
			<?endif?>
		</div>
	</div>
</div>

<script type="text/javascript">
	function BXWaitForMap_searchShopsMap() 
	{
		window.jsYandexSearch_ShopsMap = new JCBXYandexSearch('ShopsMap', document.getElementById('results_ShopsMap'), {
			mess_error: '<?echo GetMessage('MYMS_TPL_JS_ERROR')?>',
			mess_search: '<?echo GetMessage('MYMS_TPL_JS_SEARCH')?>',
			mess_found: '<?echo GetMessage('MYMS_TPL_JS_RESULTS')?>',
			mess_search_empty: '<?echo GetMessage('MYMS_TPL_JS_RESULTS_EMPTY')?>'
		});
	}
</script>