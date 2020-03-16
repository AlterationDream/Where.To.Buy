<?
/**
* @global CMain $APPLICATION
* @global CUser $USER
* */
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");
IncludeModuleLangFile(__FILE__);
$aTabs=array();
$editable = true;
CModule::includeModule('slytek.core');
CModule::includeModule('iblock');

$aTabs[]=array("DIV" => "edit1", "TAB" => 'Обновить прайслист', "ICON"=>"", "TITLE"=>'Обновить прайслист');
//$aTabs[]=array("DIV" => "edit1", "TAB" => 'Импорт товаров', "ICON"=>"", "TITLE"=>'Языковые фразы');
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;

$pricelist_path = $_SERVER['DOCUMENT_ROOT'].'/local/modules/slytek.core/settings/pricelist.dat';
$pricelist = unserialize(file_get_contents($pricelist_path));

$res = CIBlockElement::GetList(Array('NAME'=>'ASC'), array('IBLOCK_ID'=>Plitka::id['brands']), false, false, array('ID', 'IBLOCK_ID', 'NAME'));
while($arItem = $res->GetNext())
{
    $brands[$arItem['ID']]=$arItem;
}

if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST["Update"] && $editable)
{
    $items = array();
    if($_REQUEST['ITEM']){
        foreach($_REQUEST['ITEM'] as $bid=>$item){
            $bid = intval($bid);
            if(!$bid)continue;
            $item['PRICE'] = str_ireplace(',','.', $item['PRICE']);
            $item['RATIO'] = str_ireplace(',','.', $item['RATIO']);
            $price = floatval($item['PRICE']);
            $ratio = floatval($item['RATIO']);
            $item['SALE_PRICE'] = str_ireplace(',','.', $item['SALE_PRICE']);
            $item['SALE_RATIO'] = str_ireplace(',','.', $item['SALE_RATIO']);
            $sale_price = floatval($item['SALE_PRICE']);
            $sale_ratio = floatval($item['SALE_RATIO']);
            $items[$bid]=array('PRICE'=>$price, 'RATIO'=>$ratio, 'SALE_PRICE'=>$sale_price, 'SALE_RATIO'=>$sale_ratio);
        }
        $pricelist = $items;
        file_put_contents($pricelist_path, serialize($pricelist));
    }
    if($pricelist){
        foreach ($pricelist as $bid => $params) {
            if(!$bid)continue;
            foreach(array('NON_SALE', 'SALE') as $flag){
                $items = array();
                $arFilter = array('IBLOCK_ID'=>Plitka::id['catalog'], 'PROPERTY_BRAND'=>$bid);
                if($flag=='SALE'){
                    $arFilter['PROPERTY_SALE']='Y';
                    $pre = 'SALE_';
                }
                else{
                    $arFilter['!PROPERTY_SALE']='Y';
                    $pre = '';
                }
                $res = CIBlockElement::GetList(Array(), $arFilter, false, false, array('ID', 'IBLOCK_ID'));
                while($arItem = $res->GetNext())
                {
                    $items[$arItem['ID']]=array();
                }
                $res = \Bitrix\Catalog\ProductTable::getList(array(
                    'filter'=>array('ID'=>array_keys($items),'>PURCHASING_PRICE'=>0)
                ));
                $ratio = $params[$pre.'PRICE']*$params[$pre.'RATIO'];
                if($ratio<=0)$ratio = $params['PRICE']*$params['RATIO'];
                if($ratio<=0)continue;
                while($item = $res->fetch()){
                    $items[$item['ID']]=$item['PURCHASING_PRICE']*$ratio;
                }
                $res = \Bitrix\Catalog\Model\Price::getList(array(
                    'filter'=>array('PRODUCT_ID'=>array_keys($items)),
                    'select'=>array('ID', 'PRODUCT_ID')
                ));

                while($item = $res->fetch()){
                    $price = $items[$item['PRODUCT_ID']];
                    if($price<=0)continue;
                    \Bitrix\Catalog\Model\Price::update($item['ID'], array(
                        'PRICE'=>$price,
                        'CURRENCY'=>'RUB'
                    ));
                }
            }
        }
    }
    LocalRedirect($APPLICATION->GetCurPage()."?ok=1&lang=".LANGUAGE_ID);
} 

$APPLICATION->SetTitle('Обновить прайслист');  
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

if($_REQUEST['ok']){
    CAdminMessage::ShowMessage(array(
        "MESSAGE"=>"Цены пересчитаны",
        "HTML"=>true,
        "TYPE"=>"OK",
    )); 
}
?> 
<form enctype="multipart/form-data" method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>">
    <?=bitrix_sessid_post()?>

    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
    <tr>
        <td class="heading"  width="100">Производитель</td>
        <td class="heading" width="100">Курс за единицу валюты</td>
        <td class="heading" width="100">Коэффициент</td>
        <td class="heading" width="100">Курс за единицу валюты для распродажи</td>
        <td class="heading" width="100">Коэффициент для распродажи</td>
    </tr>
    <?foreach($brands as $arItem):?>
    <tr>
        <td  width="100"><?=$arItem['NAME']?></td>
        <td align="center" width="100"><input type="text" name="ITEM[<?=$arItem['ID']?>][PRICE]" value="<?=$pricelist[$arItem['ID']]['PRICE']?$pricelist[$arItem['ID']]['PRICE']:1?>"></td>
        <td align="center" width="100"><input type="text" name="ITEM[<?=$arItem['ID']?>][RATIO]" value="<?=$pricelist[$arItem['ID']]['RATIO']?$pricelist[$arItem['ID']]['RATIO']:1?>"></td>
        <td align="center" width="100"><input type="text" name="ITEM[<?=$arItem['ID']?>][SALE_PRICE]" value="<?=$pricelist[$arItem['ID']]['SALE_PRICE']?$pricelist[$arItem['ID']]['SALE_PRICE']:0?>"></td>
        <td align="center" width="100"><input type="text" name="ITEM[<?=$arItem['ID']?>][SALE_RATIO]" value="<?=$pricelist[$arItem['ID']]['SALE_RATIO']?$pricelist[$arItem['ID']]['SALE_RATIO']:0?>"></td>
    </tr>
    <?endforeach?>
    <?
    $tabControl->Buttons();
    ?>
    <input type="submit" name="Update" value="Сохранить и пересчитать цены товаров" title="Сохранить" class="adm-btn-save">
    <?
    $tabControl->End();
    $tabControl->ShowWarnings("form1", $message);
    ?>
</form>

<?
require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
