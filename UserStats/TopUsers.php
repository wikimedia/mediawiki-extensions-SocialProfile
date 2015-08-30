<?php

class TopUsersPoints extends SpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'TopUsers' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgMemc, $wgUserStatsTrackWeekly, $wgUserStatsTrackMonthly, $wgUserLevels;

		$out = $this->getOutput();

		// Load CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		$out->setPageTitle( $this->msg( 'user-stats-alltime-title' )->plain() );

		$count = 100;
		$realcount = 50;

		$user_list = array();

		// Try cache
		$key = wfMemcKey( 'user_stats', 'top', 'points', $realcount );
		$data = $wgMemc->get( $key );

		if ( $data != '' ) {
			wfDebug( "Got top users by points ({$count}) from cache\n" );
			$user_list = $data;
		} else {
			wfDebug( "Got top users by points ({$count}) from DB\n" );

			$params['ORDER BY'] = 'stats_total_points DESC';
			$params['LIMIT'] = $count;
			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				'user_stats',
				array( 'stats_user_id', 'stats_user_name', 'stats_total_points' ),
				array( 'stats_user_id <> 0' ),
				__METHOD__,
				$params
			);

			$loop = 0;

			foreach ( $res as $row ) {
				$user = User::newFromId( $row->stats_user_id );
				// Ensure that the user exists for real.
				// Otherwise we'll be happily displaying entries for users that
				// once existed by no longer do (account merging is a thing,
				// sadly), since user_stats entries for users are *not* purged
				// and/or merged during the account merge process (which is a
				// different bug with a different extension).
				$exists = $user->loadFromId();

				if ( !$user->isBlocked() && $exists ) {
					$user_list[] = array(
						'user_id' => $row->stats_user_id,
						'user_name' => $row->stats_user_name,
						'points' => $row->stats_total_points
					);
					$loop++;
				}

				if ( $loop >= $realcount ) {
					break;
				}
			}

			$wgMemc->set( $key, $user_list, 60 * 5 );
		}

		$recent_title = SpecialPage::getTitleFor( 'TopUsersRecent' );

		$output = '<div class="top-fan-nav">
			<h1>' . $this->msg( 'top-fans-by-points-nav-header' )->plain() . '</h1>
			<p><b>' . $this->msg( 'top-fans-total-points-link' )->plain() . '</b></p>';

		if ( $wgUserStatsTrackWeekly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=monthly' ) ) . '">' .
				$this->msg( 'top-fans-monthly-points-link' )->plain() . '</a></p>';
		}

		if ( $wgUserStatsTrackMonthly ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=weekly' ) ) . '">' .
				$this->msg( 'top-fans-weekly-points-link' )->plain() . '</a></p>';
		}

		// Build nav of stats by category based on MediaWiki:Topfans-by-category
		$by_category_title = SpecialPage::getTitleFor( 'TopFansByStatistic' );

		$byCategoryMessage = $this->msg( 'topfans-by-category' )->inContentLanguage();

		if ( !$byCategoryMessage->isDisabled() ) {
			$output .= '<h1 style="margin-top:15px !important;">' .
				$this->msg( 'top-fans-by-category-nav-header' )->plain() . '</h1>';

			$lines = explode( "\n", $byCategoryMessage->text() );
			foreach ( $lines as $line ) {
				if ( strpos( $line, '*' ) !== 0 ) {
					continue;
				} else {
					$line = explode( '|' , trim( $line, '* ' ), 2 );
					$stat = $line[0];

					$link_text = $line[1];
					// Check if the link text is actually the name of a system
					// message (refs bug #30030)
					$msgObj = $this->msg( $link_text );
					if ( !$msgObj->isDisabled() ) {
						$link_text = $msgObj->parse();
					}

					$output .= '<p> ';
					$output .= Linker::link(
						$by_category_title,
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
		$last_level = '';

		foreach ( $user_list as $user ) {
			$user_title = Title::makeTitle( NS_USER, $user['user_name'] );
			$avatar = new wAvatar( $user['user_id'], 'm' );
			$commentIcon = $avatar->getAvatarURL();

			// Break list into sections based on User Level if it's defined for this site
			if ( is_array( $wgUserLevels ) ) {
				$user_level = new UserLevel( number_format( $user['points'] ) );
				if ( $user_level->getLevelName() != $last_level ) {
					$output .= "<div class=\"top-fan-row\"><div class=\"top-fan-level\">
						{$user_level->getLevelName()}
						</div></div>";
				}
				$last_level = $user_level->getLevelName();
			}

			$output .= "<div class=\"top-fan-row\">
				<span class=\"top-fan-num\">{$x}.</span>
				<span class=\"top-fan\">
					{$commentIcon} <a href='" . htmlspecialchars( $user_title->getFullURL() ) . "'>" .
						$user['user_name'] . '</a>
				</span>';

			$output .= '<span class="top-fan-points"><b>' .
				number_format( $user['points'] ) . '</b> ' .
				$this->msg( 'top-fans-points' )->plain() . '</span>';
			$output .= '<div class="visualClear"></div>';
			$output .= '</div>';
			$x++;
		}

		$output .= '</div><div class="visualClear"></div>';
		$out->addHTML( $output );
	}
}
