<?php
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use Ivankarshev\Parser\PriceParser\SiteParseHandlers\{AbstractSiteParseHandler, SiteParseHandlerInterface};

use Symfony\Component\DomCrawler\Crawler;

/**
 * @var $this->pageContent
 */
class SiteFoshanmachineryParseHandler extends AbstractSiteParseHandler implements SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float
    {
        $crawler = $crawler->filter('.card__price > .js_shop_price');
        $response = $crawler->each(function(Crawler $node, $i){
            return $node->attr('summ');
        });

        if (!empty($response)) {
            $response = array_shift($response);
            $price = str_replace([' ', '₽', '&nbsp;', ' ', 'рублей', '/шт'], '', $response);
        }
        
        return $price ?? null;
    }
}