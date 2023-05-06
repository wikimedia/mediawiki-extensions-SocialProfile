<?php

use MediaWiki\MediaWikiServices;
use MediaWiki\User\UserIdentity;

/**
 * A special page to allow users to update their social profile
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class SpecialUpdateProfile extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'UpdateProfile' );
	}

	/**
	 * Initialize the user_profile records for a given user (either the current
	 * user or someone else).
	 *
	 * @param UserIdentity|null $user User object; null by default (=current user)
	 */
	function initProfile( $user = null ) {
		if ( $user === null ) {
			$user = $this->getUser();
		}

		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_profile',
			[ 'up_actor' ],
			[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);
		if ( $s === false ) {
			$dbw->insert(
				'user_profile',
				[ 'up_actor' => $user->getActorId() ],
				__METHOD__
			);
		}
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $section
	 */
	public function execute( $section ) {
		global $wgUpdateProfileInRecentChanges, $wgUserProfileThresholds, $wgAutoConfirmCount, $wgEmailConfirmToEdit;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// This feature is only available for logged-in users.
		$this->requireLogin();

		// Database operations require write mode
		$this->checkReadOnly();

		// No need to allow blocked users to access this page, they could abuse it, y'know.
		if ( $user->getBlock() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();
		$out->setHTMLTitle( $this->msg( 'pagetitle', $this->msg( 'edit-profile-title' ) ) );

		/**
		 * Create thresholds based on user stats
		 */
		if ( is_array( $wgUserProfileThresholds ) && count( $wgUserProfileThresholds ) > 0 ) {
			$can_create = true;

			$stats = new UserStats( $user->getId(), $user->getName() );
			$stats_data = $stats->getUserStats();

			$thresholdReasons = [];
			foreach ( $wgUserProfileThresholds as $field => $threshold ) {
				// If the threshold is greater than the user's amount of whatever
				// statistic we're looking at, then it means that they can't use
				// this special page.
				// Why, oh why did I want to be so fucking smart with these
				// field names?! This str_replace() voodoo all over the place is
				// outright painful.
				$correctField = str_replace( '-', '_', $field );
				if ( $stats_data[$correctField] < $threshold ) {
					$can_create = false;
					$thresholdReasons[$threshold] = $field;
				}
			}

			$hasEqualEditThreshold = isset( $wgUserProfileThresholds['edit'] ) && $wgUserProfileThresholds['edit'] == $wgAutoConfirmCount;
			$can_create = ( $user->isAllowed( 'createpage' ) && $hasEqualEditThreshold ) ? true : $can_create;

			// Ensure we enforce profile creation exclusively to members who confirmed their email
			if ( $user->getEmailAuthenticationTimestamp() === null && $wgEmailConfirmToEdit === true ) {
				$can_create = false;
			}

			// Boo, go away!
			if ( !$can_create ) {
				$out->setPageTitle( $this->msg( 'user-profile-create-threshold-title' )->text() );
				$thresholdMessages = [];
				foreach ( $thresholdReasons as $requiredAmount => $reason ) {
					// Replace underscores with hyphens for consistency in i18n
					// message names.
					$reason = str_replace( '_', '-', $reason );
					/**
					 * For grep:
					 * user-profile-create-threshold-edits
					 * user-profile-create-threshold-votes
					 * user-profile-create-threshold-comments
					 * user-profile-create-threshold-comment-score-plus
					 * user-profile-create-threshold-comment-score-minus
					 * user-profile-create-threshold-recruits
					 * user-profile-create-threshold-friend-count
					 * user-profile-create-threshold-foe-count
					 * user-profile-create-threshold-weekly-wins
					 * user-profile-create-threshold-monthly-wins
					 * user-profile-create-threshold-only-confirmed-email
					 * user-profile-create-threshold-poll-votes
					 * user-profile-create-threshold-picture-game-votes
					 * user-profile-create-threshold-quiz-created
					 * user-profile-create-threshold-quiz-answered
					 * user-profile-create-threshold-quiz-correct
					 * user-profile-create-threshold-quiz-points
					 */
					$thresholdMessages[] = $this->msg( 'user-profile-create-threshold-' . $reason )->numParams( $requiredAmount )->parse();
				}
				// Set a useful message of why.
				if ( $user->getEmailAuthenticationTimestamp() === null && $wgEmailConfirmToEdit === true ) {
					$thresholdMessages[] = $this->msg( 'user-profile-create-threshold-only-confirmed-email' )->text();
				}
				$out->addHTML(
					$this->msg( 'user-profile-create-threshold-reason',
						$this->getLanguage()->commaList( $thresholdMessages )
					)->parse()
				);
				return;
			}
		}

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.clearfix',
			'ext.socialprofile.userprofile.tabs.css',
			'ext.socialprofile.special.updateprofile.css'
		] );
		$out->addModules( 'ext.userProfile.updateProfile' );

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			// NoJS support
			if ( $request->getBool( 'should_update_field_visibilities' ) ) {
				$newFieldVisibilities = [];
				foreach ( $request->getValues() as $key => $val ) {
					if ( preg_match( '/up_/i', $key ) ) {
						$newFieldVisibilities[$key] = $val;
					}
				}
				if ( !empty( $newFieldVisibilities ) ) {
					foreach ( $newFieldVisibilities as $fieldKey => $visibility ) {
						// TODO Would be nice if the SPUserSecurity class had a batch API of
						// some kind for situations like these...
						SPUserSecurity::setPrivacy( $user, $fieldKey, $visibility );
					}
				}
			}

			if ( !$section ) {
				$section = 'basic';
			}
			switch ( $section ) {
				case 'basic':
					$this->saveProfileBasic( $user );
					$this->saveBasicSettings( $user );
					break;
				case 'personal':
					$this->saveProfilePersonal( $user );
					break;
				case 'custom':
					$this->saveProfileCustom( $user );
					break;
				case 'preferences':
					$this->saveSocialPreferences();
					break;
			}

			UserProfile::clearCache( $user );

			$log = new LogPage( 'profile' );
			if ( !$wgUpdateProfileInRecentChanges ) {
				$log->updateRecentChanges = false;
			}
			$log->addEntry(
				'profile',
				$user->getUserPage(),
				$this->msg( 'user-profile-update-log-section' )
					->inContentLanguage()->text() .
					" '{$section}'",
				[],
				$user
			);
			$out->addHTML(
				'<span class="profile-on">' .
				$this->msg( 'user-profile-update-saved' )->escaped() .
				'</span><br /><br />'
			);

			// create the user page if it doesn't exist yet
			$title = Title::makeTitle( NS_USER, $user->getName() );
			if ( method_exists( MediaWikiServices::class, 'getWikiPageFactory' ) ) {
				// MW 1.36+
				$page = MediaWikiServices::getInstance()->getWikiPageFactory()->newFromTitle( $title );
			} else {
				$page = WikiPage::factory( $title );
			}
			if ( !$page->exists() ) {
				if ( method_exists( $page, 'doUserEditContent' ) ) {
					// MW 1.36+
					$page->doUserEditContent(
						ContentHandler::makeContent( '', $title ),
						$this->getUser(),
						'create user page',
						EDIT_SUPPRESS_RC
					);
				} else {
					$page->doEditContent(
						ContentHandler::makeContent( '', $title ),
						'create user page',
						EDIT_SUPPRESS_RC
					);
				}
			}
		}

		if ( !$section ) {
			$section = 'basic';
		}
		switch ( $section ) {
			case 'basic':
				$out->addHTML( $this->displayBasicForm( $user ) );
				break;
			case 'personal':
				$out->addHTML( $this->displayPersonalForm( $user ) );
				break;
			case 'custom':
				$out->addHTML( $this->displayCustomForm( $user ) );
				break;
			case 'preferences':
				$out->addHTML( $this->displayPreferencesForm() );
				break;
		}
	}

	/**
	 * Save basic settings about the user (real name, e-mail address) into the
	 * database.
	 *
	 * @param User $user Representing the current user
	 */
	function saveBasicSettings( $user ) {
		global $wgEmailAuthentication;

		$request = $this->getRequest();

		$user->setRealName( $request->getVal( 'real_name' ) );
		$user->setEmail( $request->getVal( 'email' ) );

		if ( $user->getEmail() != $request->getVal( 'email' ) ) {
			$user->mEmailAuthenticated = null; # but flag as "dirty" = unauthenticated
		}

		if ( $wgEmailAuthentication && !$user->isEmailConfirmed() && $user->getEmail() ) {
			# Mail a temporary password to the dirty address.
			# User can come back through the confirmation URL to re-enable email.
			$status = $user->sendConfirmationMail();
			if ( $status->isGood() ) {
				$this->getOutput()->addWikiMsg( 'confirmemail_sent' );
			} else {
				$this->getOutput()->addWikiTextAsInterface( $status->getWikiText( 'confirmemail_sendfailed' ) );
			}
		}
		$user->saveSettings();
	}

	/**
	 * Save social preferences into the database.
	 */
	function saveSocialPreferences() {
		$request = $this->getRequest();
		$user = $this->getUser();

		$notify_friend = $request->getVal( 'notify_friend' );
		$notify_gift = $request->getVal( 'notify_gift' );
		$notify_challenge = $request->getVal( 'notify_challenge' );
		$notify_honorifics = $request->getVal( 'notify_honorifics' );
		$notify_message = $request->getVal( 'notify_message' );
		$show_year_of_birth = $request->getVal( 'show_year_of_birth', 0 );
		if ( $notify_friend == '' ) {
			$notify_friend = 0;
		}
		if ( $notify_gift == '' ) {
			$notify_gift = 0;
		}
		if ( $notify_challenge == '' ) {
			$notify_challenge = 0;
		}
		if ( $notify_honorifics == '' ) {
			$notify_honorifics = 0;
		}
		if ( $notify_message == '' ) {
			$notify_message = 0;
		}
		$userOptionsManager = MediaWikiServices::getInstance()->getUserOptionsManager();
		$userOptionsManager->setOption( $user, 'notifygift', $notify_gift );
		$userOptionsManager->setOption( $user, 'notifyfriendrequest', $notify_friend );
		$userOptionsManager->setOption( $user, 'notifychallenge', $notify_challenge );
		$userOptionsManager->setOption( $user, 'notifyhonorifics', $notify_honorifics );
		$userOptionsManager->setOption( $user, 'notifymessage', $notify_message );
		$userOptionsManager->setOption( $user, 'showyearofbirth', $show_year_of_birth );
		$userOptionsManager->saveOptions( $user );

		// Allow extensions like UserMailingList do their magic here
		$this->getHookContainer()->run( 'SpecialUpdateProfile::saveSettings_pref', [ $this, $request ] );
	}

	public static function formatBirthdayDB( $birthday ) {
		$dob = explode( '/', $birthday );
		if ( count( $dob ) == 2 || count( $dob ) == 3 ) {
			$year = $dob[2] ?? '00';
			$month = $dob[0];
			$day = $dob[1];
			$birthday_date = $year . '-' . $month . '-' . $day;
		} else {
			$birthday_date = null;
		}
		return $birthday_date;
	}

	public static function formatBirthday( $birthday, $showYOB = false ) {
		$dob = explode( '-', $birthday );
		if ( count( $dob ) == 3 ) {
			$month = $dob[1];
			$day = $dob[2];
			$birthday_date = $month . '/' . $day;
			if ( $showYOB ) {
				$year = $dob[0];
				$birthday_date .= '/' . $year;
			}
		} else {
			$birthday_date = '';
		}
		return $birthday_date;
	}

	/**
	 * Save the basic user profile info fields into the database.
	 *
	 * @param UserIdentity|null $user User object, null by default (=the current user)
	 */
	function saveProfileBasic( $user = null ) {
		if ( $user === null ) {
			$user = $this->getUser();
		}

		$this->initProfile( $user );
		$dbw = wfGetDB( DB_MASTER );
		$request = $this->getRequest();

		$basicProfileData = [
			'up_location_city' => $request->getVal( 'location_city' ) ?? '',
			'up_location_state' => $request->getVal( 'location_state' ) ?? '',
			'up_location_country' => $request->getVal( 'location_country' ) ?? '',

			'up_hometown_city' => $request->getVal( 'hometown_city' ) ?? '',
			'up_hometown_state' => $request->getVal( 'hometown_state' ) ?? '',
			'up_hometown_country' => $request->getVal( 'hometown_country' ) ?? '',

			'up_birthday' => self::formatBirthdayDB( $request->getVal( 'birthday' ) ),
			'up_about' => $request->getVal( 'about' ) ?? '',
			'up_occupation' => $request->getVal( 'occupation' ) ?? '',
			'up_schools' => $request->getVal( 'schools' ) ?? '',
			'up_places_lived' => $request->getVal( 'places' ) ?? '',
			'up_websites' => $request->getVal( 'websites' ) ?? '',
			'up_relationship' => $request->getVal( 'relationship' ) ?? 0
		];

		$dbw->update(
			'user_profile',
			/* SET */$basicProfileData,
			/* WHERE */[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);

		// BasicProfileChanged hook
		$basicProfileData['up_name'] = $request->getVal( 'real_name' );
		$basicProfileData['up_email'] = $request->getVal( 'email' );
		$this->getHookContainer()->run( 'BasicProfileChanged', [ $user, $basicProfileData ] );
		// end of the hook

		UserProfile::clearCache( $user );
	}

	/**
	 * Save the four custom (site-specific) user profile fields into the
	 * database.
	 *
	 * @param UserIdentity|null $user
	 */
	function saveProfileCustom( $user = null ) {
		if ( $user === null ) {
			$user = $this->getUser();
		}

		$this->initProfile( $user );
		$request = $this->getRequest();

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'user_profile',
			/* SET */[
				'up_custom_1' => $request->getVal( 'custom1' ),
				'up_custom_2' => $request->getVal( 'custom2' ),
				'up_custom_3' => $request->getVal( 'custom3' ),
				'up_custom_4' => $request->getVal( 'custom4' )
			],
			/* WHERE */[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);

		UserProfile::clearCache( $user );
	}

	/**
	 * Save the user's personal info (interests, such as favorite music or
	 * TV programs or video games, etc.) into the database.
	 *
	 * @param UserIdentity|null $user
	 */
	function saveProfilePersonal( $user = null ) {
		if ( $user === null ) {
			$user = $this->getUser();
		}

		$this->initProfile( $user );
		$request = $this->getRequest();

		$dbw = wfGetDB( DB_MASTER );

		$interestsData = [
			'up_companies' => $request->getVal( 'companies' ),
			'up_movies' => $request->getVal( 'movies' ),
			'up_music' => $request->getVal( 'music' ),
			'up_tv' => $request->getVal( 'tv' ),
			'up_books' => $request->getVal( 'books' ),
			'up_magazines' => $request->getVal( 'magazines' ),
			'up_video_games' => $request->getVal( 'videogames' ),
			'up_snacks' => $request->getVal( 'snacks' ),
			'up_drinks' => $request->getVal( 'drinks' )
		];

		$dbw->update(
			'user_profile',
			/* SET */$interestsData,
			/* WHERE */[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);

		// PersonalInterestsChanged hook
		$this->getHookContainer()->run( 'PersonalInterestsChanged', [ $user, $interestsData ] );
		// end of the hook

		UserProfile::clearCache( $user );
	}

	/**
	 * @param User $user
	 *
	 * @return string
	 */
	function displayBasicForm( $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow( 'user_profile',
			[
				'up_location_city', 'up_location_state', 'up_location_country',
				'up_hometown_city', 'up_hometown_state', 'up_hometown_country',
				'up_birthday', 'up_occupation', 'up_about', 'up_schools',
				'up_places_lived', 'up_websites'
			],
			[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);

		$showYOB = true;
		if ( $s !== false ) {
			$location_city = $s->up_location_city;
			$location_state = $s->up_location_state;
			$location_country = $s->up_location_country;
			$about = $s->up_about;
			$occupation = $s->up_occupation;
			$hometown_city = $s->up_hometown_city;
			$hometown_state = $s->up_hometown_state;
			$hometown_country = $s->up_hometown_country;
			$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
			$showYOB = $userOptionsLookup->getIntOption( $user, 'showyearofbirth', !isset( $s->up_birthday ) ) == 1;
			$birthday = self::formatBirthday( $s->up_birthday, $showYOB );
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
			[ 'user_real_name', 'user_email', 'user_email_authenticated' ],
			[ 'user_id' => $user->getId() ],
			__METHOD__
		);

		if ( $s !== false ) {
			$real_name = $s->user_real_name;
			$email = $s->user_email;
			$old_email = $s->user_email;
			$email_authenticated = $s->user_email_authenticated;
		}

		$countries = explode( "\n*", $this->msg( 'userprofile-country-list' )->inContentLanguage()->text() );
		array_shift( $countries );

		$this->getOutput()->setPageTitle( $this->msg( 'edit-profile-title' )->escaped() );

		$form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-personal' )->escaped() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		// NoJS thing -- JS sets this to false, which means that in execute() we skip updating
		// profile field visibilities for users with JS enabled can do and have already done that
		// with the nice JS-enabled drop-down (instead of having to rely on a plain ol'
		// <select> + form submission, as no-JS users have to)
		$form .= Html::hidden( 'should_update_field_visibilities', true );
		$form .= '<div class="profile-info clearfix">';
		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-info' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-name' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="real_name" id="real_name" value="' . htmlspecialchars( $real_name, ENT_QUOTES ) . '"/></p>
			<div class="visualClear">' . $this->renderEye( 'up_real_name' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'email' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="email" id="email" value="' . htmlspecialchars( $email, ENT_QUOTES ) . '"/>';
		if ( !$user->mEmailAuthenticated ) {
			$confirm = SpecialPage::getTitleFor( 'Confirmemail' );
			$form .= " <a href=\"{$confirm->getFullURL()}\">" . $this->msg( 'confirmemail' )->escaped() . '</a>';
		}
		$form .= '</p>
			<div class="visualClear">' . $this->renderEye( 'up_email' ) . '</div>';
		if ( !$user->mEmailAuthenticated ) {
			$form .= '<p class="profile-update-unit-left"></p>
				<p class="profile-update-unit-small">' .
					$this->msg( 'user-profile-personal-email-needs-auth' )->escaped() .
				'</p>';
		}
		$form .= '<div class="visualClear"></div>
		</div>
		<div class="visualClear"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-location' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-city' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="location_city" id="location_city" value="' . ( isset( $location_city ) ? htmlspecialchars( $location_city, ENT_QUOTES ) : '' ) . '" /></p>
			<div class="visualClear">' . $this->renderEye( 'up_location_city' ) . '</div>
			<p class="profile-update-unit-left" id="location_state_label">' . $this->msg( 'user-profile-personal-country' )->escaped() . '</p>';
		$form .= '<p class="profile-update-unit">';
		// Hidden helper for UpdateProfile.js since JS cannot directly access PHP variables
		$form .= '<input type="hidden" id="location_state_current" value="' . ( isset( $location_state ) ? htmlspecialchars( $location_state, ENT_QUOTES ) : '' ) . '" />';
		$form .= '<span id="location_state_form">';
		$form .= '</span>';
		$form .= '<select name="location_country" id="location_country"><option></option>';

		foreach ( $countries as $country ) {
			$form .= Xml::option( $country, $country, ( $country == $location_country ) );
		}

		$form .= '</select>';
		$form .= '</p>
			<div class="visualClear">' . $this->renderEye( 'up_location_country' ) . '</div>
		</div>
		<div class="visualClear"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-hometown' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-city' )->escaped() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="hometown_city" id="hometown_city" value="' . ( isset( $hometown_city ) ? htmlspecialchars( $hometown_city, ENT_QUOTES ) : '' ) . '" /></p>
			<div class="visualClear">' . $this->renderEye( 'up_hometown_city' ) . '</div>
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
			<div class="visualClear">' . $this->renderEye( 'up_hometown_country' ) . '</div>
		</div>
		<div class="visualClear"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-birthday' )->escaped() . '</p>
			<p class="profile-update-unit-left" id="birthday-format">' .
				$this->msg( $showYOB ? 'user-profile-personal-birthdate-with-year' : 'user-profile-personal-birthdate' )->escaped() .
			'</p>
			<p class="profile-update-unit"><input type="text"' .
			( $showYOB ? ' class="long-birthday"' : null ) .
			' size="25" name="birthday" id="birthday" value="' .
			( isset( $birthday ) ? htmlspecialchars( $birthday, ENT_QUOTES ) : '' ) . '" /></p>
			<div class="visualClear">' . $this->renderEye( 'up_birthday' ) . '</div>
		</div><div class="visualClear"></div>';

		$form .= '<div class="profile-update" id="profile-update-personal-aboutme">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-aboutme' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-aboutme' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="about" id="about" rows="3" cols="75">' . ( isset( $about ) ? htmlspecialchars( $about, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_about' ) . '</div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-work">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-work' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-occupation' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="occupation" id="occupation" rows="2" cols="75">' . ( isset( $occupation ) ? htmlspecialchars( $occupation, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_occupation' ) . '</div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-education">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-education' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-schools' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="schools" id="schools" rows="2" cols="75">' . ( isset( $schools ) ? htmlspecialchars( $schools, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_schools' ) . '</div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-places">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-places' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-placeslived' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="places" id="places" rows="3" cols="75">' . ( isset( $places ) ? htmlspecialchars( $places, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_places_lived' ) . '</div>
		</div>
		<div class="visualClear"></div>

		<div class="profile-update" id="profile-update-personal-web">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-web' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-websites' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="websites" id="websites" rows="2" cols="75">' . ( isset( $websites ) ? htmlspecialchars( $websites, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_websites' ) . '</div>
		</div>
		<div class="visualClear"></div>';

		$form .= '
			<input type="submit" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->escaped() . '" size="20" onclick="document.profile.submit()" />
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $form;
	}

	/**
	 * @param UserIdentity $user
	 *
	 * @return string
	 */
	function displayPersonalForm( $user ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_profile',
			[
				'up_about', 'up_places_lived', 'up_websites', 'up_relationship',
				'up_occupation', 'up_companies', 'up_schools', 'up_movies',
				'up_tv', 'up_music', 'up_books', 'up_video_games',
				'up_magazines', 'up_snacks', 'up_drinks'
			],
			[ 'up_actor' => $user->getActorId() ],
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

		$form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-interests' )->escaped() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		// NoJS thing -- JS sets this to false, which means that in execute() we skip updating
		// profile field visibilities for users with JS enabled can do and have already done that
		// with the nice JS-enabled drop-down (instead of having to rely on a plain ol'
		// <select> + form submission, as no-JS users have to)
		$form .= Html::hidden( 'should_update_field_visibilities', true );
		$form .= '<div class="profile-info profile-info-other-info clearfix">
			<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-interests-entertainment' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-movies' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="movies" id="movies" rows="3" cols="75">' . ( isset( $movies ) ? htmlspecialchars( $movies, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_movies' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-tv' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="tv" id="tv" rows="3" cols="75">' . ( isset( $tv ) ? htmlspecialchars( $tv, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_tv' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-music' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="music" id="music" rows="3" cols="75">' . ( isset( $music ) ? htmlspecialchars( $music, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_music' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-books' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="books" id="books" rows="3" cols="75">' . ( isset( $books ) ? htmlspecialchars( $books, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_books' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-magazines' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="magazines" id="magazines" rows="3" cols="75">' . ( isset( $magazines ) ? htmlspecialchars( $magazines, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_magazines' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-videogames' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="videogames" id="videogames" rows="3" cols="75">' . ( isset( $videogames ) ? htmlspecialchars( $videogames, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_video_games' ) . '</div>
			</div>
			<div class="profile-info">
			<p class="profile-update-title">' . $this->msg( 'user-profile-interests-eats' )->escaped() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-foodsnacks' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="snacks" id="snacks" rows="3" cols="75">' . ( isset( $snacks ) ? htmlspecialchars( $snacks, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_snacks' ) . '</div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-drinks' )->escaped() . '</p>
			<p class="profile-update-unit">
				<textarea name="drinks" id="drinks" rows="3" cols="75">' . ( isset( $drinks ) ? htmlspecialchars( $drinks, ENT_QUOTES ) : '' ) . '</textarea>
			</p>
			<div class="visualClear">' . $this->renderEye( 'up_drinks' ) . '</div>
			</div>
			<input type="submit" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->escaped() . '" size="20" onclick="document.profile.submit()" />
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $form;
	}

	/**
	 * Displays the form for toggling notifications related to social tools
	 * (e-mail me when someone friends/foes me, send me a gift, etc.)
	 *
	 * @return string HTML
	 */
	function displayPreferencesForm() {
		$user = $this->getUser();

		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_profile',
			[ 'up_birthday' ],
			[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);

		$showYOB = !$s || !$s->up_birthday;

		// @todo If the checkboxes are in front of the option, this would look more like Special:Preferences
		$this->getOutput()->setPageTitle( $this->msg( 'preferences' )->escaped() );

		$form = UserProfile::getEditProfileNav( $this->msg( 'preferences' )->escaped() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		$form .= '<div class="profile-info clearfix">
			<div class="profile-update">
				<p class="profile-update-title">' . $this->msg( 'user-profile-preferences-emails' )->escaped() . '</p>';
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			$form .= '<p class="profile-update-row">' .
				$this->msg( 'user-profile-preferences-emails-manage' )->parse() .
				'</p>';
		} else {
			$form .= '<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-personalmessage' )->escaped() .
					' <input type="checkbox" size="25" name="notify_message" id="notify_message" value="1"' . ( ( $userOptionsLookup->getIntOption( $user, 'notifymessage', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>
				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-friendfoe' )->escaped() .
					' <input type="checkbox" size="25" class="createbox" name="notify_friend" id="notify_friend" value="1" ' . ( ( $userOptionsLookup->getIntOption( $user, 'notifyfriendrequest', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>
				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-gift' )->escaped() .
					' <input type="checkbox" size="25" name="notify_gift" id="notify_gift" value="1" ' . ( ( $userOptionsLookup->getIntOption( $user, 'notifygift', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>

				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-level' )->escaped() .
					' <input type="checkbox" size="25" name="notify_honorifics" id="notify_honorifics" value="1"' . ( ( $userOptionsLookup->getIntOption( $user, 'notifyhonorifics', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>';
		}

		$form .= '<p class="profile-update-title">' .
			$this->msg( 'user-profile-preferences-miscellaneous' )->escaped() .
			'</p>
			<p class="profile-update-row">' .
				$this->msg( 'user-profile-preferences-miscellaneous-show-year-of-birth' )->escaped() .
				' <input type="checkbox" size="25" name="show_year_of_birth" id="show_year_of_birth" value="1"' . ( ( $userOptionsLookup->getIntOption( $user, 'showyearofbirth', $showYOB ) == 1 ) ? 'checked' : '' ) . '/>
			</p>';

		// Allow extensions (like UserMailingList) to add new checkboxes
		$this->getHookContainer()->run( 'SpecialUpdateProfile::displayPreferencesForm', [ $this, &$form ] );

		$form .= '</div>
			<div class="visualClear"></div>';
		$form .= '<input type="submit" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->escaped() . '" size="20" onclick="document.profile.submit()" />';
		$form .= Html::hidden( 'wpEditToken', $user->getEditToken() );
		$form .= '</form>';
		$form .= '</div>';

		return $form;
	}

	/**
	 * Displays the form for editing custom (site-specific) information.
	 *
	 * @param UserIdentity $user
	 * @return string HTML
	 */
	function displayCustomForm( $user ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'user_profile',
			[
				'up_custom_1', 'up_custom_2', 'up_custom_3', 'up_custom_4',
				'up_custom_5'
			],
			[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);

		if ( $s !== false ) {
			$custom1 = $s->up_custom_1;
			$custom2 = $s->up_custom_2;
			$custom3 = $s->up_custom_3;
			$custom4 = $s->up_custom_4;
		}

		$this->getOutput()->setPageTitle( $this->msg( 'user-profile-tidbits-title' )->escaped() );

		$form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-custom' )->escaped() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		// NoJS thing -- JS sets this to false, which means that in execute() we skip updating
		// profile field visibilities for users with JS enabled can do and have already done that
		// with the nice JS-enabled drop-down (instead of having to rely on a plain ol'
		// <select> + form submission, as no-JS users have to)
		$form .= Html::hidden( 'should_update_field_visibilities', true );
		$form .= '<div class="profile-info profile-info-custom-info clearfix">
				<div class="profile-update">
					<p class="profile-update-title">' . $this->msg( 'user-profile-tidbits-title' )->inContentLanguage()->parse() . '</p>
					<div id="profile-update-custom1">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field1' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom1" id="fav_moment" rows="3" cols="75">' . ( isset( $custom1 ) && $custom1 ? htmlspecialchars( $custom1, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear">' . $this->renderEye( 'up_custom_1' ) . '</div>
					<div id="profile-update-custom2">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field2' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom2" id="least_moment" rows="3" cols="75">' . ( isset( $custom2 ) && $custom2 ? htmlspecialchars( $custom2, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear">' . $this->renderEye( 'up_custom_2' ) . '</div>
					<div id="profile-update-custom3">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field3' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom3" id="fav_athlete" rows="3" cols="75">' . ( isset( $custom3 ) && $custom3 ? htmlspecialchars( $custom3, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear">' . $this->renderEye( 'up_custom_3' ) . '</div>
					<div id="profile-update-custom4">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field4' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom4" id="least_fav_athlete" rows="3" cols="75">' . ( isset( $custom4 ) && $custom4 ? htmlspecialchars( $custom4, ENT_QUOTES ) : '' ) . '</textarea>
					</p>
					</div>
					<div class="visualClear">' . $this->renderEye( 'up_custom_4' ) . '</div>
				</div>
			<input type="submit" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->escaped() . '" size="20" onclick="document.profile.submit()" />
			</div>
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
		</form>';

		return $form;
	}

	/**
	 * Renders fields privacy button by field code
	 *
	 * @param string $fieldCode Internal field code, such as up_movies for the "Movies" field
	 *
	 * @return string
	 */
	private function renderEye( $fieldCode ) {
		return SPUserSecurity::renderEye( $fieldCode, $this->getUser() );
	}
}
