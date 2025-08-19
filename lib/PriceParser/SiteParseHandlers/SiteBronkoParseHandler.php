<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * Там есть доллары, надо что-то думать... https://www.bronko.ru/catalog/commercial_suvenir/printing-equipment/shirokoformatnye_pechatnye_plottery/uv4060/
 */

/**
 * @var $this->pageContent
 */
class SiteBronkoParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        $crawler = $crawler->filter('.item-price-block .price');
        $response = $crawler->each(function(Crawler $node, $i){
            return $node->text();
        });
        if (!empty($response)) {
            $response = array_shift($response);
            $price = str_replace([' ', ' ', 'руб.', '$'], '', $response);
        }
        
        return $price ?? null;
    }
}