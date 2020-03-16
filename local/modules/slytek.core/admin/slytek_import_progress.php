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

if($_SERVER["REQUEST_METHOD"]=="POST" && $_POST["save"] && check_bitrix_sessid()){
    if(stripos($_FILES['CSV']['name'], '.csv')===false)LocalRedirect($APPLICATION->GetCurPage()."?error=1&lang=".LANGUAGE_ID);

    $upload_path= '/upload/import/import.csv';
    $uploadfileCsv = $_SERVER['DOCUMENT_ROOT'] . $upload_path;
    if (move_uploaded_file($_FILES['CSV']['tmp_name'], $uploadfileCsv)) {

        $upload_path= '/upload/import/images/['.date('H:i:s d.m.Y').'] '.basename($_FILES['ZIP']['name']);
        $uploadfile = $_SERVER['DOCUMENT_ROOT'] . $upload_path;
        if (move_uploaded_file($_FILES['ZIP']['tmp_name'], $uploadfile)) 
        {
            $resArchiver = CBXArchive::GetArchive($uploadfile);
            $resArchiver->SetOptions(Array(
                "REMOVE_PATH"      => $_SERVER["DOCUMENT_ROOT"],
                "UNPACK_REPLACE"   => true
            ));
            $resArchiver->Unpack($_SERVER['DOCUMENT_ROOT'].'/upload/import/images/');
            unlink($uploadfile);     
        }
        \Slytek\Csv\Import::start('s1');
        $pricelist_path = $_SERVER['DOCUMENT_ROOT'].'/local/modules/slytek.core/settings/pricelist.dat';
        $pricelist = unserialize(file_get_contents($pricelist_path));
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
        //unlink($uploadfileCsv);
    }
    LocalRedirect($APPLICATION->GetCurPage()."?ok=1&lang=".LANGUAGE_ID);
}

$aTabs = array(
    array("DIV" => "main_settings", "TAB" => 'Прогресс', "ICON"=>"", "TITLE"=>'Прогресс'),
);
$tabControl = new CAdminTabControl("tabControl", $aTabs);
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
CJSCore::Init(array("jquery"));
if($_REQUEST['ok']){
    CAdminMessage::ShowMessage(array(
        "MESSAGE"=>"Файлы обработаны",
        "HTML"=>true,
        "TYPE"=>"OK",
    )); 
}
?> 

<form enctype="multipart/form-data" method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>" class="custom-inputs">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y"/>
    <?  
    $tabControl->Begin();
    $tabControl->BeginNextTab(); 
    ?>
    <tr>
        <td >Загрузить файл CSV</td>
        <td>
            <input type="file" accept="text/csv" name="CSV"/> 
        </td>
    </tr>
    <tr>
        <td >Загрузить файл ZIP с картинками</td>
        <td>
            <input type="file" accept="application/zip" name="ZIP"/> 
        </td>
    </tr>
    <?
    $tabControl->Buttons();
    ?>
    <input type="submit" name="save" value="Загрузить" class="adm-btn-save">
    <?
    $tabControl->End();
    $tabControl->ShowWarnings("form1", $message);
    ?>
</form>
<script type="text/javascript">
    function checkProgress(type){
        var url = '/bitrix/modules/slytek.core/settings/progress.txt',
        progressbar = $('#main_settings .adm-info-message-gray');

        $.ajax({
            url: url,
            cache: false,
            process: false,
            success: function(html){
                var params = html.split('|');
                if(params[0])progressbar.find('.adm-info-message-title').text(params[0]);
                if(!params[2])params[2]=params[1];
                if(params[1] && params[2])progressbar.find('.bx-percents').text(params[1]+'/'+params[2]);
                if(params[0]=='complete')type='import';
                setTimeout(function(){
                    checkProgress(type);
                }, 2000)
            }
        })
    }
    $(document).ready(function(){
        checkProgress('migrate');
    })
</script>
<?
if(!defined('ADMIN_PAGE'))require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
