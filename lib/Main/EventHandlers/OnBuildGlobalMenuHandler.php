<?
namespace Ivankarshev\Parser\Main\EventHandlers;

use Bitrix\Main\Localization\Loc,
Ivankarshev\Parser\Main\Options;

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 * @category EventHandler
 */
class OnBuildGlobalMenuHandler
{
  public static function init(&$arGlobalMenu, &$arModuleMenu)
  {
    global $USER;
    if (!$USER->IsAdmin())
      return;

    $subMenuList = [
      [
        'parent_menu' => 'global_menu_IvanKarshev',
        'sort' => 10,
        'url' => '/bitrix/admin/url_parser_list.php',
        'text' => Loc::getMessage('KONTUR_SETTINGS_TAB'),
        'title' => Loc::getMessage('KONTUR_SETTINGS_TAB'),
        'icon' => 'fav_menu_icon',
        'page_icon' => 'fav_menu_icon',
        'items_id' => 'menu_custom',
      ]
    ];

    foreach (GetModuleEvents(Options::MODULE_ID, 'OnCreateAdminSubMenu', true) as $arEvent) {
      ExecuteModuleEventEx($arEvent, [&$subMenuList]);
    }

    $arGlobalMenu["global_menu_IvanKarshev"] = [
      'menu_id' => 'IvanKarshevParser',
      'text' => 'URL Parser',
      'title' => 'URL Parser',
      'url' => 'settingss.php?lang=ru',
      'sort' => 1000,
      'items_id' => 'global_menu_IvanKarshev',
      'help_section' => 'custom',
      'items' => $subMenuList,
    ];
  }
}
?>