<?
namespace Ivankarshev\Parser\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\ORM\Fields\Validators\UniqueValidator;
use Bitrix\Main\Entity\{IntegerField, DatetimeField};

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
            (new Entity\StringField('LINK_ID'))->addValidator(new UniqueValidator()),
            new DatetimeField('ADD_TO_QUEUE_TIMESTAMP'),
        );
    }
}