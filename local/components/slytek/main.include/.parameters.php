<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CModule::includeModule('slytek.settings');
$arType = array("page" => GetMessage("SLYTEK_INCLUDE_PAGE"), "sect" => GetMessage("SLYTEK_INCLUDE_SECT"), 'prop'=>GetMessage("SLYTEK_INCLUDE_PROP"));
if ($GLOBALS['USER']->CanDoOperation('edit_php'))
{
    $arType["file"] = GetMessage("SLYTEK_INCLUDE_FILE");
}

$site_template = false;
$site = ($_REQUEST["site"] <> ''? $_REQUEST["site"] : ($_REQUEST["src_site"] <> ''? $_REQUEST["src_site"] : false));
if($site !== false)
{
    $rsSiteTemplates = CSite::GetTemplateList($site);
    while($arSiteTemplate = $rsSiteTemplates->Fetch())
    {
        if(strlen($arSiteTemplate["CONDITION"])<=0)
        {
            $site_template = $arSiteTemplate["TEMPLATE"];
            break;
        }
    }
}
if (CModule::IncludeModule('fileman'))
{
    $arTemplates = CFileman::GetFileTemplates(LANGUAGE_ID, array($site_template));
    $arTemplatesList = array();
    foreach ($arTemplates as $key => $arTemplate)
    {
        $arTemplateList[$arTemplate["file"]] = "[".$arTemplate["file"]."] ".$arTemplate["name"];
    }
}
else
{
    $arTemplatesList = array("page_inc.php" => "[page_inc.php]", "sect_inc.php" => "[sect_inc.php]");
}

$arComponentParameters = array(
    "GROUPS" => array(
        "PARAMS" => array(
            "NAME" => GetMessage("SLYTEK_INCLUDE_PARAMS"),
        ),
    ),

    "PARAMETERS" => array(
        "AREA_FILE_SHOW" => array(
            "NAME" => GetMessage("SLYTEK_INCLUDE_AREA_FILE_SHOW"), 
            "TYPE" => "LIST",
            "MULTIPLE" => "N",
            "VALUES" => $arType,
            "ADDITIONAL_VALUES" => "N",
            "DEFAULT" => "page",
            "PARENT" => "PARAMS",
            "REFRESH" => "Y",
        ),
    ),
);

if ($GLOBALS['USER']->CanDoOperation('edit_php') && $arCurrentValues["AREA_FILE_SHOW"] == "file")
{
    unset(
        $arCurrentValues["AREA_FILE_SUFFIX"], 
        $arCurrentValues["AREA_FILE_RECURSIVE"] 
    );
    $arComponentParameters["PARAMETERS"]["PATH"] = array(
        "NAME" => GetMessage("SLYTEK_INCLUDE_PATH"), 
        "TYPE" => "STRING",
        "MULTIPLE" => "N",
        "ADDITIONAL_VALUES" => "N",
        "PARENT" => "PARAMS",
    );
}
elseif($arCurrentValues["AREA_FILE_SHOW"] == "prop" && CModule::includeModule('slytek.core')){
    unset(
        $arCurrentValues["AREA_FILE_SUFFIX"], 
        $arCurrentValues["AREA_FILE_SUFFIX"], 
        $arCurrentValues["AREA_FILE_RECURSIVE"], 
        $arCurrentValues["PATH"] 
    );
    $arSites=array();
    $dbSites = Bitrix\Main\SiteTable::getList(
        array(
            'order' => array('DEF' => 'DESC', 'NAME' => 'ASC'),
            'select' => array('LID', 'NAME')
        )
    );
    while($arSite = $dbSites->fetch())
    {
        $arSites[$arSite['LID']]="[".$arSite["LID"]."] ".$arSite["NAME"];
    }
    $arComponentParameters["PARAMETERS"]["SITE_ID"] = array(
        "NAME" => 'Сайт', 
        "TYPE" => "LIST",
        "VALUES" => $arSites,
        "DEFAULT" => $site,
        "ADDITIONAL_VALUES" => "N",
        "PARENT" => "PARAMS",
        "REFRESH" => "Y"
    );
    $arVals=\Slytek\Settings::getPropsParams($site);;
    $arComponentParameters["PARAMETERS"]['PROPERTY_ID']=array(
        "NAME" => 'Свойство сайта', 
        "TYPE" => "LIST",
        "MULTIPLE" => "N",
        "VALUES" => $arVals,
        "ADDITIONAL_VALUES" => "N",
        "DEFAULT" => "",
        "PARENT" => "PARAMS",
       
    );
}
else
{
    $arComponentParameters["PARAMETERS"]["AREA_FILE_SUFFIX"] = array(
        "NAME" => GetMessage("SLYTEK_INCLUDE_AREA_FILE_SUFFIX"), 
        "TYPE" => "STRING",
        "DEFAULT" => "inc",
        "PARENT" => "PARAMS",
    );

    if ($arCurrentValues["AREA_FILE_SHOW"] == "sect")
    {
        $arComponentParameters["PARAMETERS"]["AREA_FILE_RECURSIVE"] = array(
            "NAME" => GetMessage("SLYTEK_INCLUDE_AREA_FILE_RECURSIVE"), 
            "TYPE" => "CHECKBOX",
            "ADDITIONAL_VALUES" => "N",
            "DEFAULT" => "Y",
            "PARENT" => "PARAMS",
        );
    }
    else unset($arCurrentValues["AREA_FILE_RECURSIVE"]);
}

$arComponentParameters["PARAMETERS"]["EDIT_TEMPLATE"] = array(
    "NAME" => GetMessage("SLYTEK_INCLUDE_EDIT_TEMPLATE"), 
    "TYPE" => "LIST",
    "VALUES" => $arTemplateList,
    "DEFAULT" => "",
    "ADDITIONAL_VALUES" => "Y",
    "PARENT" => "PARAMS",
);
?>