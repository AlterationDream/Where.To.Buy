<?
namespace Slytek;
class Media {
	public static $nophoto= '';
	public static $arParams= '';
	static function init($arParams=false){
		static $nophoto;
		static $watermark;
		//print_r(self::$arParams['watermark']);
		if(!$nophoto){
			$nophoto = \Bitrix\Main\Config\Option::get('slytek.core', 'nophoto', '', SITE_ID);
		}
		self::$arParams = $arParams;
		self::$nophoto =$nophoto;
		if(!$watermark){
			$path=$_SERVER['DOCUMENT_ROOT'].'/local/modules/slytek.core/settings/watermark.dat';
			$watermark = json_decode(file_get_contents($path), true);
			// if($watermark['file']){
			// 	$watermark['file']=$_SERVER["DOCUMENT_ROOT"].$watermark['file'];
			// }
			// if($watermark['font']){
			// 	$watermark['font']=$_SERVER["DOCUMENT_ROOT"].$watermark['font'];
			// }
		}
		self::$arParams['watermark']=$watermark;
		return self::$nophoto;
	} 
	function video($link, $auto = true) {
		self::init();
		preg_match('#(?:youtu\.be\/|v=|\/embed\/)([^\/\?\&\<\>\"\']+)#is', $link, $idM);
		$http=\CMain::IsHTTPS()?'https':'http';
		if ($idM[1]) {
			$id = $idM[1];
			$link=$http.'://youtube.com/embed/' . $id . '?rel=0&showinfo=0' . ($auto ? '&autoplay=1' : '');
			$pic=$http.'://img.youtube.com/vi/' . $id . '/hqdefault.jpg';
			$full_pic=$http.'://img.youtube.com/vi/' . $id . '/maxresdefault.jpg';
		}else{
			$pic=self::$nophoto;
			$full_pic=self::$nophoto;
		}
		return array(
			'SRC' => $link,
			'ID' => $id,
			'IFRAME'=>'<iframe width="100%" height="660" src="'.$link.'" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen></iframe>',
			'PICTURE' => $pic,
			'FULL_PICTURE' => $full_pic,
		);
	}
	function title_alt(){
		switch (self::$arParams['TYPE']){
			case 'GALLERY':
			case 'DOUBLE':
			case 'DETAIL':
			if(!($title = self::$arParams['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_TITLE'])){
				$title = self::$arParams['NAME'];
			}
			if(!($alt = self::$arParams['IPROPERTY_VALUES']['ELEMENT_DETAIL_PICTURE_FILE_ALT'])){
				$alt = self::$arParams['NAME'];
			}
			break;
			default:
			$title = (self::$arParams['IPROPERTY_VALUES']['ELEMENT_PREVIEW_PICTURE_FILE_TITLE'] ? self::$arParams['IPROPERTY_VALUES']['ELEMENT_PREVIEW_PICTURE_FILE_TITLE'] : self::$arParams['NAME']);
			$alt = (self::$arParams['IPROPERTY_VALUES']['ELEMENT_PREVIEW_PICTURE_FILE_ALT'] ? self::$arParams['IPROPERTY_VALUES']['ELEMENT_PREVIEW_PICTURE_FILE_ALT'] : self::$arParams['NAME']);
		}
		return array($title, $alt);
	}
	function picture($arParams) {
		self::init($arParams);
		$IDS = array();
		if($arParams['MORE_PHOTO']){
			$arParams['MORE_PHOTO'] = array_filter($arParams['MORE_PHOTO'], function($element) {
				return !empty($element);
			});
		}

		switch ($arParams['TYPE']){
			case 'ALL':
			foreach(array('ID', 'PREVIEW_PICTURE', 'DETAIL_PICTURE', 'MORE_PHOTO') as $key){
				if($arParams[$key]){
					if(is_array($arParams[$key])){
						$IDS = array_merge($IDS, $arParams[$key]);
					}
					else $IDS[] = $arParams[$key];
				}
			}
			break;
			case 'DETAIL':
			foreach(array('DETAIL_PICTURE', 'PREVIEW_PICTURE', 'MORE_PHOTO') as $key){
				if($arParams[$key]){
					$IDS[] = is_array($arParams[$key])?current($arParams[$key]):$arParams[$key];
					break;
				}
			}
			self::$arParams['watermark'] = self::$arParams['watermark']['DETAIL'];
			break;
			case 'GALLERY':
			case 'DOUBLE':
			foreach(array('DETAIL_PICTURE', 'PREVIEW_PICTURE') as $key){
				if($arParams[$key]){
					$IDS[] = $arParams[$key];
					break;
				}
			}
			if ($arParams['MORE_PHOTO']) {
				if ($arParams['TYPE'] == 'DOUBLE') {
					$IDS[] = current($arParams['MORE_PHOTO']);
				} elseif(is_array($arParams['MORE_PHOTO'])) {
					$IDS = array_merge($IDS, $arParams['MORE_PHOTO']);
				}
			}
			self::$arParams['watermark'] = self::$arParams['watermark']['DETAIL'];
			break;
			default:
			foreach(array('PREVIEW_PICTURE', 'DETAIL_PICTURE', 'MORE_PHOTO') as $key){
				if($arParams[$key]){
					$IDS[] = is_array($arParams[$key])?current($arParams[$key]):$arParams[$key];
					break;
				}
			}
			self::$arParams['watermark'] = self::$arParams['watermark']['PREVIEW'];
			
		}
		list($title, $alt) = self::title_alt();
		$arPhotos = array();
		if(!array_key_exists('NOPHOTO', $arParams)){
			$arParams['NOPHOTO']=true;
		}
		if ($IDS) {
			if(self::$arParams['watermark']['active']=='Y'){
				$arWatermark = self::$arParams['watermark'];
				$arWatermark['name']='watermark';
				unset($arWatermark['active']);
				$arWatermark=array($arWatermark);
			}
			$res = \Bitrix\Main\FileTable::getList(array(
				'filter'=> array('ID'=>$IDS)
			));
			while($arPhoto = $res->fetch()){
				$arPhoto["SRC"] = \CFile::GetFileSRC($arPhoto);
				unset($arPhoto['TIMESTAMP_X']);
				$files[$arPhoto['ID']]=$arPhoto;
			}
			foreach($IDS as $id){
				$arPhoto=$files[$id];
				if(!$arParams['HEIGHT']){
					$arParams['HEIGHT']=($arPhoto['HEIGHT']/$arPhoto['WIDTH'])*$arParams['WIDTH'];
				}
				elseif(!$arParams['WIDTH']){
					$arParams['WIDTH']=($arPhoto['WIDTH']/$arPhoto['HEIGHT'])*$arParams['HEIGHT'];
				}
				if($arWatermark){
					$file = \CFile::ResizeImageGet($arPhoto, array('width' => $arPhoto['WIDTH'], 'height' => $arPhoto['HEIGHT']),BX_RESIZE_IMAGE_PROPORTIONAL, true, $arWatermark);
					$arPhoto['FULL']=$file['src'];
				}
				else $arPhoto['FULL']=$arPhoto['SRC'];
				if ($arParams['HEIGHT'] && $arParams['WIDTH']) {
					$file = \CFile::ResizeImageGet($arPhoto, array('width' => $arParams['WIDTH'], 'height' => $arParams['HEIGHT']),
						($arParams['EXACT'] ? BX_RESIZE_IMAGE_EXACT : BX_RESIZE_IMAGE_PROPORTIONAL), true, $arWatermark);
					$arPhoto['SRC'] = $file['src'];
					$arPhoto['WIDTH'] = $file['width'];
					$arPhoto['HEIGHT'] = $file['height'];
				} 
				else{
					$arPhoto['SRC'] = $arPhoto['FULL'];
				}
				$arPhoto['ABSOLUTE_FULL']=$_SERVER['DOCUMENT_ROOT'].$arPhoto['FULL'];
				$arPhoto['ABSOLUTE_SRC']=$_SERVER['DOCUMENT_ROOT'].$arPhoto['SRC'];
				if($alt)$arPhoto['ALT'] = $alt;
				if($title)$arPhoto['TITLE'] = $title;
				$arPhotos[$arPhoto['ID']] = $arPhoto;
			}

		} elseif ($arParams['NOPHOTO']) {
			$arPhotos['empty'] = array('SRC' => self::$nophoto, 'FULL'=>self::$nophoto, 'ALT' => $alt, 'TITLE' => $title, 'EMPTY' => 1);
		}
		if ($arPhotos) {
			return !in_array($arParams['TYPE'], array('GALLERY', 'DOUBLE', 'ALL'))? current($arPhotos) : $arPhotos;
		}

	}
}
?>