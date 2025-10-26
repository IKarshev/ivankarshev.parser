<?php
namespace Ivankarshev\Parser\Documents;

trait DocumentFormatTrait
{
    public function test()
    {
        return $this->createMarkup();
    }

    public function saveFile($fileNamePrefix='pricelist_section'): int
    {
        $output = $this->createMarkup();
        $datetime = (new \DateTime())
            ->setTimeZone(new \DateTimeZone('Asia/Novosibirsk'))
            ->format('dmY');

        $fileName = $fileNamePrefix.'_'.$datetime.'.xls';
        
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