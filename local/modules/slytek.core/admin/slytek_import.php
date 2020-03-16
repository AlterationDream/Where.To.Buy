<?
$module_id = "slytek.core";
require_once($_SERVER['DOCUMENT_ROOT']."/bitrix/modules/main/include/prolog_admin_before.php");

IncludeModuleLangFile(__FILE__);
global $DB;
$aTabs=array();
$k=0;
if($USER->IsAdmin())$editable=true;


CJSCore::RegisterExt("slytek_settings", Array(
 // "js" =>    BX_ROOT.'/modules/'.$module_id.'/js/settings.js',
  "rel" =>   array('jquery')
));
CJSCore::Init(array('slytek_settings'));
\Bitrix\Main\Loader::includeModule('slytek.core');
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
    array("DIV" => "csv_settings", "TAB" => 'Импорт из CSV', "ICON"=>"", "TITLE"=>'Импорт из CSV')
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
    $csv_settings=$_POST['csv_settings'];
    $csv_settings['csv_file']=$_POST['csv_settings_csv_file'];
    $csv_settings['csv_image_dir']=$_POST['csv_settings_csv_image_dir'];
    file_put_contents(__DIR__.'/../settings/csv_settings_'.$SITE_ID.'.dat', serialize($csv_settings));
    LocalRedirect($APPLICATION->GetCurPage(true).'?lang='.LANGUAGE_ID);
} 
$csv_settings=unserialize(file_get_contents(__DIR__.'/../settings/csv_settings_'.$SITE_ID.'.dat'));


$csv_settings = \Slytek\Csv\Import::initPageParams($SITE_ID, $csv_settings);

$arProperties=array();
if($csv_settings['iblock']>0){
    $properties=CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>intval($csv_settings['iblock'])));
    while ($ibProp = $properties->GetNext()){
        $arProperties[]=$ibProp;
    }
}

//Bitrix\Main\Page\Asset::getInstance()->addJs("https://code.jquery.com/jquery-3.1.0.min.js"); 

require_once($_SERVER["DOCUMENT_ROOT"].BX_ROOT."/modules/main/include/prolog_admin_after.php");
?>
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
    <pre>
        \Bitrix\Main\Loader::includeModule('slytek.core');
        \Slytek\Csv\Import::start($SITE_ID, $params=false)
    </pre>
    <a href="/bitrix/admin/slytek_import_progress.php?lang=ru" class="adm-btn adm-btn-save">Состояние текущего импорта</a>
    <br>
    <?=bitrix_sessid_post()?>
    <input type="hidden" name="Update" value="Y">
    <input type="hidden" name="lang" value="<?echo LANG?>">   

    <?
    $tabControl->Begin();
    $tabControl->BeginNextTab();
    
    \Bitrix\Main\Loader::includeModule('iblock');
    ?>
    <tr>
        <td width="30%"><label>Инфоблок для импорта</label></td>
        <td width="70%">
            <select name="csv_settings[iblock]">
                <option value="0">(не выбрано)</option>
                <? 
                $res_ib = CIBlock::GetList(Array(), Array('ACTIVE'=>'Y', ), false);
                while($arIblock = $res_ib->Fetch()):?>
                    <option value="<?=$arIblock['ID']?>" <?=$arIblock['ID']==$csv_settings['iblock']?'selected':''?>><?=$arIblock['NAME']?></option>
                    <?endwhile?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Путь к файлу CSV</label></td>
            <td width="70%">
                <?
                CAdminFileDialog::ShowScript(
                    Array
                    (
                        "event" => "OpenFileBrowserWindFileCsvFile",
                        "arResultDest" => Array("FORM_NAME" => "form1", "FORM_ELEMENT_NAME" => 'csv_settings_csv_file'),
                        "arPath" => Array('PATH' => '/'),
                        "select" => 'F',
                        "fileFilter" => 'csv',

                        "operation" => 'O',
                        "showUploadTab" => true,
                        "allowAllFiles" => true,
                        "SaveConfig" => true
                    )
                );
                ?>
                <input type="text" name="csv_settings_csv_file" value="<?=$csv_settings['csv_file']?>" size="40">
                <input type="button" value="..." onclick="OpenFileBrowserWindFileCsvFile()"/>

            </td>
        </tr>
        <tr class="heading"><td colspan="2">Соответсвующие свойства</td></tr>
        <tr>
            <td width="30%"><label>Использовать как ID</label></td>
            <td width="70%">
                <select name="csv_settings[ID]">
                    <option <?=$csv_settings['ID']=='NAME'?'selected':''?> value="NAME">Название</option>
                    <option <?=$csv_settings['ID']=='XML_ID'?'selected':''?> value="XML_ID">Внешний код</option>
                    <?foreach($arProperties as $arProp):?>
                    <option <?=$csv_settings['ID']==('PROPERTY_'.$arProp['CODE'])?'selected':''?> value="PROPERTY_<?=$arProp['CODE']?>"><?=$arProp['NAME']?></option>
                    <?endforeach?>
                </select>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Внешний код</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][XML_ID]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['XML_ID']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][XML_ID]" value="<?=$csv_settings['fields']['XML_ID']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Название раздела</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][SECTION_NAME]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['SECTION_NAME']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][SECTION_NAME]" value="<?=$csv_settings['fields']['SECTION_NAME']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Название</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][NAME]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['NAME']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][NAME]" value="<?=$csv_settings['fields']['NAME']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Описание</label></td>
            <td width="70%">
             <?if($csv_settings['headers']):?>
             <select name="csv_settings[fields][DETAIL_TEXT]">
                <option value="">(свойство из файла)</option>
                <?foreach($csv_settings['headers'] as $title):?>
                <option <?=$csv_settings['fields']['DETAIL_TEXT']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                <?endforeach?>
            </select>
            <?else:?>
            <input type="text" name="csv_settings[fields][DETAIL_TEXT]" value="<?=$csv_settings['fields']['DETAIL_TEXT']?>" size="50">
            <?endif?>
        </td>
    </tr>
    <tr>
        <td width="30%"><label>Путь к картинкам</label></td>
        <td width="70%">
            <?
            CAdminFileDialog::ShowScript(
                Array
                (
                    "event" => "OpenFileBrowserWindFileCsvImageDir",
                    "arResultDest" => Array("FORM_NAME" => "form1", "FORM_ELEMENT_NAME" => 'csv_settings_csv_image_dir'),
                    "arPath" => Array('PATH' => '/'),
                    "select" => 'D',

                    "operation" => 'O',
                    "showUploadTab" => false,
                    "allowAllFiles" => false,
                    "SaveConfig" => true
                )
            );
            ?>
            <input type="text" name="csv_settings_csv_image_dir" value="<?=$csv_settings['csv_image_dir']?>" size="40">
            <input type="button" value="..." onclick="OpenFileBrowserWindFileCsvImageDir()"/>

        </td>
    </tr>
    <tr>
        <td width="30%"><label>Идентификатор в названии картинки</label></td>
        <td width="70%">
         <?if($csv_settings['headers']):?>
         <select name="csv_settings[picture_name]">
            <option value="">(свойство из файла)</option>
            <?foreach($csv_settings['headers'] as $title):?>
            <option <?=$csv_settings['picture_name']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
            <?endforeach?>
        </select>
        <?else:?>
        <input type="text" name="csv_settings[picture_name]" value="<?=$csv_settings['picture_name']?>" size="50">
        <?endif?>
    </td>
</tr>
<tr>
    <td width="30%"><label>Основная картинка</label></td>
    <td width="70%">
      <select name="csv_settings[picture_field]">
        <option value="">(поле)</option>
        <option value="PREVIEW_PICTURE" <?=$csv_settings['picture_field']=='PREVIEW_PICTURE'?'selected':''?>>Картинка для анонса</option>
        <option value="DETAIL_PICTURE" <?=$csv_settings['picture_field']=='DETAIL_PICTURE'?'selected':''?>>Детальная картинка</option>

    </select>
</td>
</tr>
<tr>
    <td width="30%"><label>Свойство для картинок</label></td>
    <td width="70%">
     <?if($arProperties):?>
     <select name="csv_settings[picture_prop]">
        <option value="">(свойство инфоблока)</option>
        <?foreach($arProperties as $arProp):?>
        <option <?=$csv_settings['picture_prop']==$arProp['CODE']?'selected':''?> value="<?=$arProp['CODE']?>"><?=$arProp['NAME']?></option>
        <?endforeach?>
    </select>
    <?else:?>
    Нет свойств
    <?endif?>
</td>
</tr>
<?if(\Bitrix\Main\Loader::includeModule('currency') && \Bitrix\Main\Loader::includeModule('catalog') && $csv_settings['update_catalog']):?>
<tr>
    <td width="30%"><label>Закупочная цена</label></td>
    <td width="70%">
     <?if($csv_settings['headers']):?>
     <select name="csv_settings[fields][stock_price]">
        <option value="">(свойство из файла)</option>
        <?foreach($csv_settings['headers'] as $title):?>
        <option <?=$csv_settings['fields']['stock_price']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
        <?endforeach?>
    </select>
    <?else:?>
    <input type="text" name="csv_settings[fields][stock_price]" value="<?=$csv_settings[fields]['stock_price']?>" size="50">
    <?endif?>
    <?if(\Bitrix\Main\Loader::includeModule('currency')):?>
    <select name="csv_settings[stock_price][currency]">
        <option value="0">(валюта)</option>
        <? $lcur = CCurrency::GetList(($by="name"), ($order="asc"), LANGUAGE_ID);
        while($arCurrency = $lcur->Fetch()):
            ?>
            <option value="<?=$arCurrency['CURRENCY']?>" <?=$arCurrency['CURRENCY']==$csv_settings['stock_price']['currency']?'selected':''?>><?=$arCurrency['CURRENCY']?></option>
            <?endwhile?>
        </select>
        <?endif?>
    </td>
</tr>

<tr>
    <td width="30%"><label>Цена</label></td>
    <td width="70%">
        <?if($csv_settings['headers']):?>
        <select name="csv_settings[fields][price]">
            <option value="">(свойство из файла)</option>
            <?foreach($csv_settings['headers'] as $title):?>
            <option <?=$csv_settings['fields']['price']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
            <?endforeach?>
        </select>
        <?else:?>
        <input type="text" name="csv_settings[fields][price]" value="<?=$csv_settings['fields']['price']?>">
        <?endif?>
        <?if(\Bitrix\Main\Loader::includeModule('catalog')):?>
        <select name="csv_settings[price][type]">
            <option value="0">(тип цены)</option>
            <? $dbPriceType = CCatalogGroup::GetList(
                array("SORT" => "ASC"),
                array()
            );
            while ($arPriceType = $dbPriceType->Fetch()):

                ?><option value="<?=$arPriceType['ID']?>" <?=$arPriceType['ID']==$csv_settings['price']['type']?'selected':''?>><?=$arPriceType['NAME_LANG']?$arPriceType['NAME_LANG']:$arPriceType['NAME']?></option>
                <?endwhile?>
            </select>
            <?endif?>
            <?if(\Bitrix\Main\Loader::includeModule('currency')):?>
            <select name="csv_settings[price][currency]">
                <option value="0">(валюта)</option>
                <? $lcur = CCurrency::GetList(($by="name"), ($order="asc"), LANGUAGE_ID);
                while($arCurrency = $lcur->Fetch()):
                    ?>
                    <option value="<?=$arCurrency['CURRENCY']?>" <?=$arCurrency['CURRENCY']==$csv_settings['price']['currency']?'selected':''?>><?=$arCurrency['CURRENCY']?></option>
                    <?endwhile?>
                </select>
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Остаток</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][available]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['available']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][available]" value="<?=$csv_settings['fields']['available']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Ширина</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][width]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['width']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][width]" value="<?=$csv_settings['fields']['width']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Длина</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][length]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['length']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][length]" value="<?=$csv_settings['fields']['length']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Высота</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][height]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['height']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][height]" value="<?=$csv_settings['fields']['height']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Диаметр</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][diametr]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['diametr']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][diametr]" value="<?=$csv_settings['fields']['diametr']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <tr>
            <td width="30%"><label>Вес</label></td>
            <td width="70%">
                <?if($csv_settings['headers']):?>
                <select name="csv_settings[fields][weight]">
                    <option value="">(свойство из файла)</option>
                    <?foreach($csv_settings['headers'] as $title):?>
                    <option <?=$csv_settings['fields']['weight']==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                    <?endforeach?>
                </select>
                <?else:?>
                <input type="text" name="csv_settings[fields][weight]" value="<?=$csv_settings['fields']['weight']?>" size="50">
                <?endif?>
            </td>
        </tr>
        <?endif?>
        <?if($arProperties):
        foreach($arProperties as $arProps):?>
            <tr>
                <td width="30%"><label><?=$arProps['NAME']?></label></td>
                <td width="70%">
                    <?if($csv_settings['headers']):?>
                    <select name="csv_settings[props][<?=$arProps['CODE']?>]">
                        <option value="">(свойство из файла)</option>
                        <?foreach($csv_settings['headers'] as $title):?>
                        <option <?=$csv_settings['props'][$arProps['CODE']]==$title?'selected':''?> value="<?=$title?>"><?=$title?></option>
                        <?endforeach?>
                    </select>
                    <?else:?>
                    <input type="text" name="csv_settings[props][<?=$arProps['CODE']?>]" value="<?=$csv_settings['props'][$arProps['CODE']]?>" size="50">
                    <?endif?>
                </td>
            </tr>
            <?endforeach?>

            <?endif?>

            <?
            $tabControl->Buttons();
            ?>
            <input type="submit" name="apply" value="<?echo GetMessage("admin_lib_edit_save")?>" title="<?echo GetMessage("admin_lib_edit_savey_title")?>" class="adm-btn-save">
            <?
            $tabControl->End();
            $tabControl->ShowWarnings("form1", $message);
            ?>
        </form>