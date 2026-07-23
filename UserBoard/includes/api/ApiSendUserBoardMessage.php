<?php

use MediaWiki\MediaWikiServices;

class ApiSendUserBoardMessage extends ApiBase {
	public function execute() {
		$main = $this->getMain();

		$user_name = $main->getVal( 'username' );
		$message = $main->getVal( 'message' );
		$message_type = (int)$main->getVal( 'type', (string)UserBoard::MESSAGE_PUBLIC );

		$user = $this->getUser();
		$readOnlyMode = MediaWikiServices::getInstance()->getReadOnlyMode();

		// Don't allow blocked users to send messages and also don't allow message
		// sending when the database is locked for some reason
		if ( $user->getBlock() || $readOnlyMode->isReadOnly() ) {
			$this->dieWithError( 'apierror-socialprofile-send-message-nosend', 'nosend' );
		}

		$recipient = User::newFromName( $user_name );
		$b = new UserBoard( $user );

		$messageText = urldecode( $message );
		$spamStatus = UserBoard::checkForSpam( $messageText, $user, $recipient );
		if ( !$spamStatus->isOK() ) {
			$this->dieWithError( $spamStatus->getMessages()[0], 'spam' );
		}

		if ( $message_type !== UserBoard::MESSAGE_PUBLIC &&
			!$this->getConfig()->get( 'UserBoardAllowPrivateMessages' ) ) {
			$this->dieWithError( 'userboard-private-messages-disabled' );
		}

		$b->sendBoardMessage(
			$user,
			$recipient,
			$messageText,
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
