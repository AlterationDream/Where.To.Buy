<?
namespace Slytek;
class Section{
	protected $res = false;
	function __construct($res){
		$this->res = $res;
	}
	function fetch(){
		return $this->res->GetNext();
	}
	function load(){
		\Bitrix\Main\Loader::includeModule('iblock');
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

		$res = \CIBlockSection::GetList($params['order'], $params['filter'], $params['group'], $params['select'], $params['nav']);
  
		// if(in_array('SECTION_PAGE_URL', $params['select'])){
		// 	$params['select']['IBLOCK_SECTION_PAGE_URL']='IBLOCK.SECTION_PAGE_URL';
		// }
		// $res = \Bitrix\Iblock\SectionTable::getList($params);

		$result = new self($res);
		return $result;
	}
}
?>