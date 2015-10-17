<?php
/**
 * Special page that shows the top users for a given statistic, i.e.
 * "users with the most friends" or "users with the most votes".
 * Anything that exists in the user_stats table as a field can be shown via
 * this special page.
 *
 * @file
 */
class TopFansByStat extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'TopFansByStatistic' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgMemc;
		global $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly;

		$lang = $this->getLanguage();
		$out = $this->getOutput();
		$request = $this->getRequest();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Load CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		$dbr = wfGetDB( DB_SLAVE );

		$statistic = trim( $request->getVal( 'stat' ) );
		$column = "stats_{$statistic}";

		// Error if the query string value does not match our stat column
		if ( !preg_match( '/^stats_[0-9a-z_]{1,58}$/D', $column ) ||
			!$dbr->fieldExists( 'user_stats', $column )
		) {
			$out->setPageTitle( $this->msg( 'top-fans-bad-field-title' )->plain() );
			$out->addHTML( $this->msg( 'top-fans-bad-field-message' )->plain() );
			return false;
		}

		// Fix i18n message key
		$fixedStatistic = str_replace( '_', '-', $statistic );

		// Set page title
		$out->setPageTitle( $this->msg( 'top-fans-by-category-title-' . $fixedStatistic )->plain() );

		$count = 50;
		$realCount = 100;

		$user_list = array();

		// Get the list of users

		// Try cache
		$key = wfMemcKey( 'user_stats', 'top', $statistic, $realCount );
		$data = $wgMemc->get( $key );

		if ( $data != '' ) {
			wfDebug( "Got top users by {$statistic} ({$count}) from cache\n" );
			$user_list = $data;
		} else {
			wfDebug( "Got top users by {$statistic} ({$count}) from DB\n" );

			$params['ORDER BY'] = "{$column} DESC";
			$params['LIMIT'] = $count;

			$res = $dbr->select(
				'user_stats',
				array( 'stats_user_id', 'stats_user_name', $column ),
				array( 'stats_user_id <> 0', "{$column} > 0" ),
				__METHOD__,
				$params
			);

			$loop = 0;

			foreach ( $res as $row ) {
				$u = User::newFromId( $row->stats_user_id );
				// Ensure that the user exists for real.
				// Otherwise we'll be happily displaying entries for users that
				// once existed by no longer do (account merging is a thing,
				// sadly), since user_stats entries for users are *not* purged
				// and/or merged during the account merge process (which is a
				// different bug with a different extension).
				$exists = $u->loadFromId();

				if ( !$u->isBlocked() && $exists ) {
					$user_list[] = array(
						'user_id' => $row->stats_user_id,
						'user_name' => $row->stats_user_name,
						'stat' => $row->$column
					);
				}

				if ( $loop >= $realCount ) {
					break;
				}
			}

			$wgMemc->set( $key, $user_list, 60 * 5 );
		}

		// Top nav bar
		$top_title = SpecialPage::getTitleFor( 'TopUsers' );
		$recent_title = SpecialPage::getTitleFor( 'TopUsersRecent' );

		$output = '<div class="top-fan-nav">
			<h1>' . $this->msg( 'top-fans-by-points-nav-header' )->plain() . '</h1>
			<p><a href="' . htmlspecialchars( $top_title->getFullURL() ) . '">' .
				$this->msg( 'top-fans-total-points-link' )->plain() . '</a></p>';

		if ( $wgUserStatsTrackWeekly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=monthly' ) ) . '">' .
				$this->msg( 'top-fans-monthly-points-link' )->plain() . '</a><p>';
		}
		if ( $wgUserStatsTrackMonthly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=weekly' ) ) . '">' .
				$this->msg( 'top-fans-weekly-points-link' )->plain() . '</a></p>';
		}

		// Build nav of stats by category based on MediaWiki:Topfans-by-category
		$message = $this->msg( 'topfans-by-category' )->inContentLanguage();

		if ( !$message->isDisabled() ) {
			$output .= '<h1 class="top-title">' .
				$this->msg( 'top-fans-by-category-nav-header' )->plain() . '</h1>';

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
						$link_text = $msgObj->parse();
					}

					$output .= '<p>';
					$output .= Linker::link(
						$this->getPageTitle(),
						$link_text,
						array(),
						array( 'stat' => $stat )
					);
					$output .= '</p>';
				}
			}
		}

		$output .= '</div>';
		$x = 1;
		$output .= '<div class="top-users">';

		foreach ( $user_list as $user ) {
			$user_name = $lang->truncate( $user['user_name'], 22 );
			$user_title = Title::makeTitle( NS_USER, $user['user_name'] );
			$avatar = new wAvatar( $user['user_id'], 'm' );
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
					'<a href="' . htmlspecialchars( $user_title->getFullURL() ) . '">' . $user_name . '</a>
				</span>
				<span class="top-fan-points"><b>' . $statistics_row . '</b> ' . $lowercase_statistics_name . '</span>
				<div class="visualClear"></div>
			</div>';
			$x++;
		}

		$output .= '</div><div class="visualClear"></div>';
		$out->addHTML( $output );
	}
}
