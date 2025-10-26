var editLinkForm;
function editLinkData( Data )
{
    BX.ajax.runComponentAction('IvanKarshev:linkspanel', 'EditLinkData', {
        mode: 'class',
        data: {
            'ID': Data.ID,
            'TEMPLATE_FOLDER': templateFolder,
            'COMPETITOR_SECTION_ID': Data.COMPETITOR_SECTION_ID,
        },
    }).then(
        response => {
            var ob = BX.processHTML(response.data);

            var editLinkFormID = "EditProfileContainer";
            editLinkForm = BX.PopupWindowManager.create(editLinkFormID, null, {
                content: BX(editLinkFormID),
                closeIcon: {right: "20px", top: "10px" },
                titleBar: {content: BX.create("span", {'props': {'className': 'access-title-bar'}})}, 
                zIndex: 0,
                offsetLeft: 0,
                offsetTop: 0,
                draggable: true,
                resizable: false,
                closeByEsc: true,
                overlay: {
                    backgroundColor: '#000',
                    opacity: 500
                },
            });
            if (BX.PopupWindowManager.isPopupExists(editLinkFormID)) {
                editLinkForm.setContent(`${ob.HTML}`);
                BX.ajax.processScripts(ob.SCRIPT);
            };
            editLinkForm.show();
        },
        error => {
            alert('Error: ' + error);
        },
    );
}

function removeLinkItem( Data )
{
    BX.ajax.runComponentAction('IvanKarshev:linkspanel', 'deleteElement', {
        mode: 'class',
        data: {
            'ID': Data.ID
        },
    }).then(
        response => {
            RefrechGrid(arResult.LIST_ID);
        },
        error => {
            alert('Error: ' + error);
        },
    );
}

function RefrechGrid(gridID) // RefrechGrid(arResult.LIST_ID);
{
    var gridObject = BX.Main.gridManager.getById(gridID); // Идентификатор грида
    if (gridObject.hasOwnProperty('instance')){
        gridObject.instance.reloadTable('POST', {apply_filter: 'Y',clear_nav: 'Y'});
    }
}

function onDatePickCallback() {
    BX.ajax.runComponentAction('IvanKarshev:linkspanel', 'getAvailableFilesFormDay', {
        mode: 'class',
        data: {
            'DATE': $('input[name=datePicker]').val(),
        },
    }).then(
        response => {
            if (response.data!==null) {
                let section_structur_url = DownloadUrlTemplate
                    .replace('#FORMAT#', 'section_structur')
                    .replace('#DATE#', $('input[name=datePicker]').val());

                let competitor_structur_url = DownloadUrlTemplate
                    .replace('#FORMAT#', 'competitor_structur')
                    .replace('#DATE#', $('input[name=datePicker]').val());

                $('.js-download-btn-section-structur').attr('href', section_structur_url);
                $('.js-download-btn-competitor-structur').attr('href', competitor_structur_url);

                if (response.data?.section_structur) {
                    $('.js-download-btn-section-structur').removeClass('ui-btn-disabled');
                } else {
                    $('.js-download-btn-section-structur').addClass('ui-btn-disabled');
                }

                if (response.data?.competitor_structur) {
                    $('.js-download-btn-competitor-structur').removeClass('ui-btn-disabled');
                } else {
                    $('.js-download-btn-competitor-structur').addClass('ui-btn-disabled');
                }
            }
        },
        error => {
            alert('Error: ' + error);
        },
    );
}

$(function(){
    var calendarPicker = undefined;
 
    $('body').on('click', '.js-new-item-popup', function(event){
        event.preventDefault();

        editLinkData({'ID': null, 'COMPETITOR_SECTION_ID': null});
    })

    $('body').on('change', 'select[name=COMPETITOR_STRUCTURE_IBLOCK_ID]', function(event){
        event.preventDefault();

        let ID = $('#SaveLinkForm input[NAME=ID]').val();

        editLinkData({
            'ID': ID ? ID : null,
            'COMPETITOR_SECTION_ID': $(this).val()
        });
    })

    $('body').on('click', '.js-date-picker', function(){
        var calendatTimer;
        const callback = function(mutationsList, observer) {
            clearTimeout(calendatTimer);
            calendatTimer = setTimeout(function(){
                var firstdate, endDate;
                let el = $('[id ^= "calendar_popup_"]'); //найдем div с календарем
                let links = el.find(".bx-calendar-cell"); //найдем элементы отображающие дни

                $(links).each(function(){
                    if (!$(this).hasClass('bx-calendar-date-hidden')) {
                        firstdate = $(this).html();
                        return false;
                    }
                });

                $($(links).get().reverse()).each(function(){
                    if (!$(this).hasClass('bx-calendar-date-hidden')) {
                        endDate = $(this).html();
                        return false;
                    }
                });

                let month = $('.bx-calendar-top-month').html();
                let year = $('.bx-calendar-top-year').html();

                // Делаем ajax запрос и получаем доступные даты для скачки файлов.
                BX.ajax.runComponentAction('IvanKarshev:linkspanel', 'getAvailableFileDateList', {
                    mode: 'class',
                    data: {
                        'START_DATE':firstdate,
                        'END_DATE': endDate,
                        'MONTH_NAME': month,
                        'YEAR_NUMBER': year,
                    },
                }).then(
                    response => {
                        $(links).each(function(){
                            if (response.data === null) {
                                if (!$(this).hasClass('bx-calendar-date-hidden')) {
                                    $(this).addClass('disabled');
                                }
                            } else {
                                if (!$(this).hasClass('bx-calendar-date-hidden')) {
                                    if (!response.data.includes( $(this).html() )) {
                                        $(this).addClass('disabled');
                                    }
                                }

                            }
                        })
                    },
                    error => {
                        alert('Error: ' + error);
                    },
                );
            }, 250);
        };
        callback();

        if (calendarPicker===undefined) {
            calendarPicker = true;            
            // Регистрируем наблюдатель за изменениями календаря.
            const observer = new MutationObserver(callback);
            var BXcalendars = BX.findChildrenByClassName(document, 'bx-calendar-cell-block', true);
            BXcalendars.forEach(function(item, i, arr) {
                observer.observe(item, { attributes: true, childList: true, subtree: false });
            }); 
        }
    })

    $('body').on('click', '.bx-calendar-cell.disabled, .js-download-btn-section-structur.ui-btn-disabled, .js-download-btn-competitor-structur.ui-btn-disabled', function(event){
        event.preventDefault();
    })
})
