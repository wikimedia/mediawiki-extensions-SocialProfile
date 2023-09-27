<?php

class UserLevel {
	/** @var int */
	public $level_number = 0;

	/** @var string Name of the current level the user is on */
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

	/**
	 * @param int $points Amount of social points the user has,
	 *   usually fetched by calling $stats->getUserStats() where $stats
	 *   is an instance of UserStats initialized with the desired User
	 *   (object) passed to its constructor and then passing the 'points'
	 *   array key from the return value of the aforementioned into this
	 *   function
	 */
	function __construct( $points ) {
		global $wgUserLevels;
		$this->levels = $wgUserLevels;
		$this->points = (int)str_replace( ',', '', (string)$points );
		if ( $this->levels ) {
			$this->setLevel();
		}
	}

	/**
	 * Iterate over $wgUserLevels and set the correct class member variables
	 * based on the data we're working with.
	 *
	 * @return string|void Empty string sometimes but usually nothing
	 */
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

	/**
	 * Get the name of the user's _current_ level.
	 *
	 * @return string
	 */
	public function getLevelName() {
		return $this->level_name;
	}

	/**
	 * Get the level number of the user's current level.
	 *
	 * @return int
	 */
	public function getLevelNumber() {
		return $this->level_number;
	}

	/**
	 * Get the name of the next level the user can advance to.
	 *
	 * @return string
	 */
	public function getNextLevelName() {
		return $this->next_level_name;
	}

	/**
	 * How many points does the user need to advance to the next level?
	 *
	 * @return int
	 */
	public function getPointsNeededToAdvance() {
		return $this->next_level_points_needed;
	}

	/**
	 * Get the minimum amount of points needed to reach the level the user
	 * is currently at.
	 *
	 * @return int
	 */
	public function getLevelMinimum() {
		return $this->levels[$this->level_name];
	}
}
