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
    private const COMPETITOR_LIST = [
        'hmru.ru',
        'hurakan-russia.ru',
    ];

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

            foreach ($links as $arkey => $arItem) {
                if ($arItem['LINK_IS_MAIN_LINK']) {
                    $arResult['ROWS'][$arItem['ID']]['MAIN_LINK'] = $arItem;
                } else {
                    $arResult['ROWS'][$arItem['ID']]['TARGET_LINKS'][] = $arItem;
                }
            }

            $arResult['COLUMNS'] = [];
            foreach ($arResult['ROWS'] as $arkey => $arItem) {
                $arResult['COLUMNS'] = array_merge(
                    $arResult['COLUMNS'],
                    array_column($arItem['TARGET_LINKS'], 'COMPETITOR_NAME')
                );
            }
            $arResult['COLUMNS'] = array_unique($arResult['COLUMNS']);

            if ($sectionIblockId = (new OptionManager())->getOption('SECTION_IBLOCK_ID')) {
                $section = Helper::getSectionList(
                    ['IBLOCK_ID' => $sectionIblockId->getValue()],
                    ['ID', 'NAME']
                );
            }

            ob_start();
            print_r($links);
            echo "\n====================\n";
            print_r($section);
            $debug = ob_get_contents();
            ob_end_clean();
            $fp = fopen($_SERVER['DOCUMENT_ROOT'].'/lk-params.log', 'w+');
            fwrite($fp, $debug);
            fclose($fp);

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