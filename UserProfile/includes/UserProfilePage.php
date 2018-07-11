<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

/**
 * User profile Wiki Page
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class UserProfilePage extends Article {

	/**
	 * @var Title
	 */
	public $title = null;

	/**
	 * @var string user name of the user whose profile we're viewing
	 */
	public $user_name;

	/**
	 * @var int user ID of the user whose profile we're viewing
	 */
	public $user_id;

	/**
	 * @var User User object representing the user whose profile we're viewing
	 */
	public $user;

	/**
	 * @var bool is the current user the owner of the profile page?
	 */
	public $is_owner;

	/**
	 * @var array user profile data (interests, etc.) for the user whose
	 * profile we're viewing
	 */
	public $profile_data;

	/**
	 * @var array array of profile fields visible to the user viewing the profile
	 */
	public $profile_visible_fields;

	function __construct( $title ) {
		$context = $this->getContext();
		$user = $context->getUser();

		parent::__construct( $title );
		$this->user_name = $title->getText();
		$this->user_id = User::idFromName( $this->user_name );
		$this->user = User::newFromId( $this->user_id );
		$this->user->loadFromDatabase();

		$this->is_owner = ( $this->user_name == $user->getName() );

		$profile = new UserProfile( $this->user_name );
		$this->profile_data = $profile->getProfile();
		$this->profile_visible_fields = SPUserSecurity::getVisibleFields( $this->user_id, $user->getId() );
	}

	/**
	 * Is the current user the owner of the profile page?
	 * In other words, is the current user's username the same as that of the
	 * profile's owner's?
	 *
	 * @return bool
	 */
	function isOwner() {
		return $this->is_owner;
	}

	function view() {
		$context = $this->getContext();
		$out = $context->getOutput();
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		$out->setPageTitle( $this->mTitle->getPrefixedText() );

		// No need to display noarticletext, we use our own message
		if ( !$this->user_id ) {
			parent::view();
			return '';
		}

		$out->addHTML( '<div id="profile-top">' );
		$out->addHTML( $this->getProfileHeader() );
		$out->addHTML( '<div class="visualClear"></div></div>' );

		// User does not want social profile for User:user_name, so we just
		// show header + page content
		if (
			$this->getTitle()->getNamespace() == NS_USER &&
			$this->profile_data['user_id'] &&
			$this->profile_data['user_page_type'] == 0
		) {
			parent::view();
			return '';
		}

		// Left side
		$out->addHTML( '<div id="user-page-left" class="clearfix">' );

		// Avoid PHP 7.1 warning of passing $this by reference
		$userProfilePage = $this;

		if ( !Hooks::run( 'UserProfileBeginLeft', [ &$userProfilePage ] ) ) {
			$logger->debug( "{method}: UserProfileBeginLeft messed up profile!\n", [
				'method' => __METHOD__
			] );
		}

		$out->addHTML( $this->getRelationships( 1 ) );
		$out->addHTML( $this->getRelationships( 2 ) );
		$out->addHTML( $this->getGifts() );
		$out->addHTML( $this->getAwards() );
		$out->addHTML( $this->getCustomInfo() );
		$out->addHTML( $this->getInterests() );
		$out->addHTML( $this->getFanBoxes() );
		$out->addHTML( $this->getUserStats() );

		if ( !Hooks::run( 'UserProfileEndLeft', [ &$userProfilePage ] ) ) {
			$logger->debug( "{method}: UserProfileEndLeft messed up profile!\n", [
				'method' => __METHOD__
			] );
		}

		$out->addHTML( '</div>' );

		$logger->debug( "profile start right\n" );

		// Right side
		$out->addHTML( '<div id="user-page-right" class="clearfix">' );

		if ( !Hooks::run( 'UserProfileBeginRight', [ &$userProfilePage ] ) ) {
			$logger->debug( "{method}: UserProfileBeginRight messed up profile!\n", [
				'method' => __METHOD__
			] );
		}

		$out->addHTML( $this->getPersonalInfo() );
		$out->addHTML( $this->getActivity() );
		// Hook for BlogPage
		if ( !Hooks::run( 'UserProfileRightSideAfterActivity', [ $this ] ) ) {
			$logger->debug( "{method}: UserProfileRightSideAfterActivity hook messed up profile!\n", [
				'method' => __METHOD__
			] );
		}
		$out->addHTML( $this->getCasualGames() );
		$out->addHTML( $this->getUserBoard() );

		if ( !Hooks::run( 'UserProfileEndRight', [ &$userProfilePage ] ) ) {
			$logger->debug( "{method}: UserProfileEndRight messed up profile!\n", [
				'method' => __METHOD__
			] );
		}

		$out->addHTML( '</div><div class="visualClear"></div>' );
	}

	function getUserStatsRow( $label, $value ) {
		$output = ''; // Prevent E_NOTICE

		$context = $this->getContext();
		$language = $context->getLanguage();

		if ( $value != 0 ) {
			$formattedValue = $language->formatNum( $value );
			$output = "<div>
					<b>{$label}</b>
					{$formattedValue}
			</div>";
		}

		return $output;
	}

	function getUserStats() {
		global $wgUserProfileDisplay;

		if ( $wgUserProfileDisplay['stats'] == false ) {
			return '';
		}

		$output = ''; // Prevent E_NOTICE

		$user_id = $this->user_id;
		$user_name = $this->user_name;

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();

		$total_value = $stats_data['edits'] . $stats_data['votes'] .
						$stats_data['comments'] . $stats_data['recruits'] .
						$stats_data['poll_votes'] .
						$stats_data['picture_game_votes'] .
						$stats_data['quiz_points'];

		if ( $total_value != 0 ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'statistics' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
					</div>
					<div class="action-left">
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container bold-fix">' .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-edits', $stats_data['edits'] )->escaped(),
					$stats_data['edits']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-votes', $stats_data['votes'] )->escaped(),
					$stats_data['votes']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-comments', $stats_data['comments'] )->escaped(),
					$stats_data['comments'] ) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-recruits', $stats_data['recruits'] )->escaped(),
					$stats_data['recruits']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-poll-votes', $stats_data['poll_votes'] )->escaped(),
					$stats_data['poll_votes']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-picture-game-votes', $stats_data['picture_game_votes'] )->escaped(),
					$stats_data['picture_game_votes']
				) .
				$this->getUserStatsRow(
					wfMessage( 'user-stats-quiz-points', $stats_data['quiz_points'] )->escaped(),
					$stats_data['quiz_points']
				);
			if ( $stats_data['currency'] != '10000' ) {
				$output .= $this->getUserStatsRow(
					wfMessage( 'user-stats-pick-points', $stats_data['currency'] )->escaped(),
					$stats_data['currency']
				);
			}
			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get three of the polls the user has created and cache the data in
	 * memcached.
	 *
	 * @return array
	 */
	function getUserPolls() {
		global $wgMemc;

		$polls = [];
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// Try cache
		$key = $wgMemc->makeKey( 'user', 'profile', 'polls', $this->user_id );
		$data = $wgMemc->get( $key );

		if ( $data ) {
			$logger->debug( "Got profile polls for user {user_id} from cache\n", [
				'user_id' => $this->user_id
			] );

			$polls = $data;
		} else {
			$logger->debug( "Got profile polls for user {user_id} from DB\n", [
				'user_id' => $this->user_id
			] );

			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				[ 'poll_question', 'page' ],
				[ 'page_title', 'poll_date' ],
				/* WHERE */[ 'poll_user_id' => $this->user_id ],
				__METHOD__,
				[ 'ORDER BY' => 'poll_id DESC', 'LIMIT' => 3 ],
				[ 'page' => [ 'INNER JOIN', 'page_id = poll_page_id' ] ]
			);
			foreach ( $res as $row ) {
				$polls[] = [
					'title' => $row->page_title,
					'timestamp' => wfTimestamp( TS_UNIX, $row->poll_date )
				];
			}
			$wgMemc->set( $key, $polls );
		}
		return $polls;
	}

	/**
	 * Get three of the quiz games the user has created and cache the data in
	 * memcached.
	 *
	 * @return array
	 */
	function getUserQuiz() {
		global $wgMemc;

		$quiz = [];
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// Try cache
		$key = $wgMemc->makeKey( 'user', 'profile', 'quiz', $this->user_id );
		$data = $wgMemc->get( $key );

		if ( $data ) {
			$logger->debug( "Got profile quizzes for user {user_id} from cache\n", [
				'user_id' => $this->user_id
			] );

			$quiz = $data;
		} else {
			$logger->debug( "Got profile quizzes for user {user_id} from DB\n", [
				'user_id' => $this->user_id
			] );

			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				'quizgame_questions',
				[ 'q_id', 'q_text', 'q_date' ],
				[
					'q_user_id' => $this->user_id,
					'q_flag' => 0 // the same as QuizGameHome::$FLAG_NONE
				],
				__METHOD__,
				[
					'ORDER BY' => 'q_id DESC',
					'LIMIT' => 3
				]
			);
			foreach ( $res as $row ) {
				$quiz[] = [
					'id' => $row->q_id,
					'text' => $row->q_text,
					'timestamp' => wfTimestamp( TS_UNIX, $row->q_date )
				];
			}
			$wgMemc->set( $key, $quiz );
		}

		return $quiz;
	}

	/**
	 * Get three of the picture games the user has created and cache the data
	 * in memcached.
	 *
	 * @return array
	 */
	function getUserPicGames() {
		global $wgMemc;

		$pics = [];
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// Try cache
		$key = $wgMemc->makeKey( 'user', 'profile', 'picgame', $this->user_id );
		$data = $wgMemc->get( $key );
		if ( $data ) {
			$logger->debug( "Got profile picgames for user {user_id} from cache\n", [
				'user_id' => $this->user_id
			] );

			$pics = $data;
		} else {
			$logger->debug( "Got profile picgames for user {user_id} from DB\n", [
				'user_id' => $this->user_id
			] );

			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				'picturegame_images',
				[ 'id', 'title', 'img1', 'img2', 'pg_date' ],
				[
					'userid' => $this->user_id,
					'flag' => 0 // PictureGameHome::$FLAG_NONE
				],
				__METHOD__,
				[
					'ORDER BY' => 'id DESC',
					'LIMIT' => 3
				]
			);
			foreach ( $res as $row ) {
				$pics[] = [
					'id' => $row->id,
					'title' => $row->title,
					'img1' => $row->img1,
					'img2' => $row->img2,
					'timestamp' => wfTimestamp( TS_UNIX, $row->pg_date )
				];
			}
			$wgMemc->set( $key, $pics );
		}

		return $pics;
	}

	/**
	 * Get the casual games (polls, quizzes and picture games) that the user
	 * has created if $wgUserProfileDisplay['games'] is set to true and the
	 * PictureGame, PollNY and QuizGame extensions have been installed.
	 *
	 * @return string HTML or nothing if this feature isn't enabled
	 */
	function getCasualGames() {
		global $wgUserProfileDisplay;

		if ( $wgUserProfileDisplay['games'] == false ) {
			return '';
		}

		$user_id = $this->user_id;
		$user_name = $this->user_name;

		$output = '';

		// Safe titles
		$quiz_title = SpecialPage::getTitleFor( 'QuizGameHome' );
		$pic_game_title = SpecialPage::getTitleFor( 'PictureGameHome' );

		// Combine the queries
		$combined_array = [];

		$quizzes = $this->getUserQuiz();
		foreach ( $quizzes as $quiz ) {
			$combined_array[] = [
				'type' => 'Quiz',
				'id' => $quiz['id'],
				'text' => $quiz['text'],
				'timestamp' => $quiz['timestamp']
			];
		}

		$polls = $this->getUserPolls();
		foreach ( $polls as $poll ) {
			$combined_array[] = [
				'type' => 'Poll',
				'title' => $poll['title'],
				'timestamp' => $poll['timestamp']
			];
		}

		$pics = $this->getUserPicGames();
		foreach ( $pics as $pic ) {
			$combined_array[] = [
				'type' => 'Picture Game',
				'id' => $pic['id'],
				'title' => $pic['title'],
				'img1' => $pic['img1'],
				'img2' => $pic['img2'],
				'timestamp' => $pic['timestamp']
			];
		}

		usort( $combined_array, [ 'UserProfilePage', 'sortItems' ] );

		if ( count( $combined_array ) > 0 ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'casual-games-title' )->escaped().'
				</div>
				<div class="user-section-actions">
					<div class="action-right">
					</div>
					<div class="action-left">
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="casual-game-container">';

			$x = 1;

			foreach ( $combined_array as $item ) {
				$output .= ( ( $x == 1 ) ? '<p class="item-top">' : '<p>' );

				if ( $item['type'] == 'Poll' ) {
					$ns = ( defined( 'NS_POLL' ) ? NS_POLL : 300 );
					$poll_title = Title::makeTitle( $ns, $item['title'] );
					$casual_game_title = wfMessage( 'casual-game-poll' )->escaped();
					$output .= '<a href="' . htmlspecialchars( $poll_title->getFullURL() ) .
						"\" rel=\"nofollow\">
							{$poll_title->getText()}
						</a>
						<span class=\"item-small\">{$casual_game_title}</span>";
				}

				if ( $item['type'] == 'Quiz' ) {
					$casual_game_title = wfMessage( 'casual-game-quiz' )->escaped();
					$output .= '<a href="' .
						htmlspecialchars( $quiz_title->getFullURL( 'questionGameAction=renderPermalink&permalinkID=' . $item['id'] ) ) .
						"\" rel=\"nofollow\">
							{$item['text']}
						</a>
						<span class=\"item-small\">{$casual_game_title}</span>";
				}

				if ( $item['type'] == 'Picture Game' ) {
					if ( $item['img1'] != '' && $item['img2'] != '' ) {
						$image_1 = $image_2 = '';
						$render_1 = wfFindFile( $item['img1'] );
						if ( is_object( $render_1 ) ) {
							$thumb_1 = $render_1->transform( [ 'width' => 25 ] );
							$image_1 = $thumb_1->toHtml();
						}

						$render_2 = wfFindFile( $item['img2'] );
						if ( is_object( $render_2 ) ) {
							$thumb_2 = $render_2->transform( [ 'width' => 25 ] );
							$image_2 = $thumb_2->toHtml();
						}

						$casual_game_title = wfMessage( 'casual-game-picture-game' )->escaped();

						$output .= '<a href="' .
							htmlspecialchars( $pic_game_title->getFullURL( 'picGameAction=renderPermalink&id=' . $item['id'] ) ) .
							"\" rel=\"nofollow\">
								{$image_1}
								{$image_2}
								{$item['title']}
							</a>
							<span class=\"item-small\">{$casual_game_title}</span>";
					}
				}

				$output .= '</p>';

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	function sortItems( $x, $y ) {
		if ( $x['timestamp'] == $y['timestamp'] ) {
			return 0;
		} elseif ( $x['timestamp'] > $y['timestamp'] ) {
			return - 1;
		} else {
			return 1;
		}
	}

	function getProfileSection( $label, $value, $required = true ) {
		$context = $this->getContext();
		$out = $context->getOutput();
		$user = $context->getUser();

		$output = '';
		if ( $value || $required ) {
			if ( !$value ) {
				if ( $user->getName() == $this->getTitle()->getText() ) {
					$value = wfMessage( 'profile-updated-personal' )->escaped();
				} else {
					$value = wfMessage( 'profile-not-provided' )->escaped();
				}
			}

			$value = $out->parse( trim( $value ), false );

			$output = "<div><b>{$label}</b>{$value}</div>";
		}
		return $output;
	}

	function getPersonalInfo() {
		global $wgUserProfileDisplay;

		$user_id = $this->user_id;
		$user_name = $this->user_name;

		$context = $this->getContext();
		$user = $context->getUser();

		if ( $wgUserProfileDisplay['personal'] == false ) {
			return '';
		}

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$user_level = new UserLevel( $stats_data['points'] );
		$level_link = Title::makeTitle( NS_HELP, wfMessage( 'user-profile-userlevels-link' )->inContentLanguage()->text() );

		$this->initializeProfileData();
		$profile_data = $this->profile_data;

		$defaultCountry = wfMessage( 'user-profile-default-country' )->inContentLanguage()->text();

		// Current location
		$location = $profile_data['location_city'] . ', ' . $profile_data['location_state'];
		if ( $profile_data['location_country'] != $defaultCountry ) {
			if ( $profile_data['location_city'] && $profile_data['location_state'] ) { // city AND state
				$location = $profile_data['location_city'] . ', ' .
							$profile_data['location_state'] . ', ' .
							$profile_data['location_country'];
				// Privacy
				$location = '';
				if ( in_array( 'up_location_city', $this->profile_visible_fields ) ) {
					$location .= $profile_data['location_city'] . ', ';
				}
				$location .= $profile_data['location_state'];
				if ( in_array( 'up_location_country', $this->profile_visible_fields ) ) {
					$location .= ', ' . $profile_data['location_country'] . ', ';
				}
			} elseif ( $profile_data['location_city'] && !$profile_data['location_state'] ) { // city, but no state
				$location = '';
				if ( in_array( 'up_location_city', $this->profile_visible_fields ) ) {
					$location .= $profile_data['location_city'] .', ';
				}
				if ( in_array( 'up_location_country', $this->profile_visible_fields ) ) {
					$location .= $profile_data['location_country'];
				}
			} elseif ( $profile_data['location_state'] && !$profile_data['location_city'] ) { // state, but no city
				$location = $profile_data['location_state'];
				if ( in_array( 'up_location_country', $this->profile_visible_fields ) ) {
					$location .= ', ' . $profile_data['location_country'];
				}
			} else {
				$location = '';
				if ( in_array( 'up_location_country', $this->profile_visible_fields ) ) {
					$location .= $profile_data['location_country'];
				}
			}
		}

		if ( $location == ', ' ) {
			$location = '';
		}

		// Hometown
		$hometown = $profile_data['hometown_city'] . ', ' . $profile_data['hometown_state'];
		if ( $profile_data['hometown_country'] != $defaultCountry ) {
			if ( $profile_data['hometown_city'] && $profile_data['hometown_state'] ) { // city AND state
				$hometown = $profile_data['hometown_city'] . ', ' .
							$profile_data['hometown_state'] . ', ' .
							$profile_data['hometown_country'];
				$hometown = '';
				if ( in_array( 'up_hometown_city', $this->profile_visible_fields ) ) {
					$hometown .= $profile_data['hometown_city'] . ', ' . $profile_data['hometown_state'];
				}
				if ( in_array( 'up_hometown_country', $this->profile_visible_fields ) ) {
					$hometown .= ', ' . $profile_data['hometown_country'];
				}
			} elseif ( $profile_data['hometown_city'] && !$profile_data['hometown_state'] ) { // city, but no state
				$hometown = '';
				if ( in_array( 'up_hometown_city', $this->profile_visible_fields ) ) {
					$hometown .= $profile_data['hometown_city'] .', ';
				}
				if ( in_array( 'up_hometown_country', $this->profile_visible_fields ) ) {
					$hometown .= $profile_data['hometown_country'];
				}
			} elseif ( $profile_data['hometown_state'] && !$profile_data['hometown_city'] ) { // state, but no city
				$hometown = $profile_data['hometown_state'];
				if ( in_array( 'up_hometown_country', $this->profile_visible_fields ) ) {
					$hometown .= ', ' . $profile_data['hometown_country'];
				}
			} else {
				$hometown = '';
				if ( in_array( 'up_hometown_country', $this->profile_visible_fields ) ) {
					$hometown .= $profile_data['hometown_country'];
				}
			}
		}

		if ( $hometown == ', ' ) {
			$hometown = '';
		}

		$joined_data = $profile_data['real_name'] . $location . $hometown .
						$profile_data['birthday'] . $profile_data['occupation'] .
						$profile_data['websites'] . $profile_data['places_lived'] .
						$profile_data['schools'] . $profile_data['about'];
		$edit_info_link = SpecialPage::getTitleFor( 'UpdateProfile' );

		// Privacy fields holy shit!
		$personal_output = '';
		if ( in_array( 'up_real_name', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-real-name' )->escaped(), $profile_data['real_name'], false );
		}

		$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-location' )->escaped(), $location, false );
		$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-hometown' )->escaped(), $hometown, false );

		if ( in_array( 'up_birthday', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-birthday' )->escaped(), $profile_data['birthday'], false );
		}

		if ( in_array( 'up_occupation', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-occupation' )->escaped(), $profile_data['occupation'], false );
		}

		if ( in_array( 'up_websites', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-websites' )->escaped(), $profile_data['websites'], false );
		}

		if ( in_array( 'up_places_lived', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-places-lived' )->escaped(), $profile_data['places_lived'], false );
		}

		if ( in_array( 'up_schools', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-schools' )->escaped(), $profile_data['schools'], false );
		}

		if ( in_array( 'up_about', $this->profile_visible_fields ) ) {
			$personal_output .= $this->getProfileSection( wfMessage( 'user-personal-info-about-me' )->escaped(), $profile_data['about'], false );
		}

		$output = '';
		if ( $joined_data ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-personal-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $user->getName() == $user_name ) {
				$output .= '<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '">' .
					wfMessage( 'user-edit-this' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container">' .
				$personal_output .
			'</div>';
		} elseif ( $user->getName() == $user_name ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-personal-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
						<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '">' .
							wfMessage( 'user-edit-this' )->escaped() .
						'</a>
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="no-info-container">' .
				wfMessage( 'user-no-personal-info' )->escaped() .
			'</div>';
		}

		return $output;
	}

	/**
	 * Get the custom info (site-specific stuff) for a given user.
	 *
	 * @return string HTML
	 */
	function getCustomInfo() {
		global $wgUserProfileDisplay;

		$user_name = $this->user_name;

		$context = $this->getContext();
		$user = $context->getUser();

		if ( $wgUserProfileDisplay['custom'] == false ) {
			return '';
		}

		$this->initializeProfileData();

		$profile_data = $this->profile_data;

		$joined_data = $profile_data['custom_1'] . $profile_data['custom_2'] .
						$profile_data['custom_3'] . $profile_data['custom_4'];
		$edit_info_link = SpecialPage::getTitleFor( 'UpdateProfile' );

		$custom_output = '';
		if ( in_array( 'up_custom_1', $this->profile_visible_fields ) ) {
			$custom_output .= $this->getProfileSection( wfMessage( 'custom-info-field1' )->escaped(), $profile_data['custom_1'], false );
		}
		if ( in_array( 'up_custom_2', $this->profile_visible_fields ) ) {
			$custom_output .= $this->getProfileSection( wfMessage( 'custom-info-field2' )->escaped(), $profile_data['custom_2'], false );
		}
		if ( in_array( 'up_custom_3', $this->profile_visible_fields ) ) {
			$custom_output .= $this->getProfileSection( wfMessage( 'custom-info-field3' )->escaped(), $profile_data['custom_3'], false );
		}
		if ( in_array( 'up_custom_4', $this->profile_visible_fields ) ) {
			$custom_output .= $this->getProfileSection( wfMessage( 'custom-info-field4' )->escaped(), $profile_data['custom_4'], false );
		}

		$output = '';
		if ( $joined_data ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'custom-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $user->getName() == $user_name ) {
				$output .= '<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/custom">' .
					wfMessage( 'user-edit-this' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container">' .
				$custom_output .
			'</div>';
		} elseif ( $user->getName() == $user_name ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'custom-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
						<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/custom">' .
							wfMessage( 'user-edit-this' )->escaped() .
						'</a>
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="no-info-container">' .
				wfMessage( 'custom-no-info' )->escaped() .
			'</div>';
		}

		return $output;
	}

	/**
	 * Get the interests (favorite movies, TV shows, music, etc.) for a given
	 * user.
	 *
	 * @return string HTML
	 */
	function getInterests() {
		global $wgUserProfileDisplay;

		$user_name = $this->user_name;

		$context = $this->getContext();
		$user = $context->getUser();

		if ( $wgUserProfileDisplay['interests'] == false ) {
			return '';
		}

		$this->initializeProfileData();

		$profile_data = $this->profile_data;
		$joined_data = $profile_data['movies'] . $profile_data['tv'] .
						$profile_data['music'] . $profile_data['books'] .
						$profile_data['video_games'] .
						$profile_data['magazines'] . $profile_data['drinks'] .
						$profile_data['snacks'];
		$edit_info_link = SpecialPage::getTitleFor( 'UpdateProfile' );

		$interests_output = '';
		if ( in_array( 'up_movies', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-movies' )->escaped(), $profile_data['movies'], false );
		}
		if ( in_array( 'up_tv', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-tv' )->escaped(), $profile_data['tv'], false );
		}
		if ( in_array( 'up_music', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-music' )->escaped(), $profile_data['music'], false );
		}
		if ( in_array( 'up_books', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-books' )->escaped(), $profile_data['books'], false );
		}
		if ( in_array( 'up_video_games', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-video-games' )->escaped(), $profile_data['video_games'], false );
		}
		if ( in_array( 'up_magazines', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-magazines' )->escaped(), $profile_data['magazines'], false );
		}
		if ( in_array( 'up_snacks', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-snacks' )->escaped(), $profile_data['snacks'], false );
		}
		if ( in_array( 'up_drinks', $this->profile_visible_fields ) ) {
			$interests_output .= $this->getProfileSection( wfMessage( 'other-info-drinks' )->escaped(), $profile_data['drinks'], false );
		}

		$output = '';
		if ( $joined_data ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'other-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $user->getName() == $user_name ) {
				$output .= '<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/personal">' .
					wfMessage( 'user-edit-this' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="profile-info-container">' .
				$interests_output .
			'</div>';
		} elseif ( $this->isOwner() ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'other-info-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
						<a href="' . htmlspecialchars( $edit_info_link->getFullURL() ) . '/personal">' .
							wfMessage( 'user-edit-this' )->escaped() .
						'</a>
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="no-info-container">' .
				wfMessage( 'other-no-info' )->escaped() .
			'</div>';
		}
		return $output;
	}

	/**
	 * Get the header for the social profile page, which includes the user's
	 * points and user level (if enabled in the site configuration) and lots
	 * more.
	 *
	 */
	function getProfileHeader() {
		global $wgUserLevels;

		$user_id = $this->user_id;
		$user_name = $this->user_name;

		$context = $this->getContext();
		$userContext = $context->getUser();
		$language = $context->getLanguage();

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$user_level = new UserLevel( $stats_data['points'] );
		$level_link = Title::makeTitle( NS_HELP, wfMessage( 'user-profile-userlevels-link' )->inContentLanguage()->text() );

		$this->initializeProfileData();
		$profile_data = $this->profile_data;

		// Variables and other crap
		$page_title = $this->getTitle()->getText();
		$title_parts = explode( '/', $page_title );
		$user = $title_parts[0];
		$id = User::idFromName( $user );
		$user_safe = urlencode( $user );

		// Safe URLs
		$add_relationship = SpecialPage::getTitleFor( 'AddRelationship' );
		$remove_relationship = SpecialPage::getTitleFor( 'RemoveRelationship' );
		$give_gift = SpecialPage::getTitleFor( 'GiveGift' );
		$send_board_blast = SpecialPage::getTitleFor( 'SendBoardBlast' );
		$update_profile = SpecialPage::getTitleFor( 'UpdateProfile' );
		$watchlist = SpecialPage::getTitleFor( 'Watchlist' );
		$contributions = SpecialPage::getTitleFor( 'Contributions', $user );
		$send_message = SpecialPage::getTitleFor( 'UserBoard' );
		$upload_avatar = SpecialPage::getTitleFor( 'UploadAvatar' );
		$user_page = Title::makeTitle( NS_USER, $user );
		$user_social_profile = Title::makeTitle( NS_USER_PROFILE, $user );
		$user_wiki = Title::makeTitle( NS_USER_WIKI, $user );

		if ( $id != 0 ) {
			$relationship = UserRelationship::getUserRelationshipByID( $id, $userContext->getId() );
		}
		$avatar = new wAvatar( $this->user_id, 'l' );

		$logger = LoggerFactory::getInstance( 'SocialProfile' );
		$logger->debug( "profile type: {user_profile_type} \n", [
			'user_profile_type' => $profile_data['user_page_type']
		] );

		$output = '';

		// Show the link for changing user page type for the user whose page
		// it is
		if ( $this->isOwner() ) {
			$toggle_title = SpecialPage::getTitleFor( 'ToggleUserPage' );
			// Cast it to an int because PHP is stupid.
			if (
				(int)$profile_data['user_page_type'] == 1 ||
				$profile_data['user_page_type'] === ''
			) {
				$toggleMessage = wfMessage( 'user-type-toggle-old' )->escaped();
			} else {
				$toggleMessage = wfMessage( 'user-type-toggle-new' )->escaped();
			}
			$output .= '<div id="profile-toggle-button">
				<a href="' . htmlspecialchars( $toggle_title->getFullURL() ) . '" rel="nofollow">' .
					$toggleMessage . '</a>
			</div>';
		}

		$output .= '<div id="profile-image">' . $avatar->getAvatarURL();
		// Expose the link to the avatar removal page in the UI when the user has
		// uploaded a custom avatar
		$canRemoveOthersAvatars = $userContext->isAllowed( 'avatarremove' );
		if ( !$avatar->isDefault() && ( $canRemoveOthersAvatars || $this->isOwner() ) ) {
			// Different URLs for privileged and regular users
			// Need to specify the user for people who are able to remove anyone's avatar
			// via the special page; for regular users, it doesn't matter because they
			// can't remove anyone else's but their own avatar via RemoveAvatar
			if ( $canRemoveOthersAvatars ) {
				$removeAvatarURL = SpecialPage::getTitleFor( 'RemoveAvatar', $user )->getFullURL();
			} else {
				$removeAvatarURL = SpecialPage::getTitleFor( 'RemoveAvatar' )->getFullURL();
			}
			$output .= '<p><a href="' . htmlspecialchars( $removeAvatarURL ) . '" rel="nofollow">' .
					wfMessage( 'user-profile-remove-avatar' )->text() . '</a>
			</p>';
		}
		$output .= '</div>';

		$output .= '<div id="profile-right">';

		$output .= '<div id="profile-title-container">
				<div id="profile-title">' .
					$user_name .
				'</div>';
		// Show the user's level and the amount of points they have if
		// UserLevels has been configured
		if ( $wgUserLevels ) {
			$output .= '<div id="points-level">
					<a href="' . htmlspecialchars( $level_link->getFullURL() ) . '">' .
						wfMessage(
							'user-profile-points',
							$language->formatNum( $stats_data['points'] )
						)->escaped() .
					'</a>
					</div>
					<div id="honorific-level">
						<a href="' . htmlspecialchars( $level_link->getFullURL() ) . '" rel="nofollow">(' . $user_level->getLevelName() . ')</a>
					</div>';
		}
		$output .= '<div class="visualClear"></div>
			</div>
			<div class="profile-actions">';

		$profileLinks = [];
		if ( $this->isOwner() ) {
			$profileLinks['user-edit-profile'] =
				'<a href="' . htmlspecialchars( $update_profile->getFullURL() ) . '">' . wfMessage( 'user-edit-profile' )->escaped() . '</a>';
			$profileLinks['user-upload-avatar'] =
				'<a href="' . htmlspecialchars( $upload_avatar->getFullURL() ) . '">' . wfMessage( 'user-upload-avatar' )->escaped() . '</a>';
			$profileLinks['user-watchlist'] =
				'<a href="' . htmlspecialchars( $watchlist->getFullURL() ) . '">' . wfMessage( 'user-watchlist' )->escaped() . '</a>';
		} elseif ( $userContext->isLoggedIn() ) {
			if ( $relationship == false ) {
				$profileLinks['user-add-friend'] =
					'<a href="' . htmlspecialchars( $add_relationship->getFullURL( 'user=' . $user_safe . '&rel_type=1' ) ) . '" rel="nofollow">' . wfMessage( 'user-add-friend' )->escaped() . '</a>';

				$profileLinks['user-add-foe'] =
					'<a href="' . htmlspecialchars( $add_relationship->getFullURL( 'user=' . $user_safe . '&rel_type=2' ) ) . '" rel="nofollow">' . wfMessage( 'user-add-foe' )->escaped() . '</a>';
			} else {
				if ( $relationship == 1 ) {
					$profileLinks['user-remove-friend'] =
						'<a href="' . htmlspecialchars( $remove_relationship->getFullURL( 'user=' . $user_safe ) ) . '">' . wfMessage( 'user-remove-friend' )->escaped() . '</a>';
				}
				if ( $relationship == 2 ) {
					$profileLinks['user-remove-foe'] =
						'<a href="' . htmlspecialchars( $remove_relationship->getFullURL( 'user=' . $user_safe ) ) . '">' . wfMessage( 'user-remove-foe' )->escaped() . '</a>';
				}
			}

			global $wgUserBoard;
			if ( $wgUserBoard ) {
				$profileLinks['user-send-message'] =
					'<a href="' . htmlspecialchars( $send_message->getFullURL( 'user=' . $userContext->getName() . '&conv=' . $user_safe ) ) . '" rel="nofollow">' .
					wfMessage( 'user-send-message' )->escaped() . '</a>';
			}
			$profileLinks['user-send-gift'] =
				'<a href="' . htmlspecialchars( $give_gift->getFullURL( 'user=' . $user_safe ) ) . '" rel="nofollow">' .
				wfMessage( 'user-send-gift' )->escaped() . '</a>';
		}

		$profileLinks['user-contributions'] =
			'<a href="' . htmlspecialchars( $contributions->getFullURL() ) . '" rel="nofollow">' . wfMessage( 'user-contributions' )->escaped() . '</a>';

		// Links to User:user_name from User_profile:
		if (
			$this->getTitle()->getNamespace() == NS_USER_PROFILE &&
			$this->profile_data['user_id'] &&
			$this->profile_data['user_page_type'] == 0
		) {
			$profileLinks['user-page-link'] =
				'<a href="' . htmlspecialchars( $user_page->getFullURL() ) . '" rel="nofollow">' .
					wfMessage( 'user-page-link' )->escaped() . '</a>';
		}

		// Links to User:user_name from User_profile:
		if (
			$this->getTitle()->getNamespace() == NS_USER &&
			$this->profile_data['user_id'] &&
			$this->profile_data['user_page_type'] == 0
		) {
			$profileLinks['user-social-profile-link'] =
				'<a href="' . htmlspecialchars( $user_social_profile->getFullURL() ) . '" rel="nofollow">' .
					wfMessage( 'user-social-profile-link' )->escaped() . '</a>';
		}

		if (
			$this->getTitle()->getNamespace() == NS_USER && (
				!$this->profile_data['user_id'] ||
				$this->profile_data['user_page_type'] == 1
			)
		) {
			$profileLinks['user-wiki-link'] =
				'<a href="' . htmlspecialchars( $user_wiki->getFullURL() ) . '" rel="nofollow">' .
					wfMessage( 'user-wiki-link' )->escaped() . '</a>';
		}

		// Provide a hook point for adding links to the profile header
		// or maybe even removing them
		// @see https://phabricator.wikimedia.org/T152930
		Hooks::run( 'UserProfileGetProfileHeaderLinks', [ $this, &$profileLinks ] );

		$output .= $language->pipeList( $profileLinks );
		$output .= '</div>

		</div>';

		return $output;
	}

	/**
	 * This is currently unused, seems to be a leftover from the ArmchairGM
	 * days.
	 *
	 * @param string $user_name User name
	 * @return string HTML
	 */
	function getProfileImage( $user_name ) {
		$context = $this->getContext();
		$user = $context->getUser();

		$avatar = new wAvatar( $this->user_id, 'l' );
		$avatarTitle = SpecialPage::getTitleFor( 'UploadAvatar' );

		$output = '<div class="profile-image">';
		if ( $user->getName() == $this->user_name ) {
			if ( $avatar->isDefault() ) {
				$caption = 'upload image';
			} else {
				$caption = 'new image';
			}
			$output .= '<a href="' . htmlspecialchars( $avatarTitle->getFullURL() ) . '" rel="nofollow">' .
						$avatar->getAvatarURL() . '<br />
					(' . $caption . ')
				</a>';
		} else {
			$output .= $avatar->getAvatarURL();
		}
		$output .= '</div>';

		return $output;
	}

	/**
	 * Get the relationships for a given user.
	 *
	 * whose relationships we want to fetch
	 * @param int $rel_type
	 * - 1 for friends
	 * - 2 (or anything else than 1) for foes
	 */
	function getRelationships( $rel_type ) {
		global $wgMemc, $wgUserProfileDisplay;

		$user_name = $this->user_name;

		$context = $this->getContext();
		$language = $context->getLanguage();
		$user = $context->getUser();

		// If not enabled in site settings, don't display
		if ( $rel_type == 1 ) {
			if ( $wgUserProfileDisplay['friends'] == false ) {
				return '';
			}
		} else {
			if ( $wgUserProfileDisplay['foes'] == false ) {
				return '';
			}
		}

		$output = ''; // Prevent E_NOTICE

		$count = 4;
		$key = $wgMemc->makeKey( 'relationship', 'profile', "{$user->getId()}-{$rel_type}" );
		$data = $wgMemc->get( $key );

		// Try cache
		if ( !$data ) {
			$listLookup = new RelationshipListLookup( $user, $count );
			$friends = $listLookup->getRelationshipList( $rel_type );
			$wgMemc->set( $key, $friends );
		} else {
			$logger = LoggerFactory::getInstance( 'SocialProfile' );
			$logger->debug( "Got profile relationship type {rel_type} for user {user_name} from cache\n", [
				'rel_type' => $rel_type,
				'user_name' => $user_name
			] );

			$friends = $data;
		}

		$stats = new UserStats( $user->getId(), $user_name );
		$stats_data = $stats->getUserStats();
		$view_all_title = SpecialPage::getTitleFor( 'ViewRelationships' );

		if ( $rel_type == 1 ) {
			$relationship_count = $stats_data['friend_count'];
			$relationship_title = wfMessage( 'user-friends-title' )->escaped();
		} else {
			$relationship_count = $stats_data['foe_count'];
			$relationship_title = wfMessage( 'user-foes-title' )->escaped();
		}

		if ( count( $friends ) > 0 ) {
			$x = 1;
			$per_row = 4;

			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' . $relationship_title . '</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( intval( $relationship_count ) > 4 ) {
				$output .= '<a href="' . htmlspecialchars( $view_all_title->getFullURL( 'user=' . $user_name . '&rel_type=' . $rel_type ) ) .
					'" rel="nofollow">' . wfMessage( 'user-view-all' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="action-left">';
			if ( intval( $relationship_count ) > 4 ) {
				$output .= wfMessage( 'user-count-separator', $per_row, $relationship_count )->escaped();
			} else {
				$output .= wfMessage( 'user-count-separator', $relationship_count, $relationship_count )->escaped();
			}
			$output .= '</div>
				</div>
				<div class="visualClear"></div>
			</div>
			<div class="visualClear"></div>
			<div class="user-relationship-container">';

			foreach ( $friends as $friend ) {
				$user = Title::makeTitle( NS_USER, $friend['user_name'] );
				$avatar = new wAvatar( $friend['user_id'], 'ml' );

				// Chop down username that gets displayed
				$user_name = $language->truncateForVisual( $friend['user_name'], 9, '..' );

				$output .= "<a href=\"" . htmlspecialchars( $user->getFullURL() ) .
					"\" title=\"" . htmlspecialchars( $friend['user_name'] ) . "\" rel=\"nofollow\">
					{$avatar->getAvatarURL()}<br />
					{$user_name}
				</a>";

				if ( $x == count( $friends ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Gets the recent social activity for a given user.
	 *
	 */
	function getActivity() {
		global $wgUserProfileDisplay;

		// If not enabled in site settings, don't display
		if ( $wgUserProfileDisplay['activity'] == false ) {
			return '';
		}

		$user_name = $this->user_name;

		$output = '';

		$limit = 8;
		$rel = new UserActivity( $user_name, 'user', $limit );
		$rel->setActivityToggle( 'show_votes', 0 );
		$rel->setActivityToggle( 'show_gifts_sent', 1 );

		/**
		 * Get all relationship activity
		 */
		$activity = $rel->getActivityList();

		if ( $activity ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-recent-activity-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">
					</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>';

			$x = 1;

			if ( count( $activity ) < $limit ) {
				$style_limit = count( $activity );
			} else {
				$style_limit = $limit;
			}

			foreach ( $activity as $item ) {
				$item_html = '';
				$title = Title::makeTitle( $item['namespace'], $item['pagetitle'] );
				$user_title = Title::makeTitle( NS_USER, $item['username'] );
				$user_title_2 = Title::makeTitle( NS_USER, $item['comment'] );

				if ( $user_title_2 ) {
					$user_link_2 = '<a href="' . htmlspecialchars( $user_title_2->getFullURL() ) .
						'" rel="nofollow">' . $item['comment'] . '</a>';
				}

				$comment_url = '';
				if ( $item['type'] == 'comment' ) {
					$comment_url = "#comment-{$item['id']}";
				}

				$page_link = '<b><a href="' . htmlspecialchars( $title->getFullURL() ) .
					"{$comment_url}\">" . $title->getPrefixedText() . '</a></b> ';
				$b = new UserBoard(); // Easier than porting the time-related functions here
				$item_time = '<span class="item-small">' .
					wfMessage( 'user-time-ago', $b->getTimeAgo( $item['timestamp'] ) )->escaped() .
				'</span>';

				if ( $x < $style_limit ) {
					$class = 'activity-item';
				} else {
					$class = 'activity-item-bottom';
				}

				$userActivityIcon = new UserActivityIcon( $item['type'] );
				$icon = $userActivityIcon->getIconHTML();
				$item_html .= "<div class=\"{$class}\">" . $icon;

				$viewGift = SpecialPage::getTitleFor( 'ViewGift' );

				switch ( $item['type'] ) {
					case 'edit':
						$item_html .= wfMessage( 'user-recent-activity-edit' )->escaped() . " {$page_link} {$item_time}
							<div class=\"item\">";
						if ( $item['comment'] ) {
							$item_html .= "\"{$item['comment']}\"";
						}
						$item_html .= '</div>';
						break;
					case 'vote':
						$item_html .= wfMessage( 'user-recent-activity-vote' )->escaped() . " {$page_link} {$item_time}";
						break;
					case 'comment':
						$item_html .= wfMessage( 'user-recent-activity-comment' )->escaped() . " {$page_link} {$item_time}
							<div class=\"item\">
								\"{$item['comment']}\"
							</div>";
						break;
					case 'gift-sent':
						$userGiftIcon = new UserGiftIcon( $item['namespace'], 'm' );
						$icon = $userGiftIcon->getIconHTML();

						$item_html .= wfMessage( 'user-recent-activity-gift-sent' )->escaped() . " {$user_link_2} {$item_time}
						<div class=\"item\">
							<a href=\"" . htmlspecialchars( $viewGift->getFullURL( "gift_id={$item['id']}" ) ) . "\" rel=\"nofollow\">
								{$icon}
								{$item['pagetitle']}
							</a>
						</div>";
						break;
					case 'gift-rec':
						$userGiftIcon = new UserGiftIcon( $item['namespace'], 'm' );
						$icon = $userGiftIcon->getIconHTML();

						$item_html .= wfMessage( 'user-recent-activity-gift-rec' )->escaped() . " {$user_link_2} {$item_time}</span>
								<div class=\"item\">
									<a href=\"" . htmlspecialchars( $viewGift->getFullURL( "gift_id={$item['id']}" ) ) . "\" rel=\"nofollow\">
										{$icon}
										{$item['pagetitle']}
									</a>
								</div>";
						break;
					case 'system_gift':
						$systemGiftIcon = new SystemGiftIcon( $item['namespace'], 'm' );
						$icon = $systemGiftIcon->getIconHTML();

						$viewSystemGift = SpecialPage::getTitleFor( 'ViewSystemGift' );
						$item_html .= wfMessage( 'user-recent-system-gift' )->escaped() . " {$item_time}
								<div class=\"user-home-item-gift\">
									<a href=\"" . htmlspecialchars( $viewSystemGift->getFullURL( "gift_id={$item['id']}" ) ) . "\" rel=\"nofollow\">
										{$icon}
										{$item['pagetitle']}
									</a>
								</div>";
						break;
					case 'friend':
						$item_html .= wfMessage( 'user-recent-activity-friend' )->escaped() .
							" <b>{$user_link_2}</b> {$item_time}";
						break;
					case 'foe':
						$item_html .= wfMessage( 'user-recent-activity-foe' )->escaped() .
							" <b>{$user_link_2}</b> {$item_time}";
						break;
					case 'system_message':
						$item_html .= "{$item['comment']} {$item_time}";
						break;
					case 'user_message':
						$item_html .= wfMessage( 'user-recent-activity-user-message' )->escaped() .
							" <b><a href=\"" .
								htmlspecialchars(
									SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [ 'user' => $user_title_2->getText() ] ),
									ENT_QUOTES
								) .
								"\" rel=\"nofollow\">{$item['comment']}</a></b>  {$item_time}
								<div class=\"item\">
								\"{$item['namespace']}\"
								</div>";
						break;
					case 'network_update':
						$network_image = SportsTeams::getLogo( $item['sport_id'], $item['team_id'], 's' );
						$item_html .= wfMessage( 'user-recent-activity-network-update' )->escaped() .
								'<div class="item">
									<a href="' . htmlspecialchars(
										SpecialPage::getTitleFor( 'FanHome' )->getFullURL( [
											'sport_id' => $item['sport_id'],
											'team_id' => $item['team_id']
										] ),
										ENT_QUOTES
									) .
									"\" rel=\"nofollow\">{$network_image} \"{$item['comment']}\"</a>
								</div>";
						break;
				}

				$item_html .= '</div>';

				if ( $x <= $limit ) {
					$items_html_type['all'][] = $item_html;
				}
				$items_html_type[$item['type']][] = $item_html;

				$x++;
			}

			$by_type = '';
			foreach ( $items_html_type['all'] as $item ) {
				$by_type .= $item;
			}
			$output .= "<div id=\"recent-all\">$by_type</div>";
		}

		return $output;
	}

	function getGifts() {
		global $wgMemc, $wgUserProfileDisplay;

		$user_name = $this->user_name;

		$context = $this->getContext();
		$user = $context->getUser();
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// If not enabled in site settings, don't display
		if ( $wgUserProfileDisplay['gifts'] == false ) {
			return '';
		}

		$output = '';

		// User to user gifts
		$g = new UserGifts( $user_name );
		$user_safe = urlencode( $user_name );

		// Try cache
		$key = $wgMemc->makeKey( 'user', 'profile', 'gifts', "{$g->user_id}" );
		$data = $wgMemc->get( $key );

		if ( !$data ) {
			$logger->debug( "Got profile gifts for user {user_name} from DB\n", [
				'user_name' => $user_name
			] );

			$gifts = $g->getUserGiftList( 0, 4 );
			$wgMemc->set( $key, $gifts, 60 * 60 * 4 );
		} else {
			$logger->debug( "Got profile gifts for user {user_name} from cache\n", [
				'user_name' => $user_name
			] );
			$gifts = $data;
		}

		$gift_count = $g->getGiftCountByUsername( $user_name );
		$gift_link = SpecialPage::getTitleFor( 'ViewGifts' );
		$per_row = 4;

		if ( $gifts ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-gifts-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $gift_count > 4 ) {
				$output .= '<a href="' . htmlspecialchars( $gift_link->getFullURL( 'user=' . $user_safe ) ) . '" rel="nofollow">' .
					wfMessage( 'user-view-all' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="action-left">';
			if ( $gift_count > 4 ) {
				$output .= wfMessage( 'user-count-separator', '4', $gift_count )->escaped();
			} else {
				$output .= wfMessage( 'user-count-separator', $gift_count, $gift_count )->escaped();
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="user-gift-container">';

			$x = 1;

			foreach ( $gifts as $gift ) {
				if ( $gift['status'] == 1 && $user_name == $user->getName() ) {
					$g->clearUserGiftStatus( $gift['id'] );
					$wgMemc->delete( $key );
				}

				$user = Title::makeTitle( NS_USER, $gift['user_name_from'] );
				$userGiftIcon = new UserGiftIcon( $gift['gift_id'], 'ml' );
				$icon = $userGiftIcon->getIconHTML();
				$gift_link = $user = SpecialPage::getTitleFor( 'ViewGift' );
				$class = '';
				if ( $gift['status'] == 1 ) {
					$class = 'class="user-page-new"';
				}
				$output .= '<a href="' . htmlspecialchars( $gift_link->getFullURL( 'gift_id=' . $gift['id'] ) ) . '" ' .
					$class . " rel=\"nofollow\">{$icon}</a>";
				if ( $x == count( $gifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	function getAwards() {
		global $wgMemc, $wgUserProfileDisplay;

		$user_name = $this->user_name;

		$context = $this->getContext();
		$user = $context->getUser();
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// If not enabled in site settings, don't display
		if ( $wgUserProfileDisplay['awards'] == false ) {
			return '';
		}

		$output = '';

		// System gifts
		$sg = new UserSystemGifts( $user_name );

		// Try cache
		$sg_key = $wgMemc->makeKey( 'user', 'profile', 'system_gifts', "{$sg->user_id}" );
		$data = $wgMemc->get( $sg_key );
		if ( !$data ) {
			$logger->debug( "Got profile awards for user {user_name} from DB\n", [
				'user_name' => $user_name
			] );

			$listLookup = new SystemGiftListLookup( 4 );
			$systemGifts = $listLookup->getUserGiftList( $user );
			$wgMemc->set( $sg_key, $systemGifts, 60 * 60 * 4 );
		} else {
			$logger->debug( "Got profile awards for user {user_name} from cache\n", [
				'user_name' => $user_name
			] );

			$systemGifts = $data;
		}

		$system_gift_count = $sg->getGiftCountByUsername( $user_name );
		$system_gift_link = SpecialPage::getTitleFor( 'ViewSystemGifts' );
		$per_row = 4;

		if ( $systemGifts ) {
			$x = 1;

			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-awards-title' )->escaped() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			if ( $system_gift_count > 4 ) {
				$output .= '<a href="' . htmlspecialchars( $system_gift_link->getFullURL( 'user=' . $user_name ) ) . '" rel="nofollow">' .
					wfMessage( 'user-view-all' )->escaped() . '</a>';
			}
			$output .= '</div>
					<div class="action-left">';
			if ( $system_gift_count > 4 ) {
				$output .= wfMessage( 'user-count-separator', '4', $system_gift_count )->escaped();
			} else {
				$output .= wfMessage( 'user-count-separator', $system_gift_count, $system_gift_count )->escaped();
			}
			$output .= '</div>
					<div class="visualClear"></div>
				</div>
			</div>
			<div class="visualClear"></div>
			<div class="user-gift-container">';

			foreach ( $systemGifts as $gift ) {
				if ( $gift['status'] == 1 && $user_name == $user->getName() ) {
					$sg->clearUserGiftStatus( $gift['id'] );
					$wgMemc->delete( $sg_key );
				}

				$systemGiftIcon = new SystemGiftIcon( $gift['gift_id'], 'ml' );
				$icon = $systemGiftIcon->getIconHTML();

				$gift_link = $user = SpecialPage::getTitleFor( 'ViewSystemGift' );

				$class = '';
				if ( $gift['status'] == 1 ) {
					$class = 'class="user-page-new"';
				}
				$output .= '<a href="' . htmlspecialchars( $gift_link->getFullURL( 'gift_id=' . $gift['id'] ) ) .
					'" ' . $class . " rel=\"nofollow\">
					{$icon}
				</a>";

				if ( $x == count( $systemGifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Get the user board for a given user.
	 */
	function getUserBoard() {
		global $wgUserProfileDisplay;

		$user_id = $this->user_id;
		$user_name = $this->user_name;
		$context = $this->getContext();
		$out = $context->getOutput();
		$user = $context->getUser();

		// Anonymous users cannot have user boards
		if ( $user_id == 0 ) {
			return '';
		}

		// Don't display anything if user board on social profiles isn't
		// enabled in site configuration
		if ( $wgUserProfileDisplay['board'] == false ) {
			return '';
		}

		$output = ''; // Prevent E_NOTICE

		// Add JS
		$out->addModules( 'ext.socialprofile.userprofile.js' );

		$listLookup = new RelationshipListLookup( $user, 4 );
		$friends = $listLookup->getFriendList();

		$stats = new UserStats( $user_id, $user_name );
		$stats_data = $stats->getUserStats();
		$total = $stats_data['user_board'];

		// If the user is viewing their own profile or is allowed to delete
		// board messages, add the amount of private messages to the total
		// sum of board messages.
		if ( $user->getName() == $user_name || $user->isAllowed( 'userboard-delete' ) ) {
			$total = $total + $stats_data['user_board_priv'];
		}

		$output .= '<div class="user-section-heading">
			<div class="user-section-title">' .
				wfMessage( 'user-board-title' )->escaped() .
			'</div>
			<div class="user-section-actions">
				<div class="action-right">';
		if ( $user->getName() == $user_name ) {
			if ( $friends ) {
				$output .= '<a href="' .
					htmlspecialchars(
						SpecialPage::getTitleFor( 'SendBoardBlast' )->getFullURL(),
						ENT_QUOTES
					) . '">' .
					wfMessage( 'user-send-board-blast' )->escaped() . '</a>';
			}
			if ( $total > 10 ) {
				$output .= wfMessage( 'pipe-separator' )->escaped();
			}
		}
		if ( $total > 10 ) {
			$output .= '<a href="' .
				htmlspecialchars(
					SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [ 'user' => $user_name ] ),
					ENT_QUOTES
				) . '">' .
				wfMessage( 'user-view-all' )->escaped() . '</a>';
		}
		$output .= '</div>
				<div class="action-left">';
		if ( $total > 10 ) {
			$output .= wfMessage( 'user-count-separator', '10', $total )->escaped();
		} elseif ( $total > 0 ) {
			$output .= wfMessage( 'user-count-separator', $total, $total )->escaped();
		}
		$output .= '</div>
				<div class="visualClear"></div>
			</div>
		</div>
		<div class="visualClear"></div>';

		if ( $user->getName() !== $user_name ) {
			if ( $user->isLoggedIn() && !$user->isBlocked() ) {
				$output .= '<div class="user-page-message-form">
						<input type="hidden" id="user_name_to" name="user_name_to" value="' . addslashes( $user_name ) . '" />
						<span class="profile-board-message-type">' .
							wfMessage( 'userboard_messagetype' )->escaped() .
						'</span>
						<select id="message_type">
							<option value="0">' .
								wfMessage( 'userboard_public' )->escaped() .
							'</option>
							<option value="1">' .
								wfMessage( 'userboard_private' )->escaped() .
							'</option>
						</select><p>
						<textarea name="message" id="message" cols="43" rows="4"></textarea>
						<div class="user-page-message-box-button">
							<input type="button" value="' . wfMessage( 'userboard_sendbutton' )->escaped() . '" class="site-button" />
						</div>
					</div>';
			} else {
				$login_link = SpecialPage::getTitleFor( 'Userlogin' );
				$output .= '<div class="user-page-message-form">' .
					wfMessage( 'user-board-login-message', $login_link->getFullURL() )->text() .
				'</div>';
			}
		}

		$output .= '<div id="user-page-board">';
		$b = new UserBoard();
		$output .= $b->displayMessages( $user_id, 0, 10 );
		$output .= '</div>';

		return $output;
	}

	/**
	 * Gets the user's fanboxes if $wgEnableUserBoxes = true; and
	 * $wgUserProfileDisplay['userboxes'] = true; and the FanBoxes extension is
	 * installed.
	 *
	 * @return string HTML
	 */
	function getFanBoxes() {
		global $wgUserProfileDisplay, $wgEnableUserBoxes;

		$user_name = $this->user_name;

		$context = $this->getContext();
		$out = $context->getOutput();
		$user = $context->getUser();

		if ( !$wgEnableUserBoxes || $wgUserProfileDisplay['userboxes'] == false ) {
			return '';
		}

		// Add CSS & JS
		$out->addModules( 'ext.fanBoxes' );

		$output = '';
		$f = new UserFanBoxes( $user_name );

		// Try cache
		/*
		$key = $wgMemc->makeKey( 'user', 'profile', 'fanboxes', "{$f->user_id}" );
		$data = $wgMemc->get( $key );
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		if ( !$data ) {
			$logger->debug( "Got profile fanboxes for user {user_name} from DB\n", [
				'user_name' => $user_name
			] );

			$fanboxes = $f->getUserFanboxes( 0, 10 );
			$wgMemc->set( $key, $fanboxes );
		} else {
			$logger->debug( "Got profile fanboxes for user {user_name} from cache\n", [
				'user_name' => $user_name
			] );

			$fanboxes = $data;
		}
		*/

		$fanboxes = $f->getUserFanboxes( 0, 10 );

		$fanbox_count = $f->getFanBoxCountByUsername( $user_name );
		$fanbox_link = SpecialPage::getTitleFor( 'ViewUserBoxes' );
		$per_row = 1;

		if ( $fanboxes ) {
			$output .= '<div class="user-section-heading">
				<div class="user-section-title">' .
					wfMessage( 'user-fanbox-title' )->plain() .
				'</div>
				<div class="user-section-actions">
					<div class="action-right">';
			// If there are more than ten fanboxes, display a "View all" link
			// instead of listing them all on the profile page
			if ( $fanbox_count > 10 ) {
				$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
				$output .= $linkRenderer->makeLink(
					$fanbox_link,
					wfMessage( 'user-view-all' )->plain(),
					[],
					[ 'user' => $user_name ]
				);
			}
			$output .= '</div>
					<div class="action-left">';
			if ( $fanbox_count > 10 ) {
				$output .= wfMessage( 'user-count-separator' )->numParams( 10, $fanbox_count )->parse();
			} else {
				$output .= wfMessage( 'user-count-separator' )->numParams( $fanbox_count, $fanbox_count )->parse();
			}
			$output .= '</div>
					<div class="visualClear"></div>

				</div>
			</div>
			<div class="visualClear"></div>

			<div class="user-fanbox-container clearfix">';

			$x = 1;
			$tagParser = new Parser();
			foreach ( $fanboxes as $fanbox ) {
				$check_user_fanbox = $f->checkIfUserHasFanbox( $fanbox['fantag_id'] );

				if ( $fanbox['fantag_image_name'] ) {
					$fantag_image_width = 45;
					$fantag_image_height = 53;
					$fantag_image = wfFindFile( $fanbox['fantag_image_name'] );
					$fantag_image_url = '';
					if ( is_object( $fantag_image ) ) {
						$fantag_image_url = $fantag_image->createThumb(
							$fantag_image_width,
							$fantag_image_height
						);
					}
					$fantag_image_tag = '<img alt="" src="' . $fantag_image_url . '" />';
				}

				if ( $fanbox['fantag_left_text'] == '' ) {
					$fantag_leftside = $fantag_image_tag;
				} else {
					$fantag_leftside = $fanbox['fantag_left_text'];
					$fantag_leftside = $tagParser->parse(
						$fantag_leftside, $this->getTitle(),
						$out->parserOptions(), false
					);
					$fantag_leftside = $fantag_leftside->getText();
				}

				$leftfontsize = '10px';
				$rightfontsize = '11px';
				if ( $fanbox['fantag_left_textsize'] == 'mediumfont' ) {
					$leftfontsize = '11px';
				}

				if ( $fanbox['fantag_left_textsize'] == 'bigfont' ) {
					$leftfontsize = '15px';
				}

				if ( $fanbox['fantag_right_textsize'] == 'smallfont' ) {
					$rightfontsize = '10px';
				}

				if ( $fanbox['fantag_right_textsize'] == 'mediumfont' ) {
					$rightfontsize = '11px';
				}

				// Get permalink
				$fantag_title = Title::makeTitle( NS_FANTAG, $fanbox['fantag_title'] );
				$right_text = $fanbox['fantag_right_text'];
				$right_text = $tagParser->parse(
					$right_text, $this->getTitle(), $out->parserOptions(), false
				);
				$right_text = $right_text->getText();

				// Output fanboxes
				$output .= "<div class=\"fanbox-item\">
					<div class=\"individual-fanbox\" id=\"individualFanbox" . $fanbox['fantag_id'] . "\">
						<div class=\"show-message-container-profile\" id=\"show-message-container" . $fanbox['fantag_id'] . "\">
							<a class=\"perma\" style=\"font-size:8px; color:" . $fanbox['fantag_right_textcolor'] . "\" href=\"" . htmlspecialchars( $fantag_title->getFullURL() ) . "\" title=\"" . htmlspecialchars( $fanbox['fantag_title'] ) . "\">" . wfMessage( 'fanbox-perma' )->plain() . "</a>
							<table class=\"fanBoxTableProfile\" border=\"0\" cellpadding=\"0\" cellspacing=\"0\">
								<tr>
									<td id=\"fanBoxLeftSideOutputProfile\" style=\"color:" . $fanbox['fantag_left_textcolor'] . "; font-size:$leftfontsize\" bgcolor=\"" . $fanbox['fantag_left_bgcolor'] . "\">" . $fantag_leftside . "</td>
									<td id=\"fanBoxRightSideOutputProfile\" style=\"color:" . $fanbox['fantag_right_textcolor'] . "; font-size:$rightfontsize\" bgcolor=\"" . $fanbox['fantag_right_bgcolor'] . "\">" . $right_text . "</td>
								</tr>
							</table>
						</div>
					</div>";

				if ( $user->isLoggedIn() ) {
					if ( $check_user_fanbox == 0 ) {
						$output .= '<div class="fanbox-pop-up-box-profile" id="fanboxPopUpBox' . $fanbox['fantag_id'] . '">
							<table cellpadding="0" cellspacing="0" align="center">
								<tr>
									<td style="font-size:10px">' .
										wfMessage( 'fanbox-add-fanbox' )->plain() .
									'</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" class="fanbox-add-button-half" value="' . wfMessage( 'fanbox-add' )->plain() . '" size="10" />
										<input type="button" class="fanbox-cancel-button" value="' . wfMessage( 'cancel' )->plain() . '" size="10" />
									</td>
								</tr>
							</table>
						</div>';
					} else {
						$output .= '<div class="fanbox-pop-up-box-profile" id="fanboxPopUpBox' . $fanbox['fantag_id'] . '">
							<table cellpadding="0" cellspacing="0" align="center">
								<tr>
									<td style="font-size:10px">' .
										wfMessage( 'fanbox-remove-fanbox' )->plain() .
									'</td>
								</tr>
								<tr>
									<td align="center">
										<input type="button" class="fanbox-remove-button-half" value="' . wfMessage( 'fanbox-remove' )->plain() . '" size="10" />
										<input type="button" class="fanbox-cancel-button" value="' . wfMessage( 'cancel' )->plain() . '" size="10" />
									</td>
								</tr>
							</table>
						</div>';
					}
				}

				// Show a message to anonymous users, prompting them to log in
				if ( $user->getId() == 0 ) {
					$output .= '<div class="fanbox-pop-up-box-profile" id="fanboxPopUpBox' . $fanbox['fantag_id'] . '">
						<table cellpadding="0" cellspacing="0" align="center">
							<tr>
								<td style="font-size:10px">' .
									wfMessage( 'fanbox-add-fanbox-login' )->parse() .
								'</td>
							</tr>
							<tr>
								<td align="center">
									<input type="button" class="fanbox-cancel-button" value="' . wfMessage( 'cancel' )->plain() . '" size="10" />
								</td>
							</tr>
						</table>
					</div>';
				}

				$output .= '</div>';

				$x++;
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Initialize UserProfile data for the given user if that hasn't been done
	 * already.
	 */
	private function initializeProfileData() {
		if ( !$this->profile_data ) {
			$profile = new UserProfile( $this->user_name );
			$this->profile_data = $profile->getProfile();
		}
	}
}
