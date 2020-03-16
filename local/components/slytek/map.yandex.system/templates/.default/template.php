<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
$this->setFrameMode(true);
?>
<script type="text/javascript">

	if (!window.GLOBAL_arMapObjects)
		window.GLOBAL_arMapObjects = {};

	function init_ShopsMap()
	{
		if (!window.ymaps)
			return;

		if(typeof window.GLOBAL_arMapObjects['ShopsMap'] !== "undefined")
			return;

		var node = BX("BX_YMAP_ShopsMap");
		node.innerHTML = '';

		var map = window.GLOBAL_arMapObjects['ShopsMap'] = new ymaps.Map(node, {
			center: [<?echo $arParams['INIT_MAP_LAT']?>, <?echo $arParams['INIT_MAP_LON']?>],
			zoom: <?echo $arParams['INIT_MAP_SCALE']?>,
			controls: ["zoomControl", "typeSelector"]
		});
		<?
		if ($arParams['ONMAPREADY']):
			?>
			if (window.<?echo $arParams['ONMAPREADY']?>)
			{
				<?
				if ($arParams['ONMAPREADY_PROPERTY']):
					?>
					<?echo $arParams['ONMAPREADY_PROPERTY']?> = map;
					window.<?echo $arParams['ONMAPREADY']?>();
					<?
				else:
					?>
					window.<?echo $arParams['ONMAPREADY']?>(map);
					<?
				endif;
				?>
			}
			<?
		endif;
		?>
	}


	(function bx_ymaps_waiter(){
		if(typeof ymaps !== 'undefined')
			ymaps.ready(init_ShopsMap);
		else
			setTimeout(bx_ymaps_waiter, 100);
	})();


/* if map inits in hidden block (display:none)
*  after the block showed
*  for properly showing map this function must be called
*/
function BXMapYandexAfterShow(mapId)
{
	if(window.GLOBAL_arMapObjects[mapId] !== undefined)
		window.GLOBAL_arMapObjects[mapId].container.fitToViewport();
}

</script>
<div id="BX_YMAP_ShopsMap" class="bx-yandex-map" style="height: <?echo $arParams['MAP_HEIGHT'];?>; width: <?echo $arParams['MAP_WIDTH']?>;"><?echo GetMessage('MYS_LOADING'.($arParams['WAIT_FOR_EVENT'] ? '_WAIT' : ''));?></div>