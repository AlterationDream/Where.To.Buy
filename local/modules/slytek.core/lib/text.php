<?
namespace Slytek;
class Text {
	function numberWord($number, $after) {
		if(!is_array($after)){
			$after=explode(',', $after);
			foreach($after as $k=>$val){
				$after[$k]=trim($val);
			}
		}
		$cases = array(2, 0, 1, 1, 1, 2);
		return $after[($number % 100 > 4 && $number % 100 < 20) ? 2 : $cases[min($number % 10, 5)]];
	}
	function CaseName($name, $multi=false) {
		$names=explode(' ', $name);
		$name=$names[0];
		unset($names[0]);
		$end_case = array();
		if($multi){
			$end_case = array('а' => '', 'б' => 'бов', 'в' => 'вов', 'г' => 'гов', 'д' => 'дов', 'е' => 'е', 'ж' => 'жей', 'з' => 'ов', 'и' => 'ей', 'й' => 'ев', 'к' => 'ков', 'л' => 'лов', 'м' => 'мов', 'н' => 'нов', 'о' => '', 'п' => 'пов', 'р' => 'ров', 'с' => 'сов', 'т' => 'тов', 'у' => 'у', 'ф' => 'фей', 'х' => 'хов', 'ч' => 'чей', 'ш' => 'шей', 'щ' => 'щей', 'э' => 'э', 'ю' => 'ей', 'ь' => 'ей', 'я' => 'ей', 'ы' => 'ов');
		}
		else $end_case = array('а' => 'ы', 'б' => 'ба', 'в' => 'ва', 'г' => 'га', 'д' => 'да', 'е' => 'е', 'ж' => 'жа','з' => 'за', 'и' => 'и','й' => 'я', 'к' => 'ка', 'л' => 'ла', 'м' => 'ма', 'н' => 'на','о' => 'о', 'п' => 'па', 'р' => 'ра', 'с' => 'са', 'т' => 'та', 'у' => 'у','ф' => 'фа', 'х' => 'ха', 'ч' => 'ча', 'ш' => 'ша', 'щ' => 'ща', 'э' => 'э','ю' => 'ю','ь' => 'я', 'я' => 'и', 'ы' => 'ю');
		$srt_count = mb_strlen($name);
		$srt_end = mb_substr($name, $srt_count-1);
		$srt_name = mb_substr($name, 0, $srt_count - 1);
		return $srt_name . $end_case[$srt_end].($names?(' '.implode(' ', $names)):'');
	}

	function getWordEnd($number, $word) {
		return self::GetPadezh($number, array($word, self::CaseName($word), self::CaseName($word, 1)));
	}
}
?>