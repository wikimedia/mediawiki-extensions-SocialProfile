<?php
/**
 * AJAX functions used in UserStatus
 */
$wgAjaxExportList[] = 'wfSaveStatus';

function wfSaveStatus( $u_id, $status ) {
	$us_class = new UserStatusClass( $u_id );
	$us_class->setStatus( $u_id, $status );
	$user_status_array = $us_class->getStatus( $u_id );
	$buf = $user_status_array['us_status'];
	$us = $buf;
	// @todo FIXME: i18n
	$us .= " <a href=\"javascript:toEditMode('$buf','$u_id');\">Edit</a>";
	return $us;
}

$wgAjaxExportList[] = 'wfGetHistory';

function wfGetHistory( $u_id ) {
	$us_class = new UserStatusClass( $u_id );
	$historyArray = $us_class->useStatusHistory('select', $u_id);
        $output='<table>';
        /*Under construction*/
        foreach ($historyArray as $row ) {
            $output .= '<tr><td id="status-history-time">'.$row['ush_timestamp'].' </td><td> '.$row['ush_status'].'</td></tr>';
        }
        $output.='</table>';
	return $output;
}