<?
namespace Ivankarshev\Parser\Orm;

use Ivankarshev\Parser\Orm\LinkTargerTable;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\{IntegerField, BooleanField, FloatField, DatetimeField};
use Bitrix\Main\ORM\Fields\Relations\OneToMany;

/**
 * ID - Автоинкремент
 * LINK_TARGET - Ссылка на запись в таблице со ссылками
 * IS_TARGET_LINK - true, если товар конкурента. false, если наш товар
 * UPDATE_TIMESTAMP - timestamp добавления записи
 */
class PriceTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'IvanKarshevParser_Price';
    }

    public static function getMap(): array
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new IntegerField('LINK_TARGET'),
            new BooleanField('IS_TARGET_LINK'),
            new FloatField('PRICE', [
                'nullable' => true
            ]),
            new DatetimeField('UPDATE_TIMESTAMP'),
        );
    }
}