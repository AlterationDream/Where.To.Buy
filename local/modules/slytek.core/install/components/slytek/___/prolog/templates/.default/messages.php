<?
if($_REQUEST['id']>0){
	CModule::includeModule('iblock');
	$arFilter = Array("ID"=>IntVal($_REQUEST['id']), "ACTIVE_DATE"=>"Y", "ACTIVE"=>"Y");
	$res = CIBlockElement::GetList(Array(), $arFilter, false, Array("nTopCount"=>1), Array("ID", "NAME", "DETAIL_PAGE_URL"));
	$arFields = $res->GetNext();
}
switch (ACTION_TYPE) {
	case 'basket':
	?>
	<?$APPLICATION->IncludeComponent("bitrix:sale.basket.basket.line", "popup_cart", Array(
		    	"PATH_TO_BASKET" => SITE_DIR."personal/cart/",  // Страница корзины
		        "PATH_TO_PERSONAL" => SITE_DIR."personal/", // Страница персонального раздела
		        "SHOW_PERSONAL_LINK" => "N",    // Отображать персональный раздел
		        "SHOW_NUM_PRODUCTS" => "Y", // Показывать количество товаров
		        "SHOW_TOTAL_PRICE" => "Y",  // Показывать общую сумму по товарам
		        "SHOW_PRODUCTS" => "Y", // Показывать список товаров
		        "POSITION_FIXED" => "N", 
		        'ID'=>$_REQUEST['id'],
		        "SHOW_AUTHOR" => "N",   // Добавить возможность авторизации
		        "PATH_TO_REGISTER" => SITE_DIR."personal/",    // Страница регистрации
		        "PATH_TO_PROFILE" => SITE_DIR."personal/",  // Страница профиля
		        "COMPONENT_TEMPLATE" => ".default",
		        "PATH_TO_ORDER" => SITE_DIR."personal/cart/", // Страница оформления заказа
		        "SHOW_EMPTY_VALUES" => "Y", // Выводить нулевые значения в пустой корзине
		        "PATH_TO_AUTHORIZE" => "",  // Страница авторизации
		        "SHOW_DELAY" => "N",    // Показывать отложенные товары
		        "SHOW_NOTAVAIL" => "N", // Показывать товары, недоступные для покупки
		        "SHOW_IMAGE" => "N",    // Выводить картинку товара
		        "SHOW_PRICE" => "Y",    // Выводить цену товара
		        "SHOW_SUMMARY" => "Y",  // Выводить подытог по строке
		        "HIDE_ON_BASKET_PAGES" => "N",  // Не показывать на страницах корзины и оформления заказа
		    ),
	false
);?>
	<?
	die();break;

	case 'favorite':
	?>
	<?if(defined('FAVORITE_DELETED')):?>
	<center>
		<div class="title-sm"></div>
		<p>Товар удален из избранного</p>
		<a class="btn" href="<?=SITE_DIR?>personal/wishlist/">Перейти в избранное</a>
		<a class="u-link" href="javascript:void(0)" rel="modal:close">Продолжить покупки</a>
	</center>
	<?else:?>
	<center>
		<div class="title-sm"></div>
		<p>Товар добавлен в избранное</p>
		<a class="btn" href="<?=SITE_DIR?>personal/wishlist/">Перейти в избранное</a>
		<a class="u-link" href="javascript:void(0)" rel="modal:close">Продолжить покупки</a>
	</center>
	<?endif?>
	<?
	die();
	break;
}
?>