<?php
use Ivankarshev\Parser\Documents\Format\XLS as DownloadXls;
use Ivankarshev\Parser\Documents\Format\XLSCompetitorStructur as DownloadXlsCompetitor;
use Ivankarshev\Parser\Helper;

define('STOP_STATISTICS', true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('ivankarshev.parser');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$format = strtoupper($request->get("format") ?? '');

if($format!='' && in_array($format, ['XLSSECTIONS', 'XLSCOMPETITOR']) ){
    switch ($format) {
        case 'XLSSECTIONS':
            $FileDownloader = new DownloadXls(
                \Bitrix\Main\Application::getDocumentRoot().'/'.Helper::GetModuleDirrectory().'/modules/ivankarshev.parser/assets/DocumentMarkup/XlsMarkup.php'
            );
            break;
        case 'XLSCOMPETITOR':
            $FileDownloader = new DownloadXlsCompetitor(
                \Bitrix\Main\Application::getDocumentRoot().'/'.Helper::GetModuleDirrectory().'/modules/ivankarshev.parser/assets/DocumentMarkup/XlsMarkup.php'
            );
            break;
    }
    
    if($FileDownloader) {
        $FileDownloader->__proccess();
    } 
}