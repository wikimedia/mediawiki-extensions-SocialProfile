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
	$us .= "<br> <a id=\"us-link\" href=\"javascript:UserStatus.toEditMode('$buf','$u_id');\">".wfMsg('userstatus-edit')."</a>";
	return $us;
}

$wgAjaxExportList[] = 'wfGetHistory';

function wfGetHistory( $u_id ) {
	$us_class = new UserStatusClass( $u_id );
	$historyArray = $us_class->useStatusHistory('select', $u_id);
		$output='<table id="user-status-history">';
		foreach ($historyArray as $row ) {
			$time = DateTime::createFromFormat('Y-m-d H:i:s',$row['ush_timestamp']);
		
            $output .= '<tr><td id="status-history-time">'.date_format($time, 'j M G:i').' </td>';
            $output .= '<td><a href="javascript:UserStatus.fromHistoryToStatus(\''.$row['ush_status'].'\');">'
                       .$row['ush_status'].'</a></td></tr>';
		}
	$output.='</table>';
	return $output;
}

$wgHooks['MakeGlobalVariablesScript'][] = 'addJSGlobals';

function addJSGlobals( $vars ) {
	$vars['_US_EDIT'] = wfMsg( 'userstatus-edit' );
	$vars['_US_SAVE'] = wfMsg( 'userstatus-save' );
	$vars['_US_CANCEL'] = wfMsg( 'userstatus-cancel' );
	$vars['_US_HISTORY'] = wfMsg( 'userstatus-history' );
	$vars['_US_LETTERS'] = wfMsg( 'userstatus-letters-left' );
	return true;
}


$wgHooks['UserProfileBeginRight'][] = 'wfUserProfileStatusOutput';

function wfUserProfileStatusOutput( $user_profile ) {
        global $wgOut , $wgEnableUserStatus;
		if ( $wgEnableUserStatus ) {
			$userStatus = $user_profile->getStatus( $user_profile->user_id );
			$output = '<div id="status-box">
						<div id="status-box-top"></div>
							<div id="status-box-content">
								<div id="user-status-block">' . $userStatus . '</div>
							</div>
						</div>';
			$wgOut->addHTML($output);
		}
        return true;
}