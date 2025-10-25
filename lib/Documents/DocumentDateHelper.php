<?php
namespace Ivankarshev\Parser\Documents;

use Bitrix\Main\Type\DateTime;

class DocumentDateHelper
{
    /**
     * @description - Метод позволяем получить файлы модуля за определенный период. Метод не кешируется.
     * 
     * @param \Bitrix\Main\Type\DateTime $startPerion - Дата начиная с которой нужно получить файлы
     * @param \Bitrix\Main\Type\DateTime $endPerion - Дата до которой нужно получить файлы (если не задан, то равен следующему дню 0:00)
     * @return array - данные по файлам.
     */
    public static function getFileListForPerion(DateTime $startPerion, ?DateTime $endPerion = null)
    {
        if ($endPerion === null) {
            $endPerion = (new DateTime())->add('1 day');
        }

        return \Bitrix\Main\FileTable::getList([
            'select' => ['*'],
            'filter' => [
                [
                    'LOGIC' => "AND",
                    ['>=TIMESTAMP_X' => $startPerion],
                    ['<TIMESTAMP_X' => $endPerion],
                ],
                'MODULE_ID' => 'ivankarshev.parser'
            ]
        ])->fetchAll();
    }
}