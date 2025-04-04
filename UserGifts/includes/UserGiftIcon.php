<?php

use MediaWiki\Html\Html;

/**
 * Object for constructing user gift icons.
 */
class UserGiftIcon {
	/**
	 * @var int User gift ID number
	 */
	private $id;

	/**
	 * @var string Image size (s,m, ml, or l)
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
	 * Get the URL for the icon of the user gift.
	 *
	 * @return string Gift image filename (following the format
	 * ID_SIZE.ext; for example, 1_l.jpg)
	 */
	public function getIconURL() {
		$backend = new SocialProfileFileBackend( 'awards' );
		$extensions = [ 'png', 'gif', 'jpg', 'jpeg' ];

		$img = 'default_' . $this->size . '.gif';

		foreach ( $extensions as $ext ) {
			if ( $backend->fileExists( '', $this->id, $this->size, $ext ) ) {
				$img = $backend->getFileHttpUrl( '', $this->id, $this->size, $ext );

				// We only really care about one being found, so exit once it finds one
				break;
			}
		}

		return $img . '?r=' . rand();
	}

	/**
	 * Get the HTML containing the user gift icon
	 *
	 * @param array $extraParams Allows for passing extra HTML attributes
	 * @return string HTML
	 */
	public function getIconHTML( $extraParams = [] ) {
		$defaultParams = [
			'src' => $this->getIconURL(),
			'border' => 0,
			'alt' => wfMessage( 'g-gift' )->plain()
		];

		$params = array_merge( $extraParams, $defaultParams );

		return Html::element( 'img', $params, '' );
	}
}
