<?php
/**
 * @var array $arResult - данные
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body>
    <section>
        <table style="border: 1pt solid #000000;">
            <thead>
                <tr style="border-bottom: 1pt solid #000000;">
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">Раздел/Подраздел</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">Пользователь</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">Наименование</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">Код товара</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">HMRU - Основная ссылка</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">Цена</div>
                    </td>
                    <?foreach ($arResult['COLUMNS'] as $column):?>
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="head"><?=$column?></div>
                        </td>
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="head">Цена</div>
                        </td>
                    <?endforeach;?>
                </tr>
            </thead>
            <tbody>
                <?
                $sectionIteration = 0;
                foreach ($arResult['SECTIONS'] as $section):
                    $rowsIteration = 0;
                    ?>

                    <?foreach ($section['ROWS'] as $arItem):?>
                        <tr style="border-bottom: 1pt solid #000000;">
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=($rowsIteration===0) ? $section['FULL_NAME'] : ''?></div>
                            </td>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=$arItem['MAIN_LINK']['USER_NAME']?></div>
                            </td>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=$arItem['MAIN_LINK']['PRODUCT_NAME']?></div>
                            </td>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=$arItem['MAIN_LINK']['PRODUCT_CODE']?></div>
                            </td>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=$arItem['MAIN_LINK']['LINK_LINK']?></div>
                            </td>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=$arItem['MAIN_LINK']['LINK_PRICE']?></div>
                            </td>

                            <?foreach ($arResult['COLUMNS'] as $columnKey => $columnItem):?>
                                <?if (($targetItemKey = array_search($columnItem, array_column($arItem['TARGET_LINKS'], 'COMPETITOR_NAME')))!==false):

                                    if ($arItem['MAIN_LINK']['LINK_PRICE'] === null || $arItem['TARGET_LINKS'][$targetItemKey]['LINK_PRICE'] === null) {
                                        $priceStyles = 'background-color:#FD6A02;text-align:right;';
                                        $linkStyles = 'background-color:#FD6A02;';
                                    } elseif ($arItem['MAIN_LINK']['LINK_PRICE'] < $arItem['TARGET_LINKS'][$targetItemKey]['LINK_PRICE']) {
                                        $priceStyles = 'background-color:green;text-align:right;';
                                        $linkStyles = '';
                                    } elseif ($arItem['MAIN_LINK']['LINK_PRICE'] > $arItem['TARGET_LINKS'][$targetItemKey]['LINK_PRICE']) {
                                        $priceStyles = 'background-color:red;text-align:left;';
                                        $linkStyles = '';
                                    } else {
                                        $priceStyles  = $linkStyles = '';
                                    }                                    
                                    
                                    ?>
                                    <td style="padding:0 4px;border-right: 1pt solid #000000;<?=$linkStyles?>">
                                        <div class="item"><?=$arItem['TARGET_LINKS'][$targetItemKey]['LINK_LINK']?></div>
                                    </td>
                                    <td style="padding:0 4px;border-right: 1pt solid #000000;<?=$priceStyles?>">
                                        <div class="item"><?=$arItem['TARGET_LINKS'][$targetItemKey]['LINK_PRICE']?></div>
                                    </td>
                                <?else:?>
                                    <td class="empty" style="padding:0 4px;border-right: 1pt solid #000000;"><div class="item"></div></td>
                                    <td class="empty" style="padding:0 4px;border-right: 1pt solid #000000;"><div class="item"></div></td>
                                <?endif;?>
                            <?endforeach;?>
                        </tr>
                    <?$rowsIteration++;
                    endforeach;?>

                    <?$sectionIteration++;?>
                    <?if($sectionIteration < count($arResult['SECTIONS'])):?>
                        <tr style="border-bottom: 1pt solid #000000;">
                            <td colspan="<?=6+count($arResult['COLUMNS'])*2?>"></td>
                        </tr>
                    <?endif;?>
                <?endforeach;?>
            </tbody>
        </table>
    </section>
</body>
</html>