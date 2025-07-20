<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * @var $this->pageContent
 */
class SiteHmruParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        $crawler = $crawler->filter('.product-aside__price_sum');
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