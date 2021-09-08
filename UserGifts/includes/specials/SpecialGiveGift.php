<?php

use MediaWiki\MediaWikiServices;

/**
 * Special:GiveGift -- a special page for sending out user-to-user gifts
 *
 * @file
 * @ingroup Extensions
 */

class GiveGift extends SpecialPage {

	/**
	 * @var User The user (object) who we are giving a gift
	 */
	public $userTo;

	public function __construct() {
		parent::__construct( 'GiveGift' );
	}

	public function doesWrites() {
		return true;
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
	 * @param string|null $par Name of the user whom to give a gift
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$output = ''; // Prevent E_NOTICE

		// These checks must happen here, or else the "no target user" check below
		// results in $this->displayFormNoUser(); being called when the current user
		// is an anon...d'oh
		if ( $user->isAnon() ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-login' )->parse() );
			return;
		} elseif ( $user->isBlocked() ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-blocked' )->escaped() );
			return;
		}

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( [
			'ext.socialprofile.usergifts.css',
			'ext.socialprofile.special.givegift.css'
		] );
		$out->addModules( 'ext.socialprofile.usergifts.js' );

		$this->userTo = User::newFromName( $request->getVal( 'user', $par ) );
		if ( !$this->userTo ) {
			// No-JS fallback
			$this->userTo = User::newFromName( $request->getVal( 'friend-list' ) );
		}

		if ( !$this->userTo ) {
			$out->addHTML( $this->displayFormNoUser() );
			return;
		}

		$giftId = $request->getInt( 'gift_id' );
		if ( !$giftId ) {
			// NoJS fallback
			$potentialGiftIds = [];
			foreach ( $request->getValues() as $key => $val ) {
				if ( preg_match( '/nojs-gift-/i', $key ) ) {
					$potentialGiftIds[] = (int)str_replace( 'nojs-gift-', '', $key );
				}
				if ( !empty( $potentialGiftIds ) ) {
					// In NoJS mode, with JS disabled, there's no way to enforce the
					// "you can only send one gift at a time" limit before the form is
					// submitted; so while we may have e.g. 10 gifts and some no-JS
					// user may have selected all 10 checkboxes, we care only about the first one.
					$giftId = reset( $potentialGiftIds );
				}
			}
		}

		if ( $this->userTo->isAnon() ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-no-user' )->escaped() );
		} elseif ( $user->getActorId() === $this->userTo->getActorId() ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-to-yourself' )->escaped() );
		} else {
			$gift = new UserGifts( $user->getName() );

			if (
				$request->wasPosted() &&
				$user->matchEditToken( $request->getVal( 'wpEditToken' ) ) &&
				$_SESSION['alreadysubmitted'] == false
			) {
				$_SESSION['alreadysubmitted'] = true;

				$ug_gift_id = $gift->sendGift(
					$this->userTo,
					$giftId,
					0,
					$request->getVal( 'message' )
				);

				// clear the cache for the user profile gifts for this user
				$cache = MediaWikiServices::getInstance()->getMainWANObjectCache();
				$cache->delete( $cache->makeKey( 'user', 'profile', 'gifts', 'actor_id', $this->userTo->getActorId() ) );

				$key = $cache->makeKey( 'gifts', 'unique', 4 );
				$data = $cache->get( $key );

				// check to see if this type of gift is in the unique list
				$lastUniqueGifts = $data;
				$found = 1;

				if ( is_array( $lastUniqueGifts ) ) {
					foreach ( $lastUniqueGifts as $lastUniqueGift ) {
						if ( $giftId == $lastUniqueGift['gift_id'] ) {
							$found = 0;
							break;
						}
					}
				}

				if ( $found ) {
					// add new unique to array
					$lastUniqueGifts[] = [
						'id' => $ug_gift_id,
						'gift_id' => $giftId
					];

					// remove oldest value
					if ( count( $lastUniqueGifts ) > 4 ) {
						array_shift( $lastUniqueGifts );
					}

					// reset the cache
					$cache->set( $key, $lastUniqueGifts );
				}

				$sent_gift = UserGifts::getUserGift( $ug_gift_id );
				$userGiftIcon = new UserGiftIcon( $sent_gift['gift_id'], 'l' );
				$icon = $userGiftIcon->getIconHTML();

				$out->setPageTitle( $this->msg( 'g-sent-title', $this->userTo->getName() )->parse() );

				$output .= '<div class="back-links">
					<a href="' . htmlspecialchars( $this->userTo->getUserPage()->getFullURL() ) . '">' .
						$this->msg( 'g-back-link', $this->userTo->getName() )->parse() .
					'</a>
				</div>
				<div class="g-message">' .
					$this->msg( 'g-sent-message', $this->userTo->getName() )->parse() .
				'</div>
				<div class="g-container">' .
					$icon .
				'<div class="g-title">' . htmlspecialchars( $sent_gift['name'] ) . '</div>';
				if ( $sent_gift['message'] ) {
					$output .= '<div class="g-user-message">' .
						htmlspecialchars( $sent_gift['message'] ) .
					'</div>';
				}
				// NoJS TODO: these buttons should not require JS to work; might need to change
				// them into styled <a>'s or something?
				$output .= '</div>
				<div class="visualClear"></div>
				<div class="g-buttons">
					<input type="button" class="site-button" value="' . $this->msg( 'mainpage' )->escaped() . '" size="20" onclick="window.location=\'' . htmlspecialchars( Title::newMainPage()->getFullURL() ) . '\'" />
					<input type="button" class="site-button" value="' . $this->msg( 'g-your-profile' )->escaped() . '" size="20" onclick="window.location=\'' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '\'" />
				</div>';

				$out->addHTML( $output );
			} else {
				$_SESSION['alreadysubmitted'] = false;

				if ( $giftId ) {
					$out->addHTML( $this->displayFormSingle() );
				} else {
					$out->addHTML( $this->displayFormAll() );
				}
			}
		}
	}

	/**
	 * Return an array of subpages beginning with $search that this special page will accept.
	 *
	 * @param string $search Prefix to search for
	 * @param int $limit Maximum number of results to return (usually 10)
	 * @param int $offset Number of results to skip (usually 0)
	 * @return string[] Matching subpages
	 */
	public function prefixSearchSubpages( $search, $limit, $offset ) {
		$user = User::newFromName( $search );
		if ( !$user ) {
			// No prefix suggestion for invalid user
			return [];
		}
		// Autocomplete subpage as user list - public to allow caching
		return UserNamePrefixSearch::search( 'public', $search, $limit, $offset );
	}

	/**
	 * Display the form for sending out a single gift.
	 * Relies on the gift_id URL parameter and bails out if it's not there.
	 *
	 * @return string HTML
	 */
	function displayFormSingle() {
		$out = $this->getOutput();

		$giftId = $this->getRequest()->getInt( 'gift_id' );

		if ( !$giftId || !is_numeric( $giftId ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->escaped() );
			return false;
		}

		$gift = Gifts::getGift( $giftId );

		if ( empty( $gift ) ) {
			return false;
		}

		if ( $gift['access'] == 1 && $this->getUser()->getActorId() != $gift['creator_actor'] ) {
			return $this->displayFormAll();
		}

		$out->setPageTitle( $this->msg( 'g-give-to-user-title', $gift['gift_name'], $this->userTo->getName() )->parse() );

		$gift['gift_id'] = (int)$gift['gift_id'];
		$userGiftIcon = new UserGiftIcon( $gift['gift_id'], 'l' );
		$icon = $userGiftIcon->getIconHTML( [ 'id' => "gift_image_{$gift['gift_id']}" ] );

		$output = '<form action="" method="post" enctype="multipart/form-data" name="gift">
			<div class="g-message">' .
				$this->msg(
					'g-give-to-user-message',
					$this->userTo->getName()
				)->parse() . "</div>
			<div id=\"give_gift_{$gift['gift_id']}\" class=\"g-container\">
				{$icon}
				<div class=\"g-title\">" . htmlspecialchars( $gift['gift_name'] ) . "</div>";
		if ( $gift['gift_description'] ) {
			$output .= '<div class="g-describe">' .
				htmlspecialchars( $gift['gift_description'] ) .
			'</div>';
		}
		$output .= '</div>
			<div class="visualClear"></div>
			<div class="g-add-message">' . $this->msg( 'g-add-message' )->escaped() . '</div>
			<textarea name="message" id="message" rows="4" cols="50" maxlength="255"></textarea>
			<div class="g-characters-left-message">' . $this->msg(
				'g-characters-left',
				'<span class="countdown" value="255">255</span>'
			)->parse() . '</div>
			<div class="g-buttons">
				<input type="hidden" name="gift_id" value="' . $giftId . '" />
				<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
				<input type="hidden" name="user_name" value="' . htmlspecialchars( $this->userTo->getName() ) . '" />
				<input type="submit" class="site-button" value="' . $this->msg( 'g-send-gift' )->escaped() . '" size="20" onclick="document.gift.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';

		return $output;
	}

	/**
	 * Display the form for giving out a gift to a user when there was no user
	 * parameter in the URL.
	 *
	 * @return string HTML
	 */
	function displayFormNoUser() {
		global $wgFriendingEnabled;

		$this->getOutput()->setPageTitle( $this->msg( 'g-give-no-user-title' )->plain() );
		$this->getOutput()->addModules( 'mediawiki.userSuggest' );

		$output = '<form action="" method="get" enctype="multipart/form-data" name="gift">' .
			Html::hidden( 'title', $this->getPageTitle() ) .
			'<div class="g-message">' .
				$this->msg( 'g-give-no-user-message' )->escaped() .
			'</div>
			<div class="g-give-container">';

			// If friending is enabled, build a dropdown menu of the user's
			// friends
			if ( $wgFriendingEnabled ) {
				$user = $this->getUser();

				$listLookup = new RelationshipListLookup( $user );
				$friends = $listLookup->getFriendList();

				if ( $friends ) {
					$output .= '<div class="g-give-title">' .
						$this->msg( 'g-give-list-friends-title' )->escaped() .
					'</div>
					<div class="g-gift-select">
						<select name="friend-list">
							<option value="#" selected="selected">' .
								$this->msg( 'g-select-a-friend' )->escaped() .
							'</option>';
					foreach ( $friends as $friend ) {
						$friendActor = User::newFromActorId( $friend['actor'] );
						if ( !$friendActor || !$friendActor instanceof User ) {
							continue;
						}
						$output .= '<option value="' . htmlspecialchars( $friendActor->getName() ) . '">' .
							htmlspecialchars( $friendActor->getName() ) .
						'</option>' . "\n";
					}
					$output .= '</select>
					</div>
					<div class="g-give-separator">' .
						$this->msg( 'g-give-separator' )->escaped() .
					'</div>';
				}
			}

			$output .= '<div class="g-give-title">' .
				$this->msg( 'g-give-enter-friend-title' )->escaped() .
			'</div>
			<div class="g-give-textbox">
				<input type="text" width="85" name="user" class="mw-autocomplete-user" value="" />
				<input class="site-button" type="submit" value="' . $this->msg( 'g-give-gift' )->escaped() . '" onclick="document.gift.submit()" />
			</div>
			</div>
		</form>';

		return $output;
	}

	function displayFormAll() {
		global $wgGiveGiftPerRow;

		$linkRenderer = $this->getLinkRenderer();
		$out = $this->getOutput();

		$page = $this->getRequest()->getInt( 'page' );
		if ( !$page || !is_numeric( $page ) ) {
			$page = 1;
		}

		$per_page = 24;
		$per_row = $wgGiveGiftPerRow;
		if ( !$per_row ) {
			$per_row = 3;
		}

		$total = Gifts::getGiftCount();
		$listLookup = new UserGiftListLookup( $this->getContext(), $per_page, $page );
		$gifts = $listLookup->getGiftList( 'gift_name' );
		$output = '';

		if ( $gifts ) {
			$out->setPageTitle( $this->msg( 'g-give-all-title', $this->userTo->getName() )->parse() );

			$output .= '<div class="back-links">
				<a href="' . htmlspecialchars( $this->userTo->getUserPage()->getFullURL() ) . '">' .
					$this->msg( 'g-back-link', $this->userTo->getName() )->parse() .
				'</a>
			</div>
			<div class="g-message">' .
				$this->msg( 'g-give-all', $this->userTo->getName() )->parse() .
			'</div>
			<form action="" method="post" enctype="multipart/form-data" name="gift">';

			$x = 1;

			foreach ( $gifts as $gift ) {
				$userGiftIcon = new UserGiftIcon( $gift['id'], 'l' );
				$icon = $userGiftIcon->getIconHTML( [ 'id' => "gift_image_{$gift['id']}" ] );

				$output .= '<div id="give_gift_' . htmlspecialchars( $gift['id'] ) . "\" class=\"g-give-all\">
					{$icon}
					<div class=\"g-title g-blue\">" . htmlspecialchars( $gift['gift_name'] ) . '</div>';
				if ( $gift['gift_description'] ) {
					$output .= '<div class="g-describe">' . htmlspecialchars( $gift['gift_description'] ) . '</div>';
				}
				// NoJS checkbox for selecting a gift
				$output .= Html::check( "nojs-gift-{$gift['id']}", false, [
					'class' => 'g-give-checkbox',
					'id' => "nojs-gift-{$gift['id']}"
				] );
				$output .= Html::label( $this->msg( 'g-give-this-gift' )->text(), "nojs-gift-{$gift['id']}" );
				$output .= '<div class="visualClear"></div>
				</div>';
				if ( $x == count( $gifts ) || $x != 1 && $x % $per_row == 0 ) {
					$output .= '<div class="visualClear"></div>';
				}
				$x++;
			}

			/**
			 * Build next/prev nav
			 */
			$giveGiftLink = $this->getPageTitle();

			$numofpages = $total / $per_page;
			$user_name = $this->userTo->getName();

			if ( $numofpages > 1 ) {
				$output .= '<div class="page-nav">';
				if ( $page > 1 ) {
					$output .= $linkRenderer->makeLink(
						$giveGiftLink,
						$this->msg( 'g-previous' )->text(),
						[],
						[
							'user' => $user_name,
							'page' => ( $page - 1 )
						]
					) . $this->msg( 'word-separator' )->escaped();
				}

				if ( ( $total % $per_page ) != 0 ) {
					$numofpages++;
				}
				if ( $numofpages >= 9 ) {
					$numofpages = 9 + $page;
				}
				for ( $i = 1; $i <= $numofpages; $i++ ) {
					if ( $i == $page ) {
						$output .= ( $i . ' ' );
					} else {
						$output .= $linkRenderer->makeLink(
							$giveGiftLink,
							$i,
							[],
							[
								'user' => $user_name,
								'page' => $i
							]
						) . $this->msg( 'word-separator' )->escaped();
					}
				}

				if ( ( $total - ( $per_page * $page ) ) > 0 ) {
					$output .= $this->msg( 'word-separator' )->escaped() .
						$linkRenderer->makeLink(
							$giveGiftLink,
							$this->msg( 'g-next' )->text(),
							[],
							[
								'user' => $user_name,
								'page' => ( $page + 1 )
							]
						);
				}
				$output .= '</div>';
			}

			/**
			 * Build the send/cancel buttons and whatnot
			 */
			$output .= '<div class="g-give-all-message-title">' .
				$this->msg( 'g-give-all-message-title' )->escaped() .
			'</div>
				<textarea name="message" id="message" rows="4" cols="50"></textarea>
				<div class="g-characters-left-message">' . $this->msg(
					'g-characters-left',
					'<span class="countdown" value="255">255</span>'
				)->parse() . '</div>
				<div class="g-buttons">
					<input type="hidden" name="gift_id" value="0" />
					<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
					<input type="hidden" name="user_name" value="' . htmlspecialchars( $this->userTo->getName() ) . '" />
					<input type="submit" id="send-gift-button" class="site-button" value="' . $this->msg( 'g-send-gift' )->escaped() . '" size="20" />
					<input type="button" class="site-button" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
				</div>
			</form>';
		} else {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->escaped() );
		}

		return $output;
	}
}
