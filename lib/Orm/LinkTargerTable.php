<?
namespace Ivankarshev\Parser\Orm;

use Bitrix\Main\Entity;
use Bitrix\Main\Entity\{IntegerField, StringField};

class LinkTargerTable extends Entity\DataManager
{
    public static function getTableName(): string
    {
        return 'IvanKarshevParser_LinkTarger';
    }

    public static function getMap(): array
    {
        return array(
            new IntegerField('ID', array('primary' => true, 'autocomplete' => true)),
            new StringField('LINK'),
            new StringField('TARGET_LINK'),
        );
    }
}