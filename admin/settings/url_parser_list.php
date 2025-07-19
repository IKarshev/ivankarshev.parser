<?
require_once($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_before.php"); // первый общий пролог
require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_admin_after.php"); // второй общий пролог

global $APPLICATION;
$APPLICATION->setTitle('Панель настроек парсера');

\Bitrix\Main\Loader::includeModule('ivankarshev.parser');
?>

<?$APPLICATION->IncludeComponent(
    "IvanKarshev:linkspanel",
    "",
    Array()
);?>


<?require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/epilog_admin.php");?>