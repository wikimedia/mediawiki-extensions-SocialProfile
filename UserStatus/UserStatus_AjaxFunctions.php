<?php
/**
 * AJAX functions used in UserStatus
 */
$wgAjaxExportList[] = 'wfSaveStatus';

function wfSaveStatus( $u_id, $status ) {
	$us_class = new UserStatusClass();
	$us_class->setStatus( $u_id, $status );
	$user_status_array = $us_class->getStatus( $u_id );
	$us = htmlspecialchars($us_class->usHTMLcharacters($user_status_array['us_status']));
	$us .= "<br> <a id=\"us-link\" href=\"javascript:UserStatus.toEditMode('".($user_status_array['us_status'])."','$u_id');\">".wfMsg('userstatus-edit')."</a>";
	return $us;
}

$wgAjaxExportList[] = 'wfGetHistory';

function wfGetHistory( $u_id ) {
	$us_class = new UserStatusClass();
	$historyArray = $us_class->useStatusHistory('select', $u_id);
		$output='<table id="user-status-history">';
		foreach ($historyArray as $row ) {
			$time = DateTime::createFromFormat('Y-m-d H:i:s',$row['ush_timestamp']);
			$us = htmlspecialchars($us_class->usHTMLcharacters($row['ush_status']));
			
            $output .= '<tr><td width="60" id="status-history-time">'.date_format($time, 'j M G:i').' </td>';
            $output .= '<td width="360"><a href="javascript:UserStatus.fromHistoryToStatus(\''.$us.'\');">'
                       .$us.'</a></td>';
		}
	$output.='</table>';
	return $output;
}

$wgAjaxExportList[] = 'SpecialGetStatusByName';

function SpecialGetStatusByName( $user_name ) {
	global $wgUser;
	$output="";
	$user_id = $wgUser->idFromName($user_name);

	if (empty ($user_id)) {
		$output.="<div>Wrong name or user does not exist</div>";
	} else {
		$us_class = new UserStatusClass();
		$currentStatus = $us_class->getStatus($user_id);
		$output .="<br><div>USER ID: $user_id, USERNAME: $user_name</div><br>";

		if (!empty ($currentStatus)) {
			$output .="CURRENT STATUS:<br>
						<input id=\"ush_delete\" type=\"button\" value=\"Delete\" 
						onclick=\"javascript:UserStatus.specialStatusDelete('".$currentStatus['us_id']."');\">"
						.$currentStatus['us_status']."<br><br>";
		}

		$output .="HISTORY:<br>";

		$userHistory = $us_class->useStatusHistory('select', $user_id);
		if(empty ($userHistory)) {
			$output .= "No history";
		} else {
			foreach ( $userHistory as $row ) {
				$output .=  "<input id=\"ush_delete\" type=\"button\" value=\"Delete\" 
							onclick=\"javascript:UserStatus.specialHistoryDelete('".$row['ush_id']."');\">"
							.$row['ush_timestamp']." - ".$row['ush_status']." <br>";
			}
		}
	}
	return $output;
}

$wgAjaxExportList[] = 'SpecialHistoryDelete';
function SpecialHistoryDelete( $id ) {
	$us_class = new UserStatusClass();
	$us_class->removeHistoryStatus($id);
	return "";
}

$wgAjaxExportList[] = 'SpecialStatusDelete';
function SpecialStatusDelete( $id ) {
	$us_class = new UserStatusClass();
	$us_class->removeStatus($id);
	return "";
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