<?php
/**
 * A special page to allow users to update their social profile
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class SpecialUpdateProfile extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'UpdateProfile' );
	}

	/**
	 * Initialize the user_profile records for a given user (either the current
	 * user or someone else).
	 *
	 * @param $user Object: User object; null by default (=current user)
	 */
	function initProfile( $user = null ) {
		if ( is_null( $user ) ) {
			$user = $this->getUser();
		}

		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_profile',
			array( 'up_user_id' ),
			array( 'up_user_id' => $user->getID() ),
			__METHOD__
		);
		if ( $s === false ) {
			$dbw->insert(
				'user_profile',
				array( 'up_user_id' => $user->getID() ),
				__METHOD__
			);
		}
	}

	/**
	 * Show the special page
	 *
	 * @param $section Mixed: parameter passed to the page or null
	 */
	public function execute( $section ) {
		global $wgUpdateProfileInRecentChanges, $wgUserProfileThresholds, $wgSupressPageTitle;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$wgSupressPageTitle = true;

		// Set the page title, robot policies, etc.
		$this->setHeaders();
		$out->setHTMLTitle( $this->msg( 'pagetitle', $this->msg( 'edit-profile-title' )->plain() )->parse() );

		// This feature is only available for logged-in users.
		if ( !$user->isLoggedIn() ) {
			$out->setPageTitle( $this->msg( 'user-profile-update-notloggedin-title' )->plain() );
			$out->addWikiMsg( 'user-profile-update-notloggedin-text' );
			return;
		}

		// No need to allow blocked users to access this page, they could abuse it, y'know.
		if ( $user->isBlocked() ) {
			$out->blockedPage( false );
			return false;
		}

		// Database operations require write mode
		if ( wfReadOnly() ) {
			$out->readOnlyPage();
			return;
		}

		/**
		 * Create thresholds based on user stats
		 */
		if ( is_array( $wgUserProfileThresholds ) && count( $wgUserProfileThresholds ) > 0 ) {
			$can_create = true;

			$stats = new UserStats( $user->getId(), $user->getName() );
			$stats_data = $stats->getUserStats();

			$thresholdReasons = array();
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

			// Boo, go away!
			if ( $can_create == false ) {
				global $wgSupressPageTitle;
				$wgSupressPageTitle = false;
				$out->setPageTitle( $this->msg( 'user-profile-create-threshold-title' )->text() );
				$thresholdMessages = array();
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
					 * user-profile-create-threshold-poll-votes
					 * user-profile-create-threshold-picture-game-votes
					 * user-profile-create-threshold-quiz-created
					 * user-profile-create-threshold-quiz-answered
					 * user-profile-create-threshold-quiz-correct
					 * user-profile-create-threshold-quiz-points
					*/
					$thresholdMessages[] = $this->msg( 'user-profile-create-threshold-' . $reason )->numParams( $requiredAmount )->parse();
				}
				$out->addHTML(
					$this->msg( 'user-profile-create-threshold-reason',
						$this->getLanguage()->commaList( $thresholdMessages )
					)->parse()
				);
				return '';
			}
		}

		// Add CSS & JS
		$out->addModuleStyles( 'ext.socialprofile.userprofile.css' );
		$out->addModules( 'ext.userProfile.updateProfile' );

		if ( $request->wasPosted() ) {
			if ( !$section ) {
				$section = 'basic';
			}
			switch( $section ) {
				case 'basic':
					$this->saveProfileBasic( $user );
					$this->saveSettings_basic( $user );
					break;
				case 'personal':
					$this->saveProfilePersonal( $user );
					break;
				case 'custom':
					$this->saveProfileCustom( $user );
					break;
				case 'preferences':
					$this->saveSettings_pref();
					break;
			}

			UserProfile::clearCache( $user->getID() );

			$log = new LogPage( 'profile' );
			if ( !$wgUpdateProfileInRecentChanges ) {
				$log->updateRecentChanges = false;
			}
			$log->addEntry(
				'profile',
				$user->getUserPage(),
				$this->msg( 'user-profile-update-log-section' )
					->inContentLanguage()->text() .
					" '{$section}'"
			);
			$out->addHTML(
				'<span class="profile-on">' .
				$this->msg( 'user-profile-update-saved' )->plain() .
				'</span><br /><br />'
			);

			// create the user page if it doesn't exist yet
			$title = Title::makeTitle( NS_USER, $user->getName() );
			$article = new Article( $title );
			if ( !$article->exists() ) {
				$article->doEdit( '', 'create user page', EDIT_SUPPRESS_RC );
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
	 * @param $user Object: User object representing the current user
	 */
	function saveSettings_basic( $user ) {
		global $wgEmailAuthentication;

		$request = $this->getRequest();

		$user->setRealName( $request->getVal( 'real_name' ) );
		$user->setEmail( $request->getVal( 'email' ) );

		if ( $user->getEmail() != $request->getVal( 'email' ) ) {
			$user->mEmailAuthenticated = null; # but flag as "dirty" = unauthenticated
		}

		if ( $wgEmailAuthentication && !$user->isEmailConfirmed() ) {
			# Mail a temporary password to the dirty address.
			# User can come back through the confirmation URL to re-enable email.
			$status = $user->sendConfirmationMail();
			if ( $status->isGood() ) {
				$this->getOutput()->addWikiMsg( 'confirmemail_sent' );
			} else {
				$this->getOutput()->addWikiText( $status->getWikiText( 'confirmemail_sendfailed' ) );
			}
		}
		$user->saveSettings();
	}

	/**
	 * Save social preferences into the database.
	 */
	function saveSettings_pref() {
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
		$user->setOption( 'notifygift', $notify_gift );
		$user->setOption( 'notifyfriendrequest', $notify_friend );
		$user->setOption( 'notifychallenge', $notify_challenge );
		$user->setOption( 'notifyhonorifics', $notify_honorifics );
		$user->setOption( 'notifymessage', $notify_message );
		$user->setOption( 'showyearofbirth', $show_year_of_birth );
		$user->saveSettings();

		// Allow extensions like UserMailingList do their magic here
		wfRunHooks( 'SpecialUpdateProfile::saveSettings_pref', array( $this, $request ) );
	}

	public static function formatBirthdayDB( $birthday ) {
		$dob = explode( '/', $birthday );
		if ( count( $dob ) == 2 || count( $dob ) == 3 ) {
			$year = isset( $dob[2] ) ? $dob[2] : 2007;
			$month = $dob[0];
			$day = $dob[1];
			$birthday_date = $year . '-' . $month . '-' . $day;
		} else {
			$birthday_date = '';
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
	 * @param $user Object: User object, null by default (=the current user)
	 */
	function saveProfileBasic( $user = null ) {
		global $wgMemc, $wgSitename;

		if ( is_null( $user ) ) {
			$user = $this->getUser();
		}

		$this->initProfile( $user );
		$dbw = wfGetDB( DB_MASTER );
		$request = $this->getRequest();

		$basicProfileData = array(
			'up_location_city' => $request->getVal( 'location_city' ),
			'up_location_state' => $request->getVal( 'location_state' ),
			'up_location_country' => $request->getVal( 'location_country' ),

			'up_hometown_city' => $request->getVal( 'hometown_city' ),
			'up_hometown_state' => $request->getVal( 'hometown_state' ),
			'up_hometown_country' => $request->getVal( 'hometown_country' ),

			'up_birthday' => self::formatBirthdayDB( $request->getVal( 'birthday' ) ),
			'up_about' => $request->getVal( 'about' ),
			'up_occupation' => $request->getVal( 'occupation' ),
			'up_schools' => $request->getVal( 'schools' ),
			'up_places_lived' => $request->getVal( 'places' ),
			'up_websites' => $request->getVal( 'websites' ),
			'up_relationship' => $request->getVal( 'relationship' )
		);

		$dbw->update(
			'user_profile',
			/* SET */$basicProfileData,
			/* WHERE */array( 'up_user_id' => $user->getID() ),
			__METHOD__
		);

		// BasicProfileChanged hook
		$basicProfileData['up_name'] = $request->getVal( 'real_name' );
		$basicProfileData['up_email'] = $request->getVal( 'email' );
		wfRunHooks( 'BasicProfileChanged', array( $user, $basicProfileData ) );
		// end of the hook

		$wgMemc->delete( wfMemcKey( 'user', 'profile', 'info', $user->getID() ) );
	}

	/**
	 * Save the four custom (site-specific) user profile fields into the
	 * database.
	 *
	 * @param $user Object: User object
	 */
	function saveProfileCustom( $user = null ) {
		global $wgMemc;

		if ( is_null( $user ) ) {
			$user = $this->getUser();
		}

		$this->initProfile( $user );
		$request = $this->getRequest();

		$dbw = wfGetDB( DB_MASTER );
		$dbw->update(
			'user_profile',
			/* SET */array(
				'up_custom_1' => $request->getVal( 'custom1' ),
				'up_custom_2' => $request->getVal( 'custom2' ),
				'up_custom_3' => $request->getVal( 'custom3' ),
				'up_custom_4' => $request->getVal( 'custom4' )
			),
			/* WHERE */array( 'up_user_id' => $user->getID() ),
			__METHOD__
		);

		$wgMemc->delete( wfMemcKey( 'user', 'profile', 'info', $user->getID() ) );
	}

	/**
	 * Save the user's personal info (interests, such as favorite music or
	 * TV programs or video games, etc.) into the database.
	 *
	 * @param $user Object: User object
	 */
	function saveProfilePersonal( $user = null ) {
		global $wgMemc;

		if ( is_null( $user ) ) {
			$user = $this->getUser();
		}

		$this->initProfile( $user );
		$request = $this->getRequest();

		$dbw = wfGetDB( DB_MASTER );

		$interestsData = array(
			'up_companies' => $request->getVal( 'companies' ),
			'up_movies' => $request->getVal( 'movies' ),
			'up_music' => $request->getVal( 'music' ),
			'up_tv' => $request->getVal( 'tv' ),
			'up_books' => $request->getVal( 'books' ),
			'up_magazines' => $request->getVal( 'magazines' ),
			'up_video_games' => $request->getVal( 'videogames' ),
			'up_snacks' => $request->getVal( 'snacks' ),
			'up_drinks' => $request->getVal( 'drinks' )
		);

		$dbw->update(
			'user_profile',
			/* SET */$interestsData,
			/* WHERE */array( 'up_user_id' => $user->getID() ),
			__METHOD__
		);

		// PersonalInterestsChanged hook
		wfRunHooks( 'PersonalInterestsChanged', array( $user, $interestsData ) );
		// end of the hook

		$wgMemc->delete( wfMemcKey( 'user', 'profile', 'info', $user->getID() ) );
	}

	/**
	 * @param $user Object: User
	 */
	function displayBasicForm( $user ) {
		$dbr = wfGetDB( DB_SLAVE );
		$s = $dbr->selectRow( 'user_profile',
			array(
				'up_location_city', 'up_location_state', 'up_location_country',
				'up_hometown_city', 'up_hometown_state', 'up_hometown_country',
				'up_birthday', 'up_occupation', 'up_about', 'up_schools',
				'up_places_lived', 'up_websites'
			),
			array( 'up_user_id' => $user->getID() ),
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
			$showYOB = $user->getIntOption( 'showyearofbirth', !isset( $s->up_birthday ) ) == 1;
			$birthday = self::formatBirthday( $s->up_birthday, $showYOB );
			$schools = $s->up_schools;
			$places = $s->up_places_lived;
			$websites = $s->up_websites;
		}

		if ( !isset( $location_country ) ) {
			$location_country = $this->msg( 'user-profile-default-country' )->inContentLanguage()->plain();
		}
		if ( !isset( $hometown_country ) ) {
			$hometown_country = $this->msg( 'user-profile-default-country' )->inContentLanguage()->plain();
		}

		$s = $dbr->selectRow(
			'user',
			array( 'user_real_name', 'user_email', 'user_email_authenticated' ),
			array( 'user_id' => $user->getID() ),
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

		$this->getOutput()->setPageTitle( $this->msg( 'edit-profile-title' )->plain() );

		$form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-personal' )->plain() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		$form .= '<div class="profile-info clearfix">';
		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-info' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-name' )->plain() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="real_name" id="real_name" value="' . $real_name . '"/></p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-email' )->plain() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="email" id="email" value="' . $email . '"/>';
		if ( !$user->mEmailAuthenticated ) {
			$confirm = SpecialPage::getTitleFor( 'Confirmemail' );
			$form .= " <a href=\"{$confirm->getFullURL()}\">" . $this->msg( 'user-profile-personal-confirmemail' )->plain() . '</a>';
		}
		$form .= '</p>
			<div class="cleared"></div>';
		if ( !$user->mEmailAuthenticated ) {
			$form .= '<p class="profile-update-unit-left"></p>
				<p class="profile-update-unit-small">' .
					$this->msg( 'user-profile-personal-email-needs-auth' )->plain() .
				'</p>';
		}
		$form .= '<div class="cleared"></div>
		</div>
		<div class="cleared"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-location' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-city' )->plain() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="location_city" id="location_city" value="' . ( isset( $location_city ) ? $location_city : '' ) . '" /></p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left" id="location_state_label">' . $this->msg( 'user-profile-personal-country' )->plain() . '</p>';
		$form .= '<p class="profile-update-unit">';
		$form .= '<span id="location_state_form">';
		$form .= "</span>
				<script type=\"text/javascript\">
					displaySection(\"location_state\",\"" . $location_country . "\",\"" . ( isset( $location_state ) ? $location_state : '' ) . "\");
				</script>";
		$form .= "<select name=\"location_country\" id=\"location_country\" onchange=\"displaySection('location_state',this.value,'')\"><option></option>";

		foreach ( $countries as $country ) {
			$form .= "<option value=\"{$country}\"" . ( ( $country == $location_country ) ? ' selected="selected"' : '' ) . ">";
			$form .= $country . "</option>\n";
		}

		$form .= '</select>';
		$form .= '</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-hometown' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-city' )->plain() . '</p>
			<p class="profile-update-unit"><input type="text" size="25" name="hometown_city" id="hometown_city" value="' . ( isset( $hometown_city ) ? $hometown_city : '' ) . '" /></p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left" id="hometown_state_label">' . $this->msg( 'user-profile-personal-country' )->plain() . '</p>
			<p class="profile-update-unit">';
		$form .= '<span id="hometown_state_form">';
		$form .= "</span>
			<script type=\"text/javascript\">
				displaySection(\"hometown_state\",\"" . $hometown_country . "\",\"" . ( isset( $hometown_state ) ? $hometown_state : '' ) . "\");
			</script>";
		$form .= "<select name=\"hometown_country\" id=\"hometown_country\" onchange=\"displaySection('hometown_state',this.value,'')\"><option></option>";

		foreach ( $countries as $country ) {
			$form .= "<option value=\"{$country}\"" . ( ( $country == $hometown_country ) ? ' selected="selected"' : '' ) . ">";
			$form .= $country . '</option>';
		}

		$form .= '</select>';
		$form .= '</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>';

		$form .= '<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-birthday' )->plain() . '</p>
			<p class="profile-update-unit-left" id="birthday-format">' .
				$this->msg( $showYOB ? 'user-profile-personal-birthdate-with-year' : 'user-profile-personal-birthdate' )->plain() .
			'</p>
			<p class="profile-update-unit"><input type="text"' .
			( $showYOB ? ' class="long-birthday"' : null ) .
			' size="25" name="birthday" id="birthday" value="' .
			( isset( $birthday ) ? $birthday : '' ) . '" /></p>
			<div class="cleared"></div>
		</div><div class="cleared"></div>';

		$form .= '<div class="profile-update" id="profile-update-personal-aboutme">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-aboutme' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-aboutme' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="about" id="about" rows="3" cols="75">' . ( isset( $about ) ? $about : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>

		<div class="profile-update" id="profile-update-personal-work">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-work' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-occupation' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="occupation" id="occupation" rows="2" cols="75">' . ( isset( $occupation ) ? $occupation : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>

		<div class="profile-update" id="profile-update-personal-education">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-education' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-schools' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="schools" id="schools" rows="2" cols="75">' . ( isset( $schools ) ? $schools : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>

		<div class="profile-update" id="profile-update-personal-places">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-places' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-placeslived' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="places" id="places" rows="3" cols="75">' . ( isset( $places ) ? $places : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>

		<div class="profile-update" id="profile-update-personal-web">
			<p class="profile-update-title">' . $this->msg( 'user-profile-personal-web' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-personal-websites' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="websites" id="websites" rows="2" cols="75">' . ( isset( $websites ) ? $websites : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
		</div>
		<div class="cleared"></div>';

		$form .= '
			<input type="button" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->plain() . '" size="20" onclick="document.profile.submit()" />
			</div>
		</form>';

		return $form;
	}

	/**
	 * @param $user Object: User
	 */
	function displayPersonalForm( $user ) {
		$dbr = wfGetDB( DB_SLAVE );
		$s = $dbr->selectRow(
			'user_profile',
			array(
				'up_about', 'up_places_lived', 'up_websites', 'up_relationship',
				'up_occupation', 'up_companies', 'up_schools', 'up_movies',
				'up_tv', 'up_music', 'up_books', 'up_video_games',
				'up_magazines', 'up_snacks', 'up_drinks'
			),
			array( 'up_user_id' => $user->getID() ),
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

		$this->getOutput()->setPageTitle( $this->msg( 'user-profile-section-interests' )->plain() );

		$form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-interests' )->plain() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">
			<div class="profile-info clearfix">
			<div class="profile-update">
			<p class="profile-update-title">' . $this->msg( 'user-profile-interests-entertainment' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-movies' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="movies" id="movies" rows="3" cols="75">' . ( isset( $movies ) ? $movies : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-tv' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="tv" id="tv" rows="3" cols="75">' . ( isset( $tv ) ? $tv : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-music' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="music" id="music" rows="3" cols="75">' . ( isset( $music ) ? $music : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-books' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="books" id="books" rows="3" cols="75">' . ( isset( $books ) ? $books : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-magazines' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="magazines" id="magazines" rows="3" cols="75">' . ( isset( $magazines ) ? $magazines : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-videogames' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="videogames" id="videogames" rows="3" cols="75">' . ( isset( $videogames ) ? $videogames : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			</div>
			<div class="profile-info clearfix">
			<p class="profile-update-title">' . $this->msg( 'user-profile-interests-eats' )->plain() . '</p>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-foodsnacks' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="snacks" id="snacks" rows="3" cols="75">' . ( isset( $snacks ) ? $snacks : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			<p class="profile-update-unit-left">' . $this->msg( 'user-profile-interests-drinks' )->plain() . '</p>
			<p class="profile-update-unit">
				<textarea name="drinks" id="drinks" rows="3" cols="75">' . ( isset( $drinks ) ? $drinks : '' ) . '</textarea>
			</p>
			<div class="cleared"></div>
			</div>
			<input type="button" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->plain() . '" size="20" onclick="document.profile.submit()" />
			</div>
		</form>';

		return $form;
	}

	/**
	 * Displays the form for toggling notifications related to social tools
	 * (e-mail me when someone friends/foes me, send me a gift, etc.)
	 *
	 * @return HTML
	 */
	function displayPreferencesForm() {
		$user = $this->getUser();

		$dbr = wfGetDB( DB_SLAVE );
		$s = $dbr->selectRow(
			'user_profile',
			array( 'up_birthday' ),
			array( 'up_user_id' => $user->getID() ),
			__METHOD__
		);

		$showYOB = isset( $s, $s->up_birthday ) ? false : true;

		// @todo If the checkboxes are in front of the option, this would look more like Special:Preferences
		$this->getOutput()->setPageTitle( $this->msg( 'user-profile-section-preferences' )->plain() );

		$form = UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-preferences' )->plain() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">';
		$form .= '<div class="profile-info clearfix">
			<div class="profile-update">
				<p class="profile-update-title">' . $this->msg( 'user-profile-preferences-emails' )->plain() . '</p>
				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-personalmessage' )->plain() .
					' <input type="checkbox" size="25" name="notify_message" id="notify_message" value="1"' . ( ( $user->getIntOption( 'notifymessage', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>
				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-friendfoe' )->plain() .
					' <input type="checkbox" size="25" class="createbox" name="notify_friend" id="notify_friend" value="1" ' . ( ( $user->getIntOption( 'notifyfriendrequest', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>
				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-gift' )->plain() .
					' <input type="checkbox" size="25" name="notify_gift" id="notify_gift" value="1" ' . ( ( $user->getIntOption( 'notifygift', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>

				<p class="profile-update-row">'
					. $this->msg( 'user-profile-preferences-emails-level' )->plain() .
					' <input type="checkbox" size="25" name="notify_honorifics" id="notify_honorifics" value="1"' . ( ( $user->getIntOption( 'notifyhonorifics', 1 ) == 1 ) ? 'checked' : '' ) . '/>
				</p>';

		$form .= '<p class="profile-update-title">' .
			$this->msg( 'user-profile-preferences-miscellaneous' )->plain() .
			'</p>
			<p class="profile-update-row">' .
				$this->msg( 'user-profile-preferences-miscellaneous-show-year-of-birth' )->plain() .
				' <input type="checkbox" size="25" name="show_year_of_birth" id="show_year_of_birth" value="1"' . ( ( $user->getIntOption( 'showyearofbirth', $showYOB ) == 1 ) ? 'checked' : '' ) . '/>
			</p>';

		// Allow extensions (like UserMailingList) to add new checkboxes
		wfRunHooks( 'SpecialUpdateProfile::displayPreferencesForm', array( $this, &$form ) );

		$form .= '</div>
			<div class="cleared"></div>';
		$form .= '<input type="button" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->plain() . '" size="20" onclick="document.profile.submit()" />
			</form>';
		$form .= '</div>';

		return $form;
	}

	/**
	 * Displays the form for editing custom (site-specific) information.
	 *
	 * @param $user Object: User
	 * @return $form Mixed: HTML output
	 */
	function displayCustomForm( $user ) {
		$dbr = wfGetDB( DB_MASTER );
		$s = $dbr->selectRow(
			'user_profile',
			array(
				'up_custom_1', 'up_custom_2', 'up_custom_3', 'up_custom_4',
				'up_custom_5'
			),
			array( 'up_user_id' => $user->getID() ),
			__METHOD__
		);

		if ( $s !== false ) {
			$custom1 = $s->up_custom_1;
			$custom2 = $s->up_custom_2;
			$custom3 = $s->up_custom_3;
			$custom4 = $s->up_custom_4;
		}

		$this->getOutput()->setHTMLTitle( $this->msg( 'pagetitle',
			$this->msg( 'user-profile-tidbits-title' )->inContentLanguage()->escaped()
		)->parse() );

		$form = '<h1>' . $this->msg( 'user-profile-tidbits-title' ) . '</h1>';
		$form .= UserProfile::getEditProfileNav( $this->msg( 'user-profile-section-custom' )->plain() );
		$form .= '<form action="" method="post" enctype="multipart/form-data" name="profile">
			<div class="profile-info clearfix">
				<div class="profile-update">
					<p class="profile-update-title">' . $this->msg( 'user-profile-tidbits-title' )->inContentLanguage()->parse() . '</p>
					<div id="profile-update-custom1">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field1' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom1" id="fav_moment" rows="3" cols="75">' . ( isset( $custom1 ) ? $custom1 : '' ) . '</textarea>
					</p>
					</div>
					<div class="cleared"></div>
					<div id="profile-update-custom2">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field2' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom2" id="least_moment" rows="3" cols="75">' . ( isset( $custom2 ) ? $custom2 : '' ) . '</textarea>
					</p>
					</div>
					<div class="cleared"></div>
					<div id="profile-update-custom3">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field3' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom3" id="fav_athlete" rows="3" cols="75">' . ( isset( $custom3 ) ? $custom3 : '' ) . '</textarea>
					</p>
					</div>
					<div class="cleared"></div>
					<div id="profile-update-custom4">
					<p class="profile-update-unit-left">' . $this->msg( 'custom-info-field4' )->inContentLanguage()->parse() . '</p>
					<p class="profile-update-unit">
						<textarea name="custom4" id="least_fav_athlete" rows="3" cols="75">' . ( isset( $custom4 ) ? $custom4 : '' ) . '</textarea>
					</p>
					</div>
					<div class="cleared"></div>
				</div>
			<input type="button" class="site-button" value="' . $this->msg( 'user-profile-update-button' )->plain() . '" size="20" onclick="document.profile.submit()" />
			</div>
		</form>';

		return $form;
	}
}
