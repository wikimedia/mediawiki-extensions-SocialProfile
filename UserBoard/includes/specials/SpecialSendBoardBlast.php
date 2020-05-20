<?php
/**
 * A special page to allow users to send a mass board message by selecting from
 * a list of their friends and foes
 *
 * @file
 * @ingroup Extensions
 * @author David Pean <david.pean@gmail.com>
 * @copyright Copyright © 2007, Wikia Inc.
 * @license GPL-2.0-or-later
 */

class SpecialBoardBlast extends UnlistedSpecialPage {

	public function __construct() {
		parent::__construct( 'SendBoardBlast' );
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $params
	 */
	public function execute( $params ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// This feature is available only to logged-in users.
		$this->requireLogin();

		// Is the database locked?
		$this->checkReadOnly();

		// Blocked through Special:Block? No access for you!
		if ( $user->isBlocked() ) {
			throw new UserBlockedError( $user->getBlock() );
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.userboard.boardblast.css'
		] );
		$out->addModules( 'ext.socialprofile.userboard.boardblast.js' );

		$output = '';
		$errors = [];

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			// Ensure that we have something to send, and if not, make a note to nag the
			// user about that...
			if ( empty( $request->getVal( 'message' ) ) ) {
				$errors[] = 'boardblast-error-missing-message';
			}

			$ids = $request->getVal( 'ids' );
			if ( !empty( $ids ) ) {
				// If we were to blindly explode $ids without checking its emptiness, we'd
				// get an annoying technically-not-empty array ([ 0 ], in other words) that'd
				// cause at least some annoying issues later on. Let's only explode that
				// array if it's non-empty, obviously.
				$user_ids_to = explode( ',', $ids );
			} else {
				// @todo FIXME: This just smells ugly to me.
				// Ideally we'd use $request->getCheck() but the problem is that we only know
				// that the keys _begin_ with "user-", but the latter part is variable (literally
				// the recipient users' UIDs for each recipient), so...
				$user_ids_to = [];
				foreach ( $request->getValues() as $key => $val ) {
					if ( preg_match( '/user-/i', $key ) ) {
						$user_ids_to[] = str_replace( 'user-', '', $key );
					}
				}

				// Still nothing? Well that's an error that the user needs to fix, then!
				if ( empty( $user_ids_to ) ) {
					$errors[] = 'boardblast-error-missing-user';
				}
			}

			// If no errors popped up, everything should be fine and we can send the message!
			if ( empty( $errors ) ) {
				$out->setPageTitle( $this->msg( 'messagesenttitle' )->plain() );
				$b = new UserBoard( $user );

				$count = 0;

				foreach ( $user_ids_to as $user_id ) {
					$recipient = User::newFromId( $user_id );
					$recipient->loadFromId();
					$b->sendBoardMessage(
						$user,
						$recipient,
						$request->getVal( 'message' ),
						UserBoard::MESSAGE_PRIVATE
					);
					$count++;
				}

				$output .= $this->msg( 'messagesentsuccess' )->escaped();
			} else {
				$out->setPageTitle( $this->msg( 'boardblasttitle' )->plain() );

				// We can have more than one error message (at least in the case of no-JS users)
				$errorHTML = '';
				foreach ( $errors as $errorMsgKey ) {
					$errorHTML .= $this->msg( $errorMsgKey )->escaped();
					$errorHTML .= '<br />';
				}
				$output .= Html::errorBox( $errorHTML );

				$output .= $this->displayForm();
			}
		} else {
			$out->setPageTitle( $this->msg( 'boardblasttitle' )->plain() );
			$output .= $this->displayForm();
		}

		$out->addHTML( $output );
	}

	/**
	 * Displays the form for sending board blasts
	 *
	 * @return string HTML
	 */
	function displayForm() {
		$user = $this->getUser();

		$stats = new UserStats( $user->getId(), $user->getName() );
		$stats_data = $stats->getUserStats();
		$friendCount = $stats_data['friend_count'];
		$foeCount = $stats_data['foe_count'];

		$output = '<div class="board-blast-message-form">
				<h2>' . $this->msg( 'boardblaststep1' )->escaped() . '</h2>
				<form method="post" name="blast" action="">
					<input type="hidden" name="ids" id="ids" />
					<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $user->getEditToken(), ENT_QUOTES ) . '" />
					<div class="blast-message-text">'
						. $this->msg( 'boardblastprivatenote' )->escaped() .
					'</div>
					<textarea name="message" id="message" cols="63" rows="4"></textarea>
		</div>
		<div class="blast-nav">
				<h2>' . $this->msg( 'boardblaststep2' )->escaped() . '</h2>
				<div class="blast-nav-links">
					<a href="javascript:void(0);" class="blast-select-all-link">' .
						$this->msg( 'boardlinkselectall' )->escaped() . '</a> -
					<a href="javascript:void(0);" class="blast-unselect-all-link">' .
						$this->msg( 'boardlinkunselectall' )->escaped() . '</a> ';

		if ( $friendCount > 0 && $foeCount > 0 ) {
			$output .= '- <a href="javascript:void(0);" class="blast-select-friends-link">' .
				$this->msg( 'boardlinkselectfriends' )->escaped() . '</a> -';
			$output .= '<a href="javascript:void(0);" class="blast-unselect-friends-link">' .
				$this->msg( 'boardlinkunselectfriends' )->escaped() . '</a>';
		}

		if ( $foeCount > 0 && $friendCount > 0 ) {
			$output .= '- <a href="javascript:void(0);" class="blast-select-foes-link">' .
				$this->msg( 'boardlinkselectfoes' )->escaped() . '</a> -';
			$output .= '<a href="javascript:void(0);" class="blast-unselect-foes-link">' .
				$this->msg( 'boardlinkunselectfoes' )->escaped() . '</a>';
		}
		$output .= '</div>
		</div>';

		$listLookup = new RelationshipListLookup( $user );
		$relationships = $listLookup->getRelationshipList();

		$output .= '<div id="blast-friends-list" class="blast-friends-list">';

		$x = 1;
		$per_row = 3;
		if ( count( $relationships ) > 0 ) {
			foreach ( $relationships as $relationship ) {
				$friendActor = User::newFromActorId( $relationship['actor'] );
				if ( !$friendActor || !$friendActor instanceof User ) {
					continue;
				}
				if ( $relationship['type'] == 1 ) {
					$class = 'friend';
				} else {
					$class = 'foe';
				}
				$id = $friendActor->getId();
				$safeUserName = htmlspecialchars( $friendActor->getName() );
				$output .= '<input type="checkbox" name="user-' . $id . '" value="' . $safeUserName . '" />' .
					'<div class="blast-' . $class . "-unselected\" id=\"user-{$id}\">" .
					$safeUserName . '</div>';
				if ( $x == count( $relationships ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}
		} else {
			$output .= '<div>' . $this->msg( 'boardnofriends' )->escaped() . '</div>';
		}

		$output .= '</div>

			<div class="visualClear"></div>';

		$output .= '<div class="blast-message-box-button">
				<input type="submit" value="' . $this->msg( 'boardsendbutton' )->escaped() . '" class="site-button" />
			</form>
		</div>';

		return $output;
	}
}
