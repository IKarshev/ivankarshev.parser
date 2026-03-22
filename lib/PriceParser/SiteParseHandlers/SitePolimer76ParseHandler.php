<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * @var $this->pageContent
 */
class SitePolimer76ParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        $crawler = $crawler->filter('.restyled-item-card .page-top__price-value span');
        $response = $crawler->each(function(Crawler $node, $i){
            return $node->text();
        });
        if (!empty($response)) {
            $response = array_shift($response);
            $price = str_replace([' ', '₽', '&nbsp;', ' ', 'рублей', '/шт'], '', $response);
        }
        
        return $price ?? null;
    }
}