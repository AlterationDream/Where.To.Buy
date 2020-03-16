<?
global $USER;
if($USER->IsAuthorized()):?>
	<a href="<?=SITE_DIR?>personal/"><?=$USER->GetLogin()?></a><span>/</span><a href="<?=$APPLICATION->GetCurPageParam('logout=yes', array('logout'))?>">Выйти</a>
	<?
else:
	?>
	<a href="<?=SITE_DIR?>personal/">Вход</a><span>/</span><a href="<?=SITE_DIR?>personal/?register=yes">Регистрация</a>
	<?
endif;
?>