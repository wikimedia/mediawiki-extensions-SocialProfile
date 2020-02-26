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

	public function __construct() {
		// No context to use
		$this->currentUser = RequestContext::getMain()->getUser();
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
				'ub_date' => date( 'Y-m-d H:i:s' ),
			],
			__METHOD__
		);

		// Send e-mail notification (if user is not writing on own board)
		if ( $sender->getActorId() != $recipient->getActorId() ) {
			$this->sendBoardNotificationEmail( $recipient, $sender );

			global $wgMemc;

			$messageCount = new UserBoardMessageCount( $wgMemc, $recipient );
			$messageCount->increase();
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
	 * @param User $sender User (object) who wrote the board message
	 * @param User $recipient User (object) receiving the message
	 */
	private function sendBoardNotificationEmail( $sender, $recipient ) {
		$recipient->load();

		// Send email if user's email is confirmed and s/he's opted in to recieving social notifications
		$wantsEmail = ExtensionRegistry::getInstance()->isLoaded( 'Echo' ) ? $recipient->getBoolOption( 'echo-subscriptions-email-social-rel' ) : $recipient->getIntOption( 'notifymessage', 1 );
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
	 * Checks if the user with ID number $user_id owns the board message with
	 * the ID number $ub_id.
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
	 * Get the user board messages for the user with the ID $user_id.
	 *
	 * @todo FIXME: Rewrite this function to be compatible with non-MySQL DBMS
	 *
	 * @param User $user User object
	 * @param User $user_2 User object representing the second user; only used
	 * in board-to-board stuff
	 * @param int $limit Used to build the LIMIT and OFFSET for the SQL
	 * query
	 * @param int $page Used to build the LIMIT and OFFSET for the SQL
	 * query
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
			if ( $user->getActorId() != $this->currentUser->getId() ) {
				$user_sql .= ' AND ub_type = 0 ';
			}
			if ( $this->currentUser->isLoggedIn() ) {
				$user_sql .= " OR (ub_actor={$user->getActorId()} OR ub_actor_from={$this->currentUser->getActorId()}) ";
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
	 * @todo FIXME: Rewrite this function to be compatible with non-MySQL DBMS
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

	public function displayMessages( $user, $user_2 = 0, $count = 10, $page = 0 ) {
		global $wgTitle;

		$output = ''; // Prevent E_NOTICE
		$messages = $this->getUserBoardMessages( $user, $user_2, $count, $page );

		if ( $messages ) {
			foreach ( $messages as $message ) {
				$sender = User::newFromActorId( $message['ub_actor_from'] );
				$recipient = User::newFromActorId( $message['ub_actor'] );
				$avatar = new wAvatar( $sender->getId(), 'm' );

				$board_to_board = '';
				$board_link = '';
				$message_type_label = '';
				$delete_link = '';

				if ( $this->currentUser->getActorId() != $message['ub_actor_from'] ) {
					$board_to_board = '<a href="' .
						htmlspecialchars(
							SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [
								'user' => $recipient->getName(),
								'conv' => $sender->getName()
							] )
						)
						. '">' .
						wfMessage( 'userboard_board-to-board' )->plain() . '</a>';
					$board_link = '<a href="' .
						htmlspecialchars(
							SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [ 'user' => $sender->getName() ] )
						) . '">' .
						wfMessage( 'userboard_sendmessage', $sender->getName() )->parse() . '</a>';
				}
				if ( $this->currentUser->getActorId() == $message['ub_actor'] ||
					$this->currentUser->isAllowed( 'userboard-delete' )
				) {
					$delete_link = "<span class=\"user-board-red\">
							<a href=\"javascript:void(0);\" data-message-id=\"{$message['id']}\">" .
								wfMessage( 'delete' )->plain() . '</a>
						</span>';
				}
				if ( $message['type'] == 1 ) {
					$message_type_label = '(' . wfMessage( 'userboard_private' )->plain() . ')';
				}

				$message_text = $message['message_text'];
				# $message_text = preg_replace_callback( "/(<a[^>]*>)(.*?)(<\/a>)/i", 'cut_link_text', $message['message_text'] );

				$senderUserPage = htmlspecialchars( $sender->getUserPage()->getFullURL() );
				$senderTitle = htmlspecialchars( $sender->getName() );
				$output .= "<div class=\"user-board-message\">
					<div class=\"user-board-message-from\">
					<a href=\"{$senderUserPage}\" title=\"{$senderTitle}\">{$senderTitle}</a> {$message_type_label}
					</div>
					<div class=\"user-board-message-time\">" .
						wfMessage( 'userboard_posted_ago', $this->getTimeAgo( $message['timestamp'] ) )->parse() .
					"</div>
					<div class=\"user-board-message-content\">
						<div class=\"user-board-message-image\">
							<a href=\"{$senderUserPage}\" title=\"{$senderTitle}\">{$avatar->getAvatarURL()}</a>
						</div>
						<div class=\"user-board-message-body\">
							{$message_text}
						</div>
						<div class=\"visualClear\"></div>
					</div>
					<div class=\"user-board-message-links\">
						{$board_link}
						{$board_to_board}
						{$delete_link}
					</div>
				</div>";
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
