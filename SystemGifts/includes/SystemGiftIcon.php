<?php
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
	 * @return array
	 */
	public function getPathnames() {
		global $wgUploadDirectory;

		return glob(
			$wgUploadDirectory . '/awards/sg_' .
			$this->id . '_' .
			$this->size . '*'
		);
	}

	/**
	 * Gets the associated image URL for a system gift.
	 *
	 * @return string Gift image filename (following the format
	 * sg_ID_SIZE.ext; for example, sg_1_l.jpg)
	 */
	private function getIconURL() {
		$files = $this->getPathnames();

		if ( !empty( $files[0] ) ) {
			$img = basename( $files[0] );
		} else {
			$img = 'default_' . $this->size . '.gif';
		}

		return $img . '?r=' . rand();
	}

	/**
	 * Gets the HTML containing the system gift icon
	 *
	 * @return string HTML
	 */
	public function getIconHTML() {
		global $wgUploadBaseUrl, $wgUploadPath;

		$uploadPath = $wgUploadBaseUrl ? $wgUploadBaseUrl . $wgUploadPath : $wgUploadPath;

		$params = [
			'src' => "{$uploadPath}/awards/{$this->getIconURL()}",
			'border' => '0',
			'alt' => wfMessage( 'ga-gift' )->plain()
		];

		return Html::element( 'img', $params, '' );
	}
}
