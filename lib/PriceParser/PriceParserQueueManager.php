<?php
namespace Ivankarshev\Parser\PriceParser;

use Exception;
use Bitrix\Main\Type\DateTime;

use Ivankarshev\Parser\Main\Logger;
use Ivankarshev\Parser\Orm\{LinkTargerTable, ParseQueueTable, PriceTable};
use Ivankarshev\Parser\PriceParser\ParsingManager;

class PriceParserQueueManager
{
    public static function startFullParse(): void
    {
        $elementList = LinkTargerTable::getList([
            'select' => ['ID'],
        ])->fetchAll();

        if (!empty($elementList)) {
            foreach (array_column($elementList, 'ID') as $linkId) {
                ParseQueueTable::add([
                    'LINK_ID' => $linkId,
                    'ADD_TO_QUEUE_TIMESTAMP' => new DateTime(),
                ]);
            }
        }
    }

    public static function getParseList(int $limit = 15): array
    {
        $elementList = ParseQueueTable::getList([
            'select' => ['LINK_ID'],
            'limit' => $limit,
        ])->fetchAll();
        return (is_array($elementList) && !empty($elementList)) ? array_column($elementList, 'LINK_ID') : [];
    }

    /**
     * Агент для обхода 
     */
    public static function parseAgent(): string
    {
        $itemListId = self::getParseList();
        if (!empty($itemListId)) {
            $elementList = LinkTargerTable::getList([
                'select' => ['*'],
                'filter' => ['ID' => $itemListId],
            ])->fetchAll();
            if (!empty($elementList)) {
                $parsingManager = new ParsingManager();
                foreach ($elementList as $element) {
                    try {
                        $parseObjectMainLink = $parsingManager->getSiteParsingClass($element['LINK']);
                        self::setNewPrice(
                            $element['ID'],
                            true,
                            $parseObjectMainLink->getPrice()
                        );
        
                        $parseobjectTargetLink = $parsingManager->getSiteParsingClass($element['TARGET_LINK']);
                        self::setNewPrice(
                            $element['ID'],
                            false,
                            $parseobjectTargetLink->getPrice()
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

    protected static function setNewPrice(int $linkTarget, bool $isTargetLink, float $price)
    {
        $link = PriceTable::getList([
            'select' => ['*'],
            'filter' => [
                '=LINK_TARGET' => $linkTarget,
                'IS_TARGET_LINK' => $isTargetLink,
            ],
            'limit' => 1,
        ])->fetchAll();
        if (empty($link)) {
            PriceTable::add([
                'LINK_TARGET' => $linkTarget,
                'IS_TARGET_LINK' => $isTargetLink,
                'PRICE' => $price,
                'UPDATE_TIMESTAMP' => new DateTime(),
            ]);
        } else {
            PriceTable::update($link[0]['ID'], [
                'PRICE' => $price,
                'UPDATE_TIMESTAMP' => new DateTime(),
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
}