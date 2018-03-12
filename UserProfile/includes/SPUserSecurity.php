<?php
/**
 * Provides functions for managing user profile fields' visibility
 *
 * @file
 * @ingroup Extensions
 * @author Vedmaka <god.vedmaka@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SPUserSecurity {
	/**
	 * Set the visibility of a given user's given profile field ($fieldKey) to
	 * whatever $priv is.
	 *
	 * @param int $uid User ID of the user whose profile we're dealing with
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @param string $priv New privacy value (in plain English, i.e. "public" or "hidden")
	 */
	public static function setPrivacy( $uid, $fieldKey, $priv ) {
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_fields_privacy',
			array( '*' ),
			array( 'ufp_user_id' => $uid, 'ufp_field_key' => $fieldKey ),
			__METHOD__
		);

		if ( !$s ) {
			$dbw->insert(
				'user_fields_privacy',
				array(
					'ufp_user_id' => $uid,
					'ufp_field_key' => $fieldKey,
					'ufp_privacy' => $priv
				),
				__METHOD__
			);
		} else {
			$dbw->update(
				'user_fields_privacy',
				array( 'ufp_privacy' => $priv ),
				array( 'ufp_user_id' => $uid, 'ufp_field_key' => $fieldKey ),
				__METHOD__
			);
		}
	}

	/**
	 * Get the privacy value for the supplied user's supplied field key
	 *
	 * @param int $uid User ID of the user whose profile we're dealing with
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @return string Privacy value (in plain English, i.e. "public" or "hidden")
	 */
	public static function getPrivacy( $uid, $fieldKey ) {
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_fields_privacy',
			array( '*' ),
			array( 'ufp_field_key' => $fieldKey, 'ufp_user_id' => $uid ),
			__METHOD__
		);

		if ( $s ) {
			return $s->ufp_privacy;
		} else {
			return 'public';
		}
	}

	/**
	 * Render fields privacy button by field code
	 *
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @param int|null $uid User ID of the user whose profile we're dealing with
	 * @return string HTML suitable for output
	 */
	public static function renderEye( $fieldKey, $uid = null ) {
		global $wgUser;

		if ( !$uid || $uid == null ) {
			$uid = $wgUser->getId();
		}

		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_fields_privacy',
			array( '*' ),
			array( 'ufp_field_key' => $fieldKey, 'ufp_user_id' => $uid ),
			__METHOD__
		);

		if ( $s && !empty( $s->ufp_privacy ) ) {
			$privacy = $s->ufp_privacy;
		} else {
			$privacy = 'public';
		}

		// Form list with remaining privacies
		$all_privacy = array( 'public', 'hidden', 'friends', 'foaf' );

		$ret = '<div class="eye-container" current_action="' .
			htmlspecialchars( $privacy, ENT_QUOTES ) . '" fieldkey="' .
			htmlspecialchars( $fieldKey, ENT_QUOTES ) . '">
					<div class="title">' .
					// For grep: i18n messages used here:
					// user-profile-privacy-status-privacy-public,
					// user-profile-privacy-status-privacy-hidden,
					// user-profile-privacy-status-privacy-friends,
					// user-profile-privacy-status-privacy-foaf
					wfMessage( 'user-profile-privacy-status-privacy-' . $privacy )->plain() . '</div>
					<div class="menu">';

		foreach ( $all_privacy as $priv ) {
			if ( $priv == $privacy ) {
				continue;
			}

			$ret .= '<div class="item" action="' . htmlspecialchars( $priv, ENT_QUOTES ) . '">' .
				wfMessage( 'user-profile-privacy-status-privacy-' . $priv )->plain() .
				'</div>';
		}

		$ret .= '</div>
			</div>';

		return $ret;
	}

	/**
	 * Get the list of user profile fields visible to the supplied viewer
	 *
	 * @param int $ownerUid User ID of the person whose profile we're dealing with
	 * @param null|int $viewerUid User ID of the person who's viewing the owner's profile
	 * @return array Array of field keys (up_movies for "Movies" and so on)
	 */
	public static function getVisibleFields( $ownerUid, $viewerUid = null ) {
		global $wgUser;

		if ( $viewerUid == null ) {
			$viewerUid = $wgUser->getId();
		}

		$arResult = array();
		// Get fields list
		$user = User::newFromId( $ownerUid );
		if ( !$user instanceof User ) {
			return $arResult;
		}
		// The following line originally had the inline comment "does not matter",
		// but it actually matters if you pass in something that the constructor
		// expects (a username) or something that it doesn't (a user ID), because
		// the latter will lead into "fun" fatals that are tricky to track down
		// unless you know what you're doing...
		$profile = new UserProfile( $user->getName() );
		$arFields = $profile->profile_fields;

		foreach ( $arFields as $field ) {
			if ( SPUserSecurity::isFieldVisible( $ownerUid, 'up_' . $field, $viewerUid ) ) {
				$arResult[] = 'up_' . $field;
			}
		}

		return $arResult;
	}

	/**
	 * Checks if the viewer can view the profile owner's field
	 *
	 * @todo Implement new function which returns an array of accessible fields
	 * in order to reduce SQL queries
	 *
	 * @param int $ownerUid User ID of the person whose profile we're dealing with
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @param null|int $viewerUid User ID of the person who's viewing the owner's profile
	 * @return bool True if the user can view the field, otherwise false
	 */
	public static function isFieldVisible( $ownerUid, $fieldKey, $viewerUid = null ) {
		global $wgUser;

		// No user ID -> use the current user's ID
		if ( $viewerUid == null ) {
			$viewerUid = $wgUser->getId();
		}

		// Owner can always view all of their profile fields, obviously
		if ( $viewerUid == $ownerUid ) {
			return true;
		}

		$relation = UserRelationship::getUserRelationshipByID( $viewerUid, $ownerUid ); // 1 = friend, 2 = foe
		$privacy = SPUserSecurity::getPrivacy( $ownerUid, $fieldKey );

		switch ( $privacy ) {
			case 'public':
				return true;
				break;

			case 'hidden':
				return false;
				break;

			case 'friends':
				if ( $relation == 1 ) {
					return true;
				}
				break;

			case 'foaf':
				if ( $relation == 1 ) {
					return true;
				}

				// Now we know that the viewer is not the user's friend, but we
				// must check if the viewer has friends that are the owner's friends:
				if ( isset( $ownerUid ) && ( $ownerUid !== null ) ) {
					$what = $ownerUid;
				} else {
					$what = $wgUser->getId();
				}
				$user = User::newFromId( $what );
				if ( !$user instanceof User ) {
					return false;
				}

				$listLookup = new RelationshipListLookup( $user );
				$ownerFriends = $listLookup->getFriendList();

				foreach ( $ownerFriends as $friend ) {
					// If someone in the owner's friends has the viewer in their
					// friends, the test is passed
					if ( UserRelationship::getUserRelationshipByID( $friend['user_id'], $viewerUid ) == 1 ) {
						return true;
					}
				}

				break;
		}

		return false;
	}

}