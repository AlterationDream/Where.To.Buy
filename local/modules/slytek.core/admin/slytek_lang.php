<?
/**
* @global CMain $APPLICATION
* @global CUser $USER
* */
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
global $DB;
$aTabs=array();
$k=0;
if($USER->IsAdmin())$editable=true;
$aTabs[]=array("DIV" => "edit1", "TAB" => 'Языковые фразы', "ICON"=>"", "TITLE"=>'Языковые фразы');
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;
$path=$_SERVER['DOCUMENT_ROOT'].'/local/php_interface/slytek/lang';
$res = Bitrix\Main\LanguageTable::getList(array('filter'=>array('ACTIVE'=>'Y')));
while($item = $res->fetch()){
    $arLngs[]=$item['LID'];
    $MESS['LANG_TITLE_'.$item['LID']]=$item['NAME'];
}
//$arLngs=array('ru','en');
if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST["Update"]=="Y" && $editable && check_bitrix_sessid())
{
    if(!$_POST['MESSAGES']){
        foreach($arLngs as $l){
            $_POST['MESSAGES'][$l]=array();
        }
    }
    if($_POST['MESSAGES']){
        foreach($_POST['MESSAGES'] as $lang=>$arMessages){
            foreach($arMessages as $name=>$val){
                if(array_key_exists($name, $_REQUEST['REMOVE']))unset($arMessages[$name]);
            }
            if($_POST['NEWMESSAGES']){
                foreach($_POST['NEWMESSAGES'] as $new){
                    if(!$new)continue;
                    $arMessages[$new]='';
                }
            }
            file_put_contents($path.'/'.$lang.'.dat', json_encode($arMessages));
        }
    }
    LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID);

} 

global $MESSAGES;
$MESSAGES=array();

foreach($arLngs as $lang){
    $MESSAGES[$lang]=json_decode(file_get_contents($path.'/'.$lang.'.dat'), true);
    //include $path.'/'.$lang.'.php';
}
$APPLICATION->SetTitle('Языковые фразы');  
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");

//$MESS['LANG_TITLE_en']='Английская версия';
?> 
<form method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y">
    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    ?>
           <!--  <tr class="heading">
                <td colspan="2"></td>
            </tr> -->
            <?foreach($MESSAGES['ru'] as $name=>$title):
            ?>
            <tr data-name="<?=$name?>">
                <td style="width: 200px; border-bottom: 1px solid #ccc;"><h3><?=$title?$title.'<br>['.$name.']':$name?></h3></td>
                <td style="border-bottom: 1px solid #ccc;">
                    <table style="width: 100%">
                        <tbody>
                            <?foreach($arLngs as $lang):?>
                            <tr>
                                <td style="width: 150px;">
                                    <?=GetMessage('LANG_TITLE_'.$lang)?></td>
                                    <td><input type="text" name="MESSAGES[<?=$lang?>][<?=$name?>]" value="<?=$MESSAGES[$lang][$name]?>" style="width: 90%"></td>

                                </tr>
                                <?endforeach?>
                            </tbody>
                        </table>
                    </td>
                    <td style="border-bottom: 1px solid #ccc;"><input type="checkbox" name="REMOVE[<?=$name?>]"/>удалить</td>
                </tr>

                <?endforeach?>
                <tr>
                    <td style="width: 200px; border-bottom: 1px solid #ccc;"><h3>Добавить фразы (коды)</h3></td>
                    <td style="border-bottom: 1px solid #ccc;">
                        <table style="width: 100%">
                            <tbody>
                                <?for($i=0; $i<6; $i++):?>
                                <tr>
                                    <td style="width: 150px;"></td><td><input type="text" name="NEWMESSAGES[]" value="" style="width: 100%"></td>
                                </tr>
                                <?endfor?>
                            </tbody>
                        </table>
                    </td>
                </tr>
                <?
                $tabControl->Buttons();
                ?>
                <input type="submit" name="apply" value="<?echo GetMessage("admin_lib_edit_save")?>" title="<?echo GetMessage("admin_lib_edit_savey_title")?>" class="adm-btn-save">
                <?
                $tabControl->End();
                $tabControl->ShowWarnings("form1", $message);
                ?>
            </form>

            <?
            require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
            ?>
