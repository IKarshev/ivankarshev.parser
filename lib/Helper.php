<?
namespace Ivankarshev\Parser;

use Bitrix\Main\Loader,
    CIBlockSection,
    CUserTypeEntity,
    CIBlockProperty,
    CIBlockElement,
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

    /**
     * @param float $biggestNumber - наибольшее значение
     * @param float $lowwerNumber - наибольшее значение
     * @return float - Процент разницы между числами
     */
    public static function getPersentDiff(float $biggestNumber, float $lowwerNumber): float
    {
        $value = (($lowwerNumber - $biggestNumber) / $biggestNumber) * 100;
        return abs($value);
    }

    /**
     * Добавляем новый пункт в свойство типа список если его нет, проставляет активность пункта
     * 
     * @param int $IblockID — id-инфоблока
     * @param string $PropetyCode — символьный код свойства
     * @param string $ValueName — Название значение свойства
     * 
     * @return int — id значения свойства
     */
    public static function AddListProperety( int $IblockID, string $PropetyCode, string $ValueName ){
        $PropValueID = self::GetListPropValueID( $IblockID, $PropetyCode, $ValueName )[0];
        if( empty($PropValueID) ){
            // Создаем новое значение
            $property = CIBlockProperty::GetByID($PropetyCode, $IblockID)->GetNext();
            $ibpenum = new CIBlockPropertyEnum;
            
            if($PropValueID = $ibpenum->Add([
                'PROPERTY_ID' => $property['ID'],
                "VALUE" => $ValueName, // имя
                "DEF" => "", // по-умолчанию Y/N
                "SORT" => "100" // сортировка
            ]));
            return $PropValueID;
        };
        return $PropValueID;
    }

    /**
     * Получаем ID значений свойства тип список
     * 
     * @param int $IblockID — id-инфоблока
     * @param string $PropertyCode — символьный код свойства
     * @param string $ValueName — название элемента списка
     * 
     * @return array массив с id элементов свойства типа 'список' 
     */
    public static function GetListPropValueID( int $IblockID, string $PropertyCode, string $ValueName):array
    {
        $result = array_column(\Bitrix\Iblock\PropertyEnumerationTable::getList([
            "select" => ["ID"],
            "filter" => [
                "PROPERTY.IBLOCK_ID" => $IblockID,
                "PROPERTY.CODE" => $PropertyCode,
                "VALUE" => $ValueName
            ],
        ])->fetchAll(), 'ID');

        return $result;
    }

    /**
     * @return bool — флаг выгрузки из 1С
     */
    public static function IsUnloading():bool
    {
        return ($_SERVER["SCRIPT_NAME"] == '/bitrix/admin/1c_exchange.php' || (isset($_REQUEST['mode']) && $_REQUEST['mode'] == 'import'));    
    }
}
?>