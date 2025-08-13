<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * @var $this->pageContent
 */
class SiteMagikonParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        $crawler = $crawler->filter('.prices_block .price_value');
        $response = $crawler->each(function(Crawler $node, $i){
            return $node->text();
        });
        if (!empty($response)) {
            $response = array_shift($response);
            $price = str_replace([' ', '₽'], '', $response);
        }
        
        return $price ?? null;
    }
}