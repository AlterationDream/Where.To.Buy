<?
namespace Slytek\Props;
use \Bitrix\Main\Localization\Loc,
\Bitrix\Iblock;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/prop_element_list.php');

class UserPropertyList extends \CUserTypeEnum{
	function GetUserTypeDescription() {
		return array(
			"USER_TYPE_ID"	=> "slytekusertypeproperty",
			"CLASS_NAME"	=> "SLYTEK_USERTYPE_PROPERTY",
			"DESCRIPTION"	=> 'Привязка к свойству инфбоблока',
         "BASE_TYPE" => \CUserTypeManager::BASE_TYPE_STRING,
     );
	}

	function GetDBColumnType($arUserField) {
		global $DB;

		switch(strtolower($DB->type)) {
			case "mysql":
			return "text";
			case "oracle":
			return "varchar2(2000 char)";
			case "mssql":
			return "varchar(2000)";
		}
	}

	function PrepareSettings($arUserField) {
     $height = intval($arUserField["SETTINGS"]["LIST_HEIGHT"]);
     $disp = $arUserField["SETTINGS"]["DISPLAY"];
     if($disp!="CHECKBOX" && $disp!="LIST")
        $disp = "LIST";
    $iblock_id = intval($arUserField["SETTINGS"]["IBLOCK_ID"]);
    if($iblock_id <= 0)
        $iblock_id = "";
    $element_id = intval($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
    if($element_id <= 0)
        $element_id = "";

    $active_filter = $arUserField["SETTINGS"]["ACTIVE_FILTER"] === "Y"? "Y": "N";

    return array(
        "DISPLAY" => $disp,
        "LIST_HEIGHT" => ($height < 1? 1: $height),
        "IBLOCK_ID" => $iblock_id,
        "DEFAULT_VALUE" => $element_id,
        "ACTIVE_FILTER" => $active_filter,
    );
}

function GetSettingsHTML($arUserField = false, $arHtmlControl, $bVarsFromForm)
{
    $result = '';

    if($bVarsFromForm)
        $iblock_id = $GLOBALS[$arHtmlControl["NAME"]]["IBLOCK_ID"];
    elseif(is_array($arUserField))
        $iblock_id = $arUserField["SETTINGS"]["IBLOCK_ID"];
    else
        $iblock_id = "";
    if(\CModule::IncludeModule('iblock'))
    {
        $result .= '
        <tr>
        <td>'.GetMessage("USER_TYPE_IBSEC_DISPLAY").':</td>
        <td>
        '.GetIBlockDropDownList($iblock_id, $arHtmlControl["NAME"].'[IBLOCK_TYPE_ID]', $arHtmlControl["NAME"].'[IBLOCK_ID]', false, 'class="adm-detail-iblock-types"', 'class="adm-detail-iblock-list"').'
        </td>
        </tr>
        ';
    }
    else
    {
        $result .= '
        <tr>
        <td>'.GetMessage("USER_TYPE_IBSEC_DISPLAY").':</td>
        <td>
        <input type="text" size="6" name="'.$arHtmlControl["NAME"].'[IBLOCK_ID]" value="'.htmlspecialcharsbx($value).'">
        </td>
        </tr>
        ';
    }

    if($bVarsFromForm)
        $ACTIVE_FILTER = $GLOBALS[$arHtmlControl["NAME"]]["ACTIVE_FILTER"] === "Y"? "Y": "N";
    elseif(is_array($arUserField))
        $ACTIVE_FILTER = $arUserField["SETTINGS"]["ACTIVE_FILTER"] === "Y"? "Y": "N";
    else
        $ACTIVE_FILTER = "N";

    if($bVarsFromForm)
        $value = $GLOBALS[$arHtmlControl["NAME"]]["DEFAULT_VALUE"];
    elseif(is_array($arUserField))
        $value = $arUserField["SETTINGS"]["DEFAULT_VALUE"];
    else
        $value = "";
    if(($iblock_id > 0) && \CModule::IncludeModule('iblock'))
    {
        $result .= '
        <tr>
        <td>'.GetMessage("USER_TYPE_IBSEC_DEFAULT_VALUE").':</td>
        <td>
        <select name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" size="5">
        <option value="">'.GetMessage("IBLOCK_VALUE_ANY").'</option>
        ';

        $arFilter = Array("IBLOCK_ID"=>$iblock_id);
        if($ACTIVE_FILTER === "Y")
            $arFilter["GLOBAL_ACTIVE"] = "Y";

        $rsSections = \CIBlockSection::GetList(
            Array("left_margin"=>"asc"),
            $arFilter,
            false,
            array("ID", "DEPTH_LEVEL", "NAME")
        );
        while($arSection = $rsSections->GetNext())
            $result .= '<option value="'.$arSection["ID"].'"'.($arSection["ID"]==$value? " selected": "").'>'.str_repeat("&nbsp;.&nbsp;", $arSection["DEPTH_LEVEL"]).$arSection["NAME"].'</option>';

        $result .= '</select>';
    }
    else
    {
        $result .= '
        <tr>
        <td>'.GetMessage("USER_TYPE_IBSEC_DEFAULT_VALUE").':</td>
        <td>
        <input type="text" size="8" name="'.$arHtmlControl["NAME"].'[DEFAULT_VALUE]" value="'.htmlspecialcharsbx($value).'">
        </td>
        </tr>
        ';
    }

    if($bVarsFromForm)
        $value = $GLOBALS[$arHtmlControl["NAME"]]["DISPLAY"];
    elseif(is_array($arUserField))
        $value = $arUserField["SETTINGS"]["DISPLAY"];
    else
        $value = "LIST";
    $result .= '
    <tr>
    <td class="adm-detail-valign-top">'.GetMessage("USER_TYPE_ENUM_DISPLAY").':</td>
    <td>
    <label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="LIST" '.("LIST"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_IBSEC_LIST").'</label><br>
    <label><input type="radio" name="'.$arHtmlControl["NAME"].'[DISPLAY]" value="CHECKBOX" '.("CHECKBOX"==$value? 'checked="checked"': '').'>'.GetMessage("USER_TYPE_IBSEC_CHECKBOX").'</label><br>
    </td>
    </tr>
    ';

    if($bVarsFromForm)
        $value = intval($GLOBALS[$arHtmlControl["NAME"]]["LIST_HEIGHT"]);
    elseif(is_array($arUserField))
        $value = intval($arUserField["SETTINGS"]["LIST_HEIGHT"]);
    else
        $value = 5;
    $result .= '
    <tr>
    <td>'.GetMessage("USER_TYPE_IBSEC_LIST_HEIGHT").':</td>
    <td>
    <input type="text" name="'.$arHtmlControl["NAME"].'[LIST_HEIGHT]" size="10" value="'.$value.'">
    </td>
    </tr>
    ';

    return $result;
}

function GetEditFormHTML($arUserField, $arHtmlControl) {
 if($arUserField["ENTITY_VALUE_ID"]<1 && strlen($arUserField["SETTINGS"]["DEFAULT_VALUE"])>0)
    $arHtmlControl["VALUE"] = htmlspecialcharsbx($arUserField["SETTINGS"]["DEFAULT_VALUE"]);
return self::GetAdminListEditHTML($arUserField, $arHtmlControl);
}

function GetFilterHTML($arUserField, $arHtmlControl) {
  return '';
}

function GetAdminListViewHTML($arUserField, $arHtmlControl) {
  if(strlen($arHtmlControl["VALUE"])>0)
    return $arHtmlControl["VALUE"];
else
    return '&nbsp;';
}

function GetAdminListEditHTML($arProperty, $strHTMLControlName) {
  $bWasSelect = false;
  $options = self::GetOptionsHtml($arProperty, array($strHTMLControlName["VALUE"]), $bWasSelect);

  $html = '<select name="'.$strHTMLControlName["NAME"].'"'.$size.$width.'>';
  if($arProperty["IS_REQUIRED"] != "Y")
     $html .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.Loc::getMessage("IBLOCK_PROP_ELEMENT_LIST_NO_VALUE").'</option>';
 $html .= $options;
 $html .= '</select>';
 return  $html;
}

function CheckFields($arUserField, $value) {
  $aMsg = array();

  return $aMsg;
}

function OnSearchIndex($arUserField) {
  return "";
}
public static function GetOptionsHtml($arProperty, $values, &$bWasSelect)
{
  $options = "";
  $bWasSelect = false;
  $rsProperty = \CIBlockProperty::GetList(array("SORT"=>'ASC'),array('IBLOCK_ID'=>$arProperty["SETTINGS"]["IBLOCK_ID"]));

  while($arItem = $rsProperty->Fetch())
  {
     $options .= '<option value="'.$arItem["CODE"].'"';
     if(in_array($arItem["CODE"], $values))
     {
        $options .= ' selected';
        $bWasSelect = true;
    }
    $options .= '>['.$arItem["CODE"].'] '.$arItem["NAME"].'</option>';
}
return  $options;
}

}
?>