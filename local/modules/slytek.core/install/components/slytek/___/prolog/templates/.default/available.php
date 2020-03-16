<?
$id=intval($_REQUEST['ID']);
if($id>0){
	$message='В наличии';
	CModule::includeModule('catalog');
	$db_res = CCatalogProduct::GetList(
		array(),
		array("ID" => $id),
		false,
		array("nTopCount" => 1)
	);
	if ($arProduct = $db_res->Fetch())
	{
		if($arProduct['QUANTITY']>0){
			if($arProduct['MEASURE']){
				$res_measure = CCatalogMeasure::getList(array(), array('ID'));
				while($arMeasure = $res_measure->Fetch()) {
					$measure=$arMeasure['SYMBOL_RUS'];
				} 
			} else{
				$measure='шт';
			}
			$message='В наличии '.$arProduct['QUANTITY'].' '.$measure;
		}elseif($arProduct['CAN_BUY_ZERO']!='Y'){
			$message='Предзаказ, срок поставки: 20 дней';
		}
	}
	echo $message;
	
}
?>