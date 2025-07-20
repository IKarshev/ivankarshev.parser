<?
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;
use Symfony\Component\DomCrawler\Crawler;
interface SiteParseHandlerInterface
{
    public function parsePrice(Crawler $crawler): ?float;
}