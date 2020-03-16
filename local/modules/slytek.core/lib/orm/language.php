<?php
/**
 * Bitrix Framework
 * @package bitrix
 * @subpackage main
 * @copyright 2001-2014 Bitrix
 */
namespace Bitrix\Main;

use Bitrix\Main\Entity;

class LanguageTable extends Entity\DataManager
{
	public static function getTableName()
	{
		return 'b_language';
	}

	public static function getMap()
	{
		return array(
			'LID' => array(
				'data_type' => 'string',
				'primary' => true,
				'autocomplete' => true,
			),
			'SORT' => array(
				'data_type' => 'integer'
			),
			'DEF' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'ACTIVE' => array(
				'data_type' => 'boolean',
				'values' => array('N', 'Y')
			),
			'NAME' => array(
				'data_type' => 'string'
			),
			
			'FORMAT_DATE' => array(
				'data_type' => 'string'
			),
			'FORMAT_DATETIME' => array(
				'data_type' => 'string'
			),
			'FORMAT_NAME' => array(
				'data_type' => 'text'
			),
			'WEEK_START' => array(
				'data_type' => 'integer'
			),
			'CHARSET' => array(
				'data_type' => 'text'
			),
			'DIRECTION' => array(
				'data_type' => 'string'
			),
			'CULTURE_ID' => array(
				'data_type' => 'integer'
			),		
		);
	}
}
