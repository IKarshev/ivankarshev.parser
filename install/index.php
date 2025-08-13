<?
use Bitrix\Main\ORM\EventManager as OrmEventManager;
use Bitrix\Main\{Application, EventManager, Loader};
use Bitrix\Main\Localization\Loc,
    Bitrix\Main\IO\Directory,
    CUserOptions;

use DateTime;
use Bitrix\Main\Entity;

use Ivankarshev\Parser\Orm\{LinkTargerTable, ParseQueueTable, PriceTable, CompetitorTable};

Loader::includeModule('sale');

IncludeModuleLangFile(__FILE__);

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */
Class Ivankarshev_Parser extends CModule
{
    public const MODULE_ID = 'ivankarshev.parser';

    var $MODULE_ID = "ivankarshev.parser";
    var $MODULE_VERSION;
    var $MODULE_VERSION_DATE;
    var $MODULE_NAME;
    var $MODULE_DESCRIPTION;
    var $errors;
    function __construct(){
        $this->MODULE_VERSION = "0.0.1";
        $this->MODULE_VERSION_DATE = "15.07.2025";
        $this->MODULE_NAME = "IvanKarshev - парсер";
        $this->MODULE_DESCRIPTION = "Парсер цен конкурентов";
    }

    function DoInstall(){
        global $APPLICATION;

        RegisterModule($this->MODULE_ID);

        $this->InstallDB();
        $this->InstallEvents();
        $this->InstallFiles();
        $this->InstallAgent();
        $this->InstallMailEvents();

        $APPLICATION->includeAdminFile(
            "Установочное сообщение",
            __DIR__ . '/instalInfo.php'
        );
        return true;
    }

    function DoUninstall(){
        global $APPLICATION;
        $this->UnInstallDB();
        $this->UnInstallEvents();
        $this->UnInstallFiles();
        $this->UnInstallAgent();

        UnRegisterModule($this->MODULE_ID);
        
        $APPLICATION->includeAdminFile(
            "Сообщение деинсталяции",
            __DIR__ . '/deInstalInfo.php'
        );
        return true;
    }

    function InstallMailEvents()
    {
        try {
            if (!CEventType::GetByID(IVAN_KARSHEV_PARSER_MODULE_SEND_PRICE_LIST_MAIL_EVENTNAME, 'ru')) {
                (new CEventType)->Add([
                    "LID"           => 'ru',
                    "EVENT_NAME"    => IVAN_KARSHEV_PARSER_MODULE_SEND_PRICE_LIST_MAIL_EVENTNAME,
                    "NAME"          => 'Прайслист конкурентов',
                    "DESCRIPTION"   => ''
                ]);
            }

            $searchEvent = CEventMessage::GetList(
                'id',
                'desc',
                [
                    'TYPE_ID' => [IVAN_KARSHEV_PARSER_MODULE_SEND_PRICE_LIST_MAIL_EVENTNAME]
                ]
            )->Fetch();
            if (!$searchEvent) {
                (new CEventMessage)->Add([
                    "ACTIVE"      => "Y",
                    "EVENT_NAME"  => IVAN_KARSHEV_PARSER_MODULE_SEND_PRICE_LIST_MAIL_EVENTNAME,
                    "LID"         => 's1',
                    "EMAIL_FROM"  => "#DEFAULT_EMAIL_FROM#",
                    "EMAIL_TO"    => "#DEFAULT_EMAIL_FROM#",
                    "BCC"         => "",
                    "SUBJECT"     => "Прайслист конкурентов",
                    "BODY_TYPE"   => "text",
                    "MESSAGE"     => " "
                ]);
            }
        } catch (\Throwable $th) {
            //throw $th;
        }
    }

    function InstallAgent()
    {
        \CAgent::AddAgent(
            "\\Ivankarshev\\Parser\\PriceParser\\PriceParserQueueManager::parseAgent();",
            $this->MODULE_ID,
            "N",
            60,
            "",
            "Y",
            "",
            30
        );
        \CAgent::AddAgent(
            "\\Ivankarshev\\Parser\\PriceParser\\PriceParserQueueManager::startFullParseAgent();",
            $this->MODULE_ID,
            "N",
            86400,
            "",
            "Y",
            (new DateTime())
                ->setTimeZone(new \DateTimeZone('Asia/Novosibirsk'))
                ->add(new \DateInterval("P1D"))
                ->format("d.m.Y") . ' 00:00:00',
            30
        );
        \CAgent::AddAgent(
            "\\Ivankarshev\\Parser\\PriceParser\\PriceParserQueueManager::sendPriceListEmailAgent();",
            $this->MODULE_ID,
            "N",
            86400,
            "",
            "Y",
            (new DateTime())
                ->setTimeZone(new \DateTimeZone('Asia/Novosibirsk'))
                ->add(new \DateInterval("P1D"))
                ->format("d.m.Y") . ' 06:00:00',
            30
        );
    }

    function UnInstallAgent()
    {
        \CAgent::RemoveAgent(
            "\\Ivankarshev\\Parser\\PriceParser\\PriceParserQueueManager::parseAgent();", 
            $this->MODULE_ID
        );
        \CAgent::RemoveAgent(
            "\\Ivankarshev\\Parser\\PriceParser\\PriceParserQueueManager::startFullParseAgent();", 
            $this->MODULE_ID
        );
        \CAgent::RemoveAgent(
            "\\Ivankarshev\\Parser\\PriceParser\\PriceParserQueueManager::sendPriceListEmailAgent();", 
            $this->MODULE_ID
        );
    }

    function InstallDB(){
        Loader::includeModule($this->MODULE_ID);
        if (!Application::getConnection()->isTableExists(LinkTargerTable::getTableName())) {
            LinkTargerTable::getEntity()->createDbTable();
        };
        if (!Application::getConnection()->isTableExists(ParseQueueTable::getTableName())) {
            ParseQueueTable::getEntity()->createDbTable();
        };
        if (!Application::getConnection()->isTableExists(PriceTable::getTableName())) {
            PriceTable::getEntity()->createDbTable();
        };
        if (!Application::getConnection()->isTableExists(CompetitorTable::getTableName())) {
            CompetitorTable::getEntity()->createDbTable();
        };

        // Добавляем конкурентов в БД
        CompetitorTable::add(['NAME' => 'hmru.ru']);
        CompetitorTable::add(['NAME' => 'hurakan-russia.ru']);
        CompetitorTable::add(['NAME' => 'magikon.ru']);
        CompetitorTable::add(['NAME' => 'kdm-trading.ru']);

        return true;
    }

    /**
     * @todo - сделать удаление таблицы на следущем этапе
     */
    function UnInstallDB(){
        /**/
        Loader::includeModule($this->MODULE_ID);
        if (Application::getConnection()->isTableExists(LinkTargerTable::getTableName())) {
            Application::getConnection()->dropTable(LinkTargerTable::getTableName());
        }
        if (Application::getConnection()->isTableExists(ParseQueueTable::getTableName())) {
            Application::getConnection()->dropTable(ParseQueueTable::getTableName());
        }
        if (Application::getConnection()->isTableExists(PriceTable::getTableName())) {
            Application::getConnection()->dropTable(PriceTable::getTableName());
        }
        if (Application::getConnection()->isTableExists(CompetitorTable::getTableName())) {
            Application::getConnection()->dropTable(CompetitorTable::getTableName());
        }
        
        return true;
    }

    function InstallEvents(){
        EventManager::getInstance()->registerEventHandler(
            'main',
            'OnBuildGlobalMenu',
            $this->MODULE_ID,
            'Ivankarshev\\Parser\\Main\\EventHandlers\\OnBuildGlobalMenuHandler',
            'init'
        );
        return true;
    }

    function UnInstallEvents(){
        EventManager::getInstance()->unRegisterEventHandler(
            "main",
            "OnBuildGlobalMenu",
            $this->MODULE_ID,
            'Ivankarshev\\Parser\\Main\\EventHandlers\\OnBuildGlobalMenuHandler',
            'init'
        );
        return true;
    }

    function InstallFiles(){
        CopyDirFiles(
            __DIR__ . '/admin/settings',
            Application::getDocumentRoot() . '/bitrix/admin',
            true,
            true
        );
        CopyDirFiles(
            __DIR__ . '/components',
            Application::getDocumentRoot() . '/bitrix/components',
            true,
            true
        );
        return true;
    }

    function UnInstallFiles(){
        $fileList = [
            // Страницы с настройками
            Application::getDocumentRoot() . '/bitrix/admin/requisites_to_property.php',
            Application::getDocumentRoot() . '/bitrix/admin/konturSettings.php',

            // Компоненты
            Application::getDocumentRoot() . '/bitrix/components/kontur',
        ];

        foreach ($fileList as $fileUrl) {
            Directory::deleteDirectory($fileUrl);
        }
        return true;
    }
}