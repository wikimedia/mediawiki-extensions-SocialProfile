<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

/**
 * Special page that shows the top users for a given statistic, i.e.
 * "users with the most friends" or "users with the most votes".
 * Anything that exists in the user_stats table as a field can be shown via
 * this special page.
 *
 * @file
 */
class TopFansByStat extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'TopFansByStatistic' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Statistic name, i.e. friends_count or edit_count, etc. (or null)
	 */
	public function execute( $par ) {
		global $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly;

		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$linkRenderer = $this->getLinkRenderer();
		$lang = $this->getLanguage();
		$out = $this->getOutput();
		$request = $this->getRequest();
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Load CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		$dbr = wfGetDB( DB_REPLICA );

		$statistic = trim( $request->getVal( 'stat', $par ) );
		$column = "stats_{$statistic}";

		// Error if the query string value does not match our stat column
		if ( !preg_match( '/^stats_[0-9a-z_]{1,58}$/D', $column ) ||
			!$dbr->fieldExists( 'user_stats', $column )
		) {
			$out->setPageTitle( $this->msg( 'top-fans-bad-field-title' )->plain() );
			$out->addHTML( htmlspecialchars( $this->msg( 'top-fans-bad-field-message' )->plain() ) );
			return;
		}

		// Fix i18n message key
		$fixedStatistic = str_replace( '_', '-', $statistic );

		// Set page title
		$out->setPageTitle( $this->msg( 'top-fans-by-category-title-' . $fixedStatistic )->plain() );

		$count = 50;
		$realCount = 100;

		$user_list = [];

		// Get the list of users

		// Try cache
		$key = $cache->makeKey( 'user_stats', 'top', $statistic, $realCount );
		$data = $cache->get( $key );

		if ( $data != '' ) {
			$logger->debug( "Got top users by {statistic} ({count}) from cache\n", [
				'statistic' => $statistic,
				'count' => $count
			] );

			$user_list = $data;
		} else {
			$logger->debug( "Got top users by {statistic} ({count}) from DB\n", [
				'statistic' => $statistic,
				'count' => $count
			] );

			$params = [];
			$params['ORDER BY'] = "{$column} DESC";
			$params['LIMIT'] = $count;

			// @phan-suppress-next-line SecurityCheck-SQLInjection false positive, phan doesn't understand our custom validation of $column on L47
			$res = $dbr->select(
				'user_stats',
				[ 'stats_actor', $column ],
				[ 'stats_actor IS NOT NULL', "{$column} > 0" ],
				__METHOD__,
				$params
			);

			$loop = 0;

			foreach ( $res as $row ) {
				$u = User::newFromActorId( $row->stats_actor );
				// Ensure that the user exists for real.
				// Otherwise we'll be happily displaying entries for users that
				// once existed but no longer do (account merging is a thing,
				// sadly), since user_stats entries for users are *not* purged
				// and/or merged during the account merge process (which is a
				// different bug with a different extension).
				// Also ignore flagged bot accounts, no point in showing those
				// in the top lists.
				$exists = $u->load();

				if ( $exists && !$u->isBlocked() && !$u->isBot() ) {
					$user_list[] = [
						'actor' => $row->stats_actor,
						'stat' => $row->$column
					];
				}

				if ( $loop >= $realCount ) {
					break;
				}
			}

			$cache->set( $key, $user_list, 60 * 5 );
		}

		// Top nav bar
		$top_title = SpecialPage::getTitleFor( 'TopUsers' );
		$recent_title = SpecialPage::getTitleFor( 'TopUsersRecent' );

		$output = '<div class="top-fan-nav">
			<h1>' . $this->msg( 'top-fans-by-points-nav-header' )->escaped() . '</h1>
			<p><a href="' . htmlspecialchars( $top_title->getFullURL() ) . '">' .
					$this->msg( 'top-fans-total-points-link' )->escaped() . '</a></p>';

		if ( $wgUserStatsTrackWeekly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=monthly' ) ) . '">' .
				$this->msg( 'top-fans-monthly-points-link' )->escaped() . '</a><p>';
		}
		if ( $wgUserStatsTrackMonthly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=weekly' ) ) . '">' .
				$this->msg( 'top-fans-weekly-points-link' )->escaped() . '</a></p>';
		}

		// Build nav of stats by category based on MediaWiki:Topfans-by-category
		$message = $this->msg( 'topfans-by-category' )->inContentLanguage();

		if ( !$message->isDisabled() ) {
			$output .= '<h1 class="top-title">' .
				$this->msg( 'top-fans-by-category-nav-header' )->escaped() . '</h1>';

			$lines = explode( "\n", $message->text() );
			foreach ( $lines as $line ) {
				if ( strpos( $line, '*' ) !== 0 ) {
					continue;
				} else {
					$line = explode( '|', trim( $line, '* ' ), 2 );
					$stat = $line[0];

					$link_text = $line[1];
					// Check if the link text is actually the name of a system
					// message (refs bug #30030)
					$msgObj = $this->msg( $link_text );
					if ( !$msgObj->isDisabled() ) {
						$link_text = $msgObj->text();
					}

					$output .= '<p>';
					$output .= $linkRenderer->makeLink(
						$this->getPageTitle(),
						$link_text,
						[],
						[ 'stat' => $stat ]
					);
					$output .= '</p>';
				}
			}
		}

		$output .= '</div>';
		$x = 1;
		$output .= '<div class="top-users">';

		foreach ( $user_list as $user ) {
			$u = User::newFromActorId( $user['actor'] );
			if ( !$u ) {
				continue;
			}

			$user_name = $lang->truncateForVisual( $u->getName(), 22 );
			$avatar = new wAvatar( $u->getId(), 'm' );
			$commentIcon = $avatar->getAvatarURL();

			// Stats row
			// TODO: opinion_average isn't currently working, so it's not enabled in menus
			if ( $statistic == 'opinion_average' ) {
				$statistics_row = number_format( $row->opinion_average, 2 );
				$lowercase_statistics_name = 'percent';
			} else {
				$statistics_row = number_format( $user['stat'] );
				$lowercase_statistics_name = $lang->lc( $this->msg(
					"top-fans-stats-{$fixedStatistic}",
					$user['stat']
				)->parse() );
			}

			$output .= '<div class="top-fan-row">
				<span class="top-fan-num">' . $x . '.</span>
				<span class="top-fan">' .
					$commentIcon .
					// @phan-suppress-next-line SecurityCheck-DoubleEscaped T290624
					$linkRenderer->makeLink(
						$u->getUserPage(),
						$user_name
					) .
				'</span>
				<span class="top-fan-points"><b>' . htmlspecialchars( $statistics_row, ENT_QUOTES ) . '</b> ' . $lowercase_statistics_name . '</span>
				<div class="visualClear"></div>
			</div>';
			$x++;
		}

		$output .= '</div><div class="visualClear"></div>';
		$out->addHTML( $output );
	}
}
