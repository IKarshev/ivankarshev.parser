<?
namespace Ivankarshev\Parser\Orm;

use Ivankarshev\Parser\Orm\LinkTargerTable;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\{IntegerField, DatetimeField};
use Bitrix\Main\ORM\Fields\Relations\{Reference, OneToMany};
use Bitrix\Main\ORM\Query\Join;

/**
 * ID - Автоинкремент
 * LINK_ID - Ссылка на запись в таблице со ссылками
 * ADD_TO_QUEUE_TIMESTAMP - timestamp добавления записи
 */
class ParseQueueTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'IvanKarshevParser_ParseQueueTable';
    }

    public static function getMap(): array
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new IntegerField('LINK_ID'),
            new DatetimeField('ADD_TO_QUEUE_TIMESTAMP'),
        );
    }
}