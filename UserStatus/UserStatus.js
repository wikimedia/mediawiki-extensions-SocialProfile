function toShowMode(status,id) {
    document.getElementById('user-status-block').innerHTML = status;
    document.getElementById('user-status-block').innerHTML+= ' <a href="javascript:toEditMode(\''+status+'\','+id+');">Edit</a>';
}

function toEditMode(status,id) {
    var editbar =  '<input id="user-status-input" type="text" value="'+status+'">'; 
        editbar += ' <a href="javascript:save('+id+');">Save</a>';
        editbar += ' <a href="javascript:toShowMode(\''+status+'\','+id+');">Cancel</a>';
        editbar += ' <a href="javascript:showHistory;">History</a>';
        document.getElementById('user-status-block').innerHTML = editbar;
} 

function save(id) {
    var div = document.getElementById('user-status-block');
    var ustext = document.getElementById('user-status-input').value; 
    sajax_do_call( 'wfSaveStatus', [id,ustext], div );
}
             
function showHistory(){
    //A history script
}