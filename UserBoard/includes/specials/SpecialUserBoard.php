<?php

use MediaWiki\MediaWikiServices;

/**
 * Display User Board messages for a user
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class SpecialViewUserBoard extends SpecialPage {

	public function __construct() {
		parent::__construct( 'UserBoard' );
	}

	/**
	 * Group this special page under the correct header in Special:SpecialPages.
	 *
	 * @return string
	 */
	function getGroupName() {
		return 'users';
	}

	/**
	 * Show this special page on Special:SpecialPages only for registered users
	 *
	 * @return bool
	 */
	function isListed() {
		return (bool)$this->getUser()->isRegistered();
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par Name of the user whose board we want to view
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$currentUser = $this->getUser();

		$linkRenderer = $this->getLinkRenderer();

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.userboard.css'
		] );
		$out->addModules( 'ext.socialprofile.userboard.js' );

		$ub_messages_show = 25;
		$user_name = $userFromURL = $request->getVal( 'user', $par );
		$user_name_2 = $request->getVal( 'conv' );
		$user_2 = null; // Prevent E_NOTICE
		$page = $request->getInt( 'page', 1 );
		$output = '';

		/**
		 * Redirect Non-logged in users to Login Page
		 * It will automatically return them to the UserBoard page
		 */
		if ( $currentUser->getId() == 0 && $user_name == '' ) {
			$login = SpecialPage::getTitleFor( 'Userlogin' );
			$out->redirect( $login->getFullURL( 'returnto=Special:UserBoard' ) );
			return;
		}

		/**
		 * If no user is set in the URL, we assume it's the current user
		 */
		if ( !$user_name ) {
			$user_name = $currentUser->getName();
		}
		$user_id = User::idFromName( $user_name );
		$user = Title::makeTitle( NS_USER, $user_name );

		if ( $user_name_2 ) {
			$user_2 = User::newFromName( $user_name_2 );
		}

		/**
		 * Error message for username that does not exist (from URL)
		 */
		if ( $user_id == 0 ) {
			$out->showErrorPage( 'error', 'userboard_noexist' );
			return;
		}

		/**
		 * Config for the page
		 */
		$per_page = $ub_messages_show;

		$b = new UserBoard( $currentUser );

		// NoJS handling of board message actions (deletion & sending)
		$isDelete = ( $request->getVal( 'action' ) === 'delete' );
		$messageId = $request->getInt( 'messageId' );
		if ( $isDelete && !$request->wasPosted() && $messageId ) {
			$output .= $this->renderConfirmDeleteForm( $messageId );
			$out->addHTML( $output );
			return;
		}

		if ( $request->wasPosted() && !MediaWikiServices::getInstance()->getReadOnlyMode()->isReadOnly() ) {
			// Deletions
			if ( $isDelete ) {
				if (
					( $b->doesUserOwnMessage( $currentUser, $messageId ) || $currentUser->isAllowed( 'userboard-delete' ) ) &&
					$currentUser->matchEditToken( $request->getVal( 'wpDeleteToken' ) )
				) {
					$b->deleteMessage( $messageId );
					$output .= Html::successBox( $this->msg( 'userboard-delete-success' )->escaped() );
				} else {
					// CSRF attempt or something...display an informational message in that case
					$output .= Html::errorBox( $this->msg( 'sessionfailure' )->escaped() );
				}
			} elseif ( $request->getVal( 'action' ) === 'send' ) {
				// Sending a message
				if ( $currentUser->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
					$b->sendBoardMessage(
						$currentUser,
						User::newFromName( $request->getVal( 'user_name_to' ) ),
						urldecode( $request->getVal( 'message' ) ),
						$request->getInt( 'message_type' )
					);
				} else {
					// CSRF attempt or something...display an informational message in that case
					$output .= Html::errorBox( $this->msg( 'sessionfailure' )->escaped() );
				}
			}
		}

		$ub_messages = $b->getUserBoardMessages(
			// @todo FIXME: variabilize this construct since we're using it twice here
			( $userFromURL ? User::newFromName( $userFromURL ) : $currentUser ),
			$user_2,
			$ub_messages_show,
			$page
		);

		if ( !$user_2 ) {
			$stats = new UserStats( $user_id, $user_name );
			$stats_data = $stats->getUserStats();
			$total = $stats_data['user_board'];
			// If user is viewing their own board or is allowed to delete
			// others' board messages, show the total count of board messages
			// to them (public + private messages)
			if (
				$currentUser->getName() == $user_name ||
				$currentUser->isAllowed( 'userboard-delete' )
			) {
				$total = $total + $stats_data['user_board_priv'];
			}
		} else {
			$total = $b->getUserBoardToBoardCount( ( $userFromURL ? User::newFromName( $userFromURL ) : $currentUser ), $user_2 );
		}

		if ( !$user_2 ) {
			if ( !( $currentUser->getName() == $user_name ) ) {
				$out->setPageTitle( $this->msg( 'userboard_owner', $user_name )->parse() );
			} else {
				$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
				$messageCount = new UserBoardMessageCount( $cache, $currentUser );
				$messageCount->clear();
				$out->setPageTitle( $this->msg( 'userboard_yourboard' )->parse() );
			}
		} else {
			if ( $currentUser->getName() == $user_name ) {
				$out->setPageTitle( $this->msg( 'userboard_yourboardwith', $user_name_2 )->parse() );
			} else {
				$out->setPageTitle( $this->msg( 'userboard_otherboardwith', $user_name, $user_name_2 )->parse() );
			}
		}

		$output .= '<div class="user-board-top-links">';
		$output .= '<a href="' . htmlspecialchars( $user->getFullURL() ) . '">&lt; ' .
			$this->msg( 'userboard_backprofile', $user_name )->parse() . '</a>';
		$output .= '</div>';

		$board_to_board = ''; // Prevent E_NOTICE

		if ( $page == 1 ) {
			$start = 1;
		} else {
			$start = ( $page - 1 ) * $per_page + 1;
		}
		$end = $start + ( count( $ub_messages ) ) - 1;

		if ( $currentUser->getId() != 0 && $currentUser->getName() != $user_name ) {
			$board_to_board = '<a href="' .
				htmlspecialchars(
					SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [
						'user' => $currentUser->getName(),
						'conv' => $user_name
					] ),
					ENT_QUOTES
				)
				. '">' .
				htmlspecialchars( $this->msg( 'userboard_boardtoboard' )->plain() ) . '</a>';
		}

		if ( $total ) {
			$output .= '<div class="user-page-message-top">
			<span class="user-page-message-count">' .
				$this->msg( 'userboard_showingmessages', $total, $start, $end, $end - $start + 1 )->parse() .
			"</span> {$board_to_board}
			</div>";
		}

		/**
		 * Build next/prev navigation links
		 */
		$qs = [];
		if ( $user_2 ) {
			$qs['conv'] = $user_name_2;
		}
		$numofpages = $total / $per_page;

		if ( $numofpages > 1 ) {
			$output .= '<div class="page-nav">';
			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$this->getPageTitle(),
					$this->msg( 'last' )->text(),
					[],
					[
						'user' => $user_name,
						'page' => ( $page - 1 )
					] + $qs
				);
			}

			if ( ( $total % $per_page ) != 0 ) {
				$numofpages++;
			}
			if ( $numofpages >= 9 && $page < $total ) {
				$numofpages = 9 + $page;
				if ( $numofpages >= ( $total / $per_page ) ) {
					$numofpages = ( $total / $per_page ) + 1;
				}
			}

			for ( $i = 1; $i <= $numofpages; $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$this->getPageTitle(),
						$i,
						[],
						[
							'user' => $user_name,
							'page' => $i
						] + $qs
					) . $this->msg( 'word-separator' )->escaped();
				}
			}

			if ( ( $total - ( $per_page * $page ) ) > 0 ) {
				$output .= $this->msg( 'word-separator' )->escaped() .
					$linkRenderer->makeLink(
						$this->getPageTitle(),
						$this->msg( 'next' )->text(),
						[],
						[
							'user' => $user_name,
							'page' => ( $page + 1 )
						] + $qs
					);
			}
			$output .= '</div><p>';
		}

		$can_post = false;
		$user_name_from = ''; // Prevent E_NOTICE

		if ( !$user_2 ) {
			if ( $currentUser->getName() != $user_name ) {
				$can_post = true;
				$user_name_to = htmlspecialchars( $user_name, ENT_QUOTES );
			}
		} else {
			if ( $currentUser->getName() == $user_name ) {
				$can_post = true;
				$user_name_to = htmlspecialchars( $user_name_2, ENT_QUOTES );
				$user_name_from = htmlspecialchars( $user_name, ENT_QUOTES );
			}
		}

		if ( $currentUser->isBlocked() ) {
			// only let them post to admins
			// $user_to = User::newFromId( $user_id );
			// if( !$user_to->isAllowed( 'delete' ) ) {
				$can_post = false;
			// }
		}

		if ( $can_post ) {
			if ( $currentUser->isRegistered() && !$currentUser->isBlocked() ) {
				$urlParams = [ 'action' => 'send' ];
				if ( $request->getVal( 'user' ) ) {
					// Need this to ensure that no-JS users are shown the correct stuff
					// after sending a board message, i.e. if they're viewing another user's
					// board, show _that_ board and not /their own/ board
					$urlParams['user'] = $request->getVal( 'user' );
				}
				$url = htmlspecialchars( $this->getPageTitle()->getFullURL( $urlParams ), ENT_QUOTES );
				$output .= '<div class="user-page-message-form">
				<form id="board-post-form" action="' . $url . '" method="post">
					<input type="hidden" id="user_name_to" name="user_name_to" value="' . $user_name_to . '"/>
					<input type="hidden" id="user_name_from" name="user_name_from" value="' . $user_name_from . '"/>
					<span class="user-board-message-type">' . $this->msg( 'userboard_messagetype' )->escaped() . ' </span>
					<select id="message_type" name="message_type">
						<option value="0">' . $this->msg( 'userboard_public' )->escaped() . '</option>
						<option value="1">' . $this->msg( 'userboard_private' )->escaped() . '</option>
					</select>
					<p>
					<textarea name="message" id="message" cols="63" rows="4"></textarea>

					<div class="user-page-message-box-button">
						<input type="submit" value="' . $this->msg( 'userboard_sendbutton' )->escaped() . '" class="site-button" data-per-page="' . $per_page . '" />
					</div>' .
					Html::hidden( 'wpEditToken', $currentUser->getEditToken() ) .
				'</form>
				</div>';
			} else {
				$output .= '<div class="user-page-message-form">'
					. $this->msg( 'userboard_loggedout' )->parse() .
				'</div>';
			}
		}
		$output .= '<div id="user-page-board">';

		// @todo FIXME: This if-else loop *massively* duplicates
		// UserBoard::displayMessages(). We should refactor that and this into
		// one sane & sensible method. --ashley, 19 July 2017
		if ( $ub_messages ) {
			foreach ( $ub_messages as $ub_message ) {
				$sender = User::newFromActorId( $ub_message['ub_actor_from'] );
				$recipient = User::newFromActorId( $ub_message['ub_actor'] );
				$avatar = new wAvatar( $sender->getId(), 'm' );

				$board_to_board = '';
				$board_link = '';
				$ub_message_type_label = '';
				$delete_link = '';

				if ( $currentUser->getActorId() != $ub_message['ub_actor_from'] ) {
					// Prevent logged-out views from getting a board to board with 127.0.0.1
					// And also board to board with self
					if ( $currentUser->isRegistered() && $user_name != $sender->getName() ) {
						$board_to_board = '<a href="' .
							htmlspecialchars(
								SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [
									'user' => $user_name,
									'conv' => $sender->getName()
								] )
							) . '">' . $this->msg( 'userboard_boardtoboard' )->escaped() . '</a>';
					}
					$board_link = '<a href="' .
						htmlspecialchars(
							SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [ 'user' => $sender->getName() ] )
						) . '">' .
						$this->msg( 'userboard_sendmessage', $sender->getName() )->parse() . '</a>';
				} else {
					$board_link = '<a href="' .
						htmlspecialchars(
							SpecialPage::getTitleFor( 'UserBoard' )->getFullURL( [ 'user' => $sender->getName() ] )
						) . '">' . $this->msg( 'userboard_myboard' )->escaped() . '</a>';
				}

				// If the user owns this private message or they are allowed to
				// delete board messages, show the "delete" link to them
				if (
					$currentUser->getActorId() == $ub_message['ub_actor'] ||
					$currentUser->isAllowed( 'userboard-delete' )
				) {
					$deleteURLParams = [
						'action' => 'delete',
						'messageId' => $ub_message['id']
					];
					if ( $request->getVal( 'user' ) ) {
						// Need this to ensure that no-JS users are shown the correct stuff
						// after deleting a board message, i.e. if they're viewing another user's
						// board, show _that_ board and not /their own/ board
						$deleteURLParams['user'] = $request->getVal( 'user' );
					}
					$deleteURL = htmlspecialchars(
						$this->getPageTitle()->getFullURL( $deleteURLParams ),
						ENT_QUOTES
					);
					$delete_link = "<span class=\"user-board-red\">
						<a href=\"{$deleteURL}\" data-message-id=\"{$ub_message['id']}\">" .
							$this->msg( 'delete' )->escaped() . '</a>
					</span>';
				}

				// Mark private messages as such
				if ( $ub_message['type'] == 1 ) {
					$ub_message_type_label = '(' . $this->msg( 'userboard_private' )->escaped() . ')';
				}

				// had global function to cut link text if too long and no breaks
				// $ub_message_text = preg_replace_callback( "/(<a[^>]*>)(.*?)(<\/a>)/i", 'cut_link_text', $ub_message['message_text'] );
				$ub_message_text = $ub_message['message_text'];

				$templateParser = new TemplateParser( __DIR__ . '/../templates' );
				$output .= $templateParser->processTemplate(
					'board-message',
					[
						'userPageURL' => $sender->getUserPage()->getFullURL(),
						'senderName' => $sender->getName(),
						'messageTypeLabel' => $ub_message_type_label,
						'postedAgo' => $this->msg( 'userboard_posted_ago', $b->getTimeAgo( $ub_message['timestamp'] ) )->parse(),
						'avatarElement' => $avatar->getAvatarURL(),
						'messageBody' => $ub_message_text,
						'boardLink' => $board_link,
						'boardToBoard' => $board_to_board,
						'deleteLink' => $delete_link
					]
				);
			}
		} else {
			$output .= '<p>' . $this->msg( 'userboard_nomessages' )->parse() . '</p>';
		}

		$output .= '</div>';

		$out->addHTML( $output );
	}

	/**
	 * Render the "are you sure you REALLY want to delete this message?" <form>.
	 * Primarily used by no-JS users.
	 *
	 * @param int $messageId ID of the board message to be deleted
	 * @return string HTML
	 */
	private function renderConfirmDeleteForm( $messageId ) {
		$form = '';
		$user = $this->getUser();
		$b = new UserBoard( $user );

		if (
			!$b->doesUserOwnMessage( $user, $messageId ) ||
			!$b->isUserAuthor( $user, $messageId )
		) {
			if ( !$user->isAllowed( 'userboard-delete' ) ) {
				throw new PermissionsError( 'userboard-delete' );
			}
		}

		$form .= '<form method="post" name="delete-board-message" action="">';
		$form .= $this->msg( 'userboard_confirmdelete' )->escaped();
		$form .= '<br />';
		$form .= $b->displayMessage( $user, $messageId );
		$form .= Html::hidden( 'wpDeleteToken', $user->getEditToken() );
		$form .= Html::hidden( 'messageId', $messageId );
		$form .= Html::hidden( 'action', 'delete' );
		$form .= Html::submitButton( $this->msg( 'delete' )->text(), [ 'name' => 'wpSubmit', 'class' => 'site-button' ] );
		$form .= '</form>';

		return $form;
	}

}
