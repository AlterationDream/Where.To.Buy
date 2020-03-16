<?
if($_REQUEST['send_form'] && $_REQUEST['FORM']){
	CModule::includeModule('slytek.favorites');
	global $USER;
	$arFilter=array();
	foreach($_POST['FORM'] as $name=>$field){
		$arFields[htmlspecialchars($name)]=htmlspecialchars($field); 
	}
	if(mb_strlen($arFields['NAME'])<1){
		$errors[]=GetMessage('SLYTEK_CREATE_FOLDER_ERROR_NAME');
	}
	if(!$errors){
		if($USER->IsAuthorized()){
			$arFilter['USER_ID'] = $arFields['USER_ID'] = $USER->GetID();
		}else{
			if($cookie_user_id = $APPLICATION->get_cookie("SLYTEK_COOKIE_USER_ID")){
				$arFilter['COOKIE_USER_ID'] = $cookie_user_id;
			}else{
				$cookie_user_id = md5(time().randString(10));
				$APPLICATION->set_cookie("SLYTEK_COOKIE_USER_ID", $cookie_user_id);
				$arFilter['COOKIE_USER_ID'] = $cookie_user_id;
			}
			$arFields['COOKIE_USER_ID']=$cookie_user_id;
		}
		$arFilter['NAME']=$arFields['NAME'];
		/** find favor */
		$favorDb = \slytek\Favorites\FavoritesFoldersTable::getList(array(
			'select' => array('ID', 'NAME', 'COOKIE_USER_ID'),
			'filter' => $arFilter
			));
		while($favorItem = $favorDb->fetch()){
			$errors[]=GetMessage('SLYTEK_CREATE_FOLDER_ERROR_EXIST');
		}
		if(!$errors){
			$arFields['DATE_INSERT'] = new \Bitrix\Main\Type\DateTime();

			$result = \slytek\Favorites\FavoritesFoldersTable::add($arFields);
			if($result->isSuccess()){
				$success=1;
				unset($arFields);
				if(intval($_REQUEST['id'])>0){
					include 'infolder.php';
					die();
				}
			}
			else{
				$e = $result->getErrorMessages();
				throw new Main\SystemException(implode(";",$e));
			}
		}
	}
}
?>
<div>
	<div class="hide ajax-title"><?=GetMessage('SLYTEK_CREATE_FOLDER')?></div>
	<form class="form" method="post">
		<input type="hidden" name="ajax_get" value="<?=htmlspecialchars($_REQUEST['ajax_get'])?>"/>
		<input type="hidden" name="send_form" value="Y"/>
		<input type="hidden" name="id" value="<?=intval($_REQUEST['id'])?>"/>
		<?if($errors):?>
		<div class="errors"><?=implode('<br/>', $errors)?></div>
		<?elseif($success):?>
		<div class="success"><?=GetMessage('SLYTEK_CREATE_FOLDER_SUCCESS')?></div>
		<script>
			if(window.location.href.indexOf('<?=SITE_DIR?>personal/favorites/')!=-1){
				setTimeout(function(){
					window.location.reload();
				}, 1000)
			}
		</script>
		<?endif?>
		<div class="form__list">
			<div class="form__name"><?=GetMessage('SLYTEK_CREATE_FOLDER_NAME')?></div>
			<input class="form__input required" type="text" name="FORM[NAME]" placeholder="<?=GetMessage('SLYTEK_CREATE_FOLDER_NAME')?>" value="<?=$arFields['NAME']?>">
		</div>
		<div class="form__list">
			<div class="form__name"><?=GetMessage('SLYTEK_CREATE_FOLDER_DESCRIPTION')?></div>
			<textarea class="form__textarea"  name="FORM[DESCRIPTION]" placeholder="<?=GetMessage('SLYTEK_CREATE_FOLDER_DESCRIPTION')?>"><?=$arFields['DESCRIPTION']?></textarea>
		</div>
		<input class="button button_large button_w" type="submit" value="<?=GetMessage('SLYTEK_CREATE_FOLDER_CREATE')?>" />
	</form>
</div>