<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\SystemException,
    Bitrix\Main\Engine\Contract\Controllerable,
    Bitrix\Main\Application,
    Bitrix\Main\Grid\Options as GridOptions,
    Bitrix\Main\UI\PageNavigation,
    CUtil;

use Ivankarshev\Parser\Orm\{LinkTargerTable, PriceTable};

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
            "id" => 'PRODUCT_NAME',
            "name" => 'Название товара',
            "sort" => 'PRODUCT_NAME',
            "default" => true,
            'type' => 'string',
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

        ob_start();
        foreach($dataRowList as $row){
            if (isset($customRowData)) {
                unset($customRowData);
            }

            $linkData = LinkTargerTable::getList([
                'select' => ['ID', 'LINK_' => 'LINK_ITEMS'],
                'filter' => ['LINK_LINK_ID' => $row['ID']],
                'order' => ['LINK_ID' => 'ASC']
            ])->fetchAll();
            
            if (!empty($linkData)) {
                foreach ($linkData as $key => $value) {
                    $fieldName = $value['LINK_IS_MAIN_LINK'] ? 'LINK' : "LINK_$key";

                    $customRowData[$fieldName] = $value['LINK_LINK'];
                    $customColumns[$fieldName] = [
                        "id" => $fieldName,
                        "name" => $value['LINK_IS_MAIN_LINK'] ? 'Наша ссылка' : "Ссылка $key",
                        "sort" => $fieldName,
                        "default" => true,
                        'type' => 'string',
                    ];
                }
            }

            $rows[] = [
                'data' => array_merge(
                    [
                        'ID' => $row['ID'] ?? '',
                        'PRODUCT_NAME' => $row['PRODUCT_NAME'] ?? '',
                    ],
                    $customRowData ?? []
                ),
                'actions' => [
                    [
                        'text' => 'Изменить',
                        'onclick' => 'editLinkData('.CUtil::PhpToJSObject([
                            'ID' => $row['ID'],
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
            'COLUMNS' => array_merge(self::COLUMNS, $customColumns),
            'ROWS' => $rows ?? [],
            'FILTER_ARRAY' => array_merge(self::COLUMNS, $customColumns),
        ];

        return $this->arResult;
    }

    public static function EditLinkDataAction()
    {
       
        try {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $LinkId = $request->getPost('ID');
            $templateFolder = $request->getPost('TEMPLATE_FOLDER');
            $isNewItem = is_null($LinkId);

            if ($LinkId) {
                $dataRequest = LinkTargerTable::getList([
                    'select' => ['*', 'LINK_' => 'LINK_ITEMS'],
                    'filter' => ['ID' => $LinkId],
                ])->fetchAll();

                if (empty($dataRequest)) {
                    $dataRequest2 = LinkTargerTable::getList([
                        'select' => ['*'],
                        'filter' => ['ID' => $LinkId],
                    ])->fetchAll();

                    if (empty($dataRequest2)) {
                        throw new \Exception("Элемент не найден");
                    } else {
                        $dataRequest = $dataRequest2;
                    }
                };
            }

            $arResult = [
                [
                    'CODE' => 'ID',
                    'NAME_ATTRIBUTE' => 'ID',
                    'NAME' => 'ID записи',
                    'VALUE' => $dataRequest[0]['ID'] ?? '',
                    'ONLY_READ' => true,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'PRODUCT_NAME',
                    'NAME_ATTRIBUTE' => 'PRODUCT_NAME',
                    'NAME' => 'Название',
                    'VALUE' => $dataRequest[0]['PRODUCT_NAME'] ?? '',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
            ];

            foreach ($dataRequest as $arkey => $arItem) {
                if (!isset($arItem['LINK_ID'])) {
                    continue;
                }
                $arResult[] = [
                    'CODE' => 'LINK_'.$arItem['LINK_ID'],
                    'NAME_ATTRIBUTE' => 'LINK['.$arItem['LINK_ID'].']',
                    'NAME' => $arkey == 0 ? 'Основная ссылка' : 'Ссылка',
                    'VALUE' => $arItem['LINK_LINK'] ?? '',
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ];
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

    public static function SaveEditProfileAction(): void
    {
        try {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $post = $request->getPostList();

            $itemId = $request->getPost('ID');
            $isNew = $request->getPost('is_new');
            $productName = $request->getPost('PRODUCT_NAME');
            $itemLink = $request->getPost('LINK');
            $newLink = $request->getPost('NEW_LINK');

            if (!$itemId && $isNew) {
                $NewId = LinkTargerTable::add([
                    'PRODUCT_NAME' => $productName ?? '',
                ]);
                $i = 0;
                foreach ($newLink as $newLinkItem) {
                    PriceTable::add([
                        'LINK_ID' => $NewId->getId(),
                        'LINK' => $newLinkItem,
                        'IS_MAIN_LINK' => $i === 0,
                    ]);
                    $i++;
                }
            } elseif($itemId) {
                LinkTargerTable::update(
                    $itemId,
                    [
                        'PRODUCT_NAME' => $productName,
                    ]
                );
                $i = 0;
                foreach ($itemLink as $linkId => $link) {
                    PriceTable::update(
                        $linkId,
                        [
                            'LINK' => $link,
                            'IS_MAIN_LINK' => $i === 0,
                        ]
                    );
                    $i++;
                }
                if (is_array($newLink) && !empty($newLink)) {
                    $i = 0;
                    foreach ($newLink as $newLinkItem) {
                        PriceTable::add([
                            'LINK_ID' => $itemId,
                            'LINK' => $newLinkItem,
                            'IS_MAIN_LINK' => $i === 0 && (empty($itemLink) || !isset($itemLink)),
                        ]);
                        $i++;
                    }
                }
            }
        } catch (\Throwable $th) {
            throw $th;
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