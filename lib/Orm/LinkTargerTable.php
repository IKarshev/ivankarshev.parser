<?
namespace Ivankarshev\Parser\Orm;

use Bitrix\Main\{Entity, Event};
use Bitrix\Main\Entity\{IntegerField, StringField};
use Ivankarshev\Parser\Orm\{PriceTable, CompetitorTable};

use Bitrix\Main\ORM\Fields\Relations\{Reference, OneToMany};
use Bitrix\Main\ORM\Query\Join;

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
            (new Reference('LINK_ITEMS',
					PriceTable::class,
					Join::on('this.ID', 'ref.LINK_ID')
                ))->configureJoinType('inner'),
            new StringField('PRODUCT_NAME'),
            new StringField('PRODUCT_CODE'),
            new IntegerField('SECTION_ID'),
        );
    }
}