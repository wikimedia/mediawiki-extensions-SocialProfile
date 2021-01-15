<?php

class UserLevel {
	/** @var int */
	public $level_number = 0;
	/** @var string */
	public $level_name;

	/**
	 * @var int The amount of points the user has; passed to the constructor
	 */
	public $points;

	/**
	 * @var array User level configuration in the form of
	 *   'Level name' => required points; gotten from $wgUserLevels
	 */
	public $levels;

	/**
	 * @var int name of the next level
	 */
	public $next_level_name;

	/**
	 * @var int $next_level_points amount of points needed to reach the next level
	 */
	public $next_level_points_needed;

	function __construct( $points ) {
		global $wgUserLevels;
		$this->levels = $wgUserLevels;
		$this->points = (int)str_replace( ',', '', $points );
		if ( $this->levels ) {
			$this->setLevel();
		}
	}

	private function setLevel() {
		$this->level_number = 1;
		foreach ( $this->levels as $level_name => $level_points_needed ) {
			if ( $this->points >= $level_points_needed ) {
				$this->level_name = $level_name;
				$this->level_number++;
			} else {
				// Set next level and what they need to reach
				// Check if not already at highest level
				if ( ( $this->level_number ) != count( $this->levels ) ) {
					$this->next_level_name = $level_name;
					$this->next_level_points_needed = ( $level_points_needed - $this->points );
					return '';
				}
			}
		}
	}

	public function getLevelName() {
		return $this->level_name;
	}

	public function getLevelNumber() {
		return $this->level_number;
	}

	public function getNextLevelName() {
		return $this->next_level_name;
	}

	public function getPointsNeededToAdvance() {
		return $this->next_level_points_needed;
	}

	public function getLevelMinimum() {
		return $this->levels[$this->level_name];
	}
}
