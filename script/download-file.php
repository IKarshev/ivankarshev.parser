<?php
use Ivankarshev\Parser\Documents\Format\XLS as DownloadXLS;
use Ivankarshev\Parser\Helper;

define('STOP_STATISTICS', true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('ivankarshev.parser');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$get = $request->getQueryList();
$format = strtoupper($get['format']);

if( isset($get['format']) && in_array($format, ['XLS']) ){
    
    switch ($format) {
        case 'XLS':
            $FileDownloader = new DownloadXLS(
                \Bitrix\Main\Application::getDocumentRoot().'/'.Helper::GetModuleDirrectory().'/modules/ivankarshev.parser/assets/DocumentMarkup/XlsMarkup.php'
            );
            break;
    }
    
    if( $FileDownloader ) $FileDownloader->__proccess();
}