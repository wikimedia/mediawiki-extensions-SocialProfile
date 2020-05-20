<?php
/**
 * Provides functions for managing user profile fields' visibility
 *
 * @file
 * @ingroup Extensions
 * @author Vedmaka <god.vedmaka@gmail.com>
 * @license GPL-2.0-or-later
 */

class SPUserSecurity {
	/**
	 * Set the visibility of a given user's given profile field ($fieldKey) to
	 * whatever $priv is.
	 *
	 * @param User $owner User whose profile we're dealing with
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @param string $priv New privacy value (in plain English, i.e. "public" or "hidden")
	 */
	public static function setPrivacy( $owner, $fieldKey, $priv ) {
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_fields_privacy',
			[ '*' ],
			[ 'ufp_actor' => $owner->getActorId(), 'ufp_field_key' => $fieldKey ],
			__METHOD__
		);

		if ( !$s ) {
			$dbw->insert(
				'user_fields_privacy',
				[
					'ufp_actor' => $owner->getActorId(),
					'ufp_field_key' => $fieldKey,
					'ufp_privacy' => $priv
				],
				__METHOD__
			);
		} else {
			$dbw->update(
				'user_fields_privacy',
				[ 'ufp_privacy' => $priv ],
				[ 'ufp_actor' => $owner->getActorId(), 'ufp_field_key' => $fieldKey ],
				__METHOD__
			);
		}
	}

	/**
	 * Get the privacy value for the supplied user's supplied field key
	 *
	 * @param User $user User whose profile we're dealing with
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @return string Privacy value (in plain English, i.e. "public" or "hidden")
	 */
	public static function getPrivacy( $user, $fieldKey ) {
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_fields_privacy',
			[ '*' ],
			[ 'ufp_field_key' => $fieldKey, 'ufp_actor' => $user->getActorId() ],
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
	 * @param User $user User whose profile we're dealing with
	 * @return string HTML suitable for output
	 */
	public static function renderEye( $fieldKey, User $user ) {
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_fields_privacy',
			[ '*' ],
			[ 'ufp_field_key' => $fieldKey, 'ufp_actor' => $user->getActorId() ],
			__METHOD__
		);

		if ( $s && !empty( $s->ufp_privacy ) ) {
			$privacy = $s->ufp_privacy;
		} else {
			$privacy = 'public';
		}

		// Form list with remaining privacies
		$all_privacy = [ 'public', 'hidden', 'friends', 'foaf' ];

		$ret = '<div class="eye-container" current_action="' .
			htmlspecialchars( $privacy, ENT_QUOTES ) . '" fieldkey="' .
			htmlspecialchars( $fieldKey, ENT_QUOTES ) . '">
					<div class="title">' .
					// For grep: i18n messages used here:
					// user-profile-privacy-status-privacy-public,
					// user-profile-privacy-status-privacy-hidden,
					// user-profile-privacy-status-privacy-friends,
					// user-profile-privacy-status-privacy-foaf
					wfMessage( 'user-profile-privacy-status-privacy-' . $privacy )->escaped() . '</div>
					<div class="menu">';
		$noscriptVersion = '<noscript><select name="' . htmlspecialchars( $fieldKey, ENT_QUOTES ) . '">';

		foreach ( $all_privacy as $priv ) {
			if ( $priv == $privacy ) {
				$noscriptVersion .= '<option value="' . htmlspecialchars( $privacy, ENT_QUOTES ) .
					'" selected="selected">' . wfMessage( 'user-profile-privacy-status-privacy-' . $privacy )->escaped() . '</option>';
				continue;
			}

			$ret .= '<div class="item" action="' . htmlspecialchars( $priv, ENT_QUOTES ) . '">' .
				wfMessage( 'user-profile-privacy-status-privacy-' . $priv )->escaped() .
				'</div>';
			$noscriptVersion .= '<option value="' . htmlspecialchars( $priv, ENT_QUOTES ) . '">' .
				wfMessage( 'user-profile-privacy-status-privacy-' . $priv )->escaped() . '</option>';
		}

		$ret .= '</div>
			</div>';
		$noscriptVersion .= '</select></noscript>';

		return $ret . $noscriptVersion;
	}

	/**
	 * Get the list of user profile fields visible to the supplied viewer
	 *
	 * @param User $owner User whose profile we're dealing with
	 * @param null|User $viewer User who's viewing the owner's profile
	 * @return string[] Array of field keys (up_movies for "Movies" and so on)
	 */
	public static function getVisibleFields( $owner, $viewer = null ) {
		if ( $viewer == null ) {
			$viewer = RequestContext::getMain()->getUser();
		}

		$result = [];
		// Get fields list
		if ( !$owner instanceof User ) {
			return $result;
		}

		$profile = new UserProfile( $owner );
		$fields = $profile->profile_fields;

		foreach ( $fields as $field ) {
			if ( self::isFieldVisible( $owner, 'up_' . $field, $viewer ) ) {
				$result[] = 'up_' . $field;
			}
		}

		return $result;
	}

	/**
	 * Checks if the viewer can view the profile owner's field
	 *
	 * @todo Implement new function which returns an array of accessible fields
	 * in order to reduce SQL queries
	 *
	 * @param User $owner User whose profile we're dealing with
	 * @param string $fieldKey Field key, i.e. up_movies for the "Movies" field
	 * @param null|User $viewer User who's viewing the owner's profile
	 * @return bool True if the user can view the field, otherwise false
	 */
	public static function isFieldVisible( $owner, $fieldKey, $viewer = null ) {
		// No viewing user supplied -> use the current user
		if ( $viewer == null ) {
			$viewer = RequestContext::getMain()->getUser();
		}

		// Owner can always view all of their profile fields, obviously
		if ( $viewer->getActorId() == $owner->getActorId() ) {
			return true;
		}

		$relation = UserRelationship::getUserRelationshipByID( $viewer, $owner ); // 1 = friend, 2 = foe
		$privacy = self::getPrivacy( $owner, $fieldKey );

		switch ( $privacy ) {
			case 'public':
				return true;

			case 'hidden':
				return false;

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
				$listLookup = new RelationshipListLookup( $owner );
				$ownerFriends = $listLookup->getFriendList();

				foreach ( $ownerFriends as $friend ) {
					// If someone in the owner's friends has the viewer in their
					// friends, the test is passed
					$friendActor = User::newFromActorId( $friend['actor'] );
					if ( UserRelationship::getUserRelationshipByID( $friendActor, $viewer ) == 1 ) {
						return true;
					}
				}

				break;
		}

		return false;
	}

}
