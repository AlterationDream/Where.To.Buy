<?
/**
 * Bitrix vars
 * @global CMain $APPLICATION
 * @global CAdminMenu $this
 */

if ($USER->CanDoOperation('cache_control'))
{
      if(\Bitrix\Main\ModuleManager::isModuleInstalled('slytek.core'))
      {
            IncludeModuleLangFile(__FILE__);
           
            $aMenu = array(
                
                 array(
                  "parent_menu" => "global_menu_content", 
                  "section" => "Дополнительный контент сайта",
                  "sort"        => 1,                   
                  "url"         => "slytek_content.php?lang=".LANG,  
                  "text"        => 'Дополнительный контент сайта',      
                  "title"       => 'Дополнительный контент сайта', 
                  "icon"        => "form_menu_icon", 
                  "page_icon"   => "form_page_icon", 
                  "items_id"    => "menu_settings",  
                  "items"       => array()   
            ),
           


           );
return $aMenu;;
}
}
return false;
