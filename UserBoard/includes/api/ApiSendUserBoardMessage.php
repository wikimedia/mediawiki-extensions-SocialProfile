<?php

use MediaWiki\MediaWikiServices;

class ApiSendUserBoardMessage extends ApiBase {
	public function execute() {
		$main = $this->getMain();

		$user_name = $main->getVal( 'username' );
		$message = $main->getVal( 'message' );
		$message_type = $main->getVal( 'type' ) || 0;

		$user = $this->getUser();
		$readOnlyMode = MediaWikiServices::getInstance()->getReadOnlyMode();

		// Don't allow blocked users to send messages and also don't allow message
		// sending when the database is locked for some reason
		if ( $user->isBlocked() || $readOnlyMode->isReadOnly() ) {
			$this->getResult()->addValue( null, 'result', 'You cannot send messages.' );
			return true;
		}

		$user_name = stripslashes( $user_name );
		$user_name = urldecode( $user_name );
		$recipient = User::newFromName( $user_name );
		$b = new UserBoard( $user );

		$m = $b->sendBoardMessage(
			$user,
			$recipient,
			urldecode( $message ),
			$message_type
		);

		$this->getResult()->addValue( null, 'result', $b->displayMessages( $recipient, 0, 1 ) );

		return true;
	}

	public function needsToken() {
		return 'csrf';
	}

	public function isWriteMode() {
		return true;
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), [
			'username' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'message' => [
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			],
			'type' => [
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			]
		] );
	}
}
