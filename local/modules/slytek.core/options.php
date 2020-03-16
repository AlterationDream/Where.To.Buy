<?
$module_id = "slytek.core";

if (!$USER->CanDoOperation('slytek.core'))
{
    $APPLICATION->AuthForm(GetMessage("ACCESS_DENIED"));
}
CJSCore::RegisterExt("slytek_settings", Array(
 // "js" =>    BX_ROOT.'/modules/'.$module_id.'/js/settings.js',
  "rel" =>   array('jquery')
));
CJSCore::Init(array('slytek_settings'));
CModule::IncludeModule('slytek.core');
IncludeModuleLangFile($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/options.php");
IncludeModuleLangFile(__FILE__);
$arAllOptions = Array(
    Array("property_window_title", 'Прочие настройки сайта от Slytek', array("text"), "title"),
    Array("property_description", '', array("text"), "description"),
    Array("property_keywords", '', array("text"), "keywords"),
    //Array("property_internal_keywords", GetMessage('SEO_OPT_PROP_INTERNAL_KEYWORDS'), array("text"), "keywords_inner"),
);
global $DB;
$aTabs = array(
    array("DIV" => "include_areas_props", "TAB" => 'Свойства вкл. областей', "ICON"=>"", "TITLE"=>'Свойства вкл. областей'),
    array("DIV" => "include_areas", "TAB" => 'Настройки вкл. областей', "ICON"=>"", "TITLE"=>'Настройки вкл. областей'),
    array("DIV" => "protect_email", "TAB" => 'Защита E-mail', "ICON"=>"", "TITLE"=>'Защита E-mail'),
    array("DIV" => "functions", "TAB" => 'Отложенные функции', "ICON"=>"", "TITLE"=>'Отложенные функции'),
    array("DIV" => "other", "TAB" => 'Другие настройки', "ICON"=>"", "TITLE"=>'Другие настройки'),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;
$arSites=\Slytek\Settings::getSites();
$currentSite=$arSites['CURRENT'];
if($currentSite){
    $SITE_ID=$currentSite['LID'];
}else{
    die('Не найдено сайтов');
}
$arTypes=\Slytek\Settings::getTypes();

if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST["Update"]=="Y" && check_bitrix_sessid())
{
    $path=htmlspecialcharsbx($_POST['include_path']);
    $arr = explode('/', $path);  
    $curr=array(); 
    foreach($arr as $key => $val){ 
        if(empty($val))continue; 
        $curr[]=$val; 
        mkdir($arSites['CURRENT_PATH'].implode('/',$curr)."/", 0755); 
    }  
    $new_settings=array('protect_emails'=>$_POST['protect_emails']=='Y'?'Y':'N', 'include_path'=>$path, 'nophoto'=>htmlspecialcharsbx($_POST['nophoto']));
    $contentProps=$_POST['PROPERTIES'];
    if($contentProps){
        $contentProps=\Slytek\Settings::buildTree($contentProps);
    }
    file_put_contents(__DIR__.'/settings/contentprops_'.$SITE_ID.'.dat', serialize($contentProps));
    $new_settings['delay_variables']=array();
    foreach($_POST['delay_variables_name'] as $key=>$name){
        if($name)$new_settings['delay_variables'][$name]=$_POST['delay_variables_val'][$key];
    }
    $new_settings['delay_variables']=serialize($new_settings['delay_variables']);
    foreach($new_settings as $name_set=>$val_set){
        Bitrix\Main\Config\Option::set('slytek.core', $name_set, $val_set, $SITE_ID);
    }
} 
$props=\Slytek\Settings::getProps($SITE_ID);


//Bitrix\Main\Page\Asset::getInstance()->addJs("https://code.jquery.com/jquery-3.1.0.min.js"); 
?>
<script>
    <?=file_get_contents(__DIR__.'/js/settings.js')?>
    $(document).ready(function(){
        new SlytekSettings(<?=CUtil::PhpToJSObject($props)?>, <?=CUtil::PhpToJSObject($arTypes)?>, $('[id="include_areas_props_edit_table"]'));
    })

    function addQueryRow(obj){
        var original=$(obj).parent().parent();
        var parent=original.parent();
        var clone=original.prev().clone();
        parent.insertBefore(clone, original);
    }
</script>
<style>.slytek-settings .heading td{border:none !important;}

.dragActive {
    position:absolute !important;
    background-color:#333333;
    color:#eeeeee
}</style>
<form class="slytek-settings" method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>">
    <table style="margin-bottom: 10px;">
        <tr>
            <td>Сайт для которого применять настройки</td>
            <td><select name="LID" onchange="document.location.href='<?echo $APPLICATION->GetCurPage()?>?mid=<?=htmlspecialcharsbx($mid)?>&amp;lang=<?echo LANG?>&LID='+this.value">
                <?foreach($arSites['SITES'] as $arSite):?>
                <option value="<?=$arSite['LID']?>" <?=$arSite['SELECTED']?' selected':''?>>[<?=$arSite['LID']?>] <?=$arSite['NAME']?></option>
                <?endforeach?>
            </select> </td>
        </tr>
    </table>

    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y">
    <input type="hidden" name="lang" value="<?echo LANG?>">   

    <?
    $tabControl->Begin();
    foreach($aTabs as $arTab){
        $tabControl->BeginNextTab();
        switch ($arTab['DIV']):
            case 'include_areas_props':
            ?>
            <tr class="button-row"><td><input type="button" title="+" value="Добавить свойство"></td></tr>
            <?
            break;
            case 'other':
            ?>
            <tr>
                <td width="30%"><label for="nophoto">Путь к заглушке отсутсвующих картинок</label></td>
                <td width="70%">
                    <input type="text" id="nophoto" name="nophoto" value="<?=Bitrix\Main\Config\Option::get('slytek.core', 'nophoto', '', $SITE_ID)?>">
                </td>
            </tr>
            <?
            break;
            case 'include_areas':
            ?>
            <tr>
                <td width="30%"><label for="include_path">Путь к включаемым областям</label></td>
                <td width="70%">
                    <input type="text" id="include_path" name="include_path" value="<?=Bitrix\Main\Config\Option::get('slytek.core', 'include_path', '', $SITE_ID)?>">
                </td>
            </tr>
            <?
            break;

            case 'protect_email':
            ?>
            <tr>
                <td width="30%"><label for="protect_emails">Включить защиту E-mail на сайте</label></td>
                <td width="70%">
                    <input type="checkbox" id="protect_emails" name="protect_emails" value="Y"<?=Bitrix\Main\Config\Option::get('slytek.core', 'protect_emails', 'N', $SITE_ID)=='Y'?' checked':''?>>
                </td>
            </tr>
            <?
            break;
            case 'functions':
            ?>
            <tr>
                <td width="50%">Переменные в URL, при которых не будет вызываться отложенный вызов компонентов</td>
                <td width="50%"><table><?
                $variables=unserialize(Bitrix\Main\Config\Option::get('slytek.core', 'delay_variables', '', $SITE_ID));
                if($variables):?><?foreach($variables as $name=>$val):
                    ?><tr><td><input type="text" name="delay_variables_name[]" value="<?=$name?>"></td><td>=</td><td><input type="text" name="delay_variables_val[]" value="<?=$val?>"></td></tr><?
                endforeach?><?else:
                ?><tr><td><input type="text" name="delay_variables_name[]" value="<?=$name?>"></td><td>=</td><td><input type="text" name="delay_variables_val[]" value="<?=$val?>"></td></tr><?
                endif?><tr><td><input type="button" title="+" onclick="addQueryRow(this);return false;" value="+"></td><td></td></tr>
            </table>
        </td>
    </tr>
    <?
    break;
endswitch;
?>

<? 
}
?>

<?
$tabControl->Buttons();
?>
<input type="submit" name="apply" value="<?echo GetMessage("admin_lib_edit_save")?>" title="<?echo GetMessage("admin_lib_edit_savey_title")?>" class="adm-btn-save">
<?
$tabControl->End();
$tabControl->ShowWarnings("form1", $message);
?>
</form>