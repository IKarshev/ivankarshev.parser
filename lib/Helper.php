<?
namespace Ivankarshev\Parser;

use Bitrix\Main\Loader,
    CIBlockProperty,
    CIBlockPropertyEnum;

Loader::includeModule('iblock');
Loader::IncludeModule("main");

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */
Class Helper
{
    /**
     * @param int $Price — цена 
     * @param string $CurrencyCode — Код валюты (например: RUB)
     * 
     * @return string Отформатированный ценник
     */
    public static function GetFormatedPrice( $Price, bool $PrintCurrency = true, $CurrencyCode = 'RUB' ):string{
        $result = rtrim(rtrim(number_format($Price, 2, '.', ' '), '\0'), '\.');
        if( $PrintCurrency ){
            $result = str_replace('#', $result, CCurrencyLang::GetCurrencyFormat($CurrencyCode)['FORMAT_STRING']);
        };

        return $result;
    }

    public static function parseUrlAll(string $url){
        $url = substr($url,0,4)=='http'? $url: 'http://'.$url;
        $d = parse_url($url);
        $tmp = explode('.',$d['host']);
        $n = count($tmp);
        if ($n>=2){
            if ($n==4 || ($n==3 && strlen($tmp[($n-2)])<=3)){
                $d['domain'] = $tmp[($n-3)].".".$tmp[($n-2)].".".$tmp[($n-1)];
                $d['domainX'] = $tmp[($n-3)];
            } else {
                $d['domain'] = $tmp[($n-2)].".".$tmp[($n-1)];
                $d['domainX'] = $tmp[($n-2)];
            }
        }
        return $d;
    }

    public static function GetModuleDirrectory():string{
        $modulePath = str_replace($_SERVER['DOCUMENT_ROOT'], '', realpath(__DIR__));
        if (strpos($modulePath, DIRECTORY_SEPARATOR . 'bitrix' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR) !== false) {
            // Модуль в /bitrix/modules/
            return "bitrix";
        } elseif (strpos($modulePath, DIRECTORY_SEPARATOR . 'local' . DIRECTORY_SEPARATOR . 'modules' . DIRECTORY_SEPARATOR) !== false) {
            // Модуль в /local/modules/
            return "local";
        };
    }
}
?>