<?php
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
	 * @return array
	 */
	public function getPathnames() {
		global $wgUploadDirectory;

		return glob(
			$wgUploadDirectory . '/awards/' .
			$this->id . '_' .
			$this->size . "*"
		);
	}

	/**
	 * Get the URL for the icon of the user gift.
	 *
	 * @return string Gift image filename (following the format
	 * ID_SIZE.ext; for example, 1_l.jpg)
	 */
	public function getIconURL() {
		$files = $this->getPathnames();

		if ( !empty( $files[0] ) ) {
			$img = basename( $files[0] );
		} else {
			$img = 'default_' . $this->size . '.gif';
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
		global $wgUploadBaseUrl, $wgUploadPath;

		$uploadPath = $wgUploadBaseUrl ? $wgUploadBaseUrl . $wgUploadPath : $wgUploadPath;

		$defaultParams = [
			'src' => "{$uploadPath}/awards/{$this->getIconURL()}",
			'border' => 0,
			'alt' => wfMessage( 'g-gift' )->plain()
		];

		$params = array_merge( $extraParams, $defaultParams );

		return Html::element( 'img', $params, '' );
	}
}
