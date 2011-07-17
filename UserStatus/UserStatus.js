var UserStatus = {
	historyOpened : false,
	maxStatusLength : 70,
	
	toShowMode: function( status, id ) {
		document.getElementById( 'user-status-block' ).innerHTML = status;
		document.getElementById( 'user-status-block' ).innerHTML += '<br> <a id="us-link" href="javascript:UserStatus.toEditMode(\'' + status + '\',' + id + ');">'+_US_EDIT+'</a>';
	},
	
    usLettersLeft: function() {
		var len = this.maxStatusLength - document.getElementById('user-status-input').value.length;
		if ( len < 0  ) {
			var usBlock = document.getElementById('user-status-input');
			document.getElementById('user-status-input').value = usBlock.value.slice(0,this.maxStatusLength);
			len++;
		}
		document.getElementById('status-letter-count').innerHTML =len + " "+_US_LETTERS;
	},
    
	toEditMode: function( status, id ) {
		var editbar = '<input id="user-status-input" type="text" size="50" value="' + 
					status + '" onkeyup="javascript:UserStatus.usLettersLeft();">';
		editbar += '<br> <div id="status-bar">';
		editbar += '<a id="us-link" href="javascript:UserStatus.saveStatus(' + id + ');">'+_US_SAVE+'</a>';
		editbar += ' <a id="us-link" href="javascript:UserStatus.useHistory(' + id + ');">'+_US_HISTORY+'</a>';
		editbar += ' <a id="us-link" href="javascript:UserStatus.toShowMode(\'' + status + '\',' + id + ');">'+_US_CANCEL+'</a>';
		editbar += '<span id="status-letter-count"></span></div>';
		document.getElementById( 'user-status-block' ).innerHTML = editbar;
	},
    
	saveStatus: function( id ) {
		var div = document.getElementById( 'user-status-block' );
		var ustext = document.getElementById( 'user-status-input' ).value;
		sajax_do_call( 'wfSaveStatus', [id, ustext], div );
	},

	useHistory: function( id ){
		if (this.historyOpened) {
			this.closeStatusHistory();
		} else {
			this.openStatusHistory(id);
		}
	},

	openStatusHistory: function( id ) {  
		var historyBlock = document.getElementById('status-history-block');
		if(historyBlock===null) {
			var statusBlock = document.getElementById('user-status-block');
			historyBlock = document.createElement('div');
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

	fromHistoryToStatus: function( str ) {
		document.getElementById('user-status-input').value = str;
	}
};