<?php
namespace Ivankarshev\Parser\PriceParser;

use Exception;
use Bitrix\Main\Type\DateTime;
use Ivankarshev\Parser\Main\Logger;
use Ivankarshev\Parser\Helper;
use Ivankarshev\Parser\Orm\{LinkTargerTable, ParseQueueTable, PriceTable};
use Ivankarshev\Parser\PriceParser\ParsingManager;
use Ivankarshev\Parser\Documents\Format\XLS as DownloadXLS;
class PriceParserQueueManager
{
    public static function addItemToQueue(int $linkId)
    {
        $elementList = PriceTable::getList([
            'select' => ['ID'],
            'filter' => ['LINK_ID' => $linkId]
        ])->fetchAll();

        if (!empty($elementList)) {
            foreach (array_column($elementList, 'ID') as $linkId) {
                ParseQueueTable::add([
                    'LINK_ID' => $linkId,
                    'ADD_TO_QUEUE_TIMESTAMP' => (new DateTime())->setTimeZone(new \DateTimeZone('Asia/Novosibirsk')),
                ]);
            }
        }
    }

    public static function startFullParse(): void
    {
        $elementList = PriceTable::getList([
            'select' => ['ID'],
        ])->fetchAll();

        if (!empty($elementList)) {
            foreach (array_column($elementList, 'ID') as $linkId) {
                ParseQueueTable::add([
                    'LINK_ID' => $linkId,
                    'ADD_TO_QUEUE_TIMESTAMP' => (new DateTime())->setTimeZone(new \DateTimeZone('Asia/Novosibirsk')),
                ]);
            }
        }
    }

    public static function getParseList(int $limit = 15): array
    {
        $elementList = ParseQueueTable::getList([
            'select' => ['ID', 'LINK_ID'],
            'limit' => $limit,
        ])->fetchAll();
        
        return (is_array($elementList) && !empty($elementList)) 
            ? array_column($elementList, 'LINK_ID') 
            : [];
    }

    /**
     * Агент для обхода 
     */
    public static function parseAgent(): string
    {
        $itemListId = self::getParseList();
        if (!empty($itemListId)) {
            $elementList = PriceTable::getList([
                'select' => ['ID', 'LINK'],
                'filter' => ['ID' => $itemListId],
            ])->fetchAll();

            if (!empty($elementList)) {
                $parsingManager = new ParsingManager();

                foreach ($elementList as $element) {
                    try {
                        $parseObjectMainLink = $parsingManager->getSiteParsingClass($element['LINK']);
                        self::setNewPrice(
                            $element['ID'],
                            $parseObjectMainLink->getPrice()
                        );
                        
                    } catch (\Throwable $th) {
                        Logger::error('Ошибка при парсинге: ' . $th->getMessage(), [
                            'trace: ' . $th->getTraceAsString(),
                        ]);
                    } finally {
                        // Удаляем элемент из очереди
                        $elementData = ParseQueueTable::getList([
                            'select' => ['ID'],
                            'filter' => ['LINK_ID' => $element['ID']],
                            'limit' => 1,
                        ])->fetchAll();
                        if (!empty($elementData)) {
                            ParseQueueTable::delete($elementData[0]['ID']);
                        }
                    }
                }
                
            }
        }

        return '\\'.__METHOD__.'();';
    }

    protected static function setNewPrice(int $linkId, float $price)
    {
        $link = PriceTable::getList([
            'select' => ['ID'],
            'filter' => [
                'ID' => $linkId,
            ],
            'limit' => 1,
        ])->fetchAll();
        if (!empty($link)) {
            PriceTable::update($link[0]['ID'], [
                'PRICE' => $price,
                'UPDATE_TIMESTAMP' => (new DateTime())->setTimeZone(new \DateTimeZone('Asia/Novosibirsk')),
            ]);
        }
    }

    public static function startFullParseAgent()
    {
        try {
            self::startFullParse();
        } catch (\Throwable $th) {
            Logger::error('Ошибка при добавлении свех записей в переиндексацию', [
                'trace: ' . $th->getTraceAsString(),
            ]);
        } finally {
            return '\\'.__METHOD__.'();';
        }
    }

    public static function sendPriceListEmailAgent()
    {
        try {
            $FileDownloader = new DownloadXLS(
                \Bitrix\Main\Application::getDocumentRoot().'/'.Helper::GetModuleDirrectory().'/modules/ivankarshev.parser/assets/DocumentMarkup/XlsMarkup.php'
            );

            $fileId = $FileDownloader->SaveFile();

            \CEvent::Send(
                IVAN_KARSHEV_PARSER_MODULE_SEND_PRICE_LIST_MAIL_EVENTNAME,
                's1',
                [],
                'Y',
                '',
                [$fileId]
            );
        } catch (\Throwable $th) {
            Logger::error('Ошибка при отправке письма с прайс листом', [
                'trace: ' . $th->getTraceAsString(),
            ]);
        } finally {
            return '\\'.__METHOD__.'();';
        }
    }
}