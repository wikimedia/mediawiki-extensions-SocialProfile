<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserNamePrefixSearch;

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
	 * Show this special page on Special:SpecialPages only for registered users
	 *
	 * @return bool
	 */
	function isListed() {
		return $this->getUser()->isRegistered();
	}

	/**
	 * Special page description shown on Special:SpecialPages -- different for
	 * privileged users and mortals
	 *
	 * @return Message
	 */
	function getDescription() {
		if ( $this->isUserPrivileged() ) {
			return $this->msg( 'removeavatar' );
		} else {
			return $this->msg( 'removeavatar-remove-my-avatar' );
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
		if ( !$user->isRegistered() ) {
			$this->displayRestrictionError();
		}

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
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
			$out->wrapWikiMsg( '<div class="successbox">$1</div>', 'avatarupload-removesuccess' );
			if ( $userIsPrivileged ) {
				// No point in showing this message to mortals, they can't
				// remove others' avatars anyway
				$out->addHTML(
					'<div><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) . '">' .
						$this->msg( 'avatarupload-removeanother' )->escaped() .
					'</a></div>'
				);
			}
		} else {
			// "0" is a valid user name
			// For whatever reason, such a user may exist indeed. It is also entirely
			// possible, and, in fact, likely that they are NOT a privileged account.
			// So let's not tempt [[User:0]] with a rather pointless user search form
			// that won't even work for them, yes?
			// (Hence if ( $par !== '' ) instead of the old if ( $par ) check!)
			if ( isset( $par ) && $par !== '' ) {
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

		$request = $this->getRequest();
		$user = $this->getUser();

		// Only privileged users can delete others' avatars, but everyone
		// can delete their own avatar
		if ( $this->isUserPrivileged() ) {
			$user_id = $request->getInt( 'user_id' );
			$user_deleted = User::newFromId( $user_id );
		} else {
			$user_id = $user->getId();
			$user_deleted = $user;
		}

		self::deleteImage( $user_id, 's' );
		self::deleteImage( $user_id, 'm' );
		self::deleteImage( $user_id, 'l' );
		self::deleteImage( $user_id, 'ml' );

		$log = new LogPage( 'avatar' );

		if ( !$wgUploadAvatarInRecentChanges ) {
			$log->updateRecentChanges = false;
		}

		$reason = $request->getText( 'wpReason' );

		if ( $this->isUserPrivileged() && $reason !== '' ) {
			$log->addEntry(
				'avatar',
				$user->getUserPage(),
				$this->msg(
					'user-profile-picture-log-delete-entry-with-reason',
					$user_deleted->getName(),
					$reason
				)->inContentLanguage()->text(),
				[],
				$user
			);
		} else {
			$log->addEntry(
				'avatar',
				$user->getUserPage(),
				$this->msg( 'user-profile-picture-log-delete-entry', $user_deleted->getName() )
					->inContentLanguage()->text(),
				[],
				$user
			);
		}
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
			$services = MediaWikiServices::getInstance();
			// Autocomplete subpage as user list - public to allow caching
			return $services->getUserNamePrefixSearch()->search(
				UserNamePrefixSearch::AUDIENCE_PUBLIC, $user, $limit, $offset
			);
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
			->setWrapperLegend( '' )
			->prepareForm()
			->displayForm( false );
		return true;
	}

	/**
	 * Shows the requested user's current avatar and the button for deleting it
	 *
	 * @param string $user_name Name of the user whose avatars we want to delete
	 *
	 * @return string
	 */
	private function showUserAvatar( $user_name ) {
		$out = $this->getOutput();
		$user_name = str_replace( '_', ' ', $user_name ); // replace underscores with spaces
		if ( method_exists( MediaWikiServices::class, 'getUserIdentityLookup' ) ) {
			// MW 1.36+
			$userIdentity = MediaWikiServices::getInstance()->getUserIdentityLookup()
				->getUserIdentityByName( $user_name );
			$user_id = $userIdentity ? $userIdentity->getId() : 0;
		} else {
			// @phan-suppress-next-line PhanUndeclaredStaticMethod Removed in MW 1.41+
			$user_id = User::idFromName( $user_name );
		}

		$currentUser = $this->getUser();
		$userIsAvatarOwner = ( $currentUser->getName() === $user_name );
		$userIsPrivileged = $this->isUserPrivileged();
		$avatar = new wAvatar( $user_id, 'l' );
		$output = '';

		if ( !$avatar->isDefault() ) {
			if ( !$userIsAvatarOwner ) {
				$out->addHTML( '<div><b>' . $this->msg( 'avatarupload-currentavatar', $user_name )->parse() . '</b></div>' );
			}
			$out->addHTML( "<div>{$avatar->getAvatarURL()}</div>" );

			$htmlForm = HTMLForm::factory( 'ooui', [], $this->getContext() );

			// Let privileged users specify a reason for the deletion when they're
			// about to delete another user's avatar (T158621)
			if ( $userIsPrivileged && !$userIsAvatarOwner ) {
				$htmlForm->addFields( [
					// Capital R instead of lowercase so that we can use "wpReason"
					// as the identifier in onSubmit() instead of "wpreason";
					// readability matters!
					'Reason' => [
						'type' => 'text',
						'label-message' => 'removeavatar-reason'
					]
				] );
			}

			$htmlForm
				->addHiddenField( 'user_id', $user_id )
				->setAction( '' )
				->setMethod( 'post' )
				->setName( 'avatar' )
				->setSubmitDestructive()
				->setSubmitTextMsg( 'delete' )
				->prepareForm()
				->displayForm( false );

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
	 * Deletes all of the requested user's avatar images from the file backend
	 *
	 * @param int $id User ID
	 * @param string $size Size of the avatar image to delete (small, medium or large).
	 *  Doesn't really matter since we're just going to blast 'em all.
	 */
	public static function deleteImage( $id, $size ) {
		global $wgAvatarKey;

		$backend = new SocialProfileFileBackend( 'avatars' );

		$extensions = [ 'png', 'gif', 'jpg', 'jpeg' ];
		foreach ( $extensions as $ext ) {
			if ( $backend->fileExists( $wgAvatarKey . '_', $id, $size, $ext ) ) {
				$backend->getFileBackend()->quickDelete( [
					'src' => $backend->getPath( $wgAvatarKey . '_', $id, $size, $ext )
				] );
			}
		}

		// clear cache
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$key = $cache->makeKey( 'user', 'profile', 'avatar', $id, $size );
		$cache->delete( $key );
	}
}
