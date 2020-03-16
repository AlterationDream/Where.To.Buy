<?
Bitrix\Main\Loader::includeModule('iblock');
$ids=array();
$result=array();
if($_REQUEST['ID']){
	foreach($_REQUEST['ID'] as $id){
		if($id>0){
			$ids[]=intval($id);
		}
	}
}
$resTypes = \Slytek\HL::get('b_hlbd_availabletypes');
foreach($resTypes as $arType){
	$types[$arType['UF_XML_ID']]=$arType['UF_NAME'];
}

$select = 'PROPERTY_AVAILABLE';
$city=SlytekRegions::getGeo();
if($city['DEFAULT']!='Y'){
	$select.='_'.$city['CODE'];
}
$res = CIBlockElement::GetList(array(), array('ID'=>$ids, 'ACTIVE'=>'Y'), false, false, array('ID', 'IBLOCK_ID', 'PROPERTY_AVAILABLE', $select));
while($arItem = $res->GetNext()){
	if(array_key_exists($select.'_VALUE', $arItem)){
		$available=$arItem[$select.'_VALUE'];
	}else{
		$available=$arItem['PROPERTY_AVAILABLE_VALUE'];
	}
	if(!$available)$available='pod_zakaz';
	$result[$arItem['ID']]=array(
		'image'=>SITE_TEMPLATE_PATH.'/images/'.$available.'.png',
		'title'=>$types[$available],
	);
	if($available=='ozhidaetsya'){
		$result[$arItem['ID']]['link']='<a href="javascript:;" class="subscribe-link" ajax-form="subscribe" data-id="'.$arItem['ID'].'">'.GetMessage('HEADER_NOTIFY_AVAILABLE').'</a>';
	}
}
echo json_encode($result);
/*
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
*/
?>