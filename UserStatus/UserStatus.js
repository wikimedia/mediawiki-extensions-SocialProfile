var STATUS = "[this is status]";
var editModeOff = false;


function toEditMode () {
     document.getElementById("status").innerHTML =   "<input type='text' value='STATUS'><a href='javascript:cancel()'>Cancel</a>";
}

function setStatus () {
     document.getElementById("status").innerHTML = STATUS + ' _ ';
}
