<?
namespace Ivankarshev\Parser\Documents;

use Bitrix\Sale;
use \Exception;

\Bitrix\Main\Loader::includeModule('sale');
\Bitrix\Main\Loader::includeModule('disk');

abstract Class GetOrderInfo
{
    protected $markupFileUrl;
    public function __construct(string $markupFileUrl)
    {
        try {
            $this->markupFileUrl = $markupFileUrl;
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    /**
     * Отдаем файл на загрузку
     * @param string $fileContent - Контент файла
     * @param string $fileName - название файла
     * @return void
     */
    protected function downloadFile(string $fileContent, string $fileName): void
    {
        try {
            Header("Content-Type: application/force-download");
            Header("Content-Type: application/octet-stream");
            Header("Content-Type: application/download");
            Header("Content-Disposition: attachment;filename=$fileName");
            Header("Content-Transfer-Encoding: binary");
            echo $fileContent;
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}
?>