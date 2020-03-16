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
$this->setFrameMode(true);?>
<?
$INPUT_ID = trim($arParams["~INPUT_ID"]);
if(strlen($INPUT_ID) <= 0)
	$INPUT_ID = "title-search-input";
$INPUT_ID = CUtil::JSEscape($INPUT_ID);

$CONTAINER_ID = trim($arParams["~CONTAINER_ID"]);
if(strlen($CONTAINER_ID) <= 0)
	$CONTAINER_ID = "title-search";
$CONTAINER_ID = CUtil::JSEscape($CONTAINER_ID);

if($arParams["SHOW_INPUT"] !== "N"):?>
	<div class="search-wrapper">
		<form action="<?echo $arResult["FORM_ACTION"]?>">
			<input name="q" type="text" placeholder="Поиск" class="search-input js-search-input" maxlength="50" autocomplete="off" id="<?echo $INPUT_ID?>"/>
			<input type="submit" name="s" class="search-button js-search-button" value="y"/>
			
		</form><div id="<?echo $CONTAINER_ID?>" class="option-search-list"></div>
	</div>
	<?endif?>
	<script>
		BX.ready(function(){
			new JCTitleSearch({
				'AJAX_PAGE' : '/',
				'CONTAINER_ID': '<?echo $CONTAINER_ID?>',
				'INPUT_ID': '<?echo $INPUT_ID?>',
				'RESULT_ID': 'ownedmuhaha-search-input-result',
				'MIN_QUERY_LEN': 2
			});
		});
	</script>
