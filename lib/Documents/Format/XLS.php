<?
/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */

namespace Ivankarshev\Parser\Documents\Format;

use Ivankarshev\Parser\Documents\GetOrderInfo;
use Ivankarshev\Parser\Documents\DocumentsInterface;

use Ivankarshev\Parser\Orm\{LinkTargerTable, PriceTable};

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
    private function createMarkup(){

        try {
            $links = LinkTargerTable::getList([
                'select' => ['*', 'LINK_' => 'LINK_ITEMS'],
            ])->fetchAll();

            foreach ($links as $arkey => $arItem) {
                if ($arItem['LINK_IS_MAIN_LINK']) {
                    $arResult['ROWS'][$arItem['ID']]['MAIN_LINK'] = $arItem;
                } else {
                    $arResult['ROWS'][$arItem['ID']]['TARGET_LINKS'][] = $arItem;
                }
            }


            $arResult['COLUMN_COUNT'] = 0;
            foreach ($arResult['ROWS'] as $arkey => $arItem) {
                if (count($arItem['TARGET_LINKS']) > $arResult['COLUMN_COUNT']) {
                    $arResult['COLUMN_COUNT'] = count($arItem['TARGET_LINKS']);
                }
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