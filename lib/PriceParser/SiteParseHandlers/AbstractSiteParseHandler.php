<?
namespace Ivankarshev\Parser\PriceParser\SiteParseHandlers;

use GuzzleHttp\Client as GuzzleHttpClient;
use Symfony\Component\DomCrawler\Crawler;

abstract class AbstractSiteParseHandler
{
    protected $url;
    protected $crawler;

    public function __construct(string $url) {
        $this->url = $url;

        $response = (new GuzzleHttpClient())->get($this->url);
        $content = $response->getBody()->getContents();

        $this->crawler = new Crawler($content);
    }

    public function getPrice(): ?float
    {
        return $this->parsePrice($this->crawler);
    }
}