<?
use SLytek\Settings;
switch (INFO_TYPE) {
	case 'measure':
	?><div style="white-space: pre-wrap; text-align: left"><?
	echo Settings::get('info->measure');
	?></div><?
	break;
	case 'sizes':
	?><div class="lg-table-wrapper"><?
	echo Settings::get('info->sizes');
	?></div><?
	break;
}
?>