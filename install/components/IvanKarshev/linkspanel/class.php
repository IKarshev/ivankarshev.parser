<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\SystemException,
    Bitrix\Main\Engine\Contract\Controllerable,
    Bitrix\Main\Application,
    Bitrix\Main\Grid\Options as GridOptions,
    Bitrix\Main\UI\PageNavigation,
    Bitrix\Iblock\SectionElementTable,
    CUtil;

use Ivankarshev\Parser\Helper;
use Ivankarshev\Parser\Main\Logger;
use Ivankarshev\Parser\Options\OptionManager;
use Ivankarshev\Parser\Orm\{LinkTargerTable, PriceTable, CompetitorTable};
use Ivankarshev\Parser\PriceParser\PriceParserQueueManager;
use Ivankarshev\Parser\Exceptions\LinkExistException;


Loader::includeModule('iblock');
Loader::includeModule('ivankarshev.parser');

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */
class KonturPaymentProfilesComponent extends CBitrixComponent implements Controllerable{

    protected const GRID_LIST_ID = 'LINK_LIST_GRID';
    protected const FIELD_PREFIX = 'IVANKARSHEV_PARSER_ORM_LINK_TARGER_LINK_ITEMS_';

    protected const COLUMNS = [
        [
            "id" => 'ID',
            "name" => 'ID',
            "sort" => 'ID',
            "default" => true,
            'type' => 'number',
        ],
        [
            "id" => 'USER_FIO',
            "name" => 'Пользователь',
            "sort" => 'USER_FIO',
            "default" => true,
            'type' => 'string',
        ],
        [
            "id" => 'PRODUCT_NAME',
            "name" => 'Название товара',
            "sort" => 'PRODUCT_NAME',
            "default" => true,
            'type' => 'string',
        ],
        [
            "id" => 'PRODUCT_CODE',
            "name" => 'Код товара',
            "sort" => 'PRODUCT_CODE',
            "default" => true,
            'type' => 'string',
        ],
        [
            "id" => 'SECTION_ID',
            "name" => 'Привязка к разделу',
            "sort" => 'SECTION_ID',
            "default" => true,
            'type' => 'select',
        ],
        [
            "id" => 'COMPETITOR_STRUCTURE_IBLOCK_ID',
            "name" => 'Привязка к структуре конкурентов',
            "sort" => 'COMPETITOR_STRUCTURE_IBLOCK_ID',
            "default" => true,
            'type' => 'select',
        ],
        [
            "id" => 'LINK',
            "name" => 'Наш товар',
            "sort" => 'LINK',
            "default" => true,
            'type' => 'string',
        ],
    ];
    
    public function configureActions(){
        return [
            // 'EditLinkData' => ['prefilters' => [],'postfilters' => []],
            // 'SaveEditProfile' => ['prefilters' => [],'postfilters' => []],
        ];
    }

    public function executeComponent(){
        try{
            $this->checkModules();
            $this->getResult();
            $this->includeComponentTemplate();
        }
        catch (SystemException $e){
            ShowError($e->getMessage());
        }
    }

    protected function checkModules(){// если модуль не подключен выводим сообщение в catch (метод подключается внутри класса try...catch)
        if (!Loader::includeModule('iblock')){
            throw new SystemException(Loc::getMessage('IBLOCK_MODULE_NOT_INSTALLED'));
        }
    }


    public function onPrepareComponentParams($arParams){//обработка $arParams (метод подключается автоматически)
        return $arParams;
    }

    /**
     * @param array $filter - Исходный фильтр
     * @return array - Нормализованный фильтр
     */
    protected function normalizeFilter(array $filter)
    {
        foreach ($filter as $arkey => $arItem) {
            // Пропускаем служебные фильтры
            if( 
                in_array($arkey, ['PRESET_ID', 'FILTER_ID', 'FILTER_APPLIED', 'FIND']) ||
                strpos($arkey, '_from') !== false ||
                strpos($arkey, '_to') !== false
            ){
                continue;
            }

            $propertyCode = str_replace(['%', '_numsel', '_from', '_to' ], '', $arkey);
            $filterType = array_filter(self::COLUMNS, function($item) use ($propertyCode){
                return $item['id'] == $propertyCode;
            });
            $filterType = (!empty($filterType)) ? array_shift($filterType)['type'] : null;

            // Нормализация
            switch ($filterType ?? 'string') {
                case 'string':
                    $resultFilter['%'.$propertyCode] = $arItem;
                    break;
                case 'number':
                    $UsePrefix = !in_array($propertyCode, ['USER_ID']);

                    if( $arItem == 'exact' ){ // точно
                        $propCode = ($UsePrefix) ? '='.$propertyCode : $propertyCode;
                        $resultFilter[$propCode] = $filter[$propertyCode.'_from'];
                    };
                    if( $arItem == 'range' ){ // Диапозон
                        $propCodeFrom = ($UsePrefix) ? '>'.$propertyCode : $propertyCode;
                        $propCodeTo = ($UsePrefix) ? '<'.$propertyCode : $propertyCode;

                        $resultFilter[$propCodeFrom] = $filter[$propertyCode.'_from'];
                        $resultFilter[$propCodeTo] = $filter[$propertyCode.'_to'];
                    };
                    if( $arItem == 'more' ){ // Больше, чем
                        $propCode = ($UsePrefix) ? '>'.$propertyCode : $propertyCode;
                        $resultFilter[$propCode] = $filter[$propertyCode.'_from'];
                    };
                    if( $arItem == 'less' ){ // меньше, чем
                        $propCode = ($UsePrefix) ? '<'.$propertyCode : $propertyCode;
                        $resultFilter[$propCode] = $filter[$propertyCode.'_to'];
                    };
                    
                    break;
            }
        }

        return $resultFilter;
    }

    protected function getResult(){ // подготовка массива $arResult (метод подключается внутри класса try...catch)
        // Навигация
        $grid_options = new GridOptions(self::GRID_LIST_ID);
        
        // Сортировка
        $sort = $grid_options->GetSorting([
            'sort' => ['ID' => 'DESC'], // Дефолтные сортировки для 'стандартных' свойств
        ]);
        
        // Убираем сортировку ссылок
        if (isset($sort['sort'])) {
            foreach ($sort['sort'] as $arkey => $arItem) {
                if (str_contains($arkey, 'LINK')){
                    unset($sort['sort'][$arkey]);
                }
            }
        }

        /*
        // Фильтрация
        $filterOption = new Bitrix\Main\UI\Filter\Options(self::GRID_LIST_ID);
        $filterData = $filterOption->getFilter([]);
        $NormalizefilterData = $this->normalizeFilter($filterData);

        // Делаем фильтр по любому полю ссылки унифицированным
        foreach ($NormalizefilterData as $arkey => $arItem) {
            if (str_contains($arkey, 'LINK')) {
                unset($NormalizefilterData[$arkey]);
                $NormalizefilterData['%'.self::FIELD_PREFIX.'LINK'] = $arItem;
            }
        }
        */

        $nav_params = $grid_options->GetNavParams();
        $nav = new PageNavigation('request_list');
        $nav->allowAllRecords(true)
            ->setPageSize($nav_params['nPageSize'])
            ->initFromUri();

        // Получаем данные
        $offset = $nav->getOffset();
        $limit = $nav->getLimit();

        $dataRequest = LinkTargerTable::getList([
            'select' => ['*'],
            'offset' => $offset,
            'limit' => $limit,
            'order' => $sort['sort'],
            'count_total' => true,
            // 'filter' => $NormalizefilterData ? $NormalizefilterData : [],
            // 'filter' => ['IVANKARSHEV_PARSER_ORM_LINK_TARGER_LINK_ITEMS_IS_MAIN_LINK' => true],
        ]);
        $dataRowList = $dataRequest->fetchAll();

        $CompetitorList = array_column(
            CompetitorTable::getList(['select'=>['NAME']])->fetchAll(),
            'NAME'
        );
        $CompetitorList = array_fill_keys($CompetitorList, 0);
        
        foreach ($dataRowList as $row) {
            $linkFullData[$row['ID']] = LinkTargerTable::getList([
                'select' => [
                    'ID',
                    'SECTION_ID',
                    'COMPETITOR_SECTION_ID',
                    'LINK_' => 'LINK_ITEMS',
                    'COMPETITOR_ID' => 'COMPETITOR.ID',
                    'COMPETITOR_NAME' => 'COMPETITOR.NAME',
                ],
                'filter' => ['LINK_LINK_ID' => $row['ID']],
                'order' => ['LINK_ID' => 'ASC'],
                'runtime' => [
                    'COMPETITOR' => [
                        'data_type' => CompetitorTable::class,
                        'reference' => [
                            '=this.LINK_COMPETITOR_ID' => 'ref.ID',
                        ],
                        ['join_type' => 'LEFT']
                    ],
                ]
            ])->fetchAll();

            foreach ($linkFullData as $linkFullDataItem) {
                foreach (array_column($linkFullDataItem, 'COMPETITOR_NAME') as $competitiorName) {
                    if (array_key_exists($competitiorName, $CompetitorList)) {
                        $CompetitorList[$competitiorName]++;
                    }
                }
            }
            $columnFullList = $tempArray = array_filter($CompetitorList, function ($item) {
                return $item > 0;
            });

            $columnFullList = [];
            foreach ($tempArray as $name => $countItem) {
                $columnFullList[] = $name;
            }
        }

        foreach($dataRowList as $row){
            // Затираем переменные с предыдущей итерации
            foreach (['customRowData', 'section'] as $varName) {
                if (isset($$varName)) {
                    unset($$varName);
                }
            }

            $linkData = $linkFullData[$row['ID']];
            
            if (!empty($linkData)) {
                foreach ($linkData as $key => $value) {
                    if (($competitorKey = array_search($value['COMPETITOR_NAME'], $columnFullList)) !== null) {
                        if ($value['LINK_IS_MAIN_LINK']) {
                            $customRowData["LINK"] = $value['LINK_LINK'];
                        } else {
                            $customRowData["LINK_$competitorKey"] = $value['LINK_LINK'];
                        }
                    }

                    $fieldName = $value['LINK_IS_MAIN_LINK'] ? 'LINK' : "LINK_$key";
                    $customColumns[$fieldName] = [
                        "id" => $fieldName,
                        "name" => $value['LINK_IS_MAIN_LINK'] ? 'Наша ссылка' : $value['COMPETITOR_NAME'],
                        "sort" => $fieldName,
                        "default" => true,
                        'type' => 'string',
                    ];
                }
            }

            if ($linkData[0]['SECTION_ID'] != 0 && $sectionIblockId = (new OptionManager())->getOption('SECTION_IBLOCK_ID')) {
                $section = \Bitrix\Iblock\SectionTable::getList([
                    'filter' => [
                        'ID' => $linkData[0]['SECTION_ID'],
                        'IBLOCK_ID' => $sectionIblockId->getValue()
                    ],
                    'select' => ['ID', 'NAME'],
                    'limit' => 1,
                ])->fetch();

                $sectionName = $section
                    ? '['.$section['ID'].'] '.$section['NAME']
                    : '';
            } elseif ($linkData[0]['SECTION_ID'] == 0) {
                $sectionName = 'Без раздела';
            }

            $rows[] = [
                'data' => array_merge(
                    [
                        'ID' => $row['ID'] ?? '',
                        'USER_FIO' => ($userName = Helper::getUserFullName($row['USER_ID']))
                            ? $userName
                            : '',
                        'PRODUCT_NAME' => $row['PRODUCT_NAME'] ?? '',
                        'PRODUCT_CODE' => $row['PRODUCT_CODE'] ?? '',
                        'SECTION_ID' => $sectionName,
                    ],
                    $customRowData ?? []
                ),
                'actions' => [
                    [
                        'text' => 'Изменить',
                        'onclick' => 'editLinkData('.CUtil::PhpToJSObject([
                            'ID' => $row['ID'],
                            'COMPETITOR_SECTION_ID' => $row['COMPETITOR_SECTION_ID'],
                        ]).')',
                    ],
                    [
                        'text' => 'Удалить',
                        'onclick' => 'removeLinkItem('.CUtil::PhpToJSObject([
                            'ID' => $row['ID'],
                        ]).')',
                    ],
                ],
            ];
        };

        $this->arResult = [
            'LIST_ID' => self::GRID_LIST_ID,
            'TOTAL_ELEMENTS' => $dataRequest->getCount(),
            'COLUMNS' => array_merge(self::COLUMNS, $customColumns ?? []),
            'ROWS' => $rows ?? [],
            'FILTER_ARRAY' => array_merge(self::COLUMNS, $customColumns ?? []),
        ];

        return $this->arResult;
    }

    public static function EditLinkDataAction()
    {
        try {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $LinkId = $request->getPost('ID');
            $templateFolder = $request->getPost('TEMPLATE_FOLDER');
            $competitorSectionId = $request->getPost('COMPETITOR_SECTION_ID');
            $isNewItem = is_null($LinkId);

            $OptionManager = new OptionManager();

            if ($LinkId) {
                $dataRequest = LinkTargerTable::getList([
                    'select' => [
                        '*', 
                        'LINK_' => 'LINK_ITEMS',
                        'COMPETITOR_ID' => 'COMPETITOR.ID',
                        'COMPETITOR_NAME' => 'COMPETITOR.NAME',
                    ],
                    'filter' => ['ID' => $LinkId],
                    'runtime' => [
                        'COMPETITOR' => [
                            'data_type' => CompetitorTable::class,
                            'reference' => [
                                '=this.LINK_COMPETITOR_ID' => 'ref.ID',
                            ],
                            ['join_type' => 'LEFT']
                        ],
                    ]
                ])->fetchAll();
            }

            // Привязка к разделу
            if ($sectionIblockId = $OptionManager->getOption('SECTION_IBLOCK_ID')) {
                $rsSection = \Bitrix\Iblock\SectionTable::getList([
                    'order' => ['LEFT_MARGIN' => 'ASC'], // Важно для правильного порядка дерева
                    'filter' => [
                        'IBLOCK_ID' => $sectionIblockId->getValue(),
                        'ACTIVE' => 'Y', // если нужно только активные
                    ],
                    'select' => [
                        'ID', 
                        'CODE',
                        'NAME', 
                        'DEPTH_LEVEL',
                        'IBLOCK_SECTION_ID', // ID родительского раздела
                        'LEFT_MARGIN',
                        'RIGHT_MARGIN',
                    ],
                ])->fetchAll();
            }

            $SectionOptions[] = [
                'ID' => 0,
                'NAME' => 'Без раздела',
                'DISPLAY_NAME' => 'Без раздела',
                'DEPTH_LEVEL' => 0,
            ];

            foreach ($rsSection as $section) {
                $displayName = '';
                if ($section['DEPTH_LEVEL'] > 1) {
                    for ($i=1; $i < $section['DEPTH_LEVEL']; $i++) { 
                        $displayName .= '_';
                    }
                }
                $displayName .= '['.$section['ID'].'] '. $section['NAME'];

                $SectionOptions[] = [
                    'ID' => $section['ID'],
                    'NAME' => $section['NAME'],
                    'DISPLAY_NAME' => $displayName,
                    'DEPTH_LEVEL' => $section['DEPTH_LEVEL'],
                ];
            }

            // Привязка к структуре конкурентов
            if ($competitorStructureIblockId = $OptionManager->getOption('COMPETITOR_STRUCTURE_IBLOCK_ID')) {
                $rsSection = \Bitrix\Iblock\SectionTable::getList([
                    'order' => ['LEFT_MARGIN' => 'ASC'], // Важно для правильного порядка дерева
                    'filter' => [
                        'IBLOCK_ID' => $competitorStructureIblockId->getValue(),
                        'ACTIVE' => 'Y', // если нужно только активные
                    ],
                    'select' => [
                        'ID', 
                        'CODE',
                        'NAME', 
                        'DEPTH_LEVEL',
                        'IBLOCK_SECTION_ID', // ID родительского раздела
                        'LEFT_MARGIN',
                        'RIGHT_MARGIN',
                    ],
                ])->fetchAll();
            }

            $competitorSectionOptions[] = [
                'ID' => 0,
                'NAME' => 'Без раздела',
                'DISPLAY_NAME' => 'Без раздела',
                'DEPTH_LEVEL' => 0,
            ];

            foreach ($rsSection as $section) {
                $displayName = '';
                if ($section['DEPTH_LEVEL'] > 1) {
                    for ($i=1; $i < $section['DEPTH_LEVEL']; $i++) { 
                        $displayName .= '_';
                    }
                }
                $displayName .= '['.$section['ID'].'] '. $section['NAME'];

                $competitorSectionOptions[] = [
                    'ID' => $section['ID'],
                    'NAME' => $section['NAME'],
                    'DISPLAY_NAME' => $displayName,
                    'DEPTH_LEVEL' => $section['DEPTH_LEVEL'],
                ];
            }

            $arResult = [
                [
                    'CODE' => 'ID',
                    'NAME_ATTRIBUTE' => 'ID',
                    'NAME' => 'ID записи',
                    'VALUE' => $dataRequest[0]['ID'] ?? '',
                    'TYPE' => 'string',
                    'ONLY_READ' => true,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'PRODUCT_NAME',
                    'NAME_ATTRIBUTE' => 'PRODUCT_NAME',
                    'NAME' => 'Название',
                    'VALUE' => $dataRequest[0]['PRODUCT_NAME'] ?? '',
                    'TYPE' => 'string',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'PRODUCT_CODE',
                    'NAME_ATTRIBUTE' => 'PRODUCT_CODE',
                    'NAME' => 'Код товара',
                    'VALUE' => $dataRequest[0]['PRODUCT_CODE'] ?? '',
                    'TYPE' => 'string',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'SECTION_ID',
                    'NAME_ATTRIBUTE' => 'SECTION_ID',
                    'NAME' => 'Привязка к разделу',
                    'VALUE' => $dataRequest[0]['SECTION_ID'] ?? '',
                    'OPTIONS' => $SectionOptions,
                    'TYPE' => 'select',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => false,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'COMPETITOR_STRUCTURE_IBLOCK_ID',
                    'NAME_ATTRIBUTE' => 'COMPETITOR_STRUCTURE_IBLOCK_ID',
                    'NAME' => 'Привязка к структуре конкурентов',
                    'VALUE' => $competitorSectionId,
                    'OPTIONS' => $competitorSectionOptions,
                    'TYPE' => 'select',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => false,
                    'MULTIPLE' => false,
                ],
            ];

            if ($competitorSectionId && $competitorStructureIblockId) {
                $IblockClass = \Bitrix\Iblock\Iblock::wakeUp($competitorStructureIblockId->getValue())->getEntityDataClass();

                $sectionCompetitorIdList = SectionElementTable::getList([
                    'select' => ['IBLOCK_ELEMENT_ID'],
                    'filter' => [
                        '=IBLOCK_SECTION_ID' => $competitorSectionId
                    ],
                ])->fetchAll();

                if (!empty($sectionCompetitorIdList)) {
                    $competitorSectionItemsQuery = $IblockClass::getList([
                        'select' => ['ID', 'NAME'],
                        'filter' => [
                            'ID' =>  array_column($sectionCompetitorIdList, 'IBLOCK_ELEMENT_ID'),
                        ],
                    ])->fetchAll();
                }

                if (!empty($competitorSectionItemsQuery)) {
                    $competitorSectionItems = array_column($competitorSectionItemsQuery, 'NAME');
                    array_unshift($competitorSectionItems, 'hmru.ru');
                }
            }

            $competitorSectionItems = isset($competitorSectionItems) 
                ? $competitorSectionItems
                : null;

            $siteData = function(string $siteName) use ($dataRequest, $competitorSectionItems){
                if ($competitorSectionItems!==null && !in_array($siteName, $competitorSectionItems)) {
                    return false;
                }

                $sitekey = is_array($dataRequest) && !empty($dataRequest)
                    ? array_search($siteName, array_column($dataRequest, 'COMPETITOR_NAME'))
                    : null;
                return [
                    'CODE' => is_int($sitekey)
                        ? 'LINK_'.$dataRequest[$sitekey]['LINK_ID']
                        : '',

                    'NAME_ATTRIBUTE' => is_int($sitekey)
                        ? "LINK[$siteName]"
                        : "NEW_LINK[$siteName]",
                    'NAME' => $siteName,
                    'VALUE' => is_int($sitekey)
                        ? $dataRequest[$sitekey]['LINK_LINK']
                        : '',
                    'TYPE' => 'string',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ];
            };

            // Добавляем сайты
            $siteList = [
                'hmru.ru',
                'hurakan-russia.ru',
                'magikon.ru',
                'kdm-trading.ru',
                'voltekgroup.com',
                // 'hurakan.ru',
                // 'fartov.com',
                'bronko.ru',
                'агрозавод.рф',
                // 'airhot.ru',
                'assum.ru',
                // '7pack.pro',
                'p-z-o.com',
                // 'bristolgroup.ru',
            ];
            foreach ($siteList as $siteDomain) {
                if ($siteInfo = $siteData($siteDomain)) {
                    $arResult[] = $siteInfo;
                }
            }

            ob_start();
            require(\Bitrix\Main\Application::getDocumentRoot().$templateFolder.'/form.php');
            $form = ob_get_contents();
            ob_end_clean();
            
            return $form ?? '';
        } catch (\Throwable $th) {
            throw $th;
        }
    }

    public static function SaveEditProfileAction()
    {
        try {
            global $USER;

            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            // $post = $request->getPostList();

            $itemId = $request->getPost('ID');
            $isNew = $request->getPost('is_new');
            $productName = $request->getPost('PRODUCT_NAME');
            $productCode = $request->getPost('PRODUCT_CODE');
            $sectionId = $request->getPost('SECTION_ID');
            $competitorSectionId = $request->getPost('COMPETITOR_STRUCTURE_IBLOCK_ID');
            $itemLink = $request->getPost('LINK');
            $newLink = $request->getPost('NEW_LINK');
            // Проверка на дубль
            $checkLink = $isNew ? $newLink : $itemLink;
            $checkLink = array_filter($checkLink, function($item){
                return $item!=='';
            });

            $searchLink = LinkTargerTable::getList([
                'select' => [
                    '*',
                    'LINK_' => 'LINK_ITEMS',
                    'COMPETITOR_ID' => 'COMPETITOR.ID',
                    'COMPETITOR_NAME' => 'COMPETITOR.NAME',
                ],
                'runtime' => [
                    'COMPETITOR' => [
                        'data_type' => CompetitorTable::class,
                        'reference' => [
                            '=this.LINK_COMPETITOR_ID' => 'ref.ID',
                        ],
                        ['join_type' => 'LEFT']
                    ],
                ],
                'filter' => [
                    'PRODUCT_NAME' => $productName,
                    'LINK_LINK' => $checkLink,
                ],
            ])->fetchAll();

            if (!empty($searchLink)) {
                $linkCompetitor = implode(', ', array_unique(array_column($searchLink, 'COMPETITOR_NAME')));
                $noticeMsg = "Ссылки ($linkCompetitor) для товара $productName уже существуют.";
                if ($isNew) {
                    throw new LinkExistException($noticeMsg);
                } else {
                    $noticeMsg .= " Элемент был изменен.";
                }
            }

            $CompetitorTableData = CompetitorTable::getList([
                'select' => ['*'],
            ])->fetchAll();

            $getCompetitorid = function (string $CompetitorName) use ($CompetitorTableData): ?int {
                if (($searchKey = array_search($CompetitorName, array_column($CompetitorTableData, 'NAME'))) !== null) {
                    return $CompetitorTableData[$searchKey]['ID'];
                }
                return null;
            };

            if (!$itemId && $isNew) {
                $NewId = LinkTargerTable::add([
                    'USER_ID' => $USER->getId(),
                    'PRODUCT_NAME' => $productName ?? '',
                    'PRODUCT_CODE' => $productCode ?? '',
                    'SECTION_ID' => $sectionId ?? '',
                    'COMPETITOR_SECTION_ID' => $competitorSectionId ?? '',
                ]);
                $i = 0;
                foreach ($newLink as $competitorName => $newLinkItem) {
                    if (trim($newLinkItem)==='') {
                        continue;
                    }
                    if ($competitorId = $getCompetitorid($competitorName)) {
                        PriceTable::add([
                            'LINK_ID' => $NewId->getId(),
                            'COMPETITOR_ID' => $competitorId,
                            'LINK' => $newLinkItem,
                            'IS_MAIN_LINK' => $i === 0,
                        ]);
                        $i++;
                    }
                }
                PriceParserQueueManager::addItemToQueue($NewId->getId());
            } elseif($itemId) {
                LinkTargerTable::update(
                    $itemId,
                    [
                        'USER_ID' => $USER->getId(),
                        'PRODUCT_NAME' => $productName,
                        'PRODUCT_CODE' => $productCode,
                        'SECTION_ID' => $sectionId,
                        'COMPETITOR_SECTION_ID' => $competitorSectionId ?? '',
                    ]
                );
                $i = 0;
                foreach ($itemLink as $competitorName => $link) {

                    $itemLinkData = PriceTable::getList([
                        'select' => [
                            '*',
                            'COMPETITOR_NAME' => 'COMPETITOR.NAME',
                        ],
                        'filter' => [
                            'LINK_ID' => $itemId,
                            'COMPETITOR_NAME' => $competitorName,
                        ],
                        'limit' => 1,
                    ])->fetch();

                    if (trim($link)!==''){
                        PriceTable::update(
                            $itemLinkData['ID'],
                            [
                                'LINK' => $link,
                                'IS_MAIN_LINK' => $i === 0,
                            ]
                        );
                        $i++;
                    } else {
                        PriceTable::delete($itemLinkData['ID']);
                    }
                }
                if (is_array($newLink) && !empty($newLink)) {
                    $i = 0;
                    foreach ($newLink as $competitorName => $newLinkItem) {
                        if (trim($newLinkItem)==='') {
                            continue;
                        };
                        if ($competitorId = $getCompetitorid($competitorName)) {
                            PriceTable::add([
                                'LINK_ID' => $itemId,
                                'COMPETITOR_ID' => $competitorId,
                                'LINK' => $newLinkItem,
                                'IS_MAIN_LINK' => $i === 0 && (empty($itemLink) || !isset($itemLink)),
                            ]);
                            $i++;
                        }
                    }
                }
                PriceParserQueueManager::addItemToQueue($itemId);
            }

            if (isset($noticeMsg)) {
                return ['notice' => $noticeMsg];
            }
        } catch (LinkExistException $th) {
            Logger::notice($th->getMessage());
            throw new \Exception('Ошибка: '.$th->getMessage());
        } catch (\Throwable $th) {
            Logger::error($th->getMessage());
            throw new \Exception('Неизвестная ошибка. Обратитесь к администратору');
        }
    }

    public static function deleteElementAction(): void
    {
        try {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $itemId = $request->getPost('ID');
            
            if ($itemId) {
                LinkTargerTable::delete($itemId);

                $priceIdList = PriceTable::getList([
                    'select' => ['ID'],
                    'filter' => ['LINK_ID' => $itemId],
                ])->fetchAll();
                
                if (!empty($priceIdList)) {
                    foreach (array_column($priceIdList, 'ID') as $currentItemId) {
                        PriceTable::delete($currentItemId);
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}