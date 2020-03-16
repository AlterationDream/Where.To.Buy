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
$aTabs[]=array("DIV" => "PREVIEW", "TAB" => 'Для превью картинок', "ICON"=>"", "TITLE"=>'Для превью картинок');
$aTabs[]=array("DIV" => "DETAIL", "TAB" => 'Для детальных картинок', "ICON"=>"", "TITLE"=>'Для детальных картинок');
$tabControl = new CAdminTabControl("tabControl", $aTabs);
$bFormValues = false;

$options = array(
    'active'=>array('name'=>'Включен', 'type'=>'checkbox'),
    'position'=>array(
        'name'=>'Расположение',
        'type'=>'select',
        'options'=>array(
            'topleft'=>'Вверху слева',
            'topcenter'=>'Вверху в центре',
            'topright'=>'Вверху справа',
            'centerleft'=>'По центру слева',
            'center'=>'По центру',
            'centerright'=>'По центру справа',
            'bottomleft'=>'Внизу слева',
            'bottomcenter'=>'Внизу в центре',
            'bottomright'=>'Внизу справа'
        )
    ),
    'type'=>array(
        'name'=>'Тип',
        'type'=>'select',
        'options'=>array(
            'image'=>'Картинка',
            'text'=>'Текст',
        )
    ),
    'size'=>array(
        'name'=>'Размер',
        'type'=>'select',
        'options'=>array(
            'big'=>'Большой',
            'medium'=>'Средний',
            'small'=>'Маленький',
            'real'=>'Реальный',
        )
    ),
    'coefficient'=>array(
        'name'=>'Коэфициент размера (альтернатива параметру Размер. Для текста - от 1 до 7, для картинок 0.1 до 1 )',
        'type'=>'text'
    ),
    'fill'=>array(
        'name'=>'Способ размещения',
        'type'=>'select',
        'options'=>array(
            'exact'=>'Разместить',
            'resize'=>'Заполнить',
            'repeat'=>'Повторять',
        )
    ),
    'file'=>array(
        'name'=>'Файл картинки',
        'type'=>'file'
    ),
    'alpha_level'=>array(
        'name'=>'Прозрачность (0-100)',
        'type'=>'text'
    ),
    'text'=>array(
        'name'=>'Текст водяного знака',
        'type'=>'text'
    ),
    'color'=>array(
        'name'=>'Цвет Текста водяного знака (например ffffff)',
        'type'=>'text'
    ),
    'font'=>array(
        'name'=>'Шрифт текста (формат ttf)',
        'type'=>'file'
    )
);

$path=$_SERVER['DOCUMENT_ROOT'].'/local/modules/slytek.core/settings/watermark.dat';

if($_SERVER["REQUEST_METHOD"]=="POST" && $_REQUEST["Update"]=="Y" && $editable && check_bitrix_sessid())
{
    $arOptions=array();
    foreach($aTabs as $arTab){
        foreach($options as $name=>$option){
            if($value=$_REQUEST['options___'.$arTab['DIV'].'___'.$name]){
                switch ($option['type']) {
                    case 'select':
                    if(array_key_exists($value, $option['options'])){
                        $arOptions[$arTab['DIV']][$name]=$value;
                    }
                    break;
                    case 'checkbox':
                    $arOptions[$arTab['DIV']][$name]=$value=='Y'?'Y':'N';

                    break;
                    case 'file':
                    $value = $_SERVER["DOCUMENT_ROOT"].str_ireplace('/../', '/', $value);
                    if(file_exists($value)){
                        $arOptions[$arTab['DIV']][$name] = $value;
                    }
                    break;
                    default:
                    $arOptions[$arTab['DIV']][$name] = htmlspecialchars($value);
                    break;
                }
            }
        }
    }
    file_put_contents($path, json_encode($arOptions));
    LocalRedirect($APPLICATION->GetCurPage()."?lang=".LANGUAGE_ID);

} 

$arResult = json_decode(file_get_contents($path), true);

$APPLICATION->SetTitle('Настройка водяного знака');  
require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?> 
<form method="POST" name="form1" action="<?echo $APPLICATION->GetCurPage()?>">
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y">
    <?
    $tabControl->Begin();
    foreach($aTabs as $arTab):
        $tabControl->BeginNextTab();
        ?>
           <!--  <tr class="heading">
                <td colspan="2"></td>
            </tr> -->
            <?foreach($options as $name=>$arOption):
            $arOption['field_name']='options___'.$arTab['DIV'].'___'.$name;
            $arOption['value']=$arResult[$arTab['DIV']][$name];
            ?>
            <tr>
                <td width="300"><?=$arOption['name']?></td>
                <td>
                   <?
                   switch ($arOption['type']) {
                       case 'select':
                       ?>
                       <select name="<?=$arOption['field_name']?>">
                        <?foreach($arOption['options'] as $value=>$option):?>
                        <option value="<?=$value?>" <?=$value==$arOption['value']?'selected':''?>><?=$option?></option>
                        <?endforeach?>
                    </select>
                    <?
                    break;
                    case 'checkbox':
                    ?>
                    <input type="checkbox" name="<?=$arOption['field_name']?>" value="Y"<?=$arOption['value']=='Y'?' checked':''?>>
                    <?
                    break;
                    case 'file':
                    $arOption['value'] = str_ireplace($_SERVER['DOCUMENT_ROOT'],'',$arOption['value']);
                    \CAdminFileDialog::ShowScript(
                        Array
                        (
                            "event" => "OpenFileBrowserWindFile".$arTab['DIV'].$name,
                            "arResultDest" => Array("FORM_NAME" => "form1", "FORM_ELEMENT_NAME" => $arOption['field_name']),
                            "arPath" => Array('PATH' => '/upload'),
                            "select" => 'F',
                            "operation" => 'O',
                            "showUploadTab" => true,
                            "fileFilter" => $name=='font'?'ttf':'jpg,png,gif',
                            "allowAllFiles" => false,
                            "SaveConfig" => true
                        )
                    );
                    ?>
                    <input type="text" name="<?=$arOption['field_name']?>" value="<?=$arOption['value']?>" size="40">
                    <input type="button" value="..." onclick="OpenFileBrowserWindFile<?=$arTab['DIV'].$name?>()">
                    <?
                    break;

                    default:
                    ?>
                    <input type="text" name="<?=$arOption['field_name']?>" value="<?=$arOption['value']?>" size="40">
                    
                    <?
                    break;
                }
                ?>
            </td>
        </tr>
        <?
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
require_once ($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/epilog_admin.php");
?>
