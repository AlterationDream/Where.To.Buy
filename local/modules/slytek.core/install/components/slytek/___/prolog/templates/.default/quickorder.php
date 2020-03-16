<?
$eid=intval($_REQUEST['ID']);
if($eid==0)die();
if($_POST['oneclick_fields']){
  CModule::includeModule('sale');
  CModule::includeModule('catalog');
  global $USER;
  foreach($_POST['oneclick_fields'] as $key=>$val){
    if(!$val)$error=1;
    $arUserFields[htmlspecialchars($key)]=htmlspecialchars($val);
  }
  if(!$error){
    if(!$USER->IsAuthorized())
    {
      $ph=preg_replace('/[^0-9+]*?/','', str_ireplace('8 (','7', $arUserFields['PHONE']));
      $rsUsers = CUser::GetList(($by = "NAME"), ($order = "desc"), array('PERSONAL_PHONE'=>$ph.'|'.$arUserFields['PHONE']));
      if ($arUser = $rsUsers->Fetch())
      {
        $uid=$arUser['ID'];
      }
      else
      {
        $arFilter = array('PROPERTY_VAL_BY_CODE_PHONE' => $arUserFields['PHONE']);
        $rsOrders = CSaleOrder::GetList(array('ID' => 'DESC'), $arFilter);
        if($arOrder = $rsOrders->Fetch())
        {
          $uid=$arOrder['USER_ID'];

        }
      }
      if($uid){
        $USER->Authorize($uid); 
        $deauth=1;
      }
    }
    if(!$USER->IsAuthorized() && !$uid){
      $pass=rand(1000000, 99999999999);
      $ph=preg_replace('/[^0-9]*?/','', $arUserFields['PHONE']);
      $user = new CUser;
      $arFields = Array(
        "NAME"              => $arUserFields['FIO'],
        "EMAIL"             => $ph."@".$_SERVER['HTTP_HOST'],
        "LOGIN"             => $ph,
        "LID"               => "ru",
        "ACTIVE"            => "Y",
        "GROUP_ID"          => array(2, 3),
        "PASSWORD"          => $pass,
        "CONFIRM_PASSWORD"  => $pass,
        "PERSONAL_PHONE"    => $arUserFields['PHONE']
      );

      $ID = $user->Add($arFields);
      if (intval($ID) > 0)
        $USER->Authorize($ID);
      else
        echo $user->LAST_ERROR;
    }


    Bitrix\Sale\DiscountCouponsManager::init();
    if(!$uid)$uid=$USER->GetID() ? $USER->GetID() :CSaleUser::GetAnonymousUserID();
    $order = Bitrix\Sale\Order::create(SITE_ID,  $uid);
    $order->setPersonTypeId(1);
    $basket =  Bitrix\Sale\Basket::create(SITE_ID);
    $currency=Bitrix\Main\Config\Option::get('sale', 'default_currency', 'RUB');
    $item = $basket->createItem('catalog', $eid);
    $item->setFields(array(
      'QUANTITY' => 1,
      'CURRENCY' => Bitrix\Currency\CurrencyManager::getBaseCurrency(),
      'LID' => Bitrix\Main\Context::getCurrent()->getSite(),
      'PRODUCT_PROVIDER_CLASS' => '\CCatalogProductProvider',
    ));
    $order->setBasket($basket);
    $shipmentCollection = $order->getShipmentCollection();
    $shipment = $shipmentCollection->createItem();
    $shipmentItemCollection = $shipment->getShipmentItemCollection();
    $shipmentItem = $shipmentItemCollection->createItem($item);
    $shipmentItem->setQuantity($item->getQuantity());

    $service = Bitrix\Sale\Delivery\Services\Manager::getById(Bitrix\Sale\Delivery\Services\EmptyDeliveryService::getEmptyDeliveryServiceId());
    $shipment->setFields(array(
      'DELIVERY_ID' => $service['ID'],
      'DELIVERY_NAME' => $service['NAME'],
      'CURRENCY' => $order->getCurrency()
    ));
    $paymentCollection = $order->getPaymentCollection();
    $payment = $paymentCollection->createItem();
    $paySystemService = Bitrix\Sale\PaySystem\Manager::getObjectById(1);
    $payment->setFields(array(
      'PAY_SYSTEM_ID' => $paySystemService->getField("PAY_SYSTEM_ID"),
      'PAY_SYSTEM_NAME' => $paySystemService->getField("NAME"),
    ));
    $order->doFinalAction(true);
    $propertyCollection = $order->getPropertyCollection();
    $propertyCollection->getPayerName()->setValue($arUserFields['FIO']);
    $propertyCollection->getPhone()->setValue($arUserFields['PHONE']);
    $order->setField('CURRENCY', $currency);
    $order->setField('USER_DESCRIPTION', 'Быстрый заказ');
    $res = $order->save();
    
    $success=1;
    if($deauth){
      $USER->Logout();
    }
  }
}
?>
<form method="post" id="buy-one-click">
<?if($success):
?>
<div class="popup__top">
  <?if ($res->isSuccess()):?>
  
  <div class="popup__title"><?=Icon::mess('BUY_ONE_CLICK')?></div>
  <div class="popup__text"><?=str_ireplace('#ID#', $order->getField("ACCOUNT_NUMBER"), Icon::mess('BUY_ONE_CLICK_SUCCESS'))?></div>
  
  <?else:?>
  <div class="popup__title"><?=Icon::mess('BUY_ONE_CLICK')?></div>
  <div class="popup__text">
    <? foreach ($res->getErrorMessages() as $error):?>
      <p><?=($error);?></p>
    <? endforeach;?>
  </div>
  <?endif?>
</div>
<div class="popup__bottom">
  <a class="btn btn--dark js-popup-close" href="javascript:;"><?=Icon::mess('CONTINUE_SHOPPING')?></a>
</div>

<?else:?>

<?
if($USER->IsAuthorized()){
  $rsUser = CUser::GetByID($USER->GetId());
  $arUser = $rsUser->Fetch();
}
?>

  <input type="hidden" name="ajax_get" value="<?=htmlspecialchars($_REQUEST['ajax_get'])?>">
  <input type="hidden" name="ID" value="<?=intval($_REQUEST['ID'])?>">
  <?if($error):?>
  <div class="popup__top">
    <div class="popup__title"><?=Icon::mess('BUY_ONE_CLICK')?></div>
    <div class="popup__text"><?=Icon::mess('SOMETHING_WRONG')?></div>
  </div>
  <?else:?>
  <div class="popup__top">
    <div class="popup__title"><?=Icon::mess('BUY_ONE_CLICK')?></div>
    <div class="popup__text"><?=Icon::mess('BUY_ONE_CLICK_DESCRIPTION')?></div>
  </div>
  <div class="popup__content">
    <label class="curt-label">
      <input class="js-input-ctrl curt-input" type="text" value="<?=$USER->GetFullName()?>"  name="oneclick_fields[FIO]" type="text" required><span><?=Icon::mess('INPUT_NAME')?></span>
    </label>
    <label class="curt-label">
      <input class="js-input-ctrl curt-input" type="tel" name="oneclick_fields[PHONE]" value="<?=$arUser["PERSONAL_PHONE"]?>" required  ><span><?=Icon::mess('INPUT_PHONE')?></span>
    </label>
    <div class="popup__content-text"><?$APPLICATION->IncludeComponent(
          "bitrix:main.userconsent.request", 
          "", 
          array(
            "AUTO_SAVE" => "Y",
            "ID" => USER_CONSENT_ID,
            "IS_CHECKED" => "N",
            "IS_LOADED" => "Y",
            "INPUT_NAME" => "ACCEPT",
            "REPLACE" => array(
              "button_caption" =>Icon::mess('SEND'),
              "fields" => array('name','email'),
            ),
            "COMPONENT_TEMPLATE" => ".default"
          ),
          false
        );?></div>
  </div>
  <div class="popup__bottom">
    <input class="btn btn--dark" type="submit" name="" value="<?=Icon::mess('SEND')?>">
  </div>
  <?endif?>


<?endif?>
</form>