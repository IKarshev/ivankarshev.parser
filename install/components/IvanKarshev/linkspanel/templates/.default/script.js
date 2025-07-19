var editProfileForm;
function editLinkData( Data )
{
    BX.ajax.runComponentAction('IvanKarshev:linkspanel', 'EditLinkData', {
        mode: 'class',
        data: {
            'ID': Data.ID,
            'TEMPLATE_FOLDER': templateFolder,
        },
    }).then(
        response => {
            var ob = BX.processHTML(response.data);

            var editProfileFormID = "EditProfileContainer";
            editProfileForm = BX.PopupWindowManager.create(editProfileFormID, null, {
                content: BX(editProfileFormID),
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
            if (BX.PopupWindowManager.isPopupExists(editProfileFormID)) {
                editProfileForm.setContent(`${ob.HTML}`);
                BX.ajax.processScripts(ob.SCRIPT);
            };
            editProfileForm.show();
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

$(function(){
    $('body').on('click', '.js-add-element', function(event){
        event.preventDefault();

        let input_cont = $(this).closest('.input_cont');
        let input = $(input_cont).find('.multiple-prop input');
        $(input_cont).find('.multiple-prop').append( $(input)[0].outerHTML );
    })
})