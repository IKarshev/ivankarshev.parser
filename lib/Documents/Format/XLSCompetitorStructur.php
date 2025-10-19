<?
/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */

namespace Ivankarshev\Parser\Documents\Format;

use Ivankarshev\Parser\Documents\Format\XLS;

use Ivankarshev\Parser\Helper;
use Ivankarshev\Parser\Options\OptionManager;
use Ivankarshev\Parser\Orm\{LinkTargerTable, CompetitorTable};
use Ivankarshev\Parser\Documents\GetOrderInfo;
use Ivankarshev\Parser\Documents\DocumentsInterface;
use Ivankarshev\Parser\Documents\DocumentFormatTrait;

use Bitrix\Iblock\SectionElementTable;

final Class XLSCompetitorStructur extends GetOrderInfo implements DocumentsInterface
{
    use DocumentFormatTrait;

    public function __construct(string $markupFileUrl) {
        parent::__construct($markupFileUrl);
    }

    public function __proccess(): void
    {
        $output = $this->createMarkup();

        $datetime = (new \DateTime())
            ->setTimeZone(new \DateTimeZone('Asia/Novosibirsk'))
            ->format('dmY');
            
        $fileName = 'pricelist_'.$datetime.'.xls';
        self::downloadFile($output, $fileName);
    }

    /**
     * Формируем разметку PDF
     */
    private function createMarkup()
    {
        try {
            // Получаем разделы
            if (($competitorStructureIblockId = (new OptionManager())->getOption('COMPETITOR_STRUCTURE_IBLOCK_ID'))!==null) {
                // Получаем класс инфоблока структуры конкурентов
                $IblockClass = \Bitrix\Iblock\Iblock::wakeUp($competitorStructureIblockId->getValue())->getEntityDataClass();
                if (!class_exists($IblockClass)) {
                    throw new \Exception("Инфоблок со структурой конкурентов не найден");
                }
                
                // Получаем категории товаров
                $sections = Helper::getSectionList(
                    ['IBLOCK_ID' => $competitorStructureIblockId->getValue()],
                    ['ID', 'NAME']
                );

                // Перебираем категории и получаем все товары со ссылками по конкурентам
                $arResult['COLUMNS'] = [];

                foreach ($sections as $section) {
                    // Удаляем переменные между итерациями
                    foreach(['sectionCompetitorList', 'sectionCompetitorIdList'] as $variableName) {
                        if (isset($$variableName)) {
                            unset($$variableName);
                        }
                    }

                    $sectionId = $section['ID'];

                    $sectionCompetitorIdList = SectionElementTable::getList([
                        'select' => ['IBLOCK_ELEMENT_ID'],
                        'filter' => [
                            '=IBLOCK_SECTION_ID' => $sectionId
                        ],
                    ])->fetchAll();

                    if (!empty($sectionCompetitorIdList)) {
                        $sectionCompetitorList = $IblockClass::getList([
                            'select' => ['ID', 'NAME'],
                            'filter' => [
                                'ID' =>  array_column($sectionCompetitorIdList, 'IBLOCK_ELEMENT_ID'),
                            ],
                        ])->fetchAll();
                    }

                    $links = LinkTargerTable::getList([
                        'select' => [
                            '*',
                            'LINK_' => 'LINK_ITEMS',
                            'COMPETITOR_ID' => 'COMPETITOR.ID',
                            'COMPETITOR_NAME' => 'COMPETITOR.NAME',
                        ],
                        'filter' => [
                            'COMPETITOR_NAME' => array_column($sectionCompetitorList, 'NAME'),
                        ],
                        'runtime' => [
                            'COMPETITOR' => [
                                'data_type' => CompetitorTable::class,
                                'reference' => [
                                    '=this.LINK_COMPETITOR_ID' => 'ref.ID',
                                ],
                                ['join_type' => 'LEFT']
                            ],
                        ]
                    ])->fetchAll();

                    foreach ($links as $arkey => &$arItem) {
                        // Прокидываем ID раздела из структуры конкурентов
                        $arItem['SECTION_ID'] = $sectionId;
                        
                        // Заполняем инфу по разделу если её там ещё нет
                        $arItem['USER_NAME'] = ($userName = Helper::getUserFullName($arItem['USER_ID']))
                            ? $userName
                            : '';

                        if ($arItem['SECTION_ID'] == 0) {
                            if (!isset($arResult['SECTIONS'][0])) {
                                $arResult['SECTIONS'][0]['SECTION_ID'] = 0;
                                $arResult['SECTIONS'][0]['BREADCRUMBS'] = '';

                                $arResult['SECTIONS'][0]['SECTION_NAME'] =
                                    $arResult['SECTIONS'][0]['BREADCRUMBS_STRING'] =
                                    $arResult['SECTIONS'][0]['FULL_NAME'] = 'Без раздела';
                            }
                        } else {
                            if (!isset($arResult['SECTIONS'][$arItem['SECTION_ID']])) {
                                $arResult['SECTIONS'][$arItem['SECTION_ID']]['SECTION_ID'] = $section['ID'];
                                $arResult['SECTIONS'][$arItem['SECTION_ID']]['SECTION_NAME'] = $section['NAME'];
                                $arResult['SECTIONS'][$arItem['SECTION_ID']]['BREADCRUMBS'] = $section['BREADCRUMBS'];
                                $arResult['SECTIONS'][$arItem['SECTION_ID']]['BREADCRUMBS_STRING'] = $section['BREADCRUMBS_STRING'];
                                $arResult['SECTIONS'][$arItem['SECTION_ID']]['FULL_NAME'] = $section['FULL_NAME'];
                            }
                        }

                        // Добавляем элементы в раздел
                        $arResult['SECTIONS'][$arItem['SECTION_ID']]['ROWS'][$arItem['ID']]['MAIN_LINK'] = LinkTargerTable::getList([
                            'select' => [
                                '*',
                                'LINK_' => 'LINK_ITEMS',
                                'COMPETITOR_ID' => 'COMPETITOR.ID',
                                'COMPETITOR_NAME' => 'COMPETITOR.NAME',
                            ],
                            'filter' => [
                                'COMPETITOR_NAME' => 'hmru.ru',
                                'LINK_LINK_ID' => $arItem['LINK_LINK_ID'],
                            ],
                            'runtime' => [
                                'COMPETITOR' => [
                                    'data_type' => CompetitorTable::class,
                                    'reference' => [
                                        '=this.LINK_COMPETITOR_ID' => 'ref.ID',
                                    ],
                                    ['join_type' => 'LEFT']
                                ],
                            ],
                            'limit' => 1,
                        ])->fetch();
                        

                        $arResult['SECTIONS'][$arItem['SECTION_ID']]['ROWS'][$arItem['ID']]['TARGET_LINKS'][] = $arItem;
                        $arResult['COLUMNS'][] = $arItem['COMPETITOR_NAME'];
                    }
                }

                
                $arResult['COLUMNS'] = $correctOrder = array_values(array_unique($arResult['COLUMNS']));

                // Сортируем данные по колонкам
                /**
                 * @todo При сортировке что-то ломается в xls
                 * 
                 * foreach ($arResult['SECTIONS'] as &$section) {
                 *     foreach ($section['ROWS'] as &$row) {
                 *         usort($row['TARGET_LINKS'], function($a, $b) use ($correctOrder) {
                 *             $posA = array_search($a['COMPETITOR_NAME'], $correctOrder);
                 *             $posB = array_search($b['COMPETITOR_NAME'], $correctOrder);
                 *             return $posA - $posB;
                 *         });
                 *     }
                 * }
                */
            }

            ob_start();
            include($this->markupFileUrl);
            $markup = ob_get_contents();
            ob_end_clean();
            
            return $markup;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
?>