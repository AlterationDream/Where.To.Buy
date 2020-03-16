<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();

$date=time()+2*24*60*60;
echo FormatDate("l", $date).', '.FormatDate('j F', $date);
?>