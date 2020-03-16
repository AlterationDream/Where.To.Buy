<?
/**
* @global CMain $APPLICATION
* @global CUser $USER
* */
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");
if (!$USER->CanDoOperation('cache_control'))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
IncludeModuleLangFile(__FILE__);
global $DB;
CModule::IncludeModule('slytek.core');
CModule::IncludeModule('iblock');
$aTabs = array(
    array("DIV" => "main_settings", "TAB" => 'Прайслист партнеров', "ICON"=>"", "TITLE"=>'Прайслист партнеров'),
    );
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;

if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST['apply'] && $_REQUEST["Update"]=="Y" && check_bitrix_sessid())
{

    foreach($_REQUEST['AGREED_PRICE'] as $id=>$val){
        $DB->StartTransaction();
        $arFields=array();
        $arFields['AGREED']=$val=='Y'?'Y':'N';
        
        $result = \Slytek\Partnerprices\PricesTable::update($id, $arFields);
        if(!$result->isSuccess())
        {
            $DB->Rollback();
            throw new Main\SystemException('delete erorr');
        }
        $DB->Commit();
    }
    LocalRedirect($APPLICATION->GetCurPage()."?LID=".$SITE_ID."&lang=".LANGUAGE_ID);
}   
$APPLICATION->SetTitle('Прайслист партнеров');  
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
CJSCore::Init(array('jquery'));
$filter=array();
if($_REQUEST['USER_ID']>0){
    $filter['USER_ID']=intval($_REQUEST['USER_ID']);
}
if($_REQUEST['CATALOG_GROUP_ID']>0){
    $filter['CATALOG_GROUP_ID']=intval($_REQUEST['CATALOG_GROUP_ID']);
}
$dbPrices = \Slytek\Partnerprices\PricesTable::getList(
    array(
        "select" => array('*'),
        "filter" => $filter,
        )
    );
while ($arPrice = $dbPrices->fetch())
{
    $arResult['GROUPS'][$arPrice['CATALOG_GROUP_ID']]=array();
    $arResult['USERS'][$arPrice['USER_ID']]=array();
    $arResult['PRICES'][$arPrice['ELEMENT_ID']][]=$arPrice;
}

if($arResult['GROUPS']){
    $dbPriceType = \CCatalogGroup::GetList(
        array("SORT" => "ASC"),
        array("ID" => array_keys($arResult['GROUPS']))
        );
    while($arPriceType = $dbPriceType->Fetch()){
        $arResult['GROUPS'][$arPriceType['ID']]=$arPriceType;
    }
}
if($arResult['USERS']){
    $rsUsers = CUser::GetList(($by="name"), ($order="asc"), array('ID'=>array_keys($arResult['USERS'])), array('SELECT'=>array(), "FIELDS"=>array('ID', 'NAME', 'LOGIN', 'LAST_NAME'))); 
    while($arUser = $rsUsers->GetNext()){
     $arResult['USERS'][$arUser['ID']]=$arUser;
 }
}
if($arResult['PRICES']){
    $arSelect = Array("ID", "NAME", 'IBLOCK_SECTION_ID', 'IBLOCK_ID', 'DETAIL_PAGE_URL');
    $res = CIBlockElement::GetList(Array('NAME'=>'ASC'), Array('ID'=>array_keys($arResult['PRICES'])), false, false, $arSelect);
    while($arItem = $res->GetNext())
    {
        $sids[$arItem['IBLOCK_SECTION_ID']][]=$arItem['ID'];
        $arResult['ITEMS'][$arItem['ID']]=$arItem;
    }
}
if($sids){
    $db_list = CIBlockSection::GetList(Array('NAME'=>'ASC'), array('IBLOCK_ID'=>CATALOG_IBLOCK_ID, 'ID'=>array_keys($sids)), true, array('ID', 'NAME', 'IBLOCK_ID', 'DEPTH_LEVEL'));
    while($arSection = $db_list->GetNext())
    {
        $arResult['SECTIONS'][]=$arSection;
    }
}
?> 
<form method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>" class="custom-inputs">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y">
    <?  
    $tabControl->Begin();
    $tabControl->BeginNextTab(); 
    ?>
    <?foreach($arResult['SECTIONS'] as $arSection): 
    if(!$sids[$arSection['ID']])continue;
    if($arSection['DEPTH_LEVEL']>1){
        $path=array();
        $nav = CIBlockSection::GetNavChain($arSection['IBLOCK_ID'], $arSection['ID'], array('ID', 'NAME'));
        while($arSectionPath = $nav->GetNext()){
            $path[]=$arSectionPath['NAME'];
        }
        if($path){
            $arSection['NAME']=implode(' - ', $path);
        }
    }
    ?>
    <tr><th colspan="4"><h3><?=$arSection['NAME']?></h3></th></tr>
    <?foreach($sids[$arSection['ID']] as $id): 
    $arItem=$arResult['ITEMS'][$id];
    $arPrices=$arResult['PRICES'][$arItem['ID']];
    $ok=false;
    foreach($arPrices as $arPrice):
        $arUser=$arResult['USERS'][$arPrice['USER_ID']];
    $arGroup=$arResult['GROUPS'][$arPrice['CATALOG_GROUP_ID']];
    ?>
    <tr>
        <td>
           <?if(!$ok):?> <a href="<?=$arItem['DETAIL_PAGE_URL']?>"><?=$arItem['NAME']?></a><?endif?>
       </td>
       <td><a href="/bitrix/admin/partners_prices.php?lang=ru&USER_ID=<?=$arUser['ID']?>"><?=$arUser['NAME'].' '.$arUser['LAST_NAME'].' ['.$arUser['LOGIN'].']['.$arUser['ID'].']'?></a></td>
       <td><a href="/bitrix/admin/partners_prices.php?lang=ru&CATALOG_GROUP_ID=<?=$arGroup['ID']?>"></a><?=$arGroup['NAME_LANG']?></td>
       <td><div><?=$arPrice['PRICE']?> руб</div></td>
       <td><label><input type="checkbox" <?=$arPrice['AGREED']=='Y'?'checked':'';?> name="AGREED_PRICE[<?=$arPrice['ID']?>]" value="Y">Включить для товара</label></td>
       <td><?=$arPrice['ACTIVE']=='Y'?'актуальна':'отключена партнером';?></td>
   </tr>
   <?$ok=true;
   endforeach;
   endforeach;
   endforeach;

   $tabControl->Buttons();
   ?>
   <input type="submit" name="apply" value="<?echo GetMessage("admin_lib_edit_save")?>" title="<?echo GetMessage("admin_lib_edit_savey_title")?>" class="adm-btn-save">
   <?
   $tabControl->End();
   $tabControl->ShowWarnings("form1", $message);
   ?>
</form>

<?
if(!defined('ADMIN_PAGE'))require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
