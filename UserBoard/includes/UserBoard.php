<?php

use MediaWiki\MediaWikiServices;

/**
 * Functions for managing user board data
 */
class UserBoard {
	// Constants for the ub_type field.
	/**
	 * @var int Public message, which is the default
	 */
	const MESSAGE_PUBLIC = 0;

	/**
	 * @var int Private message readable only by the intended user
	 */
	const MESSAGE_PRIVATE = 1;

	/**
	 * @var User the current context user
	 */
	private $currentUser;

	/**
	 * @param User|null $user
	 */
	public function __construct( $user = null ) {
		// No context to use
		$this->currentUser = $user ?? RequestContext::getMain()->getUser();
	}

	/**
	 * Sends a user board message to another user.
	 *
	 * Performs the insertion to user_board table, sends e-mail notification
	 * (if appliable), and increases social statistics as appropriate.
	 *
	 * @param User $sender User (object) sending the message
	 * @param User $recipient User (object) receiving the message
	 * @param string $message Message text
	 * @param int $message_type 0 for public message
	 * @return int The inserted value of ub_id row
	 */
	public function sendBoardMessage( $sender, $recipient, $message, $message_type = 0 ) {
		$dbw = wfGetDB( DB_MASTER );

		$dbw->insert(
			'user_board',
			[
				'ub_actor_from' => $sender->getActorId(),
				'ub_actor' => $recipient->getActorId(),
				'ub_message' => $message,
				'ub_type' => $message_type,
				'ub_date' => $dbw->timestamp( date( 'Y-m-d H:i:s' ) ),
			],
			__METHOD__
		);

		// Send e-mail notification (if user is not writing on own board)
		if ( $sender->getActorId() != $recipient->getActorId() ) {
			$this->sendBoardNotificationEmail( $recipient, $sender );

			$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
			$messageCount = new UserBoardMessageCount( $cache, $recipient );
			$messageCount->clear();
		}

		$stats = new UserStatsTrack( $recipient->getId(), $recipient->getName() );
		if ( $message_type == 0 ) {
			// public message count
			$stats->incStatField( 'user_board_count' );
		} else {
			// private message count
			$stats->incStatField( 'user_board_count_priv' );
		}

		$stats = new UserStatsTrack( $sender->getId(), $sender->getName() );
		$stats->incStatField( 'user_board_sent' );

		if ( ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ) {
			EchoEvent::create( [
				'type' => 'social-msg-send',
				'agent' => $sender,
				'extra' => [
					'target' => $recipient->getId(),
					'from' => $sender->getId(),
					'type' => $message_type,
					'message' => $message
				]
			] );
		}

		return $dbw->insertId();
	}

	/**
	 * Sends an email to a user if someone wrote on their board
	 *
	 * @param User $recipient User (object) receiving the message
	 * @param User $sender User (object) who wrote the board message
	 */
	private function sendBoardNotificationEmail( $recipient, $sender ) {
		$recipient->load();

		// Send email if user's email is confirmed and s/he's opted in to recieving social notifications
		$userOptionsLookup = MediaWikiServices::getInstance()->getUserOptionsLookup();
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ?
			$userOptionsLookup->getBoolOption( $recipient, 'echo-subscriptions-email-social-rel' ) :
			$userOptionsLookup->getIntOption( $recipient, 'notifymessage', 1 );
		if ( $recipient->isEmailConfirmed() && $wantsEmail ) {
			$board_link = SpecialPage::getTitleFor( 'UserBoard' );
			$update_profile_link = SpecialPage::getTitleFor( 'UpdateProfile' );
			$subject = wfMessage( 'message_received_subject', $sender->getName() )->parse();
			$body = [
				'html' => wfMessage( 'message_received_body_html',
					$recipient->getName(),
					$sender->getName()
				)->parse(),
				'text' => wfMessage( 'message_received_body',
					$recipient->getName(),
					$sender->getName(),
					htmlspecialchars( $board_link->getFullURL() ),
					htmlspecialchars( $update_profile_link->getFullURL() )
				)->text()
			];

			$recipient->sendMail( $subject, $body );
		}
	}

	/**
	 * Checks if the given user owns the board message with the ID number $ub_id.
	 * This means, "was the given message sent _to_ $user?".
	 * If you want to know "was the given message sent _by_ $user?", use
	 * isUserAuthor() instead.
	 *
	 * @param User $user User object
	 * @param int $ub_id User board message ID number
	 * @return bool True if user owns the message, otherwise false
	 */
	public function doesUserOwnMessage( $user, $ub_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_board',
			[ 'ub_actor' ],
			[ 'ub_id' => $ub_id ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $user->getActorId() == $s->ub_actor ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Checks if the given user wrote the board message with the ID number $ub_id.
	 * If you want to know "was the given message sent _to_ $user?", use
	 * doesUserOwnMessage() instead.
	 *
	 * @param User $user User object
	 * @param int $ub_id User board message ID number
	 * @return bool True if user owns the message, otherwise false
	 */
	public function isUserAuthor( $user, $ub_id ) {
		$dbr = wfGetDB( DB_REPLICA );
		$s = $dbr->selectRow(
			'user_board',
			[ 'ub_actor_from' ],
			[ 'ub_id' => $ub_id ],
			__METHOD__
		);
		if ( $s !== false ) {
			if ( $user->getActorId() == $s->ub_actor_from ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Deletes a user board message from the database and decreases social
	 * statistics as appropriate (either 'user_board_count' or
	 * 'user_board_count_priv' is decreased by one).
	 *
	 * @param int $ub_id ID Number of the board message that we want to delete
	 */
	public function deleteMessage( $ub_id ) {
		if ( $ub_id ) {
			$dbw = wfGetDB( DB_MASTER );
			$s = $dbw->selectRow(
				'user_board',
				[ 'ub_actor', 'ub_type' ],
				[ 'ub_id' => $ub_id ],
				__METHOD__
			);
			if ( $s !== false ) {
				$dbw->delete(
					'user_board',
					[ 'ub_id' => $ub_id ],
					__METHOD__
				);

				$user = User::newFromActorId( $s->ub_actor );
				$stats = new UserStatsTrack( $user->getId(), $user->getName() );
				if ( $s->ub_type == 0 ) {
					$stats->decStatField( 'user_board_count' );
				} else {
					$stats->decStatField( 'user_board_count_priv' );
				}
			}
		}
	}

	/**
	 * Get an individual board message from the database when we have its ID.
	 *
	 * @param int $messageId Board message ID (user_board.ub_id)
	 * @return array Array containing info about the message on success, empty array on failure
	 */
	public function getMessage( $messageId ) {
		global $wgOut, $wgTitle;

		$dbr = wfGetDB( DB_REPLICA );
		$res = $dbr->select(
			'user_board',
			'*',
			[ 'ub_id' => $messageId ],
			__METHOD__,
			[ 'LIMIT' => 1 ]
		);

		$message = [];

		foreach ( $res as $row ) {
			$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
			$message_text = $parser->parse( $row->ub_message, $wgTitle, $wgOut->parserOptions(), true );
			$message_text = $message_text->getText();

			$message = [
				'id' => $row->ub_id,
				'timestamp' => wfTimestamp( TS_UNIX, $row->ub_date ),
				'ub_actor_from' => $row->ub_actor_from,
				'ub_actor' => $row->ub_actor,
				'message_text' => $message_text,
				'type' => $row->ub_type
			];
		}

		return $message;
	}

	/**
	 * Get the user board messages for the given user or users (board-to-board view
	 * between two given users).
	 *
	 * @param User $user User object
	 * @param User $user_2 User object representing the second user; only used
	 * in board-to-board stuff
	 * @param int $limit Used to build the LIMIT and OFFSET for the SQL query
	 * @param int $page Used to build the LIMIT and OFFSET for the SQL query
	 * @return array Array of user board messages
	 */
	public function getUserBoardMessages( $user, $user_2 = 0, $limit = 0, $page = 0 ) {
		global $wgOut, $wgTitle;

		$dbr = wfGetDB( DB_REPLICA );

		$offset = 0;
		if ( $limit > 0 && $page ) {
			$offset = $page * $limit - ( $limit );
		}

		if ( $user_2 instanceof User ) {
			$user_sql = "( (ub_actor={$user->getActorId()} AND ub_actor_from={$user_2->getActorId()}) OR
					(ub_actor={$user_2->getActorId()} AND ub_actor_from={$user->getActorId()}) )";
			if ( !(
				$user->getActorId() == $this->currentUser->getActorId() ||
				$user_2->getActorId() == $this->currentUser->getActorId()
			) ) {
				$user_sql .= ' AND ub_type = 0 ';
			}
		} else {
			$user_sql = "ub_actor = {$user->getActorId()}";
			if ( $user->getActorId() != $this->currentUser->getActorId() ) {
				$user_sql .= ' AND ub_type = 0 ';
			}
			if ( $this->currentUser->isRegistered() ) {
				$user_sql .= " OR (ub_actor={$user->getActorId()} AND ub_actor_from={$this->currentUser->getActorId()}) ";
			}
		}

		$sql = "SELECT ub_id, ub_actor_from, ub_actor,
			ub_message, ub_date, ub_type
			FROM {$dbr->tableName( 'user_board' )}
			WHERE {$user_sql}
			ORDER BY ub_id DESC";
		$res = $dbr->query( $dbr->limitResult( $sql, $limit, $offset ), __METHOD__ );

		$messages = [];

		foreach ( $res as $row ) {
			$parser = MediaWikiServices::getInstance()->getParserFactory()->create();
			$message_text = $parser->parse( $row->ub_message, $wgTitle, $wgOut->parserOptions(), true );
			$message_text = $message_text->getText();

			$messages[] = [
				'id' => $row->ub_id,
				'timestamp' => wfTimestamp( TS_UNIX, $row->ub_date ),
				'ub_actor_from' => $row->ub_actor_from,
				'ub_actor' => $row->ub_actor,
				'message_text' => $message_text,
				'type' => $row->ub_type
			];
		}

		return $messages;
	}

	/**
	 * Get the amount of board-to-board messages sent between the given user objects.
	 *
	 * @param User $user The first user (object)
	 * @param User $user_2 The second user (object)
	 * @return int The amount of board-to-board messages
	 */
	public function getUserBoardToBoardCount( $user, $user_2 ) {
		$dbr = wfGetDB( DB_REPLICA );

		$user_sql = " ( (ub_actor={$user->getActorId()} AND ub_actor_from={$user_2->getActorId()}) OR
					(ub_actor={$user_2->getActorId()} AND ub_actor_from={$user->getActorId()}) )";

		if ( !(
			$user->getActorId() == $this->currentUser->getActorId() ||
			$user_2->getActorId() == $this->currentUser->getActorId()
		) ) {
			$user_sql .= ' AND ub_type = 0 ';
		}
		$sql = "SELECT COUNT(*) AS the_count
			FROM {$dbr->tableName( 'user_board' )}
			WHERE {$user_sql}";

		$res = $dbr->query( $sql, __METHOD__ );
		$row = $dbr->fetchObject( $res );

		$count = 0;
		if ( $row ) {
			$count = $row->the_count;
		}

		return $count;
	}

	/**
	 * Render an individual board message wrapped in the appropriate <div>s and whatnot.
	 *
	 * Callers should check for things like can the user even see this msg, etc.
	 *
	 * @param User $user Viewing User object
	 * @param array|int $message Database row containing info about the message,
	 *   like sender (ub_actor_from), recipient (ub_actor), etc. *or* a board message ID
	 * @return string HTML
	 */
	public function displayMessage( $user, $message ) {
		if ( !is_array( $message ) && is_int( $message ) ) {
			$message = $this->getMessage( $message );
		}

		$sender = User::newFromActorId( $message['ub_actor_from'] );
		$recipient = User::newFromActorId( $message['ub_actor'] );
		$avatar = new wAvatar( $sender->getId(), 'm' );

		$board_to_board = '';
		$board_link = '';
		$message_type_label = '';
		$delete_link = '';

		$userBoardPage = SpecialPage::getTitleFor( 'UserBoard' );

		if ( $this->currentUser->getActorId() != $message['ub_actor_from'] ) {
			$board_to_board = '<a href="' .
				htmlspecialchars(
					$userBoardPage->getFullURL( [
						'user' => $recipient->getName(),
						'conv' => $sender->getName()
					] )
				) . '">' . wfMessage( 'userboard_board-to-board' )->escaped() . '</a>';
			$board_link = '<a href="' .
				htmlspecialchars(
					$userBoardPage->getFullURL( [ 'user' => $sender->getName() ] )
				) . '">' . wfMessage( 'userboard_sendmessage', $sender->getName() )->parse() . '</a>';
		}

		if (
			$this->currentUser->getActorId() == $message['ub_actor'] ||
			$this->currentUser->isAllowed( 'userboard-delete' )
		) {
			$deleteURL = htmlspecialchars(
				$userBoardPage->getFullURL( [
					'action' => 'delete',
					'messageId' => $message['id']
				] ),
				ENT_QUOTES
			);
			$delete_link = "<span class=\"user-board-red\">
					<a href=\"{$deleteURL}\" data-message-id=\"{$message['id']}\">" .
						wfMessage( 'delete' )->escaped() . '</a>
				</span>';
		}
		if ( $message['type'] == 1 ) {
			$message_type_label = '(' . wfMessage( 'userboard_private' )->escaped() . ')';
		}

		$message_text = $message['message_text'];
		# $message_text = preg_replace_callback( "/(<a[^>]*>)(.*?)(<\/a>)/i", 'cut_link_text', $message['message_text'] );

		$templateParser = new TemplateParser( __DIR__ . '/templates' );
		$output = $templateParser->processTemplate(
			'board-message',
			[
				'userPageURL' => $sender->getUserPage()->getFullURL(),
				'senderName' => $sender->getName(),
				'messageTypeLabel' => $message_type_label,
				'postedAgo' => wfMessage( 'userboard_posted_ago', $this->getTimeAgo( $message['timestamp'] ) )->parse(),
				'avatarElement' => $avatar->getAvatarURL(),
				'messageBody' => $message_text,
				'boardLink' => $board_link,
				'boardToBoard' => $board_to_board,
				'deleteLink' => $delete_link
			]
		);

		return $output;
	}

	public function displayMessages( $user, $user_2 = 0, $count = 10, $page = 0 ) {
		global $wgTitle;

		$output = ''; // Prevent E_NOTICE
		$messages = $this->getUserBoardMessages( $user, $user_2, $count, $page );

		if ( $messages ) {
			foreach ( $messages as $message ) {
				$output .= $this->displayMessage( $user, $message );
			}
		} elseif ( $this->currentUser->getName() == $wgTitle->getText() ) {
			$output .= '<div class="no-info-container">' .
				wfMessage( 'userboard_nomessages' )->parse() .
			'</div>';
		}

		return $output;
	}

	/**
	 * Gets the difference between two given dates
	 *
	 * @param int $date1 Current time, as returned by PHP's time() function
	 * @param int $date2 Date
	 * @return array Difference between dates as an array containing 'w', 'd', 'h', 'm' and 's' keys
	 */
	public function dateDiff( $date1, $date2 ) {
		$dtDiff = $date1 - $date2;

		$totalDays = intval( $dtDiff / ( 24 * 60 * 60 ) );
		$totalSecs = $dtDiff - ( $totalDays * 24 * 60 * 60 );
		$dif = [];
		$dif['w'] = intval( $totalDays / 7 );
		$dif['d'] = $totalDays;
		$dif['h'] = $h = intval( $totalSecs / ( 60 * 60 ) );
		$dif['m'] = $m = intval( ( $totalSecs - ( $h * 60 * 60 ) ) / 60 );
		$dif['s'] = $totalSecs - ( $h * 60 * 60 ) - ( $m * 60 );

		return $dif;
	}

	public function getTimeOffset( $time, $timeabrv, $timename ) {
		$timeStr = '';
		if ( $time[$timeabrv] > 0 ) {
			$timeStr = wfMessage( "userboard-time-{$timename}", $time[$timeabrv] )->parse();
		}
		if ( $timeStr ) {
			$timeStr .= ' ';
		}
		return $timeStr;
	}

	/**
	 * Gets the time how long ago the given board message was posted
	 *
	 * @param int $time
	 * @return string Time, such as "20 days" or "11 hours"
	 */
	public function getTimeAgo( $time ) {
		$timeArray = $this->dateDiff( time(), $time );
		$timeStr = '';
		$timeStrD = $this->getTimeOffset( $timeArray, 'd', 'days' );
		$timeStrH = $this->getTimeOffset( $timeArray, 'h', 'hours' );
		$timeStrM = $this->getTimeOffset( $timeArray, 'm', 'minutes' );
		$timeStrS = $this->getTimeOffset( $timeArray, 's', 'seconds' );
		$timeStr = $timeStrD;
		if ( $timeStr < 2 ) {
			$timeStr .= $timeStrH;
			$timeStr .= $timeStrM;
			if ( !$timeStr ) {
				$timeStr .= $timeStrS;
			}
		}
		if ( !$timeStr ) {
			$timeStr = wfMessage( 'userboard-time-seconds', 1 )->parse();
		}
		return $timeStr;
	}
}
