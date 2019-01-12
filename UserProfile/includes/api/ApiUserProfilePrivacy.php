<?php
/**
 * API module for setting the visibility ("privacy") of a profile field
 *
 * @file
 * @ingroup Extensions
 * @author Vedmaka <god.vedmaka@gmail.com>
 * @license GPL-2.0-or-later
 */

class ApiUserProfilePrivacy extends ApiBase {

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
			$this->dieWithError( new RawMessage( 'No data provided' ), 'field_key' );
		}

		if ( !$tuid ) {
			$tuid = $this->getUser()->getId();
		}
		$data = [];

		switch ( $method ) {
			case 'get':
				$data['privacy'] = SPUserSecurity::getPrivacy( $tuid, $fieldKey );
				break;

			case 'set':
				if ( !$privacy || !in_array( $privacy, [ 'public', 'hidden', 'friends', 'foaf' ] ) ) {
					$this->dieWithError(
						new RawMessage( 'The supplied argument for the "privacy" parameter is invalid (no such parameter/missing parameter)' ),
						'privacy'
					);
				}

				SPUserSecurity::setPrivacy( $tuid, $fieldKey, $privacy );

				$data['replace'] = SPUserSecurity::renderEye( $fieldKey, $tuid );

				break;
		}

		// Output
		$result = $this->getResult();

		$result->addValue( null, $this->getModuleName(), $data );
	}

	public function needsToken() {
		return 'csrf';
	}

	public function isWriteMode() {
		return true;
	}

	/**
	 * @return array
	 */
	protected function getAllowedParams() {
		return [
			'method' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'field_key' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'privacy' => [
				ApiBase::PARAM_TYPE => 'string',
			],
			'tuid' => [
				ApiBase::PARAM_TYPE => 'integer',
			]
		];
	}
}
