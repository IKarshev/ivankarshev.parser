<?
/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */

namespace Ivankarshev\Parser\Documents\Format;

use Ivankarshev\Parser\Documents\GetOrderInfo;
use Ivankarshev\Parser\Documents\DocumentsInterface;

Class XLS extends GetOrderInfo implements DocumentsInterface
{
    public function __construct(string $markupFileUrl) {
        parent::__construct($markupFileUrl);
    }

    public function __proccess(): void
    {
        $output = $this->createMarkup();

        $datetime = (new \DateTime())->format('dmY');
        $fileName = 'pricelist_'.$datetime.'.xls';
        // self::downloadFile($output, $fileName);
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
            ob_start();
            $arResult = [];
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