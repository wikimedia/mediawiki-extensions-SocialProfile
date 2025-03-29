<?php

use MediaWiki\Html\Html;

/**
 * Object for easily constructing new system gift icons.
 */
class SystemGiftIcon {
	/**
	 * @var int System gift ID number
	 */
	private $id;

	/**
	 * @var string Image size (s, m, ml or l)
	 */
	private $size;

	public function __construct( $id, $size ) {
		$this->id = $id;
		$this->size = $size;
	}

	/**
	 * @return string
	 */
	public function __toString() {
		return $this->getIconHTML();
	}

	/**
	 * Gets the associated image URL for a system gift.
	 *
	 * @return string Gift image filename (following the format
	 * sg_ID_SIZE.ext; for example, sg_1_l.jpg)
	 */
	private function getIconURL() {
		$backend = new SocialProfileFileBackend( 'awards' );
		$extensions = [ 'png', 'gif', 'jpg', 'jpeg' ];

		$img = 'default_' . $this->size . '.gif';

		foreach ( $extensions as $ext ) {
			if ( $backend->fileExists( 'sg_', $this->id, $this->size, $ext ) ) {
				$img = $backend->getFileHttpUrl( 'sg_', $this->id, $this->size, $ext );

				// We only really care about one being found, so exit once it finds one
				break;
			}
		}

		return $img . '?r=' . rand();
	}

	/**
	 * Gets the HTML containing the system gift icon
	 *
	 * @return string HTML
	 */
	public function getIconHTML() {
		$params = [
			'src' => $this->getIconURL(),
			'border' => '0',
			'alt' => wfMessage( 'ga-gift' )->plain()
		];

		return Html::element( 'img', $params, '' );
	}
}
