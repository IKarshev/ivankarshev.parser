<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * @var $this->pageContent
 */
class SiteAssumParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        $crawler = $crawler->filter('meta[property="product:price:amount"]');
        $response = $crawler->each(function(Crawler $node, $i){
            return $node->attr('content');
        });
        if (!empty($response)) {
            $response = array_shift($response);
            $price = round($response);
        }
        
        return $price ?? null;
    }
}