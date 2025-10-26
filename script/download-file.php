<?php
use Bitrix\Main\Type\DateTime;
use Ivankarshev\Parser\Documents\DocumentDateHelper;

use Ivankarshev\Parser\Documents\Format\XLS as XlsSectionStructur;
use Ivankarshev\Parser\Documents\Format\XLSCompetitorStructur as XlsCompetitorStructur;
use Ivankarshev\Parser\Helper;

define('STOP_STATISTICS', true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('ivankarshev.parser');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$format = $request->getQuery('format');
$date = $request->getQuery('date');

if($format!='' && in_array($format, ['section_structur', 'competitor_structur']) ){
    if ($date === (new DateTime())->format('d.m.Y')) {
        // Удаляем прошлый файл
        $dateItems = DocumentDateHelper::getFileListForPerion(
            new DateTime($date),
            (new DateTime($date))->add('1 day'),
            $format
        );

        // Формируем новый файл
        $FileTemplateUrl = \Bitrix\Main\Application::getDocumentRoot() . "/". Helper::GetModuleDirrectory() . "/modules/ivankarshev.parser/assets/DocumentMarkup/XlsMarkup.php";
        $markup = $format == 'section_structur'
            ? new XlsSectionStructur($FileTemplateUrl)
            : new XlsCompetitorStructur($FileTemplateUrl);

        // Сохраняем для следующих использований
        $markup->saveFile(
            $format == 'section_structur'
                ? 'pricelist_section_structur'
                : 'pricelist_competitor_structur'
        );

        // Отдаем сразу на скачку.
        $markup->__proccess();
    } else {
        $dateItems = DocumentDateHelper::getFileListForPerion(
            new DateTime($date),
            (new DateTime($date))->add('1 day'),
        );
    
        foreach ($dateItems as $arFile) {
            if (str_contains($arFile['ORIGINAL_NAME'], $format)) {
                \CFile::ViewByUser(
                    \CFile::GetFileArray($arFile['ID']),
                    ['force_download' => true]
                );
            }
        }
    }
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');