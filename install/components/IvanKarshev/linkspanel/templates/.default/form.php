<?php
if ( ! defined('B_PROLOG_INCLUDED') || B_PROLOG_INCLUDED !== true) die();
/**
 * @var $arResult
 */
?>

<form action="" id="SaveLinkForm">
    <div class="form-header">
        <h1>Редактирование</h1>
        <div class="field-list">
            <?foreach ($arResult as $arkey => $arItem): ?>
                <div class="input_cont">
                    <label for="<?=$arItem['CODE']?>"><?=$arItem['NAME']?></label>
                    <input 
                        type="text" 
                        id="<?=$arItem['CODE']?>" 
                        name="<?=$arItem['CODE']?>"
                        value="<?=$arItem['VALUE']?>"
                        <?=($arItem['ONLY_READ']) ? 'disabled' : ''?>    
                    >
                </div>
            <?endforeach;?>
        </div>
    </div>
    <div class="error_placement"></div>
    <div class="button_container">
        <button type="submit" class="btn btn-save">Сохранить</button>
        <a href="javaScript::void(0)" class="btn btn-cancel" onclick="editProfileForm.close()">Отмена</a>
    </div>
</form>
<script>
    console.log('test');
    <?/*
    $('body').on('click', '.btn-cancel', function(){
        editProfileForm.close(); 
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

            BX.ajax.runComponentAction('kontur:paymentProfiles', 'SaveEditProfile', {
                mode: 'class',
                data: new FormData( document.getElementById('SaveLinkForm') ),
            }).then(
                response => {
                    editProfileForm.close();
                    RefrechGrid('PAYMENT_PROFILES_GRID');
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
            "email" : (item.CODE == "EMAIL") ? true : false,
            "checkPhone": (item.CODE == "PHONE") ? 11 : false,
            // "min-width": (item.CODE == "INN") ? 12 : false,
        };
        ValidateSettings["messages"][`${item.CODE}`] = {
            "required": `Поле "${item.NAME}" не заполнено`,
            "email": `Не корректно заполнен E-mail`,
            "checkPhone": `Номер телефона введен не корректно`,
            // "min-width": `Не корректно заполнено поле "${item.NAME}"`,
        };
    });

    $.mask.definitions['h'] = "[0|1|3|4|5|6|7|9]";

    $('#SaveLinkForm input[name=PHONE]').mask("+7 (h99) 999-99-99");
    $('#SaveLinkForm input[name=INN]').mask("999999999999");

    $(`#SaveLinkForm`).validate(ValidateSettings);
    */?>
</script>