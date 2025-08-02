<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\Title\Title;
use MediaWiki\User\UserIdentity;
use MediaWiki\User\UserNamePrefixSearch;

/**
 * A special page to allow privileged users to update others' social profiles
 *
 * @file
 * @ingroup Extensions
 * @author Frozen Wind <tuxlover684@gmail.com>
 * @license GPL-2.0-or-later
 */

class SpecialEditProfile extends SpecialUpdateProfile {
	/** @var string[] */
	public $profile_visible_fields;

	public function __construct() {
		SpecialPage::__construct( 'EditProfile', 'editothersprofiles' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		global $wgUpdateProfileInRecentChanges;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// This feature is only available for logged-in users.
		$this->requireLogin();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Database operations require write mode
		$this->checkReadOnly();

		// No need to allow blocked users to access this page, they could abuse it, y'know.
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.userprofile.tabs.css',
			'ext.socialprofile.special.updateprofile.css'
		] );
		$out->addModules( 'ext.userProfile.updateProfile' );

		// Get the user's name from the wpUser URL parameter
		$userFromRequest = $request->getText( 'wpUser' );

		// If the wpUser parameter isn't set but a parameter was passed to the
		// special page, use the given parameter instead
		if ( !$userFromRequest && $par ) {
			$userFromRequest = $par;
		}

		// Still not set? Just give up and show the "search for a user" form...
		if ( !$userFromRequest ) {
			$this->createUserInputForm();
			return;
		}

		$target = User::newFromName( $userFromRequest );

		if ( !$target || $target->getId() == 0 ) {
			$out->addHTML( $this->msg( 'nosuchusershort', htmlspecialchars( $userFromRequest ) )->escaped() );
			$this->createUserInputForm();
			return;
		}

		$this->profile_visible_fields = SPUserSecurity::getVisibleFields( $target, $user );

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$this->saveProfileBasic( $target );
			$this->saveBasicSettings( $target );
			$this->saveProfilePersonal( $target );
			$this->saveProfileCustom( $target );

			UserProfile::clearCache( $target );

			$log = new LogPage( 'profile' );
			if ( !$wgUpdateProfileInRecentChanges ) {
				$log->updateRecentChanges = false;
			}
			$log->addEntry(
				'profile',
				$target->getUserPage(),
				$this->msg( 'user-profile-edit-profile',
					[ '[[User:' . $target->getName() . ']]' ] )
				->inContentLanguage()->text(),
				[],
				$user
			);
			$out->addHTML(
				'<span class="profile-on">' .
				$this->msg( 'user-profile-edit-profile-update-saved' )->escaped() .
				'</span><br /><br />'
			);

			// create the user page if it doesn't exist yet
			$title = Title::makeTitle( NS_USER, $target->getName() );
			$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
			if ( !$page->exists() ) {
				$page->doUserEditContent(
					ContentHandler::makeContent( '', $title ),
					$this->getUser(),
					'create user page',
					EDIT_SUPPRESS_RC
				);
			}
		}

		$out->addHTML( $this->displayBasicForm( $target ) );
		$out->addHTML( $this->displayPersonalForm( $target ) );
		$out->addHTML( $this->displayCustomForm( $target ) );

		// Set the page title *once again* here
		// Needed because display*Form() functions can and do override our title so
		// if we don't do this here, the page title ends up being something like
		// "Other information" and the HTML title ends up being "Tidbits"
		$out->setPageTitle( $this->msg( 'editprofile' )->escaped() );
		$out->setHTMLTitle( $this->msg( 'pagetitle', $this->msg( 'edit-profiles-title' ) ) );
	}

	function createUserInputForm() {
		$actionUrl = $this->getPageTitle()->getLocalURL( '' );
		$formDescriptor = [
			'text' => [
				'type' => 'user',
				'name' => 'wpUser',
				'label-message' => 'username',
				'size' => 60,
				'id' => 'mw-socialprofile-user',
				'maxlength' => '200'
			]
		];

		$htmlForm = HTMLForm::factory( 'ooui', $formDescriptor, $this->getContext() );
		$htmlForm
			->setSubmitTextMsg( 'edit' )
			->setWrapperLegend( '' )
			->setAction( $actionUrl )
			->setId( 'mw-socialprofile-edit-profile-userform' )
			->setMethod( 'get' )
			->prepareForm()
			->displayForm( false );

		return true;
	}

	function saveBasicSettings( $tar ) {
		$request = $this->getRequest();

		$tar->setRealName( $request->getVal( 'real_name' ) );
		if ( $this->getUser()->isAllowed( 'editothersprofiles-private' ) ) {
			$tar->setEmail( $request->getVal( 'email' ) );

			if ( $tar->getEmail() != $request->getVal( 'email' ) ) {
				$tar->mEmailAuthenticated = null; # but flag as "dirty" = unauthenticated
			}
		}

		$tar->saveSettings();
	}

	function displayBasicForm( $tar ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_profile',
			[
				'up_location_city', 'up_location_state', 'up_location_country',
				'up_hometown_city', 'up_hometown_state', 'up_hometown_country',
				'up_birthday', 'up_occupation', 'up_about', 'up_schools',
				'up_places_lived', 'up_websites'
			],
			[ 'up_actor' => $tar->getActorId() ],
			__METHOD__
		);

		if ( $s !== false ) {
			$location_city = $s->up_location_city;
			$location_state = $s->up_location_state;
			$location_country = $s->up_location_country;
			$about = $s->up_about;
			$occupation = $s->up_occupation;
			$hometown_city = $s->up_hometown_city;
			$hometown_state = $s->up_hometown_state;
			$hometown_country = $s->up_hometown_country;
			$birthday = self::formatBirthday( $s->up_birthday, true );
			$schools = $s->up_schools;
			$places = $s->up_places_lived;
			$websites = $s->up_websites;
		}

		if ( !isset( $location_country ) ) {
			$location_country = $this->msg( 'user-profile-default-country' )->inContentLanguage()->escaped();
		}
		if ( !isset( $hometown_country ) ) {
			$hometown_country = $this->msg( 'user-profile-default-country' )->inContentLanguage()->escaped();
		}

		$s = $dbr->selectRow(
			'user',
			[ 'user_real_name', 'user_email' ],
			[ 'user_id' => $tar->getId() ],
			__METHOD__
		);

		$real_name = '';
		$email = '';
		if ( $s !== false ) {
			$real_name = $s->user_real_name;
			$email = $s->user_email;
		}

		$countries = explode( "\n*", $this->msg( 'userprofile-country-list' )->inContentLanguage()->text() );
		array_shift( $countries );

		$this->getOutput()->setPageTitle( $this->msg( 'edit-profile-title' )->escaped() );
		// $form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-personal' )->escaped() );
		$form = '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		$form .= '<div class="profile-info visualClear">';
		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-info' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-name' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="real_name" id="real_name" value="' . ( $real_name && in_array( 'up_real_name', $this->profile_visible_fields ) ? htmlspecialchars( $real_name, ENT_QUOTES ) : '' ) . '"/></p>
			<div class="visualClear"></div>';
		if ( $this->getUser()->isAllowed( 'editothersprofiles-private' ) ) {
			$form .= '<p class="profile-update-unit-left">' . $this->msg( 'email' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="email" id="email" value="' . htmlspecialchars( $email, ENT_QUOTES ) . '"/>';
			if ( !$tar->mEmailAuthenticated ) {
				$confirm = SpecialPage::getTitleFor( 'Confirmemail' );
				$form .= " <a href=\"{$confirm->getFullURL()}\">" .
					$this->msg( 'confirmemail' )->escaped() .
				'</a>';
			}
			$form .= '</p>
				<div class="visualClear"></div>';
			if ( !$tar->mEmailAuthenticated ) {
				$form .= '<p class="profile-update-unit-left"></p>
					<p class="profile-update-unit-small">' .
						$this->msg( 'user-profile-personal-email-needs-auth' )->escaped() .
					'</p>';
			}
		}
		$form .= '<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-location' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-city' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="location_city" id="location_city" value="' . ( isset( $location_city ) && in_array( 'up_location_city', $this->profile_visible_fields ) ? htmlspecialchars( $location_city, ENT_QUOTES ) : '' ) . '" /></p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left" id="location_state_label">' . $this->msg( 'user-profile-personal-country' )->escaped() . '</p>';
		$form .= '<p class="profile-update-unit">';
		$form .= '<span id="location_state_form">';
		$form .= '</span>';
		// Hidden helper for UpdateProfile.js since JS cannot directly access PHP variables
		$form .= '<input type="hidden" id="location_state_current" value="' . ( isset( $location_state ) ? htmlspecialchars( $location_state, ENT_QUOTES ) : '' ) . '" />';
		$form .= '<select name="location_country" id="location_country"><option></option>';

		foreach ( $countries as $country ) {
			$form .= Xml::option( $country, $country, ( $country == $location_country ) );
		}

		$form .= '</select>';
		$form .= '</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-hometown' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-city' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="hometown_city" id="hometown_city" value="' . ( isset( $hometown_city ) && in_array( 'up_hometown_city', $this->profile_visible_fields ) ? htmlspecialchars( $hometown_city, ENT_QUOTES ) : '' ) . '" /></p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left" id="hometown_state_label">' . $this->msg( 'user-profile-personal-country' )->escaped() . '</p>
			<p class="profile-update-unit">';
		$form .= '<span id="hometown_state_form">';
		$form .= '</span>';
		// Hidden helper for UpdateProfile.js since JS cannot directly access PHP variables
		$form .= '<input type="hidden" id="hometown_state_current" value="' . ( isset( $hometown_state ) ? htmlspecialchars( $hometown_state, ENT_QUOTES ) : '' ) . '" />';
		$form .= '<select name="hometown_country" id="hometown_country"><option></option>';

		foreach ( $countries as $country ) {
			$form .= Xml::option( $country, $country, ( $country == $hometown_country ) );
		}

		$form .= '</select>';
		$form .= '</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-birthday' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-birthdate-with-year' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" class="long-birthday" size="25" name="birthday" id="birthday" value="' . ( isset( $birthday ) && in_array( 'up_birthday', $this->profile_visible_fields ) ? htmlspecialchars( $birthday, ENT_QUOTES ) : '' ) . '" /></p>
			<div class="visualClear"></div>
		</div><div class="visualClear"></div>';

		$form .= '<div class="profile-update" id="profile-update-personal-aboutme">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-aboutme' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-aboutme' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="about" id="about" rows="3" cols="75">' . ( isset( $about ) && in_array( 'up_about', $this->profile_visible_fields ) ? htmlspecialchars( $about, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-work">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-work' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-occupation' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="occupation" id="occupation" rows="2" cols="75">' . ( isset( $occupation ) && in_array( 'up_occupation', $this->profile_visible_fields ) ? htmlspecialchars( $occupation, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-education">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-education' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-schools' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="schools" id="schools" rows="2" cols="75">' . ( isset( $schools ) && in_array( 'up_schools', $this->profile_visible_fields ) ? htmlspecialchars( $schools, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-places">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-places' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-placeslived' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="places" id="places" rows="3" cols="75">' . ( isset( $places ) && in_array( 'up_places_lived', $this->profile_visible_fields ) ? htmlspecialchars( $places, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-web">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-web' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-websites' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="websites" id="websites" rows="2" cols="75">' . ( isset( $websites ) && in_array( 'up_websites', $this->profile_visible_fields ) ? htmlspecialchars( $websites, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>';

		$form .= '</div>';

		return $form;
	}

	function displayPersonalForm( $tar ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_profile',
			[
				'up_about', 'up_places_lived', 'up_websites', 'up_relationship',
				'up_occupation', 'up_companies', 'up_schools', 'up_movies',
				'up_tv', 'up_music', 'up_books', 'up_video_games',
				'up_magazines', 'up_snacks', 'up_drinks'
			],
			[
				// @phan-suppress-next-line PhanUndeclaredMethod Removed in MW 1.41
				'up_actor' => $tar->getActorId()
			],
			__METHOD__
		);

		if ( $s !== false ) {
			$places = $s->up_places_lived;
			$websites = $s->up_websites;
			$relationship = $s->up_relationship;
			$companies = $s->up_companies;
			$schools = $s->up_schools;
			$movies = $s->up_movies;
			$tv = $s->up_tv;
			$music = $s->up_music;
			$books = $s->up_books;
			$videogames = $s->up_video_games;
			$magazines = $s->up_magazines;
			$snacks = $s->up_snacks;
			$drinks = $s->up_drinks;
		}

		$this->getOutput()->setPageTitle( $this->msg( 'user-profile-section-interests' )->escaped() );
		// $form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-interests' )->escaped() );
		$form = '<div class="profile-info visualClear">
			<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-interests-entertainment' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-movies' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="movies" id="movies" rows="3" cols="75">' . ( isset( $movies ) && in_array( 'up_movies', $this->profile_visible_fields ) ? htmlspecialchars( $movies, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-tv' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="tv" id="tv" rows="3" cols="75">' . ( isset( $tv ) && in_array( 'up_tv', $this->profile_visible_fields ) ? htmlspecialchars( $tv, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-music' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="music" id="music" rows="3" cols="75">' . ( isset( $music ) && in_array( 'up_music', $this->profile_visible_fields ) ? htmlspecialchars( $music, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-books' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="books" id="books" rows="3" cols="75">' . ( isset( $books ) && in_array( 'up_books', $this->profile_visible_fields ) ? htmlspecialchars( $books, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-magazines' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="magazines" id="magazines" rows="3" cols="75">' . ( isset( $magazines ) && in_array( 'up_magazines', $this->profile_visible_fields ) ? htmlspecialchars( $magazines, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-videogames' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="videogames" id="videogames" rows="3" cols="75">' . ( isset( $videogames ) && in_array( 'up_video_games', $this->profile_visible_fields ) ? htmlspecialchars( $videogames, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			</div>
			<div class="profile-info visualClear">
			<p class="profile-update-title">' . $this->msg( 'user-profile-interests-eats' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-foodsnacks' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="snacks" id="snacks" rows="3" cols="75">' . ( isset( $snacks ) && in_array( 'up_snacks', $this->profile_visible_fields ) ? htmlspecialchars( $snacks, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-drinks' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="drinks" id="drinks" rows="3" cols="75">' . ( isset( $drinks ) && in_array( 'up_drinks', $this->profile_visible_fields ) ? htmlspecialchars( $drinks, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear"></div>
			</div>
			</div>';

		return $form;
	}

	/**
	 * Displays the form for editing custom (site-specific) information
	 *
	 * @param UserIdentity $tar
	 *
	 * @return string HTML
	 */
	function displayCustomForm( $tar ) {
		$dbr = MediaWikiServices::getInstance()->getDBLoadBalancer()->getConnection( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_profile',
			[
				'up_custom_1', 'up_custom_2', 'up_custom_3', 'up_custom_4',
				'up_custom_5'
			],
			[
				// @phan-suppress-next-line PhanUndeclaredMethod Removed in MW 1.41
				'up_actor' => $tar->getActorId()
			],
			__METHOD__
		);

		if ( $s !== false ) {
			$custom1 = $s->up_custom_1;
			$custom2 = $s->up_custom_2;
			$custom3 = $s->up_custom_3;
			$custom4 = $s->up_custom_4;
		}

		$this->getOutput()->setHTMLTitle( $this->msg( 'pagetitle', $this->msg( 'user-profile-tidbits-title' ) ) );
		$form = '<h1>' . $this->msg( 'user-profile-tidbits-title' )->escaped() . '</h1>';
		// $form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-custom' )->escaped() );
		$form = '<div class="profile-info visualClear">
				<div class="profile-update">
					<p class="profile-update-title">' . $this->msg( 'user-profile-tidbits-title' )->inContentLanguage()->parse() . '</p>
					<div id="profile-update-custom1">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field1' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom1" id="fav_moment" rows="3" cols="75">' . ( isset( $custom1 ) && in_array( 'up_custom1', $this->profile_visible_fields ) ? htmlspecialchars( $custom1, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear"></div>
					<div id="profile-update-custom2">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field2' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom2" id="least_moment" rows="3" cols="75">' . ( isset( $custom2 ) && in_array( 'up_custom2', $this->profile_visible_fields ) ? htmlspecialchars( $custom2, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear"></div>
					<div id="profile-update-custom3">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field3' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom3" id="fav_athlete" rows="3" cols="75">' . ( isset( $custom3 ) && in_array( 'up_custom3', $this->profile_visible_fields ) ? htmlspecialchars( $custom3, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear"></div>
					<div id="profile-update-custom4">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field4' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom4" id="least_fav_athlete" rows="3" cols="75">' . ( isset( $custom4 ) && in_array( 'up_custom4', $this->profile_visible_fields ) ? htmlspecialchars( $custom4, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear"></div>
				</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
			<input type="submit" value="' . $this->msg( 'user-profile-update-button' )->escaped() . '" />
			</form></div>';
		// The <form> was opened in displayBasicForm() and left unclosed for us to close here

		return $form;
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

		$services = MediaWikiServices::getInstance();
		// Autocomplete subpage as user list - public to allow caching
		return $services->getUserNamePrefixSearch()->search(
			UserNamePrefixSearch::AUDIENCE_PUBLIC, $user, $limit, $offset
		);
	}

}
