<?if(!defined("B_PROLOG_INCLUDED") || B_PROLOG_INCLUDED!==true)die();
CModule::IncludeModule("iblock");
foreach($arResult as $key => $arItem) {
    $dbSection = CIBlockSection::GetList(
        array("SORT"=>"ASC"),
        array(
            "IBLOCK_ID" => IBLOCK_CATALOG,
            "NAME" => $arItem["TEXT"],
            'DEPTH_LEVEL' => 1
        ),
        false,
        array('UF_SVG', 'UF_IMG')
    );
    if ($arSection = $dbSection->GetNext()){
        $arResult[$key]["UF_SVG"] = $arSection["~UF_SVG"];
        $arResult[$key]["UF_IMG"] = CFile::GetPath($arSection["UF_IMG"]);
    }
}
