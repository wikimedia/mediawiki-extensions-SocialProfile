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
	 * @return string
	 */
	function getDescription() {
		if ( $this->isUserPrivileged() ) {
			return $this->msg( 'removeavatar' )->plain();
		} else {
			return $this->msg( 'removeavatar-remove-my-avatar' )->plain();
		}
	}

	/**
	 * Checks if user is privileged to remove other users' avatars
	 * by seeing if they have the 'avatarremove' right
	 *
	 * @return bool
	 */
	private function isUserPrivileged() {
		return $this->getUser()->isAllowed( 'avatarremove' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Name of the user whose avatar we're removing
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$userIsPrivileged = $this->isUserPrivileged();

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

				$this->showUserForm();
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
		if ( $this->isUserPrivileged() ) {
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
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		$user = User::newFromName( $search );
		if ( !$user ) {
			// No prefix suggestion for invalid user
			return [];
		}
		if ( $this->isUserPrivileged() ) {
			// Autocomplete subpage as user list - public to allow caching
			return UserNamePrefixSearch::search( 'public', $search, $limit, $offset );
		} else {
			return [ $this->getUser()->getName() ];
		}
	}

	/**
	 * Show the form for retrieving a user's current avatar
	 *
	 * @return bool
	 */
	private function showUserForm() {

		$formDescriptor = [
			'user' => [
				'type' => 'user',
				'name' => 'user',
				'label-message' => 'username'
			]
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->addHiddenField( 'title', $this->getPageTitle() )
			->setAction( '' )
			->setMethod( 'get' )
			->setName( 'avatar' )
			->setSubmitTextMsg( 'search' )
			->setWrapperLegend( null )
			->prepareForm()
			->displayForm( false );
		return true;
	}

	/**
	 * Shows the requested user's current avatar and the button for deleting it
	 *
	 * @param string $user_name Name of the user whose avatars we want to delete
	 */
	private function showUserAvatar( $user_name ) {
		$user_name = str_replace( '_', ' ', $user_name ); // replace underscores with spaces
		$user_id = User::idFromName( $user_name );

		$currentUser = $this->getUser();
		$userIsAvatarOwner = (bool)( $currentUser->getName() === $user_name );
		$userIsPrivileged = $this->isUserPrivileged();
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
	 * @param int $id User ID
	 * @param string $size Size of the avatar image to delete (small, medium or large).
	 * Doesn't really matter since we're just going to blast 'em all.
	 */
	private function deleteImage( $id, $size ) {
		global $wgUploadDirectory, $wgAvatarKey, $wgMemc;

		$avatar = new wAvatar( $id, $size );
		$files = glob( $wgUploadDirectory . '/avatars/' . $wgAvatarKey . '_' . $id .  '_' . $size . "*" );
		MediaWiki\suppressWarnings();
		$img = basename( $files[0] );
		MediaWiki\restoreWarnings();
		if ( $img && $img[0] ) {
			unlink( $wgUploadDirectory . '/avatars/' . $img );
		}

		// clear cache
		$key = $wgMemc->makeKey( 'user', 'profile', 'avatar', $id, $size );
		$wgMemc->delete( $key );
	}
}
