<?
use Bitrix\Main;
use Bitrix\Catalog;
$siteID = SITE_ID;
$productID=intval($_REQUEST['ID']);
$parentID=$productID;
if ($productID > 0 && $siteID !== '' && Main\Loader::includeModule('catalog') && Main\Loader::includeModule('sale'))
{
            // check if there was a recommendation
	$recommendationId = '';
	$recommendationCookie = $APPLICATION->get_cookie(Bitrix\Main\Analytics\Catalog::getCookieLogName());

	if (!empty($recommendationCookie))
	{
		$recommendations = \Bitrix\Main\Analytics\Catalog::decodeProductLog($recommendationCookie);

		if (is_array($recommendations) && isset($recommendations[$parentID]))
			$recommendationId = $recommendations[$parentID][0];
	}

            // add record
	Catalog\CatalogViewedProductTable::refresh(
		$productID,
		CSaleBasket::GetBasketUserID(),
		$siteID,
		$parentID,
		$recommendationId
	);
}
?>