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

    public static function getSectionList($filter, $select)
    {
        $result = array();
        $sectionMap = array(); // Для быстрого доступа к разделам по ID
        
        // Получаем все разделы в правильном порядке
        $dbSection = \CIBlockSection::GetList(
            array('LEFT_MARGIN' => 'ASC'),
            array_merge(
                array(
                    'ACTIVE' => 'Y',
                    'GLOBAL_ACTIVE' => 'Y'
                ),
                is_array($filter) ? $filter : array()
            ),
            false,
            array_merge(
                array(
                    'ID',
                    'IBLOCK_SECTION_ID',
                    'NAME',
                    'DEPTH_LEVEL'
                ),
                is_array($select) ? $select : array()
            )
        );
        
        // Сначала собираем все разделы в массив
        while ($arSection = $dbSection->GetNext(true, false)) {
            $sectionMap[$arSection['ID']] = $arSection;
            $result[] = &$sectionMap[$arSection['ID']];
        }
        
        // Затем для каждого раздела строим хлебные крошки
        foreach ($result as &$section) {
            $breadcrumbs = array();
            $parentId = $section['IBLOCK_SECTION_ID'];
            
            // Идём вверх по иерархии, пока не дойдём до корня
            while ($parentId > 0 && isset($sectionMap[$parentId])) {
                array_unshift($breadcrumbs, $sectionMap[$parentId]['NAME']);
                $parentId = $sectionMap[$parentId]['IBLOCK_SECTION_ID'];
            }
            
            $section['BREADCRUMBS'] = $breadcrumbs;
            $section['BREADCRUMBS_STRING'] = implode(' / ', $breadcrumbs);
            $section['FULL_NAME'] = !empty($breadcrumbs) 
                ? implode(' / ', $breadcrumbs) . ' / ' . $section['NAME'] 
                : $section['NAME'];
        }
        unset($section); // Разрываем ссылку
        
        return $result;
    }
}
?>