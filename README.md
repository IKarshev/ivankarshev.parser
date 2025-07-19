# ivankarshev.parser

## Установка

1. Установить зависимости `composer` и подключить в `init.php`.
```json
{
    "require": {
        "symfony/dom-crawler": "^7.3",
        "symfony/css-selector": "^7.3",
        "guzzlehttp/guzzle": "^7.9"
    }
}
```

2. Подключить, как gitsubmodule к проекту например в `/local/modules/ivankarshev.parser`.
3. Установить модуль через административную панель.

## Получение настроек модуля:
```php
use Ivankarshev\Parser\Options\OptionManager;
\Bitrix\Main\Loader::includeModule('ivankarshev.parser');

$optionManager = new OptionManager();
if ($iblockIdProp = $OptionManager->getOption('IBLOCK_ID')) {
    /** @var mixed iblockIdPropValue - значение */
    $iblockIdPropValue = $iblockIdProp->getValue();

    /** @var bool iblockIdPropIsMultiple - множественное ли свойство */
    $iblockIdPropIsMultiple = $iblockIdProp->isMultiple();

    /** @var bool iblockIdPropIsRequired - обязательное ли свойство */
    $iblockIdPropIsRequired = $iblockIdProp->isRequired();
}
```