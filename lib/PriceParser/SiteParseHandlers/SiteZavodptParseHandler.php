<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * @var $this->pageContent
 */
class SiteZavodptParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        // $crawler = $crawler->filter('.product-side .price_matrix_wrapper > .price[data-value]');
        $crawler = $crawler->filter('.price[data-value]');
        $response = $crawler->each(function(Crawler $node, $i){
            return $node->attr('data-value');
        });

        if (!empty($response)) {
            $response = array_shift($response);
            $price = str_replace([' ', '₽', '&nbsp;', ' ', 'рублей', '/шт'], '', $response);
        }
        
        return $price ?? null;
    }
}