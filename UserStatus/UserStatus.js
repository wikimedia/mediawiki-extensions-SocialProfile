var UserStatus = {
	maxStatusLength : 70,
	
	toShowMode: function( status, id ) {
		var str = this.returnJS(status);
		document.getElementById( 'user-status-block' ).innerHTML = str;
		document.getElementById( 'user-status-block' ).innerHTML += '<br> \n\
					<a id="us-link"	href="javascript:UserStatus.toEditMode(\'' + 
					status + '\',' + id + ');">'+_US_EDIT+'</a>';
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
	
	publicHistoryButton: function( id ) {
		document.getElementById( 'user-status-block' ).innerHTML += '<br> <a id="us-link" href="javascript:UserStatus.useHistory(' + id + ');">'+_US_HISTORY+'</a>';
	},
    
	toEditMode: function( status, id ) {
		var editbar = '<input id="user-status-input" type="text" size="50" value="' + 
					this.returnJS(status) + '" onkeyup="javascript:UserStatus.usLettersLeft();">';
		editbar += '<br> <div id="status-bar">';
		editbar += '<a id="us-link" href="javascript:UserStatus.saveStatus(' + id + ');">'+_US_SAVE+'</a>';
		editbar += ' <a id="us-link" href="javascript:UserStatus.useHistory(' + id + ');">'+_US_HISTORY+'</a>';
		editbar += ' <a id="us-link" href="javascript:UserStatus.toShowMode(\'' + status + '\',' + id + ');">'+_US_CANCEL+'</a>';
		editbar += '<span id="status-letter-count"></span></div>';
		document.getElementById( 'user-status-block' ).innerHTML = editbar;
	},
	
	parseJS:function ( str ) {
		var patt=/'/g;
		var s = str.replace(patt, "@q;");
		return s;
	},
	
	returnJS:function ( str ) {

		var pt= /@q;/gi;
		var s = str.replace(pt, "'");
		return s;
	},

	saveStatus: function( id ) {
		var div = document.getElementById( 'user-status-block' );
		var ustext = document.getElementById( 'user-status-input' ).value;
		var ust = this.parseJS(ustext);
		sajax_do_call( 'wfSaveStatus', [id, ust], div );
	},

	useHistory: function( id ){
		var historyBlock = document.getElementById('status-history-block');
		if(historyBlock===null) {
			var statusBlock = document.getElementById('user-status-block');
			historyBlock = document.createElement('div');
			historyBlock.id = 'status-history-block';
			statusBlock.appendChild(historyBlock);
			sajax_do_call( 'wfGetHistory', [id], historyBlock );
		}
		
		if (historyBlock.style.display == "block") {
			historyBlock.style.display = "none";
		} else {
			historyBlock.style.display = "block";
		}
	},

	fromHistoryToStatus: function( str ) {
		document.getElementById('user-status-input').value = this.returnJS(str);
	},
	
	specialGetHistory: function() {
		var us_name = document.getElementById("us-name-input").value;
		var block = document.getElementById("us-special");
		sajax_do_call( 'SpecialGetStatusByName', [us_name], block );
	},
	
	specialHistoryDelete: function(id) {
		var block = document.getElementById("us-special");
		sajax_do_call( 'SpecialHistoryDelete', [id], block );
		this.specialGetHistory();
	},
	
	specialStatusDelete: function(id) {
		var block = document.getElementById("us-special");
		sajax_do_call( 'SpecialStatusDelete', [id], block );
		this.specialGetHistory();
	}
};