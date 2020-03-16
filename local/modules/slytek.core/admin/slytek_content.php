<?
/**
* @global CMain $APPLICATION
* @global CUser $USER
* */
if(!defined('ADMIN_PAGE'))require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");
else require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/interface/admin_lib.php");

define('ADMIN_MODULE_NAME', 'slytek.core');
if (!$USER->CanDoOperation('cache_control'))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
IncludeModuleLangFile(__FILE__);
global $DB;
CModule::IncludeModule('slytek.core');
$arSites=\Slytek\Settings::getSites();
$currentSite=$arSites['CURRENT'];
if($currentSite){
    $SITE_ID=$currentSite['LID'];  
    $CURRENT_PATH=$arSites['CURRENT_PATH'];
}else{
    die('Не найдено сайтов');
}
$arTypes=\Slytek\Settings::getTypes();
$arProps=\Slytek\Settings::getPropsValues($SITE_ID, $CURRENT_PATH);


$arPropsContent=array('main_settings'=>array());
foreach($arProps as $name=>$arProp){
    if($arProp['TYPE']=='complex_page'){
        $arProp['NAME']=$arProp['NAME']?$arProp['NAME']:$arProp['CODE'];
        $arPropsContent[$arProp['CODE']]=$arProp['CHILDRENS'];
        $aPropsTabs[]=array("DIV" => $arProp['CODE'], "TAB" => $arProp['NAME'], "ICON"=>"", "TITLE"=>$arProp['NAME']);
    }
    else{
        $arPropsContent['main_settings'][$arProp['CODE']]=$arProp;
    }
}
$aTabs=array();
if($arPropsContent['main_settings'])
$aTabs[]=array("DIV" => "main_settings", "TAB" => 'Основные свойства', "ICON"=>"", "TITLE"=>'Основные свойства');
if($aPropsTabs){
    $aTabs=array_merge($aTabs, $aPropsTabs);
}
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;
if($_REQUEST['apply'] || $_REQUEST['clear_cache']){
     \Slytek\Settings::clearCache($SITE_ID);
}
if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST['apply'] && $_REQUEST["Update"]=="Y" && check_bitrix_sessid())
{
    \Slytek\Settings::saveProps($SITE_ID, $CURRENT_PATH);
    LocalRedirect($APPLICATION->GetCurPage()."?LID=".$SITE_ID."&lang=".LANGUAGE_ID);
}   
$APPLICATION->SetTitle('Настройки сайта');  
if(!defined('ADMIN_PAGE')){
      require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
}else{
    CJSCore::Init(array('admin_interface'));
    
}

?> 
<style>.img-container img{height:60px} 
    .slytek-settings .heading td{border:none !important;}
    table.edit-table {
        width: 100%;
    }
</style>

<form method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>" class="custom-inputs">
<?if(defined('ADMIN_PAGE') && defined('SITE_ID')):?>
<input type="hidden" name="LID" value="<?=SITE_ID?>">
<?else:?>
    <table style="margin-bottom: 10px;">
        <tr>
            <td>Сайт для которого заполняются свойства</td>
            <td><select name="LID" onchange="document.location.href='<?echo $APPLICATION->GetCurPage()?>?lang=<?echo LANG?>&LID='+this.value">
                <?foreach($arSites['SITES'] as $arSite):?>
                    <option value="<?=$arSite['LID']?>" <?=$arSite['SELECTED']?' selected':''?>>[<?=$arSite['LID']?>] <?=$arSite['NAME']?></option>
                    <?endforeach?>
            </select> </td>
        </tr>
    </table>
   <?endif?>
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y">
    <input type="hidden" name="lang" value="<?echo LANG?>">   
    <?  
    $tabControl->Begin();
    foreach($arPropsContent as $arProps):
        if(!$arProps)continue;
        $tabControl->BeginNextTab(); 
        \Slytek\Settings::formatProps($arProps);
    endforeach;
    $tabControl->Buttons();
    ?>
    <input type="submit" name="apply" value="<?echo GetMessage("admin_lib_edit_save")?>" title="<?echo GetMessage("admin_lib_edit_savey_title")?>" class="adm-btn-save">
    <input type="submit" name="clear_cache" value="Очистить кэш настроек сайта" class="adm-btn-save">
    <?
    $tabControl->End();
    $tabControl->ShowWarnings("form1", $message);
    ?>
</form>

<?
if(!defined('ADMIN_PAGE'))require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
