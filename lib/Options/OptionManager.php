<?
namespace Ivankarshev\Parser\Options;

use Bitrix\Main\Config\Option as bitrixCoreModuleOptions;
use Bitrix\Main\Localization\Loc;
use Exception;
use CControllerClient;
use COption;

use Ivankarshev\Parser\Options\OptionsType\{OptionTypeInt, OptionTypeString, OptionTypeCheckbox};
use Ivankarshev\Parser\Options\OptionList;
use Ivankarshev\Parser\Main\Logger;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
final Class OptionManager
{
    protected $actualItems;

    public const MODULE_ID = 'ivankarshev.parser';
    public const OPTION_TYPES = [
        'int' => 'int',
        'text' => 'string',
        'string' => 'string',
        'checkbox' => 'checkbox',
        'selectbox' => 'selectbox',
        'multiselectbox' => 'string',
    ];

    public function __construct() {
        $this->actualItems = [];
    }

    /**
     * @return array - не отформатированные настройки модуля без системных полей
     */
    private function getFullOptions(): ?array
    {
        // Получаем все настройки и отбираем настройки от системных полей  
        if(!empty( $OptionsList = (new bitrixCoreModuleOptions())::getForModule(self::MODULE_ID) )){
            $SettingsList = array_filter($OptionsList, function($key){
                return !in_array($key, ['apply_', 'autosave_id', 'sessid']);
            }, ARRAY_FILTER_USE_KEY);
        }
        
		return $SettingsList;
    }

    /**
     * @method - получаем значения свойств из http request
     * @param \Bitrix\Main\HttpRequest $request
     * @return array - массив новых значений свойств
     */
    public static function getParamsFromRequest(\Bitrix\Main\HttpRequest $request): array
    {
        // Получаем значения свойств из request
        $params = array_filter($request->getPostList()->toArray(), function($arrayItemKey){
            return !in_array($arrayItemKey, ['apply_', 'autosave_id', 'sessid']);
        }, ARRAY_FILTER_USE_KEY );

        // полный список свойств без заголовков
        $optionList = array_reduce(
            array_column(OptionList::getOptionList(), 'OPTIONS'),
            function ($carry, $options) {
            foreach ($options as $item) {
                if (is_array($item) && isset($item[0]) && is_string($item[0])) {
                    $carry[] = $item;
                }
            }
            return $carry;
        }, []);

        $fullOptionList = array_values(array_filter($optionList, function($item){
            return is_array($item);  
        }));

        // Ищем свойства без 
        $defaultOptionKeys = array_diff(
            array_column($fullOptionList, 0), 
            array_keys($params),
        );

        // set default value
        foreach ($defaultOptionKeys as $arItem) {
            $searchValueKey = array_search($arItem, array_column($fullOptionList, 0));
            if($searchValueKey !== null){
                $params[$arItem] = $fullOptionList[$searchValueKey][2];
            }
        }

        return $params;
    }

    public function getFullCustomOptions(): array
    {
        $SettingsList = [];
        // Получаем кастомные свойства из события
        foreach(GetModuleEvents(KONTUR_TOOLS_MODULE_FUNCTIONS, 'OnCreateBaseSettingsList', true) as $arEvent){
            ExecuteModuleEventEx($arEvent, array(&$SettingsList));
        }

        foreach ($SettingsList as $optionKey => $optionValue) {
            try {
                $optionValueType = self::OPTION_TYPES[$optionValue[3][0]];

                if( !array_key_exists($optionValueType, self::OPTION_TYPES) ) {
                    throw new Exception(Loc::getMessage('ERROR_NOT_SUPPORT_PROPERTY_TYPE', [
                        '#TYPE#' => $optionValue[3][0],
                        '#CODE#' => $optionValue[0],
                    ]));
                } 
                
                $optionValueCode = $optionValue[0];
                $optionValueValue = (($optionValueValue2 = $this->getOption($optionValueCode)) !== null) ? $optionValueValue2->getValue() : '';

                $className = "Kontur\\Tools\\Options\\OptionsType\\OptionType". ucfirst($optionValueType);
                $NewItem =  new $className($optionValueCode, $optionValueValue, false, false);
                $NewItem->setName($optionValue[1]);
                
                if( isset($optionValue[3][1]) && is_array($optionValue[3][1]) ) $NewItem->setVariants($optionValue[3][1]);

                $result[] = $NewItem;
            } catch (\Throwable $th) {
                Logger::notice($th->getMessage());
            }
        }

        return $result ?? [];
    }

    /**
     * @param string $optionCode
     * @return OptionTypeInterface||null
     */
    public function getOption( string $optionCode ): ?OptionTypeInterface
    {
        if( empty($this->actualItems) ) $this->getOptionsList();
        foreach ($this->actualItems as $arOption) {
            if( $arOption->getCode() == $optionCode ) return $arOption;
        }
        return null;
    }

    /**
     * @return ?array - Массив объектов свойств
     */
    public function getOptionsList(): ?array
    {
        foreach ($this->getFullOptions() as $optionKey => $optionValue) {
            try {
                $optionValue = self::getUnCompressedValue($optionValue);                

                if( !in_array($optionValue['TYPE'], self::OPTION_TYPES) ) throw new Exception(Loc::getMessage('ERROR_NOT_SUPPORT_PROPERTY_TYPE', [
                    '#TYPE#' => $optionValue['TYPE'],
                    '#CODE#' => $optionValue['CODE'],
                ]));

                $className = "Kontur\\Tools\\Options\\OptionsType\\OptionType". ucfirst($optionValue['TYPE']);
                $result[] = new $className($optionValue['CODE'], $optionValue['VALUE'], $optionValue['IS_REQUIRED'], $optionValue['IS_MULTIPLE']);
            } catch (\Throwable $th) {
                Logger::notice($th->getMessage());
            }
        }

        $this->actualItems = $result ?? [];
        return $this->actualItems;
    }

    /**
     * @param string $code - Код свойства
     * @param string $type - Тип свойства
     * @param $value - значение свойства
     * @param bool $isRequired - обязательное ли поле
     * @param bool $isMultiple - множественное ли свойство
     * 
     * @return OptionTypeInterface - Создаем объект свойства 
     */
    private function createOptionObject(string $code, string $type, $value, bool $isRequired = false, bool $isMultiple = false): OptionTypeInterface
    {
        try {
            $className = "Kontur\\Tools\\Options\\OptionsType\\OptionType" . ucfirst($type);
            return new $className($code, $value, $isRequired, $isMultiple);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Устанавливаем свойство
     * @param string $code - Код свойства
     * @param string $type - Тип свойства, поддерживаемые значения перечислены в self::OPTION_TYPES
     * @param $value - значение свойства
     * @param bool $isRequired - обязательное ли поле
     * @param bool $isMultiple - множественное ли свойство
     * 
     * @return void
     */
    public function setOption(string $code, string $type, $value, bool $isRequired = false, bool $isMultiple = false): void
    {
        try { 
            if( !in_array($type, self::OPTION_TYPES) ) {
                throw new Exception(Loc::getMessage('ERROR_NOT_SUPPORT_PROPERTY_TYPE', [
                    '#TYPE#' => $type,
                    '#CODE#' => $code,
                ]));
            } 

            $PropertyObject = $this->createOptionObject($code, $type, $value, $isRequired, $isMultiple);
            $value = self::getCompressedValue([
                'CODE' => $code,
                'TYPE' => $type,
                'VALUE' => $PropertyObject->getValue(),
                'IS_REQUIRED' => $isRequired,
                'IS_MULTIPLE' => $isMultiple,
            ]);

            (new bitrixCoreModuleOptions())::set(self::MODULE_ID, $code, $value);
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * @param mixed $value - данные
     * @return string - сжатые данные для хранениях в бд
     */
    public static function getCompressedValue(mixed $value): string
    {
        return serialize($value);
    }

    /**
     * @param string - сжатые данные
     * @return mixed - валидные данные
     */
    public static function getUnCompressedValue(string $value): mixed
    {
        return unserialize($value);
    }

    /**
     * Установка настроек по-умолчанию
     */
    public function fillModuleParams( array $aTabs ): array
    {
        $new_settings = $aTabs;
        foreach ($aTabs as $Tabskey => $TabItem) {
            foreach ($TabItem["OPTIONS"] as $optionskey => $optionsItem) {
                if ( is_array($optionsItem) ){
                
                    $optionCode = $new_settings[$Tabskey]["OPTIONS"][$optionskey][0];
                    $optionType = $new_settings[$Tabskey]["OPTIONS"][$optionskey][3][0];

                    // Set default value
                    switch ($optionType) {
                        case 'checkbox':
                            $value = "N";
                            break;
                        case 'text':
                            $value = "";
                            break;
                    };

                    // set value
                    $new_settings[$Tabskey]["OPTIONS"][$optionskey][2] = $value;
                };
            };
        };
        return $new_settings;
    }


    /**
     * Запуск отрисовки настроек
     */
    public function __AdmSettingsDrawList($arParams)
    {
        foreach($arParams as $Option)
        {
            $this->__AdmSettingsDrawRow($Option);
        }
    }


    /**
     * Отрисовка страниы настроек, получение данные
     */
    protected function __AdmSettingsDrawRow($Option)
    {
        $arControllerOption = CControllerClient::GetInstalledOptions(self::MODULE_ID);
        if($Option === null)
        {
            return;
        }

        if(!is_array($Option)):
        ?>
            <tr class="heading">
                <td colspan="2"><?=$Option?></td>
            </tr>
        <?
        elseif(isset($Option["note"])):
        ?>
            <tr>
                <td colspan="2" align="center">
                    <?echo BeginNote('align="center"');?>
                    <?=$Option["note"]?>
                    <?echo EndNote();?>
                </td>
            </tr>
        <?
        else:
            $isChoiceSites = array_key_exists(6, $Option) && $Option[6] == "Y" ? true : false;
            $listSite = array();
            $listSiteValue = array();
            if ($Option[0] != "")
            {
                if ($isChoiceSites)
                {
                    $queryObject = \Bitrix\Main\SiteTable::getList(array(
                        "select" => array("LID", "NAME"),
                        "filter" => array(),
                        "order" => array("SORT" => "ASC"),
                    ));
                    $listSite[""] = GetMessage("MAIN_ADMIN_SITE_DEFAULT_VALUE_SELECT");
                    $listSite["all"] = GetMessage("MAIN_ADMIN_SITE_ALL_SELECT");
                    while ($site = $queryObject->fetch())
                    {
                        $listSite[$site["LID"]] = $site["NAME"];
                        // $val = COption::GetOptionString(self::MODULE_ID, $Option[0], $Option[2], $site["LID"], true);
                        $val = (($optionValue = $this->getOption($Option[0])) !== null) ? $optionValue->getValue() : null;
                        if ($val)
                            $listSiteValue[$Option[0]."_".$site["LID"]] = $val;
                    }
                    $val = "";
                    if (empty($listSiteValue))
                    {
                        // $value = COption::GetOptionString(self::MODULE_ID, $Option[0], $Option[2]);
                        $value = (($optionValue = $this->getOption($Option[0])) !== null) ? $optionValue->getValue() : null;
                        if ($value)
                            $listSiteValue = array($Option[0]."_all" => $value);
                        else
                            $listSiteValue[$Option[0]] = "";
                    }
                }
                else
                {
                    // $val = COption::GetOptionString(self::MODULE_ID, $Option[0], $Option[2]);
                    $val = (($optionValue = $this->getOption($Option[0])) !== null) ? $optionValue->getValue() : null;
                }
            }
            else
            {
                $val = $Option[2];
            }
            if ($isChoiceSites):?>
            <tr>
                <td colspan="2" style="text-align: center!important;">
                    <label><?=$Option[1]?></label>
                </td>
            </tr>
            <?endif;?>
            <?if ($isChoiceSites):
                foreach ($listSiteValue as $fieldName => $fieldValue):?>
                <tr>
                <?
                    $siteValue = str_replace($Option[0]."_", "", $fieldName);
                    renderLable($Option, $listSite, $siteValue);
                    $this->renderInput($Option, $arControllerOption, $fieldName, $fieldValue);
                ?>
                </tr>
                <?endforeach;?>
            <?else:?>
                <tr>
                <?
                    renderLable($Option, $listSite);
                    $this->renderInput($Option, $arControllerOption, $Option[0], $val);
                ?>
                </tr>
            <?endif;?>
            <? if ($isChoiceSites): ?>
                <tr>
                    <td width="50%">
                        <a href="javascript:void(0)" onclick="addSiteSelector(this)" class="bx-action-href">
                            <?=GetMessage("MAIN_ADMIN_ADD_SITE_SELECTOR_1")?>
                        </a>
                    </td>
                    <td width="50%"></td>
                </tr>
            <? endif; ?>
        <?
        endif;
    }

    /**
     * Отрисовка полей настроек
     */
    protected function renderInput($Option, $arControllerOption, $fieldName, $val)
    {
        $type = $Option[3];
        $disabled = array_key_exists(4, $Option) && $Option[4] == 'Y' ? ' disabled' : '';
        ?><td width="50%"><?
        if($type[0]=="checkbox"):
            ?><input type="checkbox" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> id="<?echo htmlspecialcharsbx($Option[0])?>" name="<?=htmlspecialcharsbx($fieldName)?>" value="Y"<?if($val=="Y")echo" checked";?><?=$disabled?><?if(isset($type[2]) && $type[2]<>'') echo " ".$type[2]?>><?
        elseif($type[0]=="text" || $type[0]=="password"):
            ?><input type="<?echo $type[0]?>"<?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> size="<?echo $type[1]?>" maxlength="255" value="<?echo htmlspecialcharsbx($val)?>" name="<?=htmlspecialcharsbx($fieldName)?>"<?=$disabled?><?=($type[0]=="password" || isset($type["noautocomplete"]) && $type["noautocomplete"]? ' autocomplete="new-password"':'')?>><?
        elseif($type[0]=="selectbox"):
            $arr = $type[1];
            if(!is_array($arr))
                $arr = array();
            ?><select name="<?=htmlspecialcharsbx($fieldName)?>" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> <?=$disabled?>><?
            foreach($arr as $key => $v):
                ?><option value="<?echo $key?>"<?if($val==$key)echo" selected"?>><?echo htmlspecialcharsbx($v)?></option><?
            endforeach;
            ?></select><?
        elseif($type[0]=="multiselectbox"):
            $arr = $type[1];
            if(!is_array($arr))
                $arr = array();
            $arr_val = $val;
            ?><select size="5" <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> multiple name="<?=htmlspecialcharsbx($fieldName)?>[]"<?=$disabled?>><?
            foreach($arr as $key => $v):
                ?><option value="<?echo $key?>"<?if($arr_val!==null && in_array($key, $arr_val)) echo " selected"?>><?echo htmlspecialcharsbx($v)?></option><?
            endforeach;
            ?></select><?
        elseif($type[0]=="textarea"):
            ?><textarea <?if(isset($arControllerOption[$Option[0]]))echo ' disabled title="'.GetMessage("MAIN_ADMIN_SET_CONTROLLER_ALT").'"';?> rows="<?echo $type[1]?>" cols="<?echo $type[2]?>" name="<?=htmlspecialcharsbx($fieldName)?>"<?=$disabled?>><?echo htmlspecialcharsbx($val)?></textarea><?
        elseif($type[0]=="statictext"):
            echo htmlspecialcharsbx($val);
        elseif($type[0]=="statichtml"):
            echo $val;
        endif;?>
        </td><?
    }
}
?>