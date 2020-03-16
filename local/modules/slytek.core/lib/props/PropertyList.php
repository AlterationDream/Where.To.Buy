<?
namespace Slytek\Props;
use Bitrix\Main\Localization\Loc,
Bitrix\Iblock;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/prop_element_list.php');
class PropertyList 
{
	const USER_TYPE = 'PListSlytek';

	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => Iblock\PropertyTable::TYPE_ELEMENT,
			"USER_TYPE" => self::USER_TYPE,
			"DESCRIPTION" => 'Привязка к свойству инфбоблока '.'Slytek',
			"GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
			//"GetPropertyFieldHtmlMulty" => array(__CLASS__, "GetPropertyFieldHtmlMulty"),
			//"GetPublicEditHTML" => array(__CLASS__, "GetPropertyFieldHtml"),
			//"GetPublicEditHTMLMulty" => array(__CLASS__, "GetPropertyFieldHtmlMulty"),
			//"GetPublicViewHTML" => array(__CLASS__,  "GetPublicViewHTML"),
			//"GetAdminFilterHTML" => array(__CLASS__, "GetAdminFilterHTML"),
			"PrepareSettings" =>array(__CLASS__, "PrepareSettings"),
			"GetSettingsHTML" =>array(__CLASS__, "GetSettingsHTML"),
		//	"GetExtendedValue" => array(__CLASS__,  "GetExtendedValue"),
			);
	}
	public static function PrepareSettings($arProperty)
	{
		$size = 0;
		if(is_array($arProperty["USER_TYPE_SETTINGS"]))
			$size = intval($arProperty["USER_TYPE_SETTINGS"]["size"]);
		if($size <= 0)
			$size = 1;

		$width = 0;
		if(is_array($arProperty["USER_TYPE_SETTINGS"]))
			$width = intval($arProperty["USER_TYPE_SETTINGS"]["width"]);
		if($width <= 0)
			$width = 0;

		if(is_array($arProperty["USER_TYPE_SETTINGS"]) && $arProperty["USER_TYPE_SETTINGS"]["group"] === "Y")
			$group = "Y";
		else
			$group = "N";


		return array(
			"size" =>  $size,
			"width" => $width,
			"group" => $group
			);
	}
	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		$settings = self::PrepareSettings($arProperty);

		$arPropertyFields = array(
			"HIDE" => array("ROW_COUNT", "COL_COUNT", "MULTIPLE_CNT"),
			);

		return '
		<tr valign="top">
			<td>'.Loc::getMessage("IBLOCK_PROP_ELEMENT_LIST_SETTING_SIZE").':</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[size]" value="'.$settings["size"].'"></td>
		</tr>
		<tr valign="top">
			<td>'.Loc::getMessage("IBLOCK_PROP_ELEMENT_LIST_SETTING_WIDTH").':</td>
			<td><input type="text" size="5" name="'.$strHTMLControlName["NAME"].'[width]" value="'.$settings["width"].'">px</td>
		</tr>
		
		
		';
	}

	//PARAMETERS:
	//$arProperty - b_iblock_property.*
	//$value - array("VALUE","DESCRIPTION") -- here comes HTML form value
	//strHTMLControlName - array("VALUE","DESCRIPTION")
	//return:
	//safe html
	public static function GetOptionsHtml($arProperty, $values, &$bWasSelect)
	{
		$options = "";
		$bWasSelect = false;
		$rsProperty = \CIBlockProperty::GetList(array("SORT"=>'ASC'),array('IBLOCK_ID'=>$arProperty["LINK_IBLOCK_ID"]));

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

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$settings = self::PrepareSettings($arProperty);
		if($settings["size"] > 1)
			$size = ' size="'.$settings["size"].'"';
		else
			$size = '';

		if($settings["width"] > 0)
			$width = ' style="width:'.$settings["width"].'px"';
		else
			$width = '';

		$bWasSelect = false;
		$options = self::GetOptionsHtml($arProperty, array($value["VALUE"]), $bWasSelect);

		$html = '<select name="'.$strHTMLControlName["VALUE"].'"'.$size.$width.'>';
		if($arProperty["IS_REQUIRED"] != "Y")
			$html .= '<option value=""'.(!$bWasSelect? ' selected': '').'>'.Loc::getMessage("IBLOCK_PROP_ELEMENT_LIST_NO_VALUE").'</option>';
		$html .= $options;
		$html .= '</select>';
		return  $html;
	}
}
?>