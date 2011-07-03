var UserStatus = {
    historyOpened : false,

    toShowMode: function( status, id ) {
		document.getElementById( 'user-status-block' ).innerHTML = status;
		document.getElementById( 'user-status-block' ).innerHTML += ' <a href="javascript:UserStatus.toEditMode(\'' + status + '\',' + id + ');">Edit</a>';
    },
    
	toEditMode: function( status, id ) {
		var editbar = '<input id="user-status-input" type="text" value="' + status + '">';
		editbar += ' <a href="javascript:UserStatus.saveStatus(' + id + ');">Save</a>';
		editbar += ' <a href="javascript:UserStatus.useHistory(' + id + ');">History</a>';
		editbar += ' <a href="javascript:UserStatus.toShowMode(\'' + status + '\',' + id + ');">Cancel</a>';
		document.getElementById( 'user-status-block' ).innerHTML = editbar;
    },
    
	saveStatus: function( id ) {
		var div = document.getElementById( 'user-status-block' );
		var ustext = document.getElementById( 'user-status-input' ).value;
		sajax_do_call( 'wfSaveStatus', [id, ustext], div );
    },

	useHistory: function( id ){
		if (this.historyOpened)
			this.closeStatusHistory();
		else
            this.openStatusHistory(id);
	},

    openStatusHistory: function( id ) {  
		var historyBlock = document.getElementById('status-history-block');
		if(historyBlock==null) {
			var statusBlock = document.getElementById('user-status-block');
			var historyBlock = document.createElement('div');
			historyBlock.id = 'status-history-block';
			statusBlock.appendChild(historyBlock);
		}
			this.historyOpened = true;
			sajax_do_call( 'wfGetHistory', [id], historyBlock );
    },

	closeStatusHistory: function() {
		var hBlock = document.getElementById('status-history-block');
		hBlock.parentNode.removeChild(hBlock);
		this.historyOpened = false;
	},

	fromHistoryToStatus: function( str )
	{
		document.getElementById('user-status-input').value = str;
	}
}