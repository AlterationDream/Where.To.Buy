<?
namespace Slytek\Csv;
class Import{
	static $settings;
	static $header;
	static $encoding;
	static $converted;
	static $items;
	static $props;
	static $sort;
	static $arSections=array();
	static function start($SITE_ID, $params=array()){
		error_reporting(E_ERROR);
		set_time_limit(0);
		self::init($SITE_ID, $params);
		self::checkEncoding();
		self::getHeader();
		self::getItems();
		
		self::getCsvRows();
		self::epilog();
	}
	function init($SITE_ID, $params=false){
		if(!$params['settings_path']){
			$params['settings_path'] = __DIR__.'/../../settings/csv_settings_'.$SITE_ID.'.dat';
		}
		self::$settings = unserialize(file_get_contents($params['settings_path']));
		self::$settings['update_catalog'] = \Bitrix\Main\Loader::includeModule('catalog');
		if(self::$settings['iblock']>0 && self::$settings['update_catalog']){
			$res = Bitrix\Catalog\CatalogIblockTable::getList(array(
				'filter'=>array('IBLOCK_ID'=>self::$settings['iblock'])
			));
			if(!$res->fetch()){
				self::$settings['update_catalog']=false;
			}
		}
		if($params['no_catalog']){
			self::$settings['update_catalog'] = false;
		}
		if(self::$settings['update_catalog']){
			if(!self::$settings['price']['type']){
				\Bitrix\Main\Loader::includeModule('catalog');
				$arGroup=\CCatalogGroup::GetBaseGroup();
				self::$settings['price']['type']=$arGroup['ID'];
			}
			if(!self::$settings['price']['currency']){
				\Bitrix\Main\Loader::includeModule('currency');
				self::$settings['price']['currency']=\CCurrency::GetBaseCurrency();
			}
		}
		if(!self::$settings['ID']){
			self::$settings['ID']='NAME';
		}
		if($params['callbacks']){
			self::$settings['callbacks'] = $params['callbacks'];
		}
		if($params['path']){
			self::$settings['csv_file'] = $params['path'];
		}
		if($params['image_dir']){
			self::$settings['csv_image_dir'] = $params['image_dir'].'/';
		}
		if($params['update_prices']){
			self::$settings['update_prices'] = $params['update_prices'];
		}
		if($params['total']){
			self::$settings['import']['import']['total'] = $params['total'];
		}
	}
	function epilog(){
		\Bitrix\Main\Loader::includeModule('iblock');
		\CIBlockSection::ReSort(self::$settings['iblock']);
	}
	function initPageParams($SITE_ID){
		self::init($SITE_ID);
		self::checkEncoding();
		self::getHeader();
		self::$settings['headers']=array_keys(self::$header);
		if(self::$settings['original_csv_file'])self::$settings['csv_file']=self::$settings['original_csv_file'];
		return self::$settings;
	}
	function progress($type){
		file_put_contents(__DIR__.'/../../settings/progress.txt', $type.'|'.self::$settings['import'][$type]['count'].'|'.self::$settings['import'][$type]['total']);
	}

	function checkEncoding(){
		self::progress('check');
		$csvFile = new \CCSVData();
		$csvFile->LoadFile($_SERVER["DOCUMENT_ROOT"].self::$settings['csv_file']);
		$csvFile->SetFieldsType('R');
		$csvFile->SetDelimiter(';');
		$path=self::$settings['csv_file'];
		if($arRow = $csvFile->Fetch()){
			foreach($arRow as $k=>$text){
				if(preg_match('/[a-zA-Z]/', $text))unset($arRow[$k]);
				else break;
			}
			if(mb_detect_encoding(implode(';',$arRow), array('utf-8', 'windows-1251'))=='Windows-1251'){
				if(stripos($path, '_utf8')===false){
					self::$encoding='Windows-1251';
					exec($c = "iconv -f Windows-1251 -t UTF-8 '{$_SERVER["DOCUMENT_ROOT"]}{$path}' > '{$_SERVER["DOCUMENT_ROOT"]}{$path}_utf8'");
					if(file_exists($_SERVER["DOCUMENT_ROOT"].$path.'_utf8')){
						self::$settings['original_csv_file']=$path;
						$path=$path.'_utf8';
						self::$converted=true;
					}
				}
			}
		}
		self::$settings['csv_file']=$path;
	}
	
	function getHeader(){
		self::progress('Get header');
		$csvFile = new \CCSVData();
		$csvFile->LoadFile($_SERVER["DOCUMENT_ROOT"].self::$settings['csv_file']);
		$csvFile->SetFieldsType('R');
		$csvFile->SetDelimiter(';');
		$header = $csvFile->Fetch();
		//$header = self::convertEncode($header);
		foreach($header as $k=>$name){
			if($name)self::$header[trim($name)]=$k;
		}
	}
	function continue($position){
		global $APPLICATION;
		?>
		<!DOCTYPE html>
		<html>
		<body>
			<a id="continue" href="<?=$APPLICATION->GetCurPageParam('time='.$current.'&current_position='.$position, array('current_position', 'time'))?>">continue</a>
			<script type="text/javascript">
				setTimeout(function(){
					document.getElementById('continue').click();
				}, 1000)
				
			</script>
		</body>
		</html>
		<?
		//LocalRedirect();
		die();
	}
	function getCsvRows(){
		$start=time();
		$limit=30;
		$csvFile = new \CCSVData();
		$csvFile->LoadFile($_SERVER["DOCUMENT_ROOT"].self::$settings['csv_file']);
		$csvFile->SetFieldsType('R');
		$csvFile->SetDelimiter(';');
		$csvFile->SetFirstHeader(true);
		if($_REQUEST['current_position']>0){
			$csvFile->SetFirstHeader(false);
			$csvFile->SetPos(intval($_REQUEST['current_position']));
		}else{
			self::$settings['import']['import']['count']=0;
		}
		while($arRow = $csvFile->Fetch()){
			self::processRow($arRow);
			self::$settings['import']['import']['count']++;
			self::progress('import', self::$settings['import']['import']['count'], self::$settings['import']['import']['total']);
			if(!defined('CRON_EXE')){
				$current=time();
				if(($current-$start)>=$limit){
					$position = $csvFile->GetPos();
					self::continue($position);
				}			
			}			
		}
	}
	function getPictures($arRow){
		if(!self::$settings['picture_name'] || !self::$settings['csv_image_dir'])return;
		$name=trim($arRow[self::$header[self::$settings['picture_name']]]);
		if(!$name)return;
		$path=$_SERVER['DOCUMENT_ROOT'].self::$settings['csv_image_dir'];
		if(!is_dir($path))return;
		$files = scandir($path);
		$arFiles=array();
		foreach($files as $file){
			if($file=='.' || $file=='..')continue;
			$fileAr=explode('.', $file);
			if(ToUpper($fileAr[0])==ToUpper($name)){
				$key = preg_replace('/\.[^.$]+$/', '', $file);
				if(stripos($key,'.')!==false){
					$key=preg_replace('/[A-Za-z0-9-_\.]*\.([^.$]+)$/', '$1', $key);
				}else{
					$key=-1;
				}
				$arFiles[$key]=\CFile::MakeFileArray($path.$file);
			}
		}

		ksort($arFiles);
		return $arFiles;
	}
	function getItems(){
		if(!self::$items){
			\Bitrix\Main\Loader::includeModule('iblock');
			$arFilter = Array("IBLOCK_ID"=>IntVal(self::$settings['iblock']));
			$res = \CIBlockElement::GetList(Array(), $arFilter, false, false, Array("ID", 'IBLOCK_ID', self::$settings['ID']));
			while($arFields = $res->GetNext()){
				$value = stripos(self::$settings['ID'], 'PROPERTY_')!==false?$arFields[self::$settings['ID'].'_VALUE']:$arFields[self::$settings['ID']];
				if(!$value)continue;
				$value=ToLower($value);
				self::$items[$value]=$arFields['ID'];
			}
		}
	}
	function getItem($key){
		static $items = array();
		if($id = $items[$key]){
			return $id;
		}else{
			\Bitrix\Main\Loader::includeModule('iblock');
			$arFilter = Array("IBLOCK_ID"=>IntVal(self::$settings['iblock']), self::$settings['ID']=>$key);
			$res = \CIBlockElement::GetList(Array(), $arFilter, false, array('nTopCount'=>1), Array("ID"));
			if($arFields = $res->GetNext()){
				self::$items[$key]=$arFields['ID'];
				return $id;
			}
		}
		return false;
	}
	function syncSections($SITE_ID){
		self::init($SITE_ID);
		self::getSections();
		foreach(self::$arSections as $name=>$section){
			self::getSection($name);
		}
	}
	function getSections(){
		if(!self::$arSections){
			\Bitrix\Main\Loader::includeModule('iblock');
			$dbres = \CIBlockSection::GetList(Array("SORT"=>"­­ASC"), Array("IBLOCK_ID" => self::$settings['iblock'], "ACTIVE" => 'Y', 'GLOBAL_ACTIVE'=>'Y'), false, array('ID', 'NAME', 'IBLOCK_SECTION_ID'));
			while($arSection = $dbres->GetNext()){
				self::$arSections[trim(ToLower($arSection['NAME']))]=$arSection;
			}
		}
		return self::$arSections;
	}

	function SetSectionTree($name, $get_sort = false){
		global $sections;
		
		include $_SERVER["DOCUMENT_ROOT"]."/include/classificator.php";

		foreach($sections as $child=>$parent){
			$sections[trim(ToLower($child))]=$parent;
		}
		if($get_sort){
			static $sortSections = array();
			if(!$sortSections){
				$i = 100;
				foreach($sections as $child=>$parent){
					$sortSections[$child]=$i;
					$i+=100;
				}
			}
			if($srt = $sortSections[trim(ToLower($name))]){
				return $srt;
			}
			return 500;
		}


		static $parentSections = array();
		\Bitrix\Main\Loader::includeModule('iblock');
		if(!$parentSections){
			$names = array_unique(array_values($sections));
			$sectionsSorts = array();
			foreach($names as $k=>$sname){
				$sectionsSorts[$sname]=($k+1)*100;
			}
			$dbres = \Bitrix\Iblock\SectionTable::getList(array(
				'filter'=> Array("IBLOCK_ID" => self::$settings['iblock'], "ACTIVE" => 'Y', 'NAME'=>$names),
				'select'=>array('ID', 'NAME')
			));
			while($arSection = $dbres->fetch()){
				$parentSections[trim(ToLower($arSection['NAME']))]=$arSection['ID'];
			}
			foreach ($names as $sname) {
				if(!$parentSections[trim(ToLower($sname))]){
					$bs = new \CIBlockSection;
					$arFields = Array(
						"ACTIVE" => 'Y',
						"IBLOCK_ID" => self::$settings['iblock'],
						"NAME" => $sname,
						'IBLOCK_SECTION_ID'=>false,
						'CODE'=> \Cutil::translit($sname,"ru",array("replace_space"=>"-","replace_other"=>"-")),
						"SORT" => $sectionsSorts[$sname]?$sectionsSorts[$sname]:500,
					);

					if($ID = $bs->Add($arFields))
					{
						$parentSections[trim(ToLower($sname))]=$ID;
					}
				}
			}
		}
		return $parentSections[trim(ToLower($sections[trim(ToLower($name))]))];
	}

	function getSection($name){
		self::getSections();
		\Bitrix\Main\Loader::includeModule('iblock');
		//if(!$arProps['BRAND'] || !$arItem['NAME'])return;
		//$ar=explode(trim($arProps['BRAND']), $arItem['NAME']);
		$name=trim($name);
		$parent_sid = self::SetSectionTree($name);
		if(self::$arSections[ToLower($name)]){

			$sid = self::$arSections[ToLower($name)]['ID'];
			if(!self::$arSections[ToLower($name)]['IBLOCK_SECTION_ID'] && $parent_sid>0){
				$bs = new \CIBlockSection;
				$bs->Update($sid, array('IBLOCK_SECTION_ID'=>$parent_sid));		
			}
			return $sid;
		}else{			
			$bs = new \CIBlockSection;
			$srt = self::SetSectionTree($name, true);
			$arFields = Array(
				"ACTIVE" => 'Y',
				"IBLOCK_ID" => self::$settings['iblock'],
				"NAME" => $name,
				'IBLOCK_SECTION_ID'=>$parent_sid,
				'CODE'=> \Cutil::translit($name,"ru",array("replace_space"=>"-","replace_other"=>"-")),
				"SORT" => $srt>0?$srt:500,
			);

			if($ID = $bs->Add($arFields))
			{
				self::$arSections[ToLower($arFields['NAME'])]=array('ID'=>$ID, 'IBLOCK_SECTION_ID'=>$parent_sid);
				return $ID;
			}else{
				echo $bs->LAST_ERROR."\n";
			}
		}
	}
	function getKey($code, $prop=true){
		$result=-1;
		$name = self::$settings[($prop?'props':'fields')][$code];
		if($name && self::$header[$name]!==false)$result = self::$header[$name];
		return $result;
	}
	function getProps($code=false, $name=false){
		\Bitrix\Main\Loader::includeModule('iblock');
		if(!self::$props){
			$props = array_keys(array_filter(self::$settings['props']));
			if(!$props)return;
			$res = \Bitrix\Iblock\PropertyTable::getList(array(
				'filter'=>array(
					'IBLOCK_ID'=>self::$settings['iblock'],
					'CODE'=>$props,
				),
				'select'=>array('ID', 'CODE', 'PROPERTY_TYPE', 'LINK_IBLOCK_ID', 'IBLOCK_ID', 'USER_TYPE', 'MULTIPLE')
			));
			while($item = $res->fetch()){
				self::$props[$item['CODE']] = $item;
				switch ($item['PROPERTY_TYPE']) {
					case 'E':
					if($item['LINK_IBLOCK_ID']>0){
						$resE = \Bitrix\Iblock\ElementTable::getList(array(
							'filter'=>Array("IBLOCK_ID"=>$item['LINK_IBLOCK_ID']),
							'select'=>Array("ID", "NAME")
						));
						while($element = $resE->fetch())
						{
							self::$props[$item['CODE']]['values'][trim(ToLower($element['NAME']))]=$element['ID'];
						}
					}

					break;
					case 'L':
					$resL = \Bitrix\Iblock\PropertyEnumerationTable::getList(array(
						'filter'=>Array('PROPERTY_ID'=>$item['ID']),
						'select'=>Array("ID", "VALUE")
					));
					while($enum = $resL->fetch())
					{
						self::$props[$item['CODE']]['values'][trim(ToLower($enum['VALUE']))]=$enum['ID'];
					}
					break;
				}
				if($item['USER_TYPE']=='SASDCheckbox'){
					self::$props[$item['CODE']]['values']['N']='N';
					self::$props[$item['CODE']]['values']['Y']='Y';
					self::$props[$item['CODE']]['values'][0]='N';
					self::$props[$item['CODE']]['values'][1]='Y';
					self::$props[$item['CODE']]['values']['true']='Y';
					self::$props[$item['CODE']]['values']['false']='N';
				}
			}
		}

		if($code && $name){
			if($item['PROPERTY_TYPE']=='E' || $item['PROPERTY_TYPE']=='L'){
				$name = explode(',', $name);
				foreach($name as $k=>$n){
					if(!$n)unset($name[$k]);
					else{
						$name[$k] = trim($n);
					}
				}
			}
			else{
				$name=array($name);
			}

			$return = array();
			foreach($name as $val){
				if($id = self::$props[$code]['values'][trim(ToLower($val))]){
					$return[]=$id;
				}
				else{
					$item = self::$props[$code];
					if($item['USER_TYPE']=='SASDCheckbox'){
						$return[]=self::$props[$item['CODE']]['values'][$val];
					}
					elseif($item['USER_TYPE']=='HTML'){
						$return[]=array("TEXT"=>$val, "TYPE"=>"html");
					}
					else{
						switch ($item['PROPERTY_TYPE']) {
							case 'E':
							if($item['LINK_IBLOCK_ID']>0){
								$res = \Bitrix\Iblock\ElementTable::add(array(
									"MODIFIED_BY"    => 1, 
									"IBLOCK_SECTION_ID" => false,         
									"IBLOCK_ID"      => $item['LINK_IBLOCK_ID'],
									"NAME"           => $val,
									"ACTIVE"         => "Y",          
								));
								if ($res->isSuccess())
								{
									self::$props[$item['CODE']]['values'][trim(ToLower($val))] = $res->getId();
								}
							}

							break;
							case 'L':

							$res = new \CIBlockPropertyEnum;
							if($id = $res->Add(Array('PROPERTY_ID'=>$item['ID'], 'VALUE'=>$val))){
								self::$props[$item['CODE']]['values'][trim(ToLower($val))] = $id;
								$return[]=$id;
							}
							break;
						}
					}
				}
			}
		}
		if(self::$settings['callbacks']['prop']){
			$return = call_user_func(self::$settings['callbacks']['prop'], $return);
		}
		if($item['MULTIPLE']!='Y'){
			$return = current($return);
		}
		if(!$return)$return = $name;
		return $return;
	}
	function number($value){
		return str_ireplace(array(',', ' '), array('.',''), $value);
	}
	
	function processRow($arRow){
		$arProps=array();
		if(self::$settings['props']){
			foreach(self::$settings['props'] as $prop=>$name){
				$value = $arRow[self::$header[$name]];
				$arProps[$prop]=self::getProps($prop, $value);
			}
		}
			// $arFilter = Array("IBLOCK_ID"=>IntVal(self::$settings['iblock']), self::$settings['ID'] => $arRow[self::getKey(str_ireplace('PROPERTY_', '', self::$settings['ID']), stripos(self::$settings['ID'], 'PROPERTY_')!==false)]);
		// $res = CIBlockElement::GetList(Array(), $arFilter, false, false, Array("ID"));
		// if($arFields = $res->GetNext()){

		// }


		$key_id = ToLower($arRow[self::getKey(str_ireplace('PROPERTY_', '', self::$settings['ID']), stripos(self::$settings['ID'], 'PROPERTY_')!==false)]);
		// if($id = self::getItem($key_id)){
		// 	$arFields=array('ID'=>$id);
		// }

		if(self::$items[$key_id]){
			$arFields=array('ID'=>self::$items[$key_id]);
		}

		//if($arProps['ARTNUMBER']=='254013605')print_r($pictures);
		\Bitrix\Main\Loader::includeModule('iblock');
		$add=false;
		self::$sort+=100;
		if($arFields)
		{
			$PRODUCT_ID =$arFields['ID'];
			if(defined('UPDATE_SECTION')){
				$el = new \CIBlockElement;
				$el->Update($PRODUCT_ID, array('IBLOCK_SECTION_ID'=>self::getSection($arRow[self::getKey('NAME', false)])));
			}
			else{
				$el = new \CIBlockElement;
				$el->Update($PRODUCT_ID, array('SORT'=>self::$sort));
			}

			if($arItem[self::$settings['picture_field']]){
				// $el = new CIBlockElement;
				// $el->Update($PRODUCT_ID, $arItem);
				// echo $el->LAST_ERROR."\n";
			}
		}else{
			$nameSection=$arRow[self::getKey('SECTION_NAME', false)];
			$arItem=array(
				'NAME'=>$arRow[self::getKey('NAME', false)],
				'XML_ID'=>$arRow[self::getKey('XML_ID', false)],
				'IBLOCK_ID'=>self::$settings['iblock'],
				'DETAIL_TEXT'=>$arRow[self::getKey('DETAIL_TEXT', false)],
				'DETAIL_TEXT_TYPE'=>'html',
				'SORT'=>self::$sort,
				'ACTIVE'=>'Y'
			);
			if(!$arItem['NAME']){
				$name=array();
				if($nameSection)$name[]=$nameSection;
				if($arProps['BRAND'])$name[]=$arProps['BRAND'];
				if($arProps['ARTNUMBER'])$name[]=$arProps['ARTNUMBER'];
				$arItem['NAME']=implode(' ', $name);
			}
			if(!$arItem['NAME'])return;
			$arItem['CODE']=\Cutil::translit($arItem['NAME'],"ru",array("replace_space"=>"-","replace_other"=>"-"));

			$pictures=self::getPictures($arRow);

			if(self::$settings['picture_field'] && $pictures && !$arFields){
				$arItem[self::$settings['picture_field']]=current($pictures);
				array_shift($pictures);
			}
			if($nameSection){
				$arItem['IBLOCK_SECTION_ID']=self::getSection($nameSection);
			}

			if(self::$settings['callbacks']['before_add']){
				$arItem = call_user_func(self::$settings['callbacks']['before_add'], $arItem, $arProps);
			}
			$el = new \CIBlockElement;
			$PRODUCT_ID = $el->Add($arItem);
			$add=true;
			self::$items[$key_id]=$PRODUCT_ID;
		}
		if($PRODUCT_ID>0){
			if(($add)){
				if($arFields){
					$pictures=self::getPictures($arRow);
					array_shift($pictures);
				}
				if(self::$settings['picture_prop'] && $pictures){
					$k=0;
					foreach($pictures as $arFile){
						$arProps[self::$settings['picture_prop']]['n'.$k]=$arFile;
						$k++;
					}
				}	
			}
			if($arProps){
				\CIBlockElement::SetPropertyValuesEx($PRODUCT_ID, self::$settings['iblock'], $arProps);
			}
			\CIBlockElement::UpdateSearch($PRODUCT_ID, true);
			
			if(self::$settings['update_catalog']){
				$arProduct=array(
					"ID" => $PRODUCT_ID, 
					"VAT_ID" => 1,  
					"VAT_INCLUDED" => "Y", 
					"PURCHASING_PRICE" => self::number($arRow[self::getKey('stock_price', false)]), 
					"PURCHASING_CURRENCY" => self::$settings['stock_price']['currency'], 
					"WEIGHT" => self::number($arRow[self::getKey('weight', false)]), 
					"WIDTH" => self::number($arRow[self::getKey('width', false)]), 
					"LENGTH" => self::number($arRow[self::getKey('length', false)]), 
					"HEIGHT" => self::number($arRow[self::getKey('height', false)]), 
					'QUANTITY'=> self::number($arRow[self::getKey('available', false)]),
				);
				$diametr = self::number($arRow[self::getKey('diametr', false)]);
				if(!$arProduct['WIDTH'] && !$arProduct['LENGTH']){
					$arProduct['WIDTH'] = $diametr;
					$arProduct['LENGTH'] = $arProduct['WIDTH'];
				}
				elseif((!$arProduct['WIDTH'] || !$arProduct['LENGTH']) && $diametr){
					if(!$arProduct['WIDTH'])$arProduct['WIDTH']=$diametr;
					elseif(!$arProduct['LENGTH'])$arProduct['LENGTH']=$diametr;
				}
				elseif((!$arProduct['WIDTH'] || !$arProduct['LENGTH']) && !$diametr){
					if(!$arProduct['WIDTH'])$arProduct['WIDTH']=$arProduct['LENGTH'];
					elseif(!$arProduct['LENGTH'])$arProduct['LENGTH']=$arProduct['WIDTH'];
				}
				foreach($arProduct as $key=>$val){
					if(!$val)unset($arProduct[$key]);
				}

				\Bitrix\Main\Loader::includeModule('catalog');
				$res = \Bitrix\Catalog\Model\Product::getList(
					array(
						'filter'=> array("ID" => $PRODUCT_ID),
						'select'=>array('ID', 'QUANTITY', 'PURCHASING_PRICE')
					)
				);
				if ($item = $res->fetch())
				{
					unset($arProduct['ID']);
					$res = \Bitrix\Catalog\Model\Product::update($item['ID'], $arProduct);
				}
				else{
					$res = \Bitrix\Catalog\Model\Product::add($arProduct);
				}
				$arPrice=array(
					"PRODUCT_ID" => $PRODUCT_ID,
					"CATALOG_GROUP_ID" => self::$settings['price']['type'],
					"CURRENCY" => self::$settings['price']['currency']
				);		
				if($price = self::number($arRow[self::getKey('price', false)])){
					$arPrice['PRICE'] = $price;
					$db_res = \CPrice::GetList(array(),array("PRODUCT_ID" => $PRODUCT_ID,"CATALOG_GROUP_ID" => self::$settings['price']['type']));
					if ($ar_res = $db_res->Fetch())
					{
						if($arPrice['PRICE']!=$ar_res['PRICE'])
							\CPrice::Update($ar_res['ID'], $arPrice);
					}else{
						\CPrice::Add($arPrice);
					}
				}
			}
		}else{
			echo $arItem['NAME'].' '.$el->LAST_ERROR."\n";
		}
	}

}

?>