<?
namespace Slytek;
class Main {
	static $seo_items = array();
	function OnProlog(){
		if(defined('ADMIN_SECTION') || defined('NO_KEEP_STATISTIC'))return;
		self::mess(LANGUAGE_ID);
		$GLOBALS['APPLICATION']->IncludeComponent("slytek:prolog","",Array("COMPOSITE_FRAME_MODE" => "Y","COMPOSITE_FRAME_TYPE" => "STATIC"), false, array('HIDE_ICONS'=>'Y'));	
	}
	function OnEpilog(){
		if(defined('ADMIN_SECTION') || defined('NO_KEEP_STATISTIC'))return;
		self::setSeoParams();
		self::setSeoLangParams();
	}
	static function mess($lid){
		global $MESS;
		$pre_name = 'SLYTEK_';
		$lid=ToLower($lid);
		$path=$_SERVER['DOCUMENT_ROOT'].'/local/php_interface/slytek/lang';
		$MESSAGES[$lid]=json_decode(file_get_contents($path.'/'.$lid.'.dat'), true);
		if($lid=='en'){
			$MESSAGES['ru']=json_decode(file_get_contents($path.'/ru.dat'), true);
			foreach($MESSAGES['ru'] as $name=>$message){
				if($MESSAGES[$lid][$name]){
					$MESS[$pre_name.$name]=$MESSAGES[$lid][$name];
				}else{
					$MESS[$pre_name.$name]=$MESSAGES['ru'][$name];
				}
			}
		}else{
			foreach($MESSAGES['ru'] as $name=>$message){
				$MESS[$pre_name.$name]=$message;
			}
		}
	}
	function seo($type, $item){
		self::$seo_items[$type][]=$item;
	}
	function setSeoParams(){
		global $APPLICATION;
		if(self::$seo_items['bread']){
			foreach(self::$seo_items['bread'] as $item){
				$APPLICATION->AddChainItem($item['NAME'], $item['URL']);
			}
		}
	}
	function setSeoLangParams(){
		if(LANGUAGE_ID=='ru')return;
		if(!defined('NO_SET_META')){
			if(!$lang)$lang=LANGUAGE_ID;
			global $APPLICATION;
			$title=$APPLICATION->GetPageProperty('title_'.$lang);
			$browser_title=$APPLICATION->GetPageProperty('browser_title_'.$lang);
			$description=$APPLICATION->GetPageProperty('description_'.$lang);
			
			if(!$title)$title=$APPLICATION->GetProperty('title_'.$lang);
			if(!$browser_title)$browser_title=$APPLICATION->GetProperty('browser_title_'.$lang);
			if(!$description)$description=$APPLICATION->GetProperty('description_'.$lang);
			
			if(!$title)$title=$APPLICATION->GetPageProperty('title_en');
			if(!$browser_title)$browser_title=$APPLICATION->GetPageProperty('browser_title_en');
			if(!$description)$description=$APPLICATION->GetPageProperty('description_en');
			
			if(!$title)$title=$APPLICATION->GetProperty('title_en');
			if(!$browser_title)$browser_title=$APPLICATION->GetProperty('browser_title_en');
			if(!$description)$description=$APPLICATION->GetProperty('description_en');
			
			if(!$browser_title)$browser_title=$title;

			if($title){
				$APPLICATION->SetTitle($title);
			}
			if($browser_title){
				$APPLICATION->SetPageProperty('title', $browser_title);
			}
			if($description){
				$APPLICATION->SetPageProperty('description', $description);
			}
		}
	}
	public function showSeoData($fields){
		?>
		<div itemscope="" itemtype="http://schema.org/Store" style="display: none;">
			<meta itemprop="name" content="<?=$fields['name']?>">
			<meta itemprop="description" content="<?=$fields['description']?>">
			<meta itemprop="telephone" content="<?=$fields['phone']?>">
			<meta itemprop="email" content="<?=$fields['email']?>">
			<a itemprop="url" href="<?=$_SERVER['HTTP_HOST']?>"></a>
			<?if($fields['schedule']):?><meta itemprop="openingHours" content="<?=$fields['schedule']?>"><?endif?>
			<?foreach($fields['address'] as $address):?>
			<div itemprop="address" itemscope="" itemtype="http://schema.org/PostalAddress">
				<meta itemprop="postalCode" content="<?=$address['postalCode']?>">
				<meta itemprop="addressCountry" content="<?=$address['addressCountry']?>">
				<meta itemprop="addressLocality" content="<?=$address['addressLocality']?>">
				<meta itemprop="streetAddress" content="<?=$address['streetAddress']?>">
			</div>
			<?endforeach?>
			<?if($fields['geo']):?>
			<div itemprop="geo" itemscope="" itemtype="http://schema.org/GeoCoordinates">
				<meta itemprop="latitude" content="<?=$fields['geo']['lat']?>">
				<meta itemprop="longitude" content="<?=$fields['geo']['lon']?>">
			</div>
			<?endif?>
			<?if($fields['img']): $sizes=getimagesize($_SERVER['DOCUMENT_ROOT'].$fields['img'])?>
			<div itemscope="" itemtype="http://schema.org/ImageObject" itemprop="logo">
				<img src="<?=$fields['img']?>" itemprop="contentUrl" alt="JUST">
				<meta itemprop="name" content="<?=$fields['name']?>">
				<meta itemprop="caption" content="<?=$fields['name']?>">
				<meta itemprop="description" content="<?=$fields['name']?>">
				<meta itemprop="height" content="<?=$sizes[1]?>px">
				<meta itemprop="width" content="<?=$sizes[0]?>px">
				<div itemscope="" itemtype="http://schema.org/ImageObject" itemprop="thumbnail">
					<img src="<?=$fields['img']?>" itemprop="contentUrl" alt="<?=$fields['name']?>">
				</div>
			</div>
			<img src="<?=$fields['img']?>" itemprop="image" alt="<?=$fields['name']?>">
			<?endif?>
		</div>
		<?
	}
}
?>