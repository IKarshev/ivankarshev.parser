<?
/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */

namespace Ivankarshev\Parser\Documents\Format;

use Ivankarshev\Parser\Helper;
use Ivankarshev\Parser\Options\OptionManager;
use Ivankarshev\Parser\Documents\GetOrderInfo;
use Ivankarshev\Parser\Documents\DocumentsInterface;

use Ivankarshev\Parser\Orm\{LinkTargerTable, PriceTable, CompetitorTable};

Class XLS extends GetOrderInfo implements DocumentsInterface
{
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

    public function test()
    {
        return $this->createMarkup();
    }

    /**
     * Формируем разметку PDF
     */
    private function createMarkup()
    {
        try {
            $links = LinkTargerTable::getList([
                'select' => [
                    '*',
                    'LINK_' => 'LINK_ITEMS',
                    'COMPETITOR_ID' => 'COMPETITOR.ID',
                    'COMPETITOR_NAME' => 'COMPETITOR.NAME',
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

            // Получаем разделы
            if (($sectionIblockId = (new OptionManager())->getOption('SECTION_IBLOCK_ID'))!==null) {
                $sections = Helper::getSectionList(
                    ['IBLOCK_ID' => $sectionIblockId->getValue()],
                    ['ID', 'NAME']
                );
            }

            array_unshift(
                $sections,
                [
                    'ID' => 0,
                    'NAME' => 'Без раздела',
                ]
            );

            $arResult['COLUMNS'] = [];
            foreach ($sections as $section) {
                $sectionId = $section['ID'];
                $sectionLinks = array_filter($links, function($item) use ($sectionId){
                    return $item['SECTION_ID'] == $sectionId;
                });

                foreach ($sectionLinks as $arkey => $arItem) {
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
                    if ($arItem['LINK_IS_MAIN_LINK']) {
                        $arResult['SECTIONS'][$arItem['SECTION_ID']]['ROWS'][$arItem['ID']]['MAIN_LINK'] = $arItem;
                    } else {
                        $arResult['SECTIONS'][$arItem['SECTION_ID']]['ROWS'][$arItem['ID']]['TARGET_LINKS'][] = $arItem;
                        $arResult['COLUMNS'][] = $arItem['COMPETITOR_NAME'];
                    }
                }
            }
            $arResult['COLUMNS'] = array_unique($arResult['COLUMNS']);

            ob_start();
            include($this->markupFileUrl);
            $markup = ob_get_contents();
            ob_end_clean();
            
            return $markup;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public function saveFile(): int
    {
        $output = $this->createMarkup();
        $datetime = (new \DateTime())
            ->setTimeZone(new \DateTimeZone('Asia/Novosibirsk'))
            ->format('dmY');

        $fileName = 'pricelist_'.$datetime.'.xls';
        
        $fileId = \CFile::SaveFile(
            [
                'name' => $fileName,
                "MODULE_ID" => 'ivankarshev.parser',
                'content' => $output,
            ],
            'ivankarshev_parser'
        );

        return $fileId;
    }
}
?>