<?php
/**
 * AJAX functions used in UserStatus
 */
$wgAjaxExportList[] = 'wfSaveStatus';

function wfSaveStatus( $u_id, $status ) {
	global $wgUser;

	$us = '';

	// Would probably be best to pass an edit token here, like most other MW
	// forms do
	if ( $u_id == $wgUser->getId() ) {
		// Decode what we encoded in JS, UserStatus.saveStatus; this is safe
		// because the Database class that UserStatusClass uses for its DB queries
		// will do all the escaping for us.
		$status = urldecode( $status );

		$us_class = new UserStatusClass();
		$us_class->setStatus( $u_id, $status );

		$user_status_array = $us_class->getStatus( $u_id );

		$us = htmlspecialchars( $user_status_array['us_status'] );
		$us .= '<br /> <a class="us-link" href="javascript:UserStatus.toEditMode();">' .
			wfMsg( 'userstatus-edit' ) . '</a>';
	}

	return $us;
}

$wgAjaxExportList[] = 'wfGetHistory';

function wfGetHistory( $u_id ) {
	global $wgLang, $wgUser;

	$us_class = new UserStatusClass();
	$historyArray = $us_class->useStatusHistory( 'select', $u_id );

	$output = '<table id="user-status-history">';

	if ( empty( $historyArray ) ) {
		$output .= 'No status history.';
	} else {
		foreach ( $historyArray as $row ) {
			$us = htmlspecialchars( $row['ush_status'] );
			$statusId = intval( $row['ush_id'] );

			$href = '';
			// We can only *view* other user's past status updates, we cannot
			// do anything with them...so don't bother generating the href
			// attribute when we're not viewing our own profile
			if ( $u_id == $wgUser->getId() ) {
				$href = ' href="javascript:UserStatus.insertStatusFromHistory(' . $statusId .
					');"';
			}

			$output .= '<tr>
				<td width="60" id="status-history-time">' .
					$wgLang->timeanddate( wfTimestamp( TS_MW, $row['ush_timestamp'] ), true ) .
				'</td>
				<td width="360">
					<a id="status-history-entry-' . $statusId . '"' . $href . '>'.
						$us . '</a>
				</td>
			</tr>';
		}
	}

	$output .= '</table>';

	return $output;
}

$wgAjaxExportList[] = 'SpecialGetStatusByName';

function SpecialGetStatusByName( $user_name ) {
	$output = '';
	$user_id = User::idFromName( $user_name );

	if ( empty( $user_id ) ) {
		$output .= '<div>Wrong name or user does not exist</div>';
	} else {
		$us_class = new UserStatusClass();
		$currentStatus = $us_class->getStatus( $user_id );
		$output .= "<br /><div>USER ID: $user_id, USERNAME: $user_name</div><br>";

		if ( !empty( $currentStatus ) ) {
			$output .="CURRENT STATUS:<br />
						<input id=\"ush_delete\" type=\"button\" value=\"Delete\"
						onclick=\"javascript:UserStatus.specialStatusDelete('".$currentStatus['us_id']."');\">"
						.$currentStatus['us_status'] . '<br /><br />';
		}

		$output .= 'HISTORY:<br />';

		$userHistory = $us_class->useStatusHistory( 'select', $user_id );
		if( empty( $userHistory ) ) {
			$output .= 'No history';
		} else {
			foreach ( $userHistory as $row ) {
				$output .= "<input id=\"ush_delete\" type=\"button\" value=\"Delete\"
							onclick=\"javascript:UserStatus.specialHistoryDelete('".$row['ush_id']."');\">"
							.$row['ush_timestamp']." - ".$row['ush_status']." <br />";
			}
		}
	}
	return $output;
}

$wgAjaxExportList[] = 'SpecialHistoryDelete';
function SpecialHistoryDelete( $id ) {
	$us_class = new UserStatusClass();
	$us_class->removeHistoryStatus( $id );
	return '';
}

$wgAjaxExportList[] = 'SpecialStatusDelete';
function SpecialStatusDelete( $id ) {
	$us_class = new UserStatusClass();
	$us_class->removeStatus( $id );
	return '';
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

/**
 * Show status updates in user profiles if this feature is enabled.
 *
 * @param $user_profile UserProfile: instance of UserProfile
 * @return Boolean: true
 */
function wfUserProfileStatusOutput( $user_profile ) {
	global $wgOut, $wgUser, $wgEnableUserStatus;

	if ( $wgEnableUserStatus ) {
		$us_class = new UserStatusClass();
		$user_status_array = $us_class->getStatus( $user_profile->user_id );

		if ( empty( $user_status_array ) ) {
			$userStatus = '';
		} else {
			$userStatus = $user_status_array['us_status'];
		}

		$editLink = '';
		// This is the version of the status message that we can safely use in
		// JavaScript
		$jsEncodedStatusMsg = Xml::escapeJsString( htmlspecialchars( $userStatus ) );

		// If the user is viewing their own profile, we can show the "edit"
		// link, provided that the user isn't blocked and that the database is
		// writable
		if ( $user_profile->user_id == $wgUser->getId() ) {
			if ( $wgUser->isBlocked() ) {
				$userStatus = wfMsg( 'userstatus-blocked' );
			}

			// Database operations require write mode
			if ( wfReadOnly() ) {
				$userStatus = wfMsg( 'userstatus-readonly' );
			}

			if ( !$wgUser->isBlocked() && !wfReadOnly() ) {
				$editLink = '<br /><a class="us-link" href="javascript:UserStatus.toEditMode();">' .
					wfMsg( 'userstatus-edit' ) . '</a>';
			}
		}

		$output = '<div id="status-box">
					<div id="status-box-top"></div>
						<div id="status-box-content">
							<div id="user-status-block">' .
								htmlspecialchars( $userStatus ) . $editLink .
							'</div>';

		// No need to show the editing controls to anyone else except the owner
		// of the profile
		if ( $user_profile->user_id == $wgUser->getId() ) {
			$output .= '<div id="status-edit-controls" style="display: none;">
								<input id="user-status-input" type="text" size="50" value="' .
									htmlspecialchars( $userStatus ) .
									'" onkeyup="javascript:UserStatus.usLettersLeft();" />
								<br />
								<div id="status-bar">
									<a class="us-link" href="javascript:UserStatus.saveStatus(\'' . $user_profile->user_id . '\');">' .
										wfMsg( 'userstatus-save' ) . '</a>
									<a class="us-link" href="javascript:UserStatus.useHistory(\'' . $user_profile->user_id . '\');">' .
										wfMsg( 'userstatus-history' ) . '</a>
									<a class="us-link" href="javascript:UserStatus.toShowMode();">' .
										wfMsg( 'userstatus-cancel' ) . '</a>
									<span id="status-letter-count"></span>
								</div>
							</div><!-- #status-edit-controls -->';
		} else {
			// Public history link to the masses
			$output .= "<script>UserStatus.publicHistoryButton('{$user_profile->user_id}');</script>";
		}

		$output .= '</div>
					</div>';
		$wgOut->addHTML( $output );
	}

	return true;
}