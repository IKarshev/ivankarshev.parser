<?
use Bitrix\Main\Loader;
use Bitrix\Main\Localization\Loc;
use Bitrix\Main\HttpApplication;
use Bitrix\Main\Config\Option;
use \COption;

use Ivankarshev\Parser\Exchange1C\SectionLink;
use Ivankarshev\Parser\Options\{OptionManager, OptionList};

Loc::loadMessages(__FILE__);

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$post = $request->getPostList();

$module_id = htmlspecialcharsbx($request["mid"] != "" ? $request["mid"] : $request["id"]);
Loader::includeModule($module_id);
Loader::includeModule('iblock');

$OptionManager = new OptionManager();

$ModuleOptionsArray = OptionList::getOptionList();

// Сохраняем настройки
if ( $request->isPost() ){
    $params = OptionManager::getParamsFromRequest($request);
    foreach ($params as $paramKey => $paramValue) {
        // Сбрасываем между итерациями
        if( isset($searchPropertyType) ) unset($searchPropertyType);
        
        foreach ($ModuleOptionsArray as &$tabOptions) {
            // Ищем значение по массиву
            $searchProperty = array_filter($tabOptions['OPTIONS'], function($item) use ($paramKey){
                return (is_array($item) && $item[0] == $paramKey);
            });
            
            // Сохраняем настройку
            if( !empty($searchProperty) ){
                $searchProperty = array_shift($searchProperty);

                $searchPropertyType = (array_key_exists($searchProperty[3][0], OptionManager::OPTION_TYPES)) 
                    ? OptionManager::OPTION_TYPES[$searchProperty[3][0]]
                    : '';
                
                $IsMultiple = ( is_array($paramValue) && count($paramValue) > 1 );
                $OptionManager->setOption($paramKey, $searchPropertyType, $paramValue, false, $IsMultiple);
            }
        }
    }
} 

// Получаем текущие настройки
$currentOptions = [
    'IBLOCK_ID' => (($IblockIdOption = $OptionManager->getOption('IBLOCK_ID')) !== null ) ? $IblockIdOption->getValue() : '',
    'OFFERS_IBLOCK_ID' => (($OffersIblockIdOption = $OptionManager->getOption('OFFERS_IBLOCK_ID')) !== null) ? $OffersIblockIdOption->getValue() : '',
];

// Инфоблок
$infoblock = \Bitrix\Iblock\IblockTable::getList( [
    'select' => ['ID', 'NAME'],
]);
while ($row = $infoblock->fetch()) {
    $IBLOCK_ID_option[$row['ID']] = $OFFERS_IBLOCK_ID_option[$row['ID']] = "[".$row['ID']."] ".$row['NAME'];
}

// Свойства каталога
$rsProperty = \Bitrix\Iblock\PropertyTable::getList(array(
    'filter' => array('IBLOCK_ID'=>$currentOptions['IBLOCK_ID'],'ACTIVE'=>'Y'),
));
while($arProperty=$rsProperty->fetch()){
    $REQUISITES_PROPERTY_CODE_option[$arProperty['CODE']] = $CALCULATE_SALE_IBLOCK_PROP_option[$arProperty['CODE']] = "[".$arProperty['CODE']."] ".$arProperty["NAME"];
};

// Свойства каталога
$rsProperty = \Bitrix\Iblock\PropertyTable::getList(array(
    'filter' => array('IBLOCK_ID'=>$currentOptions['OFFERS_IBLOCK_ID'],'ACTIVE'=>'Y'),
));
while($arProperty=$rsProperty->fetch()){
    $REQUISITES_OFFERS_PROPERTY_CODE_option[$arProperty['CODE']] = $CALCULATE_SALE_OFFERS_IBLOCK_PROP_option[$arProperty['CODE']] = "[".$arProperty['CODE']."] ".$arProperty["NAME"];
};

// Разделы без привязки
if( isset($currentOptions['IBLOCK_ID']) && !empty($currentOptions['IBLOCK_ID']) ){
    foreach (SectionLink::GetIblockSection($currentOptions['IBLOCK_ID']) as $arItem) {
        $prepend = str_repeat('_', $arItem['DEPTH_LEVEL']);
        $SECTION_LINK_SELECTED_SECTIONS_option[$arItem['ID']] = '['.$arItem['ID'].'] '.$prepend.$arItem['NAME'];
    }
}

// Заполняем варианты для selectbox, multiselectbox
foreach ($ModuleOptionsArray as &$tabOptions) {
    foreach ($tabOptions['OPTIONS'] as &$Option) {
        if( !is_array($Option) ) continue;

        if( in_array($Option[3][0], ['selectbox', 'multiselectbox']) ){
            $optionValueVarName = $Option[0].'_option';
            $Option[3][] = $$optionValueVarName ?? [];
        }
    }
}
// Заполняем актуальными значениями и формируем табы
$aTabs = $OptionManager->fillModuleParams($ModuleOptionsArray);

$tabControl = new CAdminTabControl(
    "tabControl",
    $aTabs
);
$tabControl->Begin();
?>

<form id="<?=$module_id?>_Module" action="<?=$APPLICATION->GetCurPage()?>?mid=<?=$module_id?>&lang=<?=LANG?>" method="post">

<?
foreach($aTabs as $aTab){

    if($aTab["OPTIONS"]){

        $tabControl->BeginNextTab();

        $OptionManager->__AdmSettingsDrawList($aTab["OPTIONS"]);
    }
}

$tabControl->Buttons();
?>

<input type="submit" name="apply_" value="<?=Loc::GetMessage("FALBAR_TOTOP_OPTIONS_INPUT_APPLY")?>" class="adm-btn-save" />
<input type="submit" name="default" value="<?=Loc::GetMessage("FALBAR_TOTOP_OPTIONS_INPUT_DEFAULT")?>" />

<?=bitrix_sessid_post()?>
</form>
<?$tabControl->End();?>