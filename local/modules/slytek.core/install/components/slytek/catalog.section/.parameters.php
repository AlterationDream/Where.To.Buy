<?
if (!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true) die();
/** @var array $arCurrentValues */
/** @global CUserTypeManager $USER_FIELD_MANAGER */


global $arComponentParameters;
$componentPath='/bitrix/components/bitrix/catalog.section/';
$componentName='bitrix:catalog.section';
CBitrixComponent::includeComponentClass($componentName);
include $_SERVER['DOCUMENT_ROOT'].'/bitrix/components/bitrix/catalog.section/.parameters.php';
$arComponentParameters['PARAMETERS']["COMPONENT_TEMPLATE"] = array(
    "PARENT" => "DATA_SOURCE",
    "NAME" => 'Шаблон компонента',
    "TYPE" => "STRING",
    "DEFAULT" => '.default',
    );
$arComponentParameters['PARAMETERS']["TEMPLATE_THEME"] = array(
    "PARENT" => "DATA_SOURCE",
    "NAME" => 'Тема компонента',
    "TYPE" => "STRING",
    "DEFAULT" => '',
    );

$arComponentParameters['PARAMETERS']['BLOCK_TITLE'] = array(
    'PARENT' => 'DATA_SOURCE',
    'NAME' => 'Заголовок блока',
    'TYPE' => 'STRING',
    'DEFAULT' => ''
    );
$arComponentParameters['PARAMETERS']['NONOFOUND'] = array(
    'PARENT' => 'DATA_SOURCE',
    'NAME' => 'Не показывать сообщение Ничего не найдено',
    'TYPE' => 'CHECKBOX',
    'DEFAULT' => 'N'
    );
$arComponentParameters['PARAMETERS']["FILTER_PROPERTY"] = array(
    "PARENT" => "DATA_SOURCE",
    "NAME" => 'Фильтровать по свойству',
    "TYPE" => "LIST",
    "VALUES" => $arProperty,
    "ADDITIONAL_VALUES" => "Y"
);
