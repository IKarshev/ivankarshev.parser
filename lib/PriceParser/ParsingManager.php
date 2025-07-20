<?php
namespace Ivankarshev\Parser\PriceParser;

use Exception;
use Ivankarshev\Parser\Exceptions\ParseClassNotFoundException;
use Ivankarshev\Parser\Helper;

class ParsingManager
{
    protected function getSiteDomain(string $url): string
    {
        return Helper::parseUrlAll($url)['domainX'];
    }

    protected function getParseClass(string $url): ?string
    {
        $domain = self::getSiteDomain($url);
        $domain = str_replace([' ', '-', '_'], '', $domain);
        $domain = ucfirst(strtolower($domain));
        $class = "\\Ivankarshev\\Parser\\PriceParser\\SiteParseHandlers\\Site".$domain."ParseHandler";
        if (class_exists($class)) {
            return $class;
        } else {
            return null;
        }
    }

    public function getSiteParsingClass(string $url)
    {
        if ($parseClass = $this->getParseClass($url)) {
            return new $parseClass($url);
        } else {
            $domain = self::getSiteDomain($url);
            throw new ParseClassNotFoundException("Класс для парсера сайта $domain не найден");
        }
    }
}