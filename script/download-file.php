<?php
use Bitrix\Main\Type\DateTime;
use Ivankarshev\Parser\Documents\DocumentDateHelper;

define('STOP_STATISTICS', true);
define("NO_KEEP_STATISTIC", true);
define("NOT_CHECK_PERMISSIONS", true);

require($_SERVER["DOCUMENT_ROOT"]."/bitrix/modules/main/include/prolog_before.php");

\Bitrix\Main\Loader::includeModule('ivankarshev.parser');

$request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
$format = $request->getQuery('format');
$date = $request->getQuery('date');

if($format!='' && in_array($format, ['section_structur', 'competitor_structur']) ){
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

require_once($_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/epilog_after.php');