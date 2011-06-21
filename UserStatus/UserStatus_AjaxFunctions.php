<?php
$wgAjaxExportList[] = 'wfSaveStatus';
function wfSaveStatus( $u_id, $status) {
	$us_class = new UserStatusClass($u_id);
	$us_class->setStatus($u_id, $status);
                $user_status_array = $us_class->getStatus($u_id);
                $buf=$user_status_array['us_status'];
                $us ="$buf";
                $us.=" <a href=\"javascript:toEditMode('$buf','$u_id');\">Edit</a>";
	return $us;
}
?>
