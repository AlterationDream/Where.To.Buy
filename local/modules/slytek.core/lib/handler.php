<?
namespace Slytek;
class Handler {
	public static $sort_key = false;
	function sendURL($path){
		$ch = curl_init($path); 
		curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 6.2; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/27.0.1453.93 Safari/537.36');
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);    
		curl_setopt($ch, CURLOPT_TIMEOUT, 10);        
		curl_setopt($ch, CURLOPT_MAXREDIRS, 2);
		$data = curl_exec($ch);
		curl_close($ch);  
		return $data;
	}
	

	function cmp_by_key($a, $b) {
		if(!self::$sort_key)return;
		if ($a[self::$sort_key] == $b[self::$sort_key]) {
			return 0;
		}
		return ($a[self::$sort_key] < $b[self::$sort_key]) ? -1 : 1;
	}


	function sort_by_key($ar, $key, $assoc = true){
		self::$sort_key=$key;
		if($assoc){
			uasort($ar, array('self', 'cmp_by_key'));
		}else{
			usort($ar, array('self', 'cmp_by_key'));
		}
		return $ar;
	}

	function OnBeforeUserLoginFunction(&$arFields) {
		$rsUsers = CUser::GetList(($by = "LAST_NAME"), ($order = "asc"), Array("=EMAIL" => $arFields["LOGIN"]));
		if ($user = $rsUsers->GetNext()) {
			$arFields["LOGIN"] = $user["LOGIN"];
		}

		/*else $arFields["LOGIN"] = "";*/
	}
	function OnBeforeUserRegisterFunction(&$arFields) {
		$arFields["LOGIN"] = $arFields["EMAIL"];
	}

	function buildTree($arResult, $params = array()){
		if(!$arResult)return;
		if(!$params['DEPTH_KEY'])$params['DEPTH_KEY']='DEPTH_LEVEL';
		$max=0;
		foreach($arResult as $key=>$arItem){
			if($arItem[$params['DEPTH_KEY']]>$max)$max=$arItem[$params['DEPTH_KEY']];
		}
		if($params['SECTION']>0){
			$relate_depth=0;
			$result=array();
			foreach($arResult as $key=>$arItem){
				if($key==$params['SECTION']){
					$relate_depth = $arItem['DEPTH_LEVEL'];
				}
				elseif($relate_depth>0 && $arItem['DEPTH_LEVEL']>$relate_depth){
					$arItem['DEPTH_LEVEL']=$arItem['DEPTH_LEVEL']-$relate_depth;
					$result[$key]=$arItem;
				}
				elseif($relate_depth>0){
					break;
				}
			}
			$arResult=$result;
		}

		for($i=$max; $i>1; $i--){
			foreach($arResult as $key=>$arItem){
				if($i==$arItem[$params['DEPTH_KEY']]){
					if(!$arItem['MAX'])$arItem['MAX']=$arItem[$params['DEPTH_KEY']];
					$arResult[$prevKey]['MAX']=$arItem['MAX'];

					$arResult[$prevKey]['ITEMS'][$key]=$arItem;
					if($params['SORT']){
						$arResult[$prevKey]['ITEMS']=self::sort_by_key($arResult[$prevKey]['ITEMS'], $params['SORT']);
					}
					unset($arResult[$key]);
				}
				else {
					$prevKey=$key;
				}
			}

		}
		return $arResult;
	}


}
?>