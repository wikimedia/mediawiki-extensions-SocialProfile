<?php
/**
 * A special page for removing avatars.
 * Privileged users can remove other users' avatars, but everyone can remove
 * their own avatar regardless of their user rights.
 *
 * @file
 * @ingroup Extensions
 */
class RemoveAvatar extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'RemoveAvatar' );
	}

	public function doesWrites() {
		return true;
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
	 * Special page description shown on Special:SpecialPages -- different for
	 * privileged users and mortals
	 *
	 * @return string Special page description
	 */
	function getDescription() {
		if ( $this->getUser()->isAllowed( 'avatarremove' ) ) {
			return $this->msg( 'removeavatar' )->plain();
		} else {
			return $this->msg( 'removeavatar-remove-my-avatar' )->plain();
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $user Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$userIsPrivileged = $user->isAllowed( 'avatarremove' );

		// If the user isn't logged in, display an error
		if ( !$user->isLoggedIn() ) {
			$this->displayRestrictionError();
			return;
		}

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();
		if ( $userIsPrivileged ) {
			$pageTitle = $this->msg( 'avatarupload-removeavatar' )->plain();
		} else {
			$pageTitle = $this->msg( 'removeavatar-remove-your-avatar' )->plain();
		}
		$out->setPageTitle( $pageTitle );

		if ( $userIsPrivileged && $request->getVal( 'user' ) != '' ) {
			$out->redirect( $this->getPageTitle()->getFullURL() . '/' . $request->getVal( 'user' ) );
		}

		// If the user isn't allowed to delete everyone's avatars, only let
		// them remove their own avatar
		if ( !$userIsPrivileged ) {
			$par = $user->getName();
		}

		// If the request was POSTed, then delete the avatar
		if ( $request->wasPosted() ) {
			$this->onSubmit();
			$out->addHTML(
				'<div>' .
				$this->msg( 'avatarupload-removesuccess' )->plain() .
				'</div>'
			);
			if ( $userIsPrivileged ) {
				// No point in showing this message to mortals, they can't
				// remove others' avatars anyway
				$out->addHTML(
					'<div><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) . '">' .
						$this->msg( 'avatarupload-removeanother' )->plain() .
					'</a></div>'
				);
			}
		} else {
			if ( $par ) {
				$out->addHTML( $this->showUserAvatar( $par ) );
			} else {
				$out->addModules( 'mediawiki.userSuggest' );
				$out->addHTML( $this->showUserForm() );
			}
		}
	}

	/**
	 * Handle form submission, i.e. do everything we need to & log it
	 */
	private function onSubmit() {
		global $wgUploadAvatarInRecentChanges;

		$user = $this->getUser();
		// Only privileged users can delete others' avatars, but everyone
		// can delete their own avatar
		if ( $user->isAllowed( 'avatarremove' ) ) {
			$user_id = $this->getRequest()->getInt( 'user_id' );
			$user_deleted = User::newFromId( $user_id );
		} else {
			$user_id = $user->getId();
			$user_deleted = $user;
		}

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
	}

	/**
	 * Show the form for retrieving a user's current avatar
	 * @return HTML
	 */
	private function showUserForm() {
		$output = '<form method="get" name="avatar" action="">' .
				Html::hidden( 'title', $this->getPageTitle() ) .
				'<b>' . $this->msg( 'username' )->text() . '</b>
				<input type="text" name="user" class="mw-autocomplete-user" />
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

		$currentUser = $this->getUser();
		$userIsAvatarOwner = (bool)( $currentUser->getName() === $user_name );
		$userIsPrivileged = $currentUser->isAllowed( 'avatarremove' );
		$avatar = new wAvatar( $user_id, 'l' );
		$output = '';

		if ( !$avatar->isDefault() ) {
			if ( !$userIsAvatarOwner ) {
				$output .= '<div><b>' . $this->msg( 'avatarupload-currentavatar', $user_name )->parse() . '</b></div>';
			}
			$output .= "<div>{$avatar->getAvatarURL()}</div>";
			$output .= '<div><form method="post" name="avatar" action="">
				<input type="hidden" name="user_id" value="' . $user_id . '" />
				<br />
				<input type="submit" value="' . $this->msg( 'delete' )->plain() . '" />
			</form></div>';
		} else {
			// avatar IS default AND user is privileged
			if ( $userIsPrivileged ) {
				$output = $this->msg( 'removeavatar-already-default' )->parse();
			} elseif ( $userIsAvatarOwner ) {
				// avatar IS default AND user is NOT privileged -> display CTA
				// prompting the user to upload a new avatar instead
				$output = $this->msg( 'removeavatar-already-default-cta' )->parse();
			}
		}

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
