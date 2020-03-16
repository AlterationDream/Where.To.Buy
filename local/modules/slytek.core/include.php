<?php
\Bitrix\Main\Loader::registerAutoLoadClasses(
	'slytek.core',
	array(
		'Slytek\Storage' => 'lib/storage.php',
		'Slytek\Section' => 'lib/orm/section.php',
		'Slytek\Element' => 'lib/orm/element.php',
		'Bitrix\Main\LanguageTable' => 'lib/orm/language.php',
		'Bitrix\Iblock\ElementProperyTable' => 'lib/orm/elementProperty.php',
		'Slytek\Props\ElementList' => 'lib/props/ElementList.php',
		'Slytek\Props\ElementCheckList' => 'lib/props/ElementCheckList.php',
		
		'Slytek\Props\PropertyList' => 'lib/props/PropertyList.php',
		'Slytek\Props\UserPropertyList' => 'lib/props/UserPropertyList.php',

		'Slytek\Main' => 'lib/main.php',
		'Slytek\Handler' => 'lib/handler.php',
		'Slytek\Text' => 'lib/text.php',
		'Slytek\HL' => 'lib/hl.php',
		'Slytek\Media' => 'lib/media.php',
		'Slytek\Catalog' => 'lib/catalog.php',
		'Slytek\CatalogSet' => 'lib/catalogset.php',
		'Slytek\Discount' => 'lib/discount.php',
		'Slytek\Settings' => 'lib/settings.php',
		'Slytek\Buffer' => 'lib/buffer.php',
		'Slytek\Avito' => 'lib/avito.php',
		
		'Slytek\Csv\Import' => 'lib/csv/import.php',
		//'Slytek\Partnerprices\PricesTable' => 'lib/Partnerprices/partners.php',
	)
);
?>