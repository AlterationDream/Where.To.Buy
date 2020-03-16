<?
if(!defined("B_PROLOG_INCLUDED")||B_PROLOG_INCLUDED!==true)die();
/**
* Bitrix vars
*
* @var array $arParams
* @var array $arResult
* @var CBitrixComponentTemplate $this
* @global CMain $APPLICATION
* @global CUser $USER
*/
?>
<div class="mfeedback">
    <?if(!empty($arResult["ERROR_MESSAGE"]))
    {
        foreach($arResult["ERROR_MESSAGE"] as $v)
            ShowError($v);
    }
    if(strlen($arResult["OK_MESSAGE"]) > 0)
    {
        ?><div class="mf-ok-text"><?=$arResult["OK_MESSAGE"]?></div><?
    }
    $arFields=array(
        'NAME'=>array('NAME'=>'user_name', 'VALUE'=>$arResult["AUTHOR_NAME"]),
        'EMAIL'=>array('NAME'=>'user_email', 'VALUE'=>$arResult["AUTHOR_EMAIL"]),
        'PHONE'=>array('NAME'=>'user_phone', 'VALUE'=>$arResult["AUTHOR_PHONE"]),
        'CITY'=>array('NAME'=>'user_city', 'VALUE'=>$arResult["AUTHOR_CITY"]),
        'SUBJECT'=>array('NAME'=>'SUBJECT', 'VALUE'=>$arResult["SUBJECT"]),
    );
    ?>

    <form action="<?=POST_FORM_ACTION_URI?>" method="POST">
        <?=bitrix_sessid_post()?>
        <?foreach($arFields as $name=>$array):?>
        <div class="mf-name">
            <div class="mf-text">
                <?=GetMessage("MFT_".$name)?><?if(empty($arParams["REQUIRED_FIELDS"]) || in_array($name, $arParams["REQUIRED_FIELDS"])):?><span class="mf-req">*</span><?endif?>
            </div>
            <input type="text" name="<?=$array["NAME"]?>" value="<?=$array["VALUE"]?>">
        </div>
        <?endforeach?>
        <div class="mf-message">
            <div class="mf-text">
                <?=GetMessage("MFT_MESSAGE")?><?if(empty($arParams["REQUIRED_FIELDS"]) || in_array("MESSAGE", $arParams["REQUIRED_FIELDS"])):?><span class="mf-req">*</span><?endif?>
            </div>
            <textarea name="MESSAGE" rows="5" cols="40"><?=$arResult["MESSAGE"]?></textarea>
        </div>

        <?if($arParams["USE_CAPTCHA"] == "Y"):?>
            <div class="mf-captcha">
                <div class="mf-text"><?=GetMessage("MFT_CAPTCHA")?></div>
                <input type="hidden" name="captcha_sid" value="<?=$arResult["capCode"]?>">
                <img src="/bitrix/tools/captcha.php?captcha_sid=<?=$arResult["capCode"]?>" width="180" height="40" alt="CAPTCHA">
                <div class="mf-text"><?=GetMessage("MFT_CAPTCHA_CODE")?><span class="mf-req">*</span></div>
                <input type="text" name="captcha_word" size="30" maxlength="50" value="">
            </div>
            <?endif;?>
        <input type="hidden" name="PARAMS_HASH" value="<?=$arResult["PARAMS_HASH"]?>">
        <input type="submit" name="submit" value="<?=GetMessage("MFT_SUBMIT")?>">
    </form>
</div>