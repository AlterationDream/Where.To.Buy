<?
namespace Slytek;
class HL {
	static function get($id, $params=array()){
		if(!$id)return;
		$pics=array();
		if($params['picture'] && !$params['picture']['CODE']){
			$params['picture']['CODE']='UF_FILE';
		}
		$entity_data_class = self::init($id);
		if(!$params['select'])$params['select']=array('*');
		if(!$params['order'])$params['order']=array();
		if(!$params['group'])$params['group']=array();
		if(!$params['filter'])$params['filter']=array();
		if(!$params['runtime'])$params['runtime']=array();
		$res=$entity_data_class::getList(array(
			'select' => $params['select'],
			'order' => $params['order'],
			'group' =>$params['group'],
			'filter' => $params['filter'],
			'runtime' => $params['runtime'] 
		));
		while($row = $res->fetch()){
			if($params['picture'] && $row[$params['picture']['CODE']]){
				$pics[$row[$params['picture']['CODE']]]=array();
			}
			if($params['params']['as_key']){
				$result[$row[$params['params']['as_key']]]=$row;
			}
			else $result[]=$row;
		}
		if($pics){
			$params['picture']['TYPE']='GALLERY';
			$params['picture']['MORE_PHOTO']=array_keys($pics);
			$pics=Media::picture($params['picture']);
			foreach($result as $key=>$row){
				if($pics[$row[$params['picture']['CODE']]]){
					$result[$key]['PICTURE']=$pics[$row[$params['picture']['CODE']]];
				}
			}
		}
		return $result;
	}
	static function init($id){
		\CModule::IncludeModule('highloadblock');
		if(!is_numeric($id)){
			$hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getList(array('filter'=>array('TABLE_NAME'=>$id)))->fetch(); 
		}
		else $hlblock = \Bitrix\Highloadblock\HighloadBlockTable::getById($id)->fetch();   
		$entity = \Bitrix\Highloadblock\HighloadBlockTable::compileEntity($hlblock);
		$entity_data_class = $entity->getDataClass();
		return $entity_data_class;
	}
	static function add($id, $vals){
		if(!$id)return;
		$entity_data_class = self::init($id);
		$result = $entity_data_class::add($vals);
		return $result->getId();
	}
	static function update($id, $vals){
		if(!$id)return;
		$entity_data_class = self::init($id);
		$eid=$vals['ID'];
		unset($vals['ID']);
		$result = $entity_data_class::update($eid, $vals);
		return $result->getId();
	}
	static function delete($id, $did){
		if(!$id)return;
		$entity_data_class = self::init($id);
		$result = $entity_data_class::delete($did);
		return $result;
	}
	static function fields($id, $lang=false){
		if(!$id)return;
		if(!$lang)$lang=LANGUAGE_ID;
		\CModule::IncludeModule('highloadblock');
		$arResult = \Bitrix\Highloadblock\HighloadBlockLangTable::getList(array('filter' => array('ID' => $id, '=LID' => $lang)))->fetch();
		if (!$arResult)
		{
			$arResult = \Bitrix\Highloadblock\HighloadBlockTable::getById($id)->fetch();
		}
		$USER_FIELD_MANAGER = new \CUserTypeManager();
		$props= $USER_FIELD_MANAGER->GetUserFields('HLBLOCK_'.$id, 0, $lang);
		foreach($props as $arProp){
			$arResult['ITEMS'][$arProp['FIELD_NAME']]=$arProp;
		}
		return $arResult;
	}
}
?>