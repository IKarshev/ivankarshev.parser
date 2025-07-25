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
                        <div class="head">Название</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head">Наш сайт</div>
                    </td>
                    <td style="padding:0 4px;border-right: 1pt solid #000000;">
                        <div class="head"></div>
                    </td>
                    <?for ($i=0; $i < $arResult['COLUMN_COUNT']; $i++):?>
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="head">Конкурент <?=$i+1?></div>
                        </td>
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="head"></div>
                        </td>
                    <?endfor;?>
                </tr>
            </thead>
            <tbody>
                <?foreach ($arResult['ROWS'] as $arkey => $arItem):?>
                    <tr style="border-bottom: 1pt solid #000000;">
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="item"><?=$arItem['MAIN_LINK']['PRODUCT_NAME']?></div>
                        </td>
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="item"><?=$arItem['MAIN_LINK']['LINK_LINK']?></div>
                        </td>
                        <td style="padding:0 4px;border-right: 1pt solid #000000;">
                            <div class="item"><?=$arItem['MAIN_LINK']['LINK_PRICE']?></div>
                        </td>
                        <?foreach ($arItem['TARGET_LINKS'] as $targetLinkKey => $targetLinkValue):
                            if ($arItem['MAIN_LINK']['LINK_PRICE'] < $targetLinkValue['LINK_PRICE']) {
                                $priceStyles = 'background-color:green;text-align:right;';
                            } elseif ($arItem['MAIN_LINK']['LINK_PRICE'] > $targetLinkValue['LINK_PRICE']) {
                                $priceStyles = 'background-color:red;text-align:left;';
                            } else {
                                $priceStyles = '';
                            }

                            ?>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;">
                                <div class="item"><?=$targetLinkValue['LINK_LINK']?></div>
                            </td>
                            <td style="padding:0 4px;border-right: 1pt solid #000000;<?=$priceStyles?>">
                                <div class="item"><?=$targetLinkValue['LINK_PRICE']?></div>
                            </td>
                        <?endforeach;?>
                    </tr>
                <?endforeach;?>
            </tbody>
        </table>
    </section>
</body>
</html>