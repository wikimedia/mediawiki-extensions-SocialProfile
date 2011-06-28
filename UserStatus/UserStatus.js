var historyOpened = false;

function toShowMode( status, id ) {
	document.getElementById( 'user-status-block' ).innerHTML = status;
	document.getElementById( 'user-status-block' ).innerHTML += ' <a href="javascript:toEditMode(\'' + status + '\',' + id + ');">Edit</a>';
}

function toEditMode( status, id ) {
	var editbar = '<input id="user-status-input" type="text" value="' + status + '">';
	editbar += ' <a href="javascript:saveStatus(' + id + ');">Save</a>';
	editbar += ' <a href="javascript:toShowMode(\'' + status + '\',' + id + ');">Cancel</a>';
	editbar += ' <a href="javascript:useHistory(' + id + ');">History</a>';
	document.getElementById( 'user-status-block' ).innerHTML = editbar;
}

function saveStatus( id ) {
	var div = document.getElementById( 'user-status-block' );
	var ustext = document.getElementById( 'user-status-input' ).value;
	sajax_do_call( 'wfSaveStatus', [id, ustext], div );
}

function useHistory(id){
        if (historyOpened){
            //alert('closed');
            closeStatusHistory();
        } 
        else {
            //alert('opened');
            openStatusHistory(id);
        }
}

function openStatusHistory(id) {  
        var statusBlock = document.getElementById('user-status-block');
        var historyBlock = document.createElement('div');
        historyBlock.id = 'status-history-block';
        statusBlock.appendChild(historyBlock);
        historyOpened = true;
        sajax_do_call( 'wfGetHistory', [id], historyBlock );
}

function closeStatusHistory() {
        var hBlock = document.getElementById('status-history-block');
        hBlock.parentNode.removeChild(hBlock);
        historyOpened = false;
}