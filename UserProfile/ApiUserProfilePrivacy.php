<?php
/**
 * API module for setting the visibility ("privacy") of a profile field
 *
 * @file
 * @ingroup Extensions
 * @author Vedmaka <god.vedmaka@gmail.com>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 */

class ApiUserProfilePrivacy extends ApiBase {

	/**
	 * Constructor
	 */
	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * Main entry point
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		$method = $params['method'];
		$fieldKey = $params['field_key'];
		$privacy = $params['privacy'];
		$tuid = $params['tuid'];

		// Search content: for example let's search
		if ( strlen( $fieldKey ) == 0 ) {
			$this->dieUsage( 'No data provided', 'field_key' );
		}

		if ( !$tuid ) {
			$tuid = $this->getUser()->getId();
		}
		$data = array();

		switch ( $method ) {
			case 'get':
				$data['privacy'] = SPUserSecurity::getPrivacy( $tuid, $fieldKey );
				break;

			case 'set':
				if ( !$privacy || !in_array( $privacy, array( 'public', 'hidden', 'friends', 'foaf' ) ) ) {
					$this->dieUsage( 'The supplied argument for the "privacy" parameter is invalid (no such parameter/missing parameter)', 'privacy' );
				}

				SPUserSecurity::setPrivacy( $tuid, $fieldKey, $privacy );

				$data['replace'] = SPUserSecurity::renderEye( $fieldKey, $tuid );

				break;
		}

		// Output
		$result = $this->getResult();

		$result->addValue( null, $this->getModuleName(), $data );
	}

	/**
	 * @return array
	 */
	protected function getAllowedParams() {
		return array(
			'method' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'field_key' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'privacy' => array(
				ApiBase::PARAM_TYPE => 'string',
			),
			'tuid' => array(
				ApiBase::PARAM_TYPE => 'integer',
			)
		);
	}
}