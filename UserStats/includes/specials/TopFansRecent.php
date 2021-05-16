<?php

use MediaWiki\Logger\LoggerFactory;
use MediaWiki\MediaWikiServices;

class TopFansRecent extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'TopUsersRecent' );
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
		return [ 'weekly', 'monthly' ];
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Period name, i.e. weekly or monthly (or null)
	 */
	public function execute( $par ) {
		$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
		$linkRenderer = $this->getLinkRenderer();
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();
		$logger = LoggerFactory::getInstance( 'SocialProfile' );

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Load CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		$periodFromRequest = $request->getVal( 'period', $par );
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

		$user_list = [];

		// Try cache
		$key = $cache->makeKey( 'user_stats', $period, 'points', $realCount );
		$data = $cache->get( $key );

		if ( $data != '' ) {
			$logger->debug( "Got top users by {period} points ({count}) from cache\n", [
				'period' => $period,
				'count' => $count
			] );

			$user_list = $data;
		} else {
			$logger->debug( "Got top users by {period} points ({count}) from DB\n", [
				'period' => $period,
				'count' => $count
			] );

			$params = [];
			$params['ORDER BY'] = 'up_points DESC';
			$params['LIMIT'] = $count;

			$dbr = wfGetDB( DB_REPLICA );
			$res = $dbr->select(
				"user_points_{$period}",
				[ 'up_actor', 'up_points' ],
				[ 'up_actor IS NOT NULL' ],
				__METHOD__,
				$params
			);

			$loop = 0;

			foreach ( $res as $row ) {
				$u = User::newFromActorId( $row->up_actor );
				// Ensure that the user exists for real.
				// Otherwise we'll be happily displaying entries for users that
				// once existed by no longer do (account merging is a thing,
				// sadly), since user_stats entries for users are *not* purged
				// and/or merged during the account merge process (which is a
				// different bug with a different extension).
				// Also ignore flagged bot accounts, no point in showing those
				// in the top lists.
				$u->load();
				$exists = $u->getId() !== 0;

				if ( $exists && !$u->isBlocked() && !$u->isBot() ) {
					$user_list[] = [
						'actor' => $row->up_actor,
						'points' => $row->up_points
					];
					$loop++;
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

		if ( $period == 'weekly' ) {
			$output .= '<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=monthly' ) ) . '">' .
				$this->msg( 'top-fans-monthly-points-link' )->escaped() . '</a><p>
			<p><b>' . $this->msg( 'top-fans-weekly-points-link' )->escaped() . '</b></p>';
		} else {
			$output .= '<p><b>' . $this->msg( 'top-fans-monthly-points-link' )->escaped() . '</b><p>
			<p><a href="' . htmlspecialchars( $recent_title->getFullURL( 'period=weekly' ) ) . '">' .
				$this->msg( 'top-fans-weekly-points-link' )->escaped() . '</a></p>';
		}

		// Build nav of stats by category based on MediaWiki:Topfans-by-category
		$by_category_title = SpecialPage::getTitleFor( 'TopFansByStatistic' );
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
						$by_category_title,
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

			$avatar = new wAvatar( $u->getId(), 'm' );
			$avatarImage = $avatar->getAvatarURL();

			$output .= '<div class="top-fan-row">
				<span class="top-fan-num">' . $x . '.</span>
				<span class="top-fan">' .
					$avatarImage .
					$linkRenderer->makeLink(
						$u->getUserPage(),
						$u->getName()
					) .
				'</span>';

			$output .= '<span class="top-fan-points">' .
				$this->msg( 'top-fans-points' )->numParams( $user['points'] )->parse() . '</span>';
			$output .= '<div class="visualClear"></div>';
			$output .= '</div>';
			$x++;
		}

		$output .= '</div><div class="visualClear"></div>';
		$out->addHTML( $output );
	}
}
