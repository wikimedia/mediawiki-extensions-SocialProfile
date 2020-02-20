<?php
/**
 * API module for setting the type of user profile, i.e. should a social profile
 * page or the wikitext page be shown by default when [[User:Foo]] is accessed
 *
 * @file
 * @ingroup API
 * @license GPL-2.0-or-later
 */

class ApiUserProfileType extends ApiBase {

	public function __construct( $query, $moduleName ) {
		parent::__construct( $query, $moduleName );
	}

	/**
	 * Main entry point
	 */
	public function execute() {
		$params = $this->extractRequestParams();
		// up_type = 1 == social profile
		// up_type = 0 == regular wikitext user page

		// Only allow changing your own user page type for the time being...
		$user = $this->getUser();
		if ( $user->isAnon() ) {
			// Boo, go away!
			$this->dieWithError( 'apierror-mustbeloggedin-generic', 'login-required' );
		}

		// ...unless we're just getting info as to what type of page someone is using
		// (Using the API is still cleaner than manually parsing HTML, obviously.)
		if ( isset( $params['do'] ) && $params['do'] === 'get' && isset( $params['user'] ) && $params['user'] ) {
			$user = User::newFromName( $params['user'] );
			if ( !$user || !$user instanceof User ) {
				$this->dieWithError(
					[ 'nosuchusershort', wfEscapeWikiText( $params['user'] ) ],
					'baduser'
				);
			}

			$profile = new UserProfile( $user );
			$profile_data = $profile->getProfile();

			$data = [
				'type' => (int)$profile_data['user_page_type']
			];

			$result = $this->getResult();
			$result->addValue( null, $this->getModuleName(), $data );

			return;
		}

		// Ensure that nobody is trying to set another user's user page type, this API module
		// can only be used to set your own user page type
		if ( isset( $params['do'] ) && $params['do'] === 'set' && isset( $params['user'] ) && $params['user'] ) {
			$user = User::newFromName( $params['user'] );
			if ( !$user || !$user instanceof User ) {
				$this->dieWithError(
					[ 'nosuchusershort', wfEscapeWikiText( $params['user'] ) ],
					'baduser'
				);
			}

			if ( $user->getName() !== $this->getUser()->getName() ) {
				$this->dieWithError(
					new RawMessage( "Can't set another user's user page type" ),
					'actionisnotpossible'
				);
			}
		}

		// @todo FIXME: M A S S I V E L Y duplicates SpecialToggleUserPageType.php!
		$dbw = wfGetDB( DB_MASTER );
		$s = $dbw->selectRow(
			'user_profile',
			[ 'up_actor' ],
			[ 'up_actor' => $user->getActorId() ],
			__METHOD__
		);
		if ( $s === false ) {
			$dbw->insert(
				'user_profile',
				[ 'up_actor' => $user->getActorId() ],
				__METHOD__
			);
		}

		$profile = new UserProfile( $user );
		$profile_data = $profile->getProfile();

		// If type is currently 1 (social profile), the user will want to change it to
		// 0 (wikitext page), and vice-versa
		$user_page_type = ( ( $profile_data['user_page_type'] == 1 ) ? 0 : 1 );

		$dbw->update(
			'user_profile',
			/* SET */[
				'up_type' => $user_page_type
			],
			/* WHERE */[
				'up_actor' => $user->getActorId()
			],
			__METHOD__
		);

		UserProfile::clearCache( $user );

		if ( $user_page_type == 1 && !$user->isBlocked() ) {
			SpecialToggleUserPage::importUserWiki( $user );
		}
		// End massive code duplication

		$result = $this->getResult();
		$data = [
			'type' => $user_page_type
		];
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
			'do' => [
				ApiBase::PARAM_TYPE => [
					'get',
					'set'
				],
				ApiBase::PARAM_REQUIRED => true
			],
			'user' => [
				ApiBase::PARAM_TYPE => 'user',
			]
		];
	}

}
