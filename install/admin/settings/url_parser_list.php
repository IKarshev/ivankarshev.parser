<?
if( file_exists($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ivankarshev.parser/admin/settings/url_parser_list.php") ){
    require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/ivankarshev.parser/admin/settings/url_parser_list.php");
} elseif( file_exists($_SERVER["DOCUMENT_ROOT"]."/local/modules/ivankarshev.parser/admin/settings/url_parser_list.php") ){
    require($_SERVER["DOCUMENT_ROOT"]."/local/modules/ivankarshev.parser/admin/settings/url_parser_list.php");
};
?>