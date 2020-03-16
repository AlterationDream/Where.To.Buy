<?php
namespace Slytek;
IncludeModuleLangFile(__FILE__);

class Buffer {
    public function get($params)
    {

        global $APPLICATION;
        if($params['PAGE_PROP'])$strShow = $APPLICATION->GetPageProperty($params['PAGE_PROP']);
        if(!$strShow && $params['SECTION_PROP'])$strShow = $APPLICATION->GetDirProperty($params['SECTION_PROP']);
        if(!$strShow && $params['DEFAULT'])$strShow=str_ireplace('#TITLE#', $APPLICATION->GetTitle(false), $params['DEFAULT']);
        if(!$strShow && $params['COMPONENT']){
            $ajax_variables=unserialize(Bitrix\Main\Config\Option::get('slytek.core', 'delay_variables', '', SITE_ID));
            if($ajax_variables){
                foreach($ajax_variables as $name=>$val){
                    if(($val && $_REQUEST[$name]==$val) || array_key_exists($name, $_REQUEST)){
                        $ajax=true;
                        break; 
                    }
                }
            }
            if(!$ajax){
                $c_params=array();
                if(is_array($params['COMPONENT'])){
                    $c_params=$params['COMPONENT'];
                }
                if(!$c_params){
                    $c_params_d=$APPLICATION->GetPageProperty('COMPONENT_'.$params['COMPONENT']);  
                    $c_params_d=unserialize(base64_decode($c_params_d));
                    $c_params=array_merge($c_params, $c_params_d);
                }
                elseif($c_params['COMPONENT_ID']){
                    $c_params_d=$APPLICATION->GetPageProperty('COMPONENT_'.$c_params['COMPONENT_ID']);
                    $c_params_d=unserialize(base64_decode($c_params_d));
                    if($c_params_d)$c_params['PARAMS']=array_merge($c_params['PARAMS'], $c_params_d);
                } 
                if($c_params['NAME']){
                    ob_start();
                    $APPLICATION->IncludeComponent($c_params['NAME'], $c_params['TEMPLATE'], $c_params['PARAMS']);
                    $strShow = ob_get_contents();
                    ob_end_clean();
                }
            }
        }
        if($strShow && $params['WRAP']){
            $strShow=str_ireplace('###', $strShow, $params['WRAP']); 
        }
        return $strShow;
    }

    public function show($params)
    {
        global $APPLICATION;
        $APPLICATION->AddBufferContent(array('\Slytek\Buffer',"get"), $params);
    }
    public static function protectMail($s)
    {
        $result = '';
        for($i=0; $i< strlen($s); $i++)
        {
            $result .= '&#'.ord(substr($s, $i, 1)).';';
        }
        return $result;
    }
    public function ProtectEmail(&$content){
        if(!defined('ADMIN_SECTION') && !defined('NO_KEEP_STATISTIC') && \Bitrix\Main\Config\Option::get('slytek.core', 'protect_emails', 'N', SITE_ID)=='Y'){

            $content=preg_replace_callback('/([A-Za-z0-9_\-]+\.)*[A-Za-z0-9_\-]+@([A-Za-z0-9][A-Za-z0-9\-]*[A-Za-z0-9]\.)+[A-Za-z]{2,4}/u', function($matches) { 
                return self::protectMail($matches[0]);
            }, $content);
        }
        return $content;
    }
}