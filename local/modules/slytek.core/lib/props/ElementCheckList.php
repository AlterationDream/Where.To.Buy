<?
namespace Slytek\Props;
use Bitrix\Main\Localization\Loc,
Bitrix\Iblock;

Loc::loadMessages($_SERVER['DOCUMENT_ROOT'].'/bitrix/modules/iblock/classes/general/prop_element_list.php');

class ElementCheckList 
{
	const USER_TYPE = 'EListChecksSlytek';

	public static function GetUserTypeDescription()
	{
		return array(
			"PROPERTY_TYPE" => Iblock\PropertyTable::TYPE_ELEMENT,
			"USER_TYPE" => self::USER_TYPE,
			"DESCRIPTION" => Loc::getMessage("IBLOCK_PROP_ELIST_DESC").' '.'Checkboxes Slytek',
			"GetPropertyFieldHtml" => array(__CLASS__, "GetPropertyFieldHtml"),
			"GetPropertyFieldHtmlMulty" => array(__CLASS__, "GetPropertyFieldHtml"),
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
		
	}
	public static function GetSettingsHTML($arProperty, $strHTMLControlName, &$arPropertyFields)
	{
		
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
		$settings = self::PrepareSettings($arProperty);
		$bWasSelect = false;
		$k=0;
		foreach($values as $arValue){
			$arValues[]=$arValue['VALUE'];
		}
		foreach(\CIBlockPropertyElementList::GetElements($arProperty["LINK_IBLOCK_ID"]) as $arItem)
		{
			if($arProperty['MULTIPLE']=='Y')$name=$arProperty['FIELD_NAME'].'[]';
			else $name=$arProperty['FIELD_NAME'];
			$options .= '<div><label><input type="'.($arProperty['MULTIPLE']=='Y'?'checkbox':'radio').'" name="'.$name.'" value="'.$arItem["ID"].'"';
			if(in_array($arItem["~ID"], $arValues))
			{
				$options .= ' checked';
				$bWasSelect = true;
			}
			$k++;
			$options .= '>'.$arItem["NAME"].'</label></div>';
		}

		return  $options;
	}

	public static function GetPropertyFieldHtml($arProperty, $value, $strHTMLControlName)
	{
		$settings = self::PrepareSettings($arProperty);
		$bWasSelect = false;
		$arProperty['FIELD_NAME']='PROP['.$arProperty['ID'].']';
		$options = self::GetOptionsHtml($arProperty, $value, $bWasSelect);

		$html= $options;
		return  $html;
	}
}
?>