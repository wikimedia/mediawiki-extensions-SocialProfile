<?php

class ApiSendUserBoardMessage extends ApiBase {
	public function execute() {
		$main = $this->getMain();

		$user_name = $main->getVal( 'username' );
		$message = $main->getVal( 'message' );
		$message_type = $main->getVal( 'type' ) || 0;

		$user = $this->getUser();

		// Don't allow blocked users to send messages and also don't allow message
		// sending when the database is locked for some reason
		if ( $user->isBlocked() || wfReadOnly() ) {
			$this->getResult()->addValue( null, 'result', 'You cannot send messages.' );
			return true;
		}

		$user_name = stripslashes( $user_name );
		$user_name = urldecode( $user_name );
		$user_id_to = User::idFromName( $user_name );
		$b = new UserBoard();

		$m = $b->sendBoardMessage(
			$user->getId(),
			$user->getName(),
			$user_id_to,
			$user_name,
			urldecode( $message ),
			$message_type
		);

		$this->getResult()->addValue( null, 'result', $b->displayMessages( $user_id_to, 0, 1 ) );

		return true;
	}

	public function getDescription() {
		return 'Send a message to a user\'s UserBoard.';
	}

	public function getAllowedParams() {
		return array_merge( parent::getAllowedParams(), array(
			'username' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'message' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'type' => array(
				ApiBase::PARAM_TYPE => 'integer',
				ApiBase::PARAM_REQUIRED => false
			)
		) );
	}

	public function getParamDescription() {
		return array_merge( parent::getParamDescription(), array(
			'username' => 'The recipient\'s user name.',
			'message' => 'urlencoded version of the message to send.',
			'type' => 'Message type; 0 for a public message, 1 for a private message.'
		) );
	}

	public function getExamplesMessages() {
		return array();
	}
}