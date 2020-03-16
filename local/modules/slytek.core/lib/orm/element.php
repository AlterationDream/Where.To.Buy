<?
namespace Slytek;
class Element{
	protected $res = false;
	function __construct($res){
		$this->res = $res;
	}
	function fetch($next_element = false){
		if($next_element)return $this->res->GetNextElement();
		else return $this->res->GetNext();
	}
	function add($params){
		self::load();
		$section = new \CIBlockSection;
		$id = $section->Add($params);
		return $id;
	}
	function update($id, $params){
		self::load();
		$res = \Bitrix\Iblock\SectionTable::update($id, $params);
		return $res;
	}
	function getList(array $params = Array()){
		self::load();

		$res = \CIBlockElement::GetList($params['order'], $params['filter'], $params['group'], $params['nav'], $params['select']);
  
		// $res = \Bitrix\Iblock\ElementTable::getList($params);
		// return $res;

		$result = new self($res);
		return $result;
	}
}
?>