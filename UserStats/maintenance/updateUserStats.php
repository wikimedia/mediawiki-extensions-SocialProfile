<?php
/**
 * @file
 * @ingroup Maintenance
 */

$IP = getenv( 'MW_INSTALL_PATH' );
if ( $IP === false ) {
	$IP = __DIR__ . '/../../../..';
}
require_once "$IP/maintenance/Maintenance.php";

/**
 * Updates the edit count and total points of the user_stats table
 *
 * @see https://phabricator.wikimedia.org/T341098
 * @since July 2023
 */
class UpdateUserStats extends Maintenance {
	public function __construct() {
		parent::__construct();
		$this->addDescription( 'Updates or populates the user_stats table, with edit counts and total points.' );
	}

	public function execute() {
		$updater = new UserStatsUpdater();

		$this->output( "Updating edit counts...\n" );
		$updater->updateMainEditsCount( [ $this, 'reportProgress' ] );
		$this->output( "Updating total points...\n" );
		$count = $updater->updateTotalPoints();
		$this->output( "Updated $count rows\n" );
	}

	/**
	 * Prints each user that gets their edit count updated
	 *
	 * @param string $userName User name
	 * @param int $editCount Updated edit count
	 */
	public function reportProgress( $userName, $editCount ) {
		$this->output( "Updating $userName with $editCount\n" );
	}
}

$maintClass = UpdateUserStats::class;
require_once RUN_MAINTENANCE_IF_MAIN;
