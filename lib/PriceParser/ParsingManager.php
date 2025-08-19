<?php
namespace Ivankarshev\Parser\PriceParser;

use Exception;
use Ivankarshev\Parser\Exceptions\ParseClassNotFoundException;
use Ivankarshev\Parser\Helper;

class ParsingManager
{
    protected function getSiteDomain(string $url): ?string
    {
        return Helper::parseUrlAll($url)['domainX'];
    }

    protected function getParseClass(string $url): ?string
    {
        $domain = self::getSiteDomain($url);        
        $parserClass = function(string $domain): string {
            $className = str_replace([' ', '-', '_'], '', $domain);
            $className = ucfirst(strtolower($className));
            return "\\Ivankarshev\\Parser\\PriceParser\\SiteParseHandlers\\Site".$className."ParseHandler";
        };

        if (class_exists($class = $parserClass($domain))) {
            return $class;
        } elseif(class_exists($class = $parserClass(Helper::translit($domain)))) {
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
            $domain = (($siteDomain = self::getSiteDomain($url))!==null) 
                ? trim($siteDomain) 
                : 'null';

            throw new ParseClassNotFoundException("Класс для парсера сайта $domain не найден");
        }
    }
}