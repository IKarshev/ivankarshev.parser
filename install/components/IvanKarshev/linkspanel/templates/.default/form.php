<?php
if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var array $arResult - данные
 * @var bool $isNewItem - товар новый
 */

$ItemId = (($itemKey = array_search('ID', array_column($arResult, 'CODE')))!==null)
    ? $arResult[$itemKey]['VALUE']
    : null;

ob_start();
print_r($arResult);
$debug = ob_get_contents();
ob_end_clean();
$fp = fopen($_SERVER['DOCUMENT_ROOT'].'/lk-params.log', 'w+');
fwrite($fp, $debug);
fclose($fp);
?>

<form action="" id="SaveLinkForm">
    <div class="form-header">
        <div class="title-row">
            <h1><?=$isNewItem ? "Новый элемент" : "Редактирование ссылки №$ItemId"?></h1>
            <div class="htn js-add-link">Добавить ссылку</div>
        </div>
        <div class="field-list">
            <?if($ItemId!==null && !$isNewItem):?>
                <input type="hidden" name="ID" value="<?=$ItemId?>">
            <?endif;?>
            <input type="hidden" name="is_new" value="<?=$isNewItem?>">
            
            <?foreach ($arResult as $arkey => $arItem):
                if ($arItem['CODE']=='ID') {
                    continue;
                }?>
                <?switch ($arItem['TYPE']) {
                    case 'string':?>
                        <div class="input_cont">
                            <label for="<?=$arItem['CODE']?>"><?=$arItem['NAME']?></label>
                            <input 
                                type="text" 
                                id="<?=$arItem['CODE']?>" 
                                name="<?=$arItem['NAME_ATTRIBUTE']?>"
                                value="<?=$arItem['VALUE']?>"
                                <?=($arItem['ONLY_READ']) ? 'disabled' : ''?>    
                            >
                        </div>
                        <?break;
                    case 'select':?>
                        <div class="input_cont">
                            <label for="<?=$arItem['CODE']?>"><?=$arItem['NAME']?></label>
                            <select name="<?=$arItem['NAME_ATTRIBUTE']?>" id="<?=$arItem['CODE']?>">
                                <?foreach ($arItem['OPTIONS'] as $option):?>
                                    <option value="<?=$option['ID']?>"><?=$option['DISPLAY_NAME']?></option>
                                <?endforeach;?>
                            </select>
                        </div>
                        <?break;
                }?>

            <?endforeach;?>
        </div>
    </div>
    <div class="error_placement"></div>
    <div class="button_container">
        <button type="submit" class="btn btn-save">Сохранить</button>
        <a href="javaScript::void(0)" class="btn btn-cancel" onclick="editLinkForm.close()">Отмена</a>
    </div>
</form>
<script>
    $('body').on('click', '.btn-cancel', function(){
        editLinkForm.close(); 
    });

    var propertys = <?=CUtil::PhpToJSObject($arResult)?>;
    
    // Валидация
    var ValidateSettings = {
        rules: {},
        messages : {},
        errorElement : "div",
        errorPlacement : function(error, element) {
            $(element).closest("form").find(".error_placement").append(error);
        },
        submitHandler : function(form, event){
            event.preventDefault();    

            BX.ajax.runComponentAction('IvanKarshev:linkspanel', 'SaveEditProfile', {
                mode: 'class',
                data: new FormData( document.getElementById('SaveLinkForm') ),
            }).then(
                response => {
                    editLinkForm.close();
                    RefrechGrid('LINK_LIST_GRID');
                },
                error => {
                    console.log(error);
                    alert('Ошибка. Обратитесь к разработчику.');
                },
            );
        },

    };
    propertys.forEach(function(item, i, arr) {
        ValidateSettings["rules"][`${item.CODE}`] = {
            "required": item.IS_REQUIRED,
        };
        ValidateSettings["messages"][`${item.CODE}`] = {
            "required": `Поле "${item.NAME}" не заполнено`,
        };
    });

    $(`#SaveLinkForm`).validate(ValidateSettings);
</script>