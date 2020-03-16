<?
IncludeModuleLangFile(__FILE__);

Class slytek_core extends CModule {
    var $MODULE_ID = 'slytek.core';
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $MODULE_CSS;

    function __construct() {
        include(dirname(__FILE__).'/version.php');
        $this->MODULE_VERSION = $arModuleVersion['VERSION'];
        $this->MODULE_VERSION_DATE = $arModuleVersion['VERSION_DATE'];
        $this->PARTNER_NAME = 'Slytek';
        $this->PARTNER_URI = 'slytek.ru';
        $this->MODULE_NAME = 'Ядро вашего сайта';
        $this->MODULE_DESCRIPTION = 'Ядро вашего сайта';
        $path = substr(__DIR__, 0, strlen(__DIR__)-strlen('/install'));
        $this->MODULE_PATH = $path;
    }
    
    function InstallDB() {
        global $APPLICATION, $DB, $DBType;
        return true;
    }
    
    function UnInstallDB() {
        global $APPLICATION, $DB, $DBType;
         return true;
    }
    
    function InstallFiles() {
        CopyDirFiles( $this->MODULE_PATH."/install/admin/", $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin', true, true);
        CopyDirFiles($this->MODULE_PATH."/install/components/", $_SERVER['DOCUMENT_ROOT'].'/local/components', true, true);
        return true;
    }
    
    function UnInstallFiles() {
       DeleteDirFiles($this->MODULE_PATH."/install/admin/", $_SERVER['DOCUMENT_ROOT'].'/bitrix/admin');
       DeleteDirFiles($this->MODULE_PATH."/install/components/", $_SERVER['DOCUMENT_ROOT'].'/local/components');
       return true;
    }

    function DoInstall() {
        $this->InstallDB();
        $this->InstallFiles();
        RegisterModule($this->MODULE_ID);
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, '\Slytek\Buffer', 'ProtectEmail');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID, '\Slytek\Settings', 'OnBeforeProlog');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('main', 'OnProlog', $this->MODULE_ID, '\Slytek\Main', 'OnProlog');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('main', 'OnEpilog', $this->MODULE_ID, '\Slytek\Main', 'OnEpilog');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('main', 'OnUserTypeBuildList', $this->MODULE_ID, '\Slytek\Props\UserPropertyList', 'GetUserTypeDescription');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, '\Slytek\Props\ElementList', 'GetUserTypeDescription');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, '\Slytek\Props\ElementCheckList', 'GetUserTypeDescription');
        Bitrix\Main\EventManager::getInstance()->registerEventHandler('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, '\Slytek\Props\PropertyList', 'GetUserTypeDescription');
        return true;
    }

    function DoUninstall() {
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('main', 'OnEndBufferContent', $this->MODULE_ID, '\Slytek\Buffer', 'ProtectEmail');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('main', 'OnBeforeProlog', $this->MODULE_ID, '\Slytek\Settings', 'OnBeforeProlog');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('main', 'OnProlog', $this->MODULE_ID, '\Slytek\Main', 'OnProlog');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('main', 'OnEpilog', $this->MODULE_ID, '\Slytek\Main', 'OnEpilog');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('main', 'OnUserTypeBuildList', $this->MODULE_ID, '\Slytek\Props\UserPropertyList', 'GetUserTypeDescription');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, '\Slytek\Props\ElementList', 'GetUserTypeDescription');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, '\Slytek\Props\ElementCheckList', 'GetUserTypeDescription');
        Bitrix\Main\EventManager::getInstance()->unRegisterEventHandler('iblock', 'OnIBlockPropertyBuildList', $this->MODULE_ID, '\Slytek\Props\PropertyList', 'GetUserTypeDescription');
        UnRegisterModule($this->MODULE_ID);
        $this->UnInstallFiles();
        $this->UnInstallDB();
        return true;
    }
}
?>