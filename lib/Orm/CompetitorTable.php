<?
namespace Ivankarshev\Parser\Orm;

use Bitrix\Main\{Entity, Event};
use Bitrix\Main\Entity\{IntegerField, StringField};
use Ivankarshev\Parser\Orm\LinkTargerTable;

use Bitrix\Main\ORM\Fields\Relations\{Reference, OneToMany};
use Bitrix\Main\ORM\Query\Join;

class CompetitorTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'IvanKarshevParser_CompetitorTable';
    }

    public static function getMap(): array
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new StringField('NAME'),
        );
    }
}