<?
namespace Slytek;
class Storage
{
	protected static $storage = array();
	public static function set($name, $value){
		self::$storage[$name] = $value;
	} 
	public static function get($name){
		return self::$storage[$name];
	} 
}
?>