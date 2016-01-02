<?php
/**
 * A special page for privileged users to remove other users' avatars.
 *
 * @file
 * @ingroup Extensions
 */
class RemoveAvatar extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'RemoveAvatar'/*class*/, 'avatarremove'/*restriction*/ );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'users';
	}

	/**
	 * Show the special page
	 *
	 * @param $user Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgUploadAvatarInRecentChanges;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// If the user isn't logged in, display an error
		if ( !$user->isLoggedIn() ) {
			$this->displayRestrictionError();
			return;
		}

		// If the user doesn't have 'avatarremove' permission, display an error
		if ( !$user->isAllowed( 'avatarremove' ) ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		if ( wfReadOnly() ) {
			$out->readOnlyPage();
			return;
		}

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();
		$out->setPageTitle( $this->msg( 'avatarupload-removeavatar' )->plain() );

		if ( $request->getVal( 'user' ) != '' ) {
			$out->redirect( $this->getPageTitle()->getFullURL() . '/' . $request->getVal( 'user' ) );
		}

		// If the request was POSTed, then delete the avatar
		if ( $request->wasPosted() ) {
			$user_id = $request->getInt( 'user_id' );
			$user_deleted = User::newFromId( $user_id );

			$this->deleteImage( $user_id, 's' );
			$this->deleteImage( $user_id, 'm' );
			$this->deleteImage( $user_id, 'l' );
			$this->deleteImage( $user_id, 'ml' );

			$log = new LogPage( 'avatar' );
			if ( !$wgUploadAvatarInRecentChanges ) {
				$log->updateRecentChanges = false;
			}
			$log->addEntry(
				'avatar',
				$user->getUserPage(),
				$this->msg( 'user-profile-picture-log-delete-entry', $user_deleted->getName() )
					->inContentLanguage()->text()
			);
			$out->addHTML(
				'<div>' .
				$this->msg( 'avatarupload-removesuccess' )->plain() .
				'</div>'
			);
			$out->addHTML(
				'<div><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) . '">' .
					$this->msg( 'avatarupload-removeanother' )->plain() .
				'</a></div>'
			);
		} else {
			if ( $par ) {
				$out->addHTML( $this->showUserAvatar( $par ) );
			} else {
				$out->addHTML( $this->showUserForm() );
			}
		}
	}

	/**
	 * Show the form for retrieving a user's current avatar
	 * @return HTML
	 */
	private function showUserForm() {
		$output = '<form method="get" name="avatar" action="">' .
				Html::hidden( 'title', $this->getPageTitle() ) .
				'<b>' . $this->msg( 'username' )->text() . '</b>
				<input type="text" name="user" />
				<input type="submit" value="' . $this->msg( 'search' )->plain() . '" />
			</form>';
		return $output;
	}

	/**
	 * Shows the requested user's current avatar and the button for deleting it
	 *
	 * @param $user_name String: name of the user whose avatars we want to delete
	 */
	private function showUserAvatar( $user_name ) {
		$user_name = str_replace( '_', ' ', $user_name ); // replace underscores with spaces
		$user_id = User::idFromName( $user_name );

		$avatar = new wAvatar( $user_id, 'l' );

		$output = '<div><b>' . $this->msg( 'avatarupload-currentavatar', $user_name )->parse() . '</b></div>';
		$output .= "<div>{$avatar->getAvatarURL()}</div>";
		$output .= '<div><form method="post" name="avatar" action="">
				<input type="hidden" name="user_id" value="' . $user_id . '" />
				<br />
				<input type="submit" value="' . $this->msg( 'delete' )->plain() . '" />
			</form></div>';
		return $output;
	}

	/**
	 * Deletes all of the requested user's avatar images from the filesystem
	 *
	 * @param $id Integer: user ID
	 * @param $size String: size of the avatar image to delete (small, medium or large).
	 * 			Doesn't really matter since we're just going to blast 'em all.
	 */
	private function deleteImage( $id, $size ) {
		global $wgUploadDirectory, $wgAvatarKey, $wgMemc;

		$avatar = new wAvatar( $id, $size );
		$files = glob( $wgUploadDirectory . '/avatars/' . $wgAvatarKey . '_' . $id .  '_' . $size . "*" );
		wfSuppressWarnings();
		$img = basename( $files[0] );
		wfRestoreWarnings();
		if ( $img && $img[0] ) {
			unlink( $wgUploadDirectory . '/avatars/' . $img );
		}

		// clear cache
		$key = wfMemcKey( 'user', 'profile', 'avatar', $id, $size );
		$wgMemc->delete( $key );
	}
}
