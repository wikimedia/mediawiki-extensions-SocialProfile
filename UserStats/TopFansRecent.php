<?php

class TopFansRecent extends UnlistedSpecialPage {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( 'TopUsersRecent' );
	}

	/**
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgMemc;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Load CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		$periodFromRequest = $request->getVal( 'period' );
		if ( $periodFromRequest == 'weekly' ) {
			$period = 'weekly';
		} elseif ( $periodFromRequest == 'monthly' ) {
			$period = 'monthly';
		}

		if ( !isset( $period ) ) {
			$period = 'weekly';
		}

		if ( $period == 'weekly' ) {
			$pageTitle = 'user-stats-weekly-title';
		} else {
			$pageTitle = 'user-stats-monthly-title';
		}
		$out->setPageTitle( $this->msg( $pageTitle )->plain() );

		$count = 100;
		$realCount = 50;

		$user_list = array();

		// Try cache
		$key = wfMemcKey( 'user_stats', $period, 'points', $realCount );
		$data = $wgMemc->get( $key );

		if ( $data != '' ) {
			wfDebug( "Got top users by {$period} points ({$count}) from cache\n" );
			$user_list = $data;
		} else {
			wfDebug( "Got top users by {$period} points ({$count}) from DB\n" );

			$params['ORDER BY'] = 'up_points DESC';
			$params['LIMIT'] = $count;

			$dbr = wfGetDB( DB_SLAVE );
			$res = $dbr->select(
				"user_points_{$period}",
				array( 'up_user_id', 'up_user_name', 'up_points' ),
				array( 'up_user_id <> 0' ),
				__METHOD__,
				$params
			);

			$loop = 0;

			foreach ( $res as $row ) {
				$u = User::newFromId( $row->up_user_id );
				// Ensure that the user exists for real.
				// Otherwise we'll be happily displaying entries for users that
				// once existed by no longer do (account merging is a thing,
				// sadly), since user_stats entries for users are *not* purged
				// and/or merged during the account merge process (which is a
				// different bug with a different extension).
				$exists = $u->loadFromId();

				if ( !$u->isBlocked() && $exists ) {
					$user_list[] = array(
						'user_id' => $row->up_user_id,
						'user_name' => $row->up_user_name,
						'points' => $row->up_points
					);
					$loop++;
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

		if ( $period == 'weekly' ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=monthly' ) ) . '">' .
				$this->msg( 'top-fans-monthly-points-link' )->plain() . '</a><p>
			<p><b>' . $this->msg( 'top-fans-weekly-points-link' )->plain() . '</b></p>';
		} else {
			$output .= '<p><b>' . $this->msg( 'top-fans-monthly-points-link' )->plain() . '</b><p>
			<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=weekly' ) ) . '">' .
				$this->msg( 'top-fans-weekly-points-link' )->plain() . '</a></p>';
		}

		// Build nav of stats by category based on MediaWiki:Topfans-by-category
		$by_category_title = SpecialPage::getTitleFor( 'TopFansByStatistic' );
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

		foreach ( $user_list as $user ) {
			$user_title = Title::makeTitle( NS_USER, $user['user_name'] );
			$avatar = new wAvatar( $user['user_id'], 'm' );
			$avatarImage = $avatar->getAvatarURL();

			$output .= '<div class="top-fan-row">
				<span class="top-fan-num">' . $x . '.</span>
				<span class="top-fan">' .
					$avatarImage .
					'<a href="' . htmlspecialchars( $user_title->getFullURL() ) . '" >' . $user['user_name'] . '</a>
				</span>';

			$output .= '<span class="top-fan-points"><b>' .
				$this->getLanguage()->formatNum( $user['points'] ) . '</b> ' .
				$this->msg( 'top-fans-points' )->plain() . '</span>';
			$output .= '<div class="cleared"></div>';
			$output .= '</div>';
			$x++;
		}

		$output .= '</div><div class="cleared"></div>';
		$out->addHTML( $output );
	}
}
