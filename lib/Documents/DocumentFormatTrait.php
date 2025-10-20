<?php
namespace Ivankarshev\Parser\Documents;

trait DocumentFormatTrait
{
    public function test()
    {
        return $this->createMarkup();
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