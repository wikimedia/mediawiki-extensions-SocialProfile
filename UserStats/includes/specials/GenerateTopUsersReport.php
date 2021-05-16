<?php
/**
 * A special page to generate the report of the users who earned the most
 * points during the past week or month. This is the only way to update the
 * points_winner_weekly and points_winner_monthly columns in the user_stats
 * table.
 *
 * This special page also creates a weekly report in the project namespace.
 * The name of that page is controlled by two system messages,
 * MediaWiki:User-stats-report-weekly-page-title and
 * MediaWiki:User-stats-report-monthly-page-title (depending on the type of the
 * report).
 *
 * @file
 * @ingroup Extensions
 */
class GenerateTopUsersReport extends SpecialPage {

	public function __construct() {
		parent::__construct( 'GenerateTopUsersReport', 'generatetopusersreport' );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string $period Either weekly or monthly
	 */
	public function execute( $period ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// Check for the correct permission
		$this->checkPermissions();

		// Is the database locked or not?
		$this->checkReadOnly();

		// Blocked through Special:Block? Tough luck.
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policy, etc.
		$this->setHeaders();

		$period = $request->getVal( 'period', $period );

		// If we don't have a period, default to weekly or else we'll be
		// hitting a database error because when constructing table names
		// later on in the code, we assume that $period is set to something
		if ( !$period || ( $period != 'weekly' && $period != 'monthly' ) ) {
			$period = 'weekly';
		}

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$this->generateReport( $period );
		} else {
			$out->addHTML( $this->displayForm( $period ) );
		}
	}

	/**
	 * Render the confirmation form
	 *
	 * @param string $period Either weekly or monthly
	 * @return string HTML
	 */
	private function displayForm( $period ) {
		$form = '<form method="post" name="generate-top-users-report-form" action="">';
		// For grep: generatetopusersreport-confirm-monthly, generatetopusersreport-confirm-weekly
		$form .= $this->msg( 'generatetopusersreport-confirm-' . $period )->escaped();
		$form .= '<br />';
		$form .= Html::hidden( 'period', $period ); // not sure if this is strictly needed but paranoia
		$form .= Html::hidden( 'wpEditToken', $this->getUser()->getEditToken() );
		// passing null as the 1st argument makes the button use the browser default text
		// (on Firefox 72 with English localization this is "Submit Query" which is good enough,
		// since MW core lacks a generic "submit" message and I don't feel like introducing
		// a new i18n msg just for this button...)
		$form .= Html::submitButton( null, [ 'name' => 'wpSubmit' ] );
		$form .= '</form>';
		return $form;
	}

	/**
	 * Actually generate the report.
	 *
	 * Includes:
	 * -calculating the winners
	 * -updating the relevant stats tables in the DB with new data
	 * -generating the on-wiki page
	 *
	 * @todo This could probably be made more modular and reusable in general
	 *  by using Status or StatusValue objects or somesuch instead of directly
	 *  outputting HTML via OutputPage. Something to work on a rainy day...
	 *
	 * @suppress SecurityCheck-SQLInjection phan can't tell that we only allow 'monthly' or 'weekly' as $period
	 * @param string $period Either weekly or monthly
	 */
	private function generateReport( $period ) {
		global $wgUserStatsPointValues;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$contLang = MediaWiki\MediaWikiServices::getInstance()->getContentLanguage();

		// Make sure that we are actually going to give out some extra points
		// for weekly and/or monthly wins, depending on which report we're
		// generating here. If not, there's no point in continuing.
		if ( empty( $wgUserStatsPointValues["points_winner_{$period}"] ) ) {
			$out->addHTML( $this->msg( 'user-stats-report-error-variable-not-set', $period )->escaped() );
			return;
		}

		// There used to be a lot of inline CSS here in the original version.
		// I removed that, because most of it is already in TopList.css, inline
		// CSS (and JS, for that matter) is evil, there were only 5 CSS
		// declarations that weren't in TopList.css and it was making the
		// display look worse, not better.

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.userstats.css' );

		// Used as the LIMIT for SQL queries; basically, show this many users
		// in the generated reports.
		$user_count = $request->getInt( 'user_count', 10 );

		if ( $period == 'weekly' ) {
			$period_title = $contLang->date( wfTimestamp( TS_MW, strtotime( '-1 week' ) ) ) .
				'-' . $contLang->date( wfTimestampNow() );
		} elseif ( $period == 'monthly' ) {
			$date = getdate(); // It's a PHP core function
			$period_title = $contLang->getMonthName( $date['mon'] ) .
				' ' . $date['year'];
		}

		$dbw = wfGetDB( DB_MASTER );
		// Query the appropriate points table
		$res = $dbw->select(
			"user_points_{$period}",
			[ 'up_actor', 'up_points' ],
			[],
			__METHOD__,
			[ 'ORDER BY' => 'up_points DESC', 'LIMIT' => $user_count ]
		);

		$last_rank = 0;
		$last_total = 0;
		$x = 1;

		$users = [];

		// Initial run is a special case
		if ( $dbw->numRows( $res ) <= 0 ) {
			// For the initial run, everybody's a winner!
			// Yes, I know that this isn't ideal and I'm sorry about that.
			// The original code just wouldn't work if the first query
			// (the $res above) returned nothing so I had to work around that
			// limitation.
			$res = $dbw->select(
				'user_stats',
				[ 'stats_actor', 'stats_total_points' ],
				[],
				__METHOD__,
				[
					'ORDER BY' => 'stats_total_points DESC',
					'LIMIT' => $user_count
				]
			);

			$output = '<div class="top-users">';

			foreach ( $res as $row ) {
				if ( $row->stats_total_points == $last_total ) {
					$rank = $last_rank;
				} else {
					$rank = $x;
				}
				$last_rank = $x;
				$last_total = $row->stats_total_points;
				$x++;
				$users[] = [
					'actor' => $row->stats_actor,
					'points' => $row->stats_total_points,
					'rank' => $rank
				];
			}
		} else {
			$output = '<div class="top-users">';

			foreach ( $res as $row ) {
				if ( $row->up_points == $last_total ) {
					$rank = $last_rank;
				} else {
					$rank = $x;
				}
				$last_rank = $x;
				$last_total = $row->up_points;
				$x++;
				$users[] = [
					'actor' => $row->up_actor,
					'points' => $row->up_points,
					'rank' => $rank
				];
			}
		}

		$winner_count = 0;
		$winners = '';

		if ( !empty( $users ) ) {
			$localizedUserNS = $contLang->getNsText( NS_USER );
			foreach ( $users as $user ) {
				if ( $user['rank'] == 1 ) {
					// Mark the user ranked #1 as the "winner" for the given
					// period
					$stats = new UserStatsTrack( $user['actor'] );
					$stats->incStatField( "points_winner_{$period}" );
					if ( $winners ) {
						$winners .= ', ';
					}
					$actorUser = User::newFromActorId( $user['actor'] );
					if ( !$actorUser ) {
						continue;
					}
					$winners .= "[[{$localizedUserNS}:{$actorUser->getName()}|{$actorUser->getName()}]]";
					$winner_count++;
				}
			}
		}

		// Start building the content of the report page
		$pageContent = "__NOTOC__\n";

		// For grep: user-stats-weekly-winners, user-stats-monthly-winners
		$pageContent .= '==' . $this->msg(
			"user-stats-{$period}-winners"
		)->numParams( $winner_count )->inContentLanguage()->parse() . "==\n\n";

		// For grep: user-stats-weekly-win-congratulations, user-stats-monthly-win-congratulations
		$pageContent .= $this->msg(
			"user-stats-{$period}-win-congratulations"
		)->numParams(
			$winner_count,
			$contLang->formatNum( $wgUserStatsPointValues["points_winner_{$period}"] )
		)->inContentLanguage()->parse() . "\n\n";
		$pageContent .= "=={$winners}==\n\n<br />\n";

		$pageContent .= '==' . $this->msg( 'user-stats-full-top' )->numParams(
			$contLang->formatNum( $user_count ) )->inContentLanguage()->parse() . "==\n\n";

		foreach ( $users as $user ) {
			$u = User::newFromActorId( $user['actor'] );
			if ( !$u ) {
				continue;
			}

			$pageContent .= '{{int:user-stats-report-row|' .
				$contLang->formatNum( $user['rank'] ) . '|' .
				$u->getName() . '|' .
				$contLang->formatNum( $user['points'] ) . "}}\n\n";

			$output .= "<div class=\"top-fan-row\">
			<span class=\"top-fan-num\">{$user['rank']}</span><span class=\"top-fan\"> <a href='" .
				htmlspecialchars( $u->getUserPage()->getFullURL() ) . "' >" . htmlspecialchars( $u->getName() ) . "</a>
			</span>";

			$output .= '<span class="top-fan-points">' . $this->msg(
				'user-stats-report-points',
				$contLang->formatNum( $user['points'] )
			)->inContentLanguage()->parse() . '</span>
		</div>';
		}

		// Create the Title object that represents the report page
		// For grep: user-stats-report-weekly-page-title, user-stats-report-monthly-page-title
		$title = Title::makeTitleSafe(
			NS_PROJECT,
			$this->msg( "user-stats-report-{$period}-page-title", $period_title )->inContentLanguage()->plain()
		);

		$page = WikiPage::factory( $title );
		// If the article doesn't exist, create it!
		// @todo Would there be any point in updating a pre-existing article?
		// I think not, but...
		if ( !$page->exists() ) {
			$this->createReportPage( $page, $title, $period, $pageContent );
		}

		$output .= '</div>'; // .top-users
		$out->addHTML( $output );
	}

	/**
	 * Make the edit
	 *
	 * @param WikiPage $page
	 * @param Title $title
	 * @param string $period
	 * @param string $pageContent
	 */
	private function createReportPage( WikiPage $page, Title $title, $period, $pageContent ) {
		// Grab a user object to make the edit as
		$user = User::newFromName( 'MediaWiki default' );
		if ( $user->getId() === 0 ) {
			$user = User::newSystemUser( 'MediaWiki default', [ 'steal' => true ] );
		}

		// Add a note to the page that it was automatically generated
		$pageContent .= "\n\n''" . $this->msg( 'user-stats-report-generation-note' )->parse() . "''\n\n";

		// Make the edit as MediaWiki default
		// For grep: user-stats-report-weekly-edit-summary, user-stats-report-monthly-edit-summary
		$page->doEditContent(
			ContentHandler::makeContent( $pageContent, $title ),
			$this->msg( "user-stats-report-{$period}-edit-summary" )->inContentLanguage()->plain(),
			EDIT_NEW | EDIT_FORCE_BOT,
			false, /* $originalRevId */
			$user
		);

		$date = date( 'Y-m-d H:i:s' );
		// Archive points from the weekly/monthly table into the archive table
		$dbw = wfGetDB( DB_MASTER );
		$dbw->insertSelect(
			'user_points_archive',
			"user_points_{$period}",
			[
				'up_actor' => 'up_actor',
				'up_points' => 'up_points',
				'up_period' => ( ( $period == 'weekly' ) ? 1 : 2 ),
				'up_date' => $dbw->addQuotes( $dbw->timestamp( $date ) )
			],
			'*',
			__METHOD__
		);

		// Clear the current point table to make way for the next period
		$res = $dbw->delete( "user_points_{$period}", '*', __METHOD__ );
	}

}
