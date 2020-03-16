<?if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die(); 
// error_reporting(E_ERROR | E_WARNING);
// ini_set('display_errors', 'on');
global $APPLICATION;
?>
<div class="site-inner main-page">
	<div class="container-page">
		<div class="left-container">
			<?$APPLICATION->IncludeComponent(
				"bitrix:menu",
				"main_left_menu_type_a",
				Array(
					"ALLOW_MULTI_SELECT" => "Y",
					"CHILD_MENU_TYPE" => "left_a",
					"DELAY" => "N",
					"MAX_LEVEL" => "3",
					"MENU_CACHE_GET_VARS" => "",
					"MENU_CACHE_TIME" => "3600",
					"MENU_CACHE_TYPE" => "N",
					"MENU_CACHE_USE_GROUPS" => "Y",
					"ROOT_MENU_TYPE" => "left_a",
					"USE_EXT" => "Y"
				)
			);?>
		</div>
		<div class="content-container content-container-catalog is-inner-style">
			<div class="title-grey">
				Результат поиска <?if($_REQUEST['SECTION_ID']):?>в разделе «<?$APPLICATION->ShowTitle()?>»<?endif?>
			</div>
			<div class="content-inner-wrapper">
				<h1><?=$arResult['TITLE']?></h1>
				<?if($_REQUEST['SECTION_ID']):?><h2><a href="<?=$APPLICATION->GetCurPageParam('', array('SECTION_ID'))?>">Вернуться ко всем результатам поиска</a></h2><?endif?>
			</div>
			<?
			if($arResult['SECTION_FILTER_NAME'] && !$_REQUEST['SECTION_ID']){
				$APPLICATION->IncludeComponent(
					"slytek:catalog.section.list",
					"",
					Array(
						"ADD_SECTIONS_CHAIN" => "N",
						"CACHE_GROUPS" => "N",
						"CACHE_TIME" => "36000000",
						"CACHE_TYPE" => "A",
						"COUNT_ELEMENTS" => "N",
						"FILTER_NAME" => $arResult['SECTION_FILTER_NAME'],
						"ELEMENT_FILTER_NAME" => $arResult['ELEMENT_FILTER_NAME'],
						"IBLOCK_ID" => "1",
						"IBLOCK_TYPE" => "catalog",
						"SECTION_CODE" => "",
						"SECTION_FIELDS" => array("", ""),
						"SECTION_ID" => "",
						"SECTION_URL" => "",
						"SECTION_USER_FIELDS" => array("", ""),
						"TOP_DEPTH" => "4"
					)
				);
			}
			if($arResult['ELEMENT_FILTER_NAME']){
				$APPLICATION->IncludeComponent(
                    "zubr:news.list",
                    'group-power',
                    Array(
                        "IBLOCK_ID" => IBLOCK_CATALOG,
                        "NEWS_COUNT" => "20",
                        "SECTION_SORT_FIELD" => "SORT",
                        "SECTION_SORT_ORDER" => "ASC",
                        "SORT_ARRAY" => array(
                            "property_classifier_sort" => "asc,nulls",
                            "IBLOCK_SECTION_ID" => "ASC",
                            "SORT" => "ASC",
                        ),
                        'SIMPLE'=>'Y',
                        "FILTER_NAME" => $arResult['ELEMENT_FILTER_NAME'],
                        "FIELD_CODE" => array("NAME","PREVIEW_TEXT","PREVIEW_PICTURE"),
                        "PROPERTY_CODE" => array("*"),
                        "CHECK_DATES" => "Y",
                        "DETAIL_URL" => "",
                        "AJAX_MODE" => "N",
                        "AJAX_OPTION_JUMP" => "N",
                        "AJAX_OPTION_STYLE" => "Y",
                        "AJAX_OPTION_HISTORY" => "N",
                        "CACHE_TYPE" => "A",
                        "CACHE_TIME" => "3600",
                        "CACHE_FILTER" => "Y",
                        "CACHE_GROUPS" => "Y",
                        "PREVIEW_TRUNCATE_LEN" => "",
                        "ACTIVE_DATE_FORMAT" => "d.M.Y",
                        "SET_STATUS_404" => "Y",
                        "SET_TITLE" => $_REQUEST['SECTION_ID']?'Y':"N",
                        "INCLUDE_IBLOCK_INTO_CHAIN" => "N",
                        "ADD_SECTIONS_CHAIN" => "Y",
                        "HIDE_LINK_WHEN_NO_DETAIL" => "N",
                        "PARENT_SECTION" => $_REQUEST['SECTION_ID'],
                        "PARENT_SECTION_CODE" => "",
                        "INCLUDE_SUBSECTIONS" => "Y",
                        "DISPLAY_DATE" => "Y",
                        "DISPLAY_NAME" => "Y",
                        "DISPLAY_PICTURE" => "Y",
                        "DISPLAY_PREVIEW_TEXT" => "Y",
                        "PAGER_TEMPLATE" => "page",
                        "DISPLAY_TOP_PAGER" => "N",
                        "DISPLAY_BOTTOM_PAGER" => "Y",
                        "PAGER_TITLE" => "Новости",
                        "PAGER_SHOW_ALWAYS" => "N",
                        "PAGER_DESC_NUMBERING" => "N",
                        "PAGER_DESC_NUMBERING_CACHE_TIME" => "36000",
                        "PAGER_SHOW_ALL" => "N"
                    )
                );
		}
			?>
		</div>
	</div>
</div>