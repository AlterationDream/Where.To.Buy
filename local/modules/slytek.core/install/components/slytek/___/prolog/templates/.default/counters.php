<?
if($_REQUEST['ajax_get']=='counters')$basket=$wishlist=$compare=$rates=true;
else if($_REQUEST['ajax_get']=='basket')$basket=true;
else if($_REQUEST['ajax_get']=='wishlist')$wishlist=true;
else if($_REQUEST['ajax_get']=='compare')$compare=true;
else if($_REQUEST['ajax_get']=='likes')$likes=true;
else if($_REQUEST['ajax_get']=='rates')$rates=true;

if($basket && Bitrix\Main\Loader::includeModule('sale')){
	Bitrix\Main\Loader::includeModule('sale');
	Bitrix\Main\Loader::includeModule('currency');
	//$basket = Bitrix\Sale\Basket::loadItemsForFUser(Bitrix\Sale\Fuser::getId(), Bitrix\Main\Context::getCurrent()->getSite());
	//$orderBasket = $basket->getOrderableItems();
	//$bq=count($orderBasket->getQuantityList());
	$result['basket'] = Bitrix\Sale\Internals\BasketTable::getList(array(
		'filter' => array(
			'FUSER_ID' => Bitrix\Sale\Fuser::getId(), 
			'ORDER_ID' => null,
			'LID' => SITE_ID,
			'CAN_BUY' => 'Y',
			),
		'select' => array('count', 'sum'),
		'runtime' => array(
			new \Bitrix\Main\Entity\ExpressionField('count', 'COUNT(*)'),
			new \Bitrix\Main\Entity\ExpressionField('sum', 'SUM(PRICE*QUANTITY)'),
			)
		))->fetch();
	if(!$result['basket']['sum'])$result['basket']['sum']=0;
	$result['basket']['sum']=CurrencyFormat($result['basket']['sum'], CCurrency::GetBaseCurrency());
	//$result['basket']['text']=intval($result['basket']['count']);
	//$result['basket']=array('count'=>$basket['BASKET_COUNT'], 'sum'=>CurrencyFormat());
}

if($wishlist && Bitrix\Main\Loader::includeModule('slytek.favorites')){
	global $USER;
	Bitrix\Main\Loader::includeModule('iblock');
	Bitrix\Main\Loader::includeModule('slytek.favorites');
	$arFilter=array('ACTIVE'=>'Y');
	if($USER->IsAuthorized()){
		$arFilter['USER_ID'] = $USER->GetID();
	}else{
		if($cookie_user_id = $APPLICATION->get_cookie("SLYTEK_COOKIE_USER_ID")){
			$arFilter['COOKIE_USER_ID'] = $cookie_user_id;
		}else{
			$cookie_user_id = md5(time().randString(10));
			$APPLICATION->set_cookie("SLYTEK_COOKIE_USER_ID", $cookie_user_id);
			$arFilter['COOKIE_USER_ID'] = $cookie_user_id;
		}
	}
	foreach(\slytek\Favorites\FavoritesTable::getList(array("filter" => $arFilter))->fetchAll() as $arItem){
		$ids[]=$arItem['ELEMENT_ID'];
	}
	$result["wishlist"]['count'] =$ids?CIBlockElement::GetList(array(), array('ID'=>$ids, 'ACTIVE'=>'Y'), array(), false):0;
	$result["wishlist"]['items'] = $ids;
	$result["wishlist"]['mess'] = 'Убрать из Wishlist';
	$result["wishlist"]['default'] = 'Добавить в Wishlist';
}

if($compare){
	if($_SESSION['CATALOG_COMPARE_LIST']){
		$ids=array();
		foreach($_SESSION['CATALOG_COMPARE_LIST'] as $iblock=>$items){
			foreach($items['ITEMS'] as $id=>$item){
				$ids[]=$id;
			}
		}
		$result["compare"]['count']= $ids?CIBlockElement::GetList(array(), array('ID'=>$ids, 'ACTIVE'=>'Y'), array(), false):0;
		$result["compare"]['items'] = $ids;
		$result["compare"]['mess'] = 'В сравнении';
		$result["compare"]['title'] = 'Перейти в сравнение';
		$result["compare"]['default'] = 'Добавить в сравнение';
		$result["compare"]['url'] = '?action=DELETE_FROM_COMPARE_LIST&id=#ID#';
		$result["compare"]['href'] = '/catalog/compare/';
		$result["compare"]['def_url'] = '?action=ADD_TO_COMPARE_LIST&id=#ID#';
	}
}
if($likes && $_REQUEST['ID'] && CModule::includeModule('slytek.likeit')){
	CModule::includeModule('slytek.likeit');
	$result['like']['items_count'] = Slytek\Likeit\LikeTable::checkLike($_REQUEST['ID']);
	$result['dislike']['items_count'] = Slytek\Likeit\LikeTable::checkDisLike($_REQUEST['ID']);
}

if($rates && Bitrix\Main\Loader::includeModule('slytek.rate') && $_REQUEST['ID']){
	$ids=$_REQUEST['ID'];
	$rates = \Slytek\Rate\RateTable::checkRate($ids);
	foreach($rates as $id=>$rate){
		$result['rates']['count'][$id]=$rate;
		for($i=1; $i<=5; $i++){
			if($i<=$result['rates']['count'][$id]){
				$result['rates']['items_count'][$id].='<i data-rate="'.$i.'" class="fa fa-star active"></i>';
			}else{
				$result['rates']['items_count'][$id].='<i data-rate="'.$i.'" class="fa fa-star"></i>';
			}
		}
	}
}

echo json_encode($result);
?>