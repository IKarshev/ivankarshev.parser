<?require_once($_SERVER['DOCUMENT_ROOT'] . "/bitrix/modules/main/include/prolog_before.php");

use Bitrix\Main\Loader,
    Bitrix\Main\Localization\Loc,
    Bitrix\Main\SystemException,
    Bitrix\Main\Engine\Contract\Controllerable,
    Bitrix\Main\Application,
    Bitrix\Main\Grid\Options as GridOptions,
    Bitrix\Main\UI\PageNavigation,
    CUtil;

use Ivankarshev\Parser\Orm\LinkTargerTable;

Loader::includeModule('ivankarshev.parser');

/**
 * @author Karshev Ivan — https://github.com/IKarshev
 */
class KonturPaymentProfilesComponent extends CBitrixComponent implements Controllerable{

    protected const GRID_LIST_ID = 'LINK_LIST_GRID';
    protected const COLUMNS = [
        [
            "id" => 'ID',
            "name" => 'ID',
            "sort" => 'ID',
            "default" => true,
            'type' => 'number',
        ],
        [
            "id" => 'LINK',
            "name" => 'Наш товар',
            "sort" => 'LINK',
            "default" => true,
            'type' => 'string',
        ],
        [
            "id" => 'TARGET_LINK',
            "name" => 'Товар конкурента',
            "sort" => 'TARGET_LINK',
            "default" => true,
            'type' => 'string',
        ],
    ];
    
    public function configureActions(){
        return [
            'EditLinkData' => ['prefilters' => [],'postfilters' => []],
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
        
        // Фильтрация
        $filterOption = new Bitrix\Main\UI\Filter\Options(self::GRID_LIST_ID);
        $filterData = $filterOption->getFilter([]);
        $NormalizefilterData = $this->normalizeFilter($filterData);

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
            'filter' => $NormalizefilterData ? $NormalizefilterData : [],
        ]);
        $dataRowList = $dataRequest->fetchAll();

        foreach($dataRowList as $row){
            $rows[] = [
                'data' => [
                    'ID' => $row['ID'] ?? '',
                    'LINK' => $row['LINK'] ?? '',
                    'TARGET_LINK' => $row['TARGET_LINK'] ?? '',
                ],
                'actions' => [
                    [
                        'text' => 'Изменить',
                        'onclick' => 'editLinkData('.CUtil::PhpToJSObject([
                            'ID' => $row['ID'],
                        ]).')',
                    ],
                ],
                
            ];
        };

        $this->arResult = [
            'LIST_ID' => self::GRID_LIST_ID,
            'TOTAL_ELEMENTS' => $dataRequest->getCount(),
            'COLUMNS' => self::COLUMNS ?? [],
            'ROWS' => $rows ?? [],
            'FILTER_ARRAY' => self::COLUMNS,
        ];

        return $this->arResult;
    }

    public static function EditLinkDataAction()
    {
        /**/
        try {
            $request = \Bitrix\Main\Application::getInstance()->getContext()->getRequest();
            $LinkId = $request->getPost('ID');
            $templateFolder = $request->getPost('TEMPLATE_FOLDER');

            $dataRequest = LinkTargerTable::getList([
                'select' => ['*'],
                'filter' => ['ID' => $LinkId],
            ])->fetchAll();
            
            if (empty($dataRequest)) {
                throw new \Exception("Элемент не найден");
            } else{
                $RowItem = array_shift($dataRequest);
            }

            $arResult = [
                [
                    'CODE' => 'ID',
                    'NAME' => 'ID записи',
                    'VALUE' => $RowItem['ID'],
                    'ONLY_READ' => true,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'LINK',
                    'NAME' => 'Наш товар',
                    'VALUE' => $RowItem['LINK'],
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
                [
                    'CODE' => 'TARGET_LINK',
                    'NAME' => 'Товар конкурента',
                    'VALUE' => $RowItem['TARGET_LINK'],
                    'ONLY_READ' => false,
                    'IS_REQUIRED' => true,
                    'MULTIPLE' => false,
                ],
            ];

            ob_start();
            require(\Bitrix\Main\Application::getDocumentRoot().$templateFolder.'/form.php');
            $form = ob_get_contents();
            ob_end_clean();
    
            return $form ?? '';            
        } catch (\Throwable $th) {
            throw $th;
        }
    }
}