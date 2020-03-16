<?
if($_POST['STATUS_ORDER_ID']>0){
	$sended=1;
	CModule::includeModule('sale');
	$oid=intval($_POST['STATUS_ORDER_ID']);
	if($order = Bitrix\Sale\Order::loadByAccountNumber($oid)){
		$arOrder = $order->getFieldValues();
		if($arOrder['STATUS_ID']){
			if ($arStatus = CSaleStatus::GetByID($arOrder['STATUS_ID']))
			{
				$success=1;
			}
		}
	}
	
}
?>
<div class="comment_form">
		<form method="POST" action="<?=$APPLICATION->GetCurPage(true)?>">
			<input type="hidden" name="ajax_get" value="<?=htmlspecialchars($_REQUEST['ajax_get'])?>">
			<?if($sended):
			if($success):?>
				<div class="alert alert-success">
					<?=$arStatus['NAME']?>
					<div><?=$arStatus['DESCRIPTION']?></div>
				</div>
				<?else:?>
				<div class="alert alert-error">
					Заказ не найден
				</div>
				<?endif?>
				<?endif?>
				<p>
					<label>Номер заказа</label>
					<input type="text" name="STATUS_ORDER_ID"  placeholder="Номер заказа">
				</p>
				<div class="button_for_text">
					<button type="submit">Проверить</button>
				</div>

			</form>
		</div>