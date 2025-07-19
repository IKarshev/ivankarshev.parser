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
                "DIV" => "1C_settings",
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
                    Loc::getMessage("IBLOCK_ID_SETTINGS"),
                    array(
                        "IBLOCK_ID",
                        Loc::getMessage("IBLOCK_ID"),
                        '',
                        array("selectbox"),
                    ),
                    array(
                        "OFFERS_IBLOCK_ID",
                        Loc::getMessage("OFFERS_IBLOCK_ID"),
                        '',
                        array("selectbox"),
                    ),
                    Loc::getMessage("OPERATIONS_WITH_REQUISITES"),
                    array(
                        "MATCHING_REQUISITES_PROPERTIES",
                        Loc::getMessage("MATCHING_REQUISITES_PROPERTIES"),
                        "",
                        array("checkbox")
                    ),
                    array(
                        "REQUISITES_PROPERTY_CODE",
                        Loc::getMessage("REQUISITES_PROPERTY_CODE"),
                        [],
                        array("multiselectbox"),
                    ),
                    array(
                        "REQUISITES_OFFERS_PROPERTY_CODE",
                        Loc::getMessage("REQUISITES_OFFERS_PROPERTY_CODE"),
                        [],
                        array("multiselectbox"),
                    ),
                    Loc::getMessage("SECTIONS_LINK_SETTINGS"),
                    array(
                        "SECTION_LINK_ACTIVITY",
                        Loc::getMessage("SECTION_LINK_ACTIVITY"),
                        "",
                        array("checkbox"),
                    ),
                    array(
                        "SECTION_LINK_SELECTED_SECTIONS",
                        Loc::getMessage("SECTION_LINK_SELECTED_SECTIONS"),
                        [],
                        array("multiselectbox"),
                    ),
                ]
            ],
            [
                "DIV" => "calculateSales",
                "TAB"=> Loc::getMessage("CALCULATE_PRICE_OPTION_TAB_NAME"),
                "TITLE" => Loc::getMessage("CALCULATE_PRICE_OPTION_TAB_NAME"),
                "OPTIONS" => [
                    array(
                        "USE_CALCULATE_SALE",
                        Loc::getMessage("USE_CALCULATE_SALE"),
                        "",
                        array("checkbox"),
                    ),
                    array(
                        "DEFAULT_CALCULATE_ITEM_PER_ITERATION",
                        Loc::getMessage("DEFAULT_CALCULATE_ITEM_PER_ITERATION"),
                        "",
                        array("text"),
                    ),
                    array(
                        "CALCULATE_SALE_IBLOCK_PROP",
                        Loc::getMessage("CALCULATE_SALE_IBLOCK_PROP"),
                        "",
                        array("selectbox"),
                    ),
                    array(
                        "CALCULATE_SALE_OFFERS_IBLOCK_PROP",
                        Loc::getMessage("CALCULATE_SALE_OFFERS_IBLOCK_PROP"),
                        "",
                        array("selectbox"),
                    ),
                ],
            ]
        ];
    }
}