<?if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();

use Bitrix\Main\Grid\Options as GridOptions,
    Bitrix\Main\UI\PageNavigation;

use Ivankarshev\Parser\Helper;

\Bitrix\Main\Loader::includeModule('ivankarshev.parser');

CJSCore::Init(['jquery']);
$this->addExternalJS($templateFolder."/assets/js/jquery.validate.min.js");
$this->addExternalJS($templateFolder."/assets/js/jqueryValidateCustomRules.js");

// Настройки пагинации
$grid_options = new GridOptions($arResult['LIST_ID']);
$nav_params = $grid_options->GetNavParams();
$nav = new PageNavigation('request_list');
$nav->allowAllRecords(true)
    ->setPageSize($nav_params['nPageSize'])
    ->initFromUri();
$nav->setRecordCount( $arResult['TOTAL_ELEMENTS'] );

?>

<div class="filter-btn-row">
	<div class="ui-filter">
		<?/*$APPLICATION->IncludeComponent('bitrix:main.ui.filter', '', [
			'FILTER_ID' => $arResult['LIST_ID'],
			'GRID_ID' => $arResult['LIST_ID'],
			'FILTER' => $arResult['FILTER_ARRAY'],
			'ENABLE_LIVE_SEARCH' => true,
			'ENABLE_LABEL' => true
		]);*/?>
	</div>
	<div class="btn-row">
		<a href="/<?=Helper::GetModuleDirrectory() . '/modules/ivankarshev.parser/script/download-file.php?format=XLS'?>" class="ui-btn ui-btn-success">Скачать xls</a>
		<button class="ui-btn ui-btn-primary js-new-item-popup">Добавить ссылку</button>
	</div>
</div>

<div class="ul-grid">
	<?$APPLICATION->IncludeComponent('bitrix:main.ui.grid', '', [
		'GRID_ID' => $arResult['LIST_ID'],
		'COLUMNS' => $arResult['COLUMNS'],
		'ROWS' => $arResult['ROWS'],
		'SHOW_ROW_CHECKBOXES' => false,
		'NAV_OBJECT' => $nav,
		'AJAX_ID' => \CAjax::getComponentID('bitrix:main.ui.grid', '.default', ''),
		'PAGE_SIZES' =>  [
			['NAME' => '1', 'VALUE' => '1'],
			['NAME' => '2', 'VALUE' => '2'],

			['NAME' => '20', 'VALUE' => '20'],
			// ['NAME' => '50', 'VALUE' => '50'],
			// ['NAME' => '100', 'VALUE' => '100']
		],
		'AJAX_MODE' => 'Y',
		'AJAX_OPTION_JUMP'          => 'N',
		'AJAX_OPTION_HISTORY'       => 'N',
		'SHOW_CHECK_ALL_CHECKBOXES' => false,
		'SHOW_ROW_ACTIONS_MENU'     => true,
		'SHOW_GRID_SETTINGS_MENU'   => true,
		'SHOW_NAVIGATION_PANEL'     => true,
		'SHOW_PAGINATION'           => true,
		'SHOW_SELECTED_COUNTER'     => true,
		'SHOW_TOTAL_COUNTER'        => true,
		'SHOW_PAGESIZE'             => true,
		'SHOW_ACTION_PANEL'         => true,
		'ALLOW_COLUMNS_SORT'        => true,
		'ALLOW_COLUMNS_RESIZE'      => true,
		'ALLOW_HORIZONTAL_SCROLL'   => true,
		'ALLOW_SORT'                => true,
		'ALLOW_PIN_HEADER'          => true,
	]);?>
</div>

<div id="EditProfileContainer" style="display:none;">
	<pre>test</pre>
</div>

<script>
	// Передаем значения в JS
	var arResult = <?=CUtil::PhpToJSObject($arResult)?>;
	var templateFolder = <?=CUtil::PhpToJSObject($templateFolder)?>;
</script>