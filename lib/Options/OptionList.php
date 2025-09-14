<?php
namespace Ivankarshev\Parser\Options;

use Bitrix\Main\Localization\Loc;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category ModuleOptions
 */
class OptionList
{
    // text, checkbox, selectbox, multiselectbox, textarea, statictext
    public static function getOptionList(): array
    {
        return [
            [
                "DIV" => "parser_base_settings",
                "TAB"=> Loc::getMessage("FALBAR_TOTOP_OPTIONS_TAB_NAME"),
                "TITLE" => Loc::getMessage("FALBAR_TOTOP_OPTIONS_TAB_NAME"),
                "OPTIONS" => [
                    Loc::getMessage("MAIN_SETTINGS"),
                    array(
                        "USE_LOGGER",
                        Loc::getMessage("USE_LOGGER"),
                        "",
                        array("checkbox"),
                    ),
                    array(
                        "SECTION_IBLOCK_ID",
                        Loc::getMessage("SECTION_IBLOCK_ID"),
                        '',
                        array("selectbox"),
                    )
                ]
            ],
            [
                "DIV" => "parser_base_access_settings",
                "TAB"=> 'Настройки доступов',
                "TITLE" => 'Настройки доступов',
                "OPTIONS" => []
            ],
        ];
    }
}