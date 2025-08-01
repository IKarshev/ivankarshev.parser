var editLinkForm;
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

$(function(){
    $('body').on('click', '.js-new-item-popup', function(event){
        event.preventDefault();

        editLinkData({'ID': null});
    })

    $('body').on('click', '.js-add-link', function(event){
        event.preventDefault();

        $('.field-list').append(`
            <div class="input_cont">
                <label>Ссылка</label>
                <input type="text" id="NEW_LINK" name=NEW_LINK[]" value="" >
            </div>
        `);
    })
})