<?php
/**
 * Special:GiveGift -- a special page for sending out user-to-user gifts
 *
 * @file
 * @ingroup Extensions
 */

class GiveGift extends SpecialPage {

	/**
	 * Constructor
	 */
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
	 * Show the special page
	 *
	 * @param $par Mixed: parameter passed to the page or null
	 */
	public function execute( $par ) {
		global $wgMemc, $wgUploadPath;

		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		$output = ''; // Prevent E_NOTICE

		// Set the page title, robot policies, etc.
		$this->setHeaders();

		// Add CSS & JS
		$out->addModuleStyles( 'ext.socialprofile.usergifts.css' );
		$out->addModules( 'ext.socialprofile.usergifts.js' );

		$userTitle = Title::newFromDBkey( $request->getVal( 'user' ) );
		if ( !$userTitle ) {
			$out->addHTML( $this->displayFormNoUser() );
			return false;
		}

		$user_title = Title::makeTitle( NS_USER, $request->getVal( 'user' ) );
		$this->user_name_to = $userTitle->getText();
		$this->user_id_to = User::idFromName( $this->user_name_to );
		$giftId = $request->getInt( 'gift_id' );

		if ( $user->getID() === $this->user_id_to ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-to-yourself' )->plain() );
		} elseif ( $user->isBlocked() ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-blocked' )->plain() );
		} elseif ( $this->user_id_to == 0 ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-no-user' )->plain() );
		} elseif ( $user->getID() == 0 ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-login' )->plain() );
		} else {
			$gift = new UserGifts( $user->getName() );

			if ( $request->wasPosted() && $_SESSION['alreadysubmitted'] == false ) {
				$_SESSION['alreadysubmitted'] = true;

				$ug_gift_id = $gift->sendGift(
					$this->user_name_to,
					$request->getInt( 'gift_id' ),
					0,
					$request->getVal( 'message' )
				);

				// clear the cache for the user profile gifts for this user
				$wgMemc->delete( wfMemcKey( 'user', 'profile', 'gifts', $this->user_id_to ) );

				$key = wfMemcKey( 'gifts', 'unique', 4 );
				$data = $wgMemc->get( $key );

				// check to see if this type of gift is in the unique list
				$lastUniqueGifts = $data;
				$found = 1;

				if ( is_array( $lastUniqueGifts ) ) {
					foreach ( $lastUniqueGifts as $lastUniqueGift ) {
						if ( $request->getInt( 'gift_id' ) == $lastUniqueGift['gift_id'] ) {
							$found = 0;
						}
					}
				}

				if ( $found ) {
					// add new unique to array
					$lastUniqueGifts[] = array(
						'id' => $ug_gift_id,
						'gift_id' => $request->getInt( 'gift_id' )
					);

					// remove oldest value
					if ( count( $lastUniqueGifts ) > 4 ) {
						array_shift( $lastUniqueGifts );
					}

					// reset the cache
					$wgMemc->set( $key, $lastUniqueGifts );
				}

				$sent_gift = UserGifts::getUserGift( $ug_gift_id );
				$gift_image = '<img src="' . $wgUploadPath . '/awards/' .
					Gifts::getGiftImage( $sent_gift['gift_id'], 'l' ) .
					'" border="0" alt="" />';

				$out->setPageTitle( $this->msg( 'g-sent-title', $this->user_name_to )->parse() );

				$output .= '<div class="back-links">
					<a href="' . htmlspecialchars( $user_title->getFullURL() ) . '">' .
						$this->msg( 'g-back-link', $this->user_name_to )->parse() .
					'</a>
				</div>
				<div class="g-message">' .
					$this->msg( 'g-sent-message', $this->user_name_to )->parse() .
				'</div>
				<div class="g-container">' .
					$gift_image .
				'<div class="g-title">' . $sent_gift['name'] . '</div>';
				if ( $sent_gift['message'] ) {
					$output .= '<div class="g-user-message">' .
						$sent_gift['message'] .
					'</div>';
				}
				$output .= '</div>
				<div class="visualClear"></div>
				<div class="g-buttons">
					<input type="button" class="site-button" value="' . $this->msg( 'g-main-page' )->plain() . '" size="20" onclick="window.location=\'index.php?title=' . $this->msg( 'mainpage' )->inContentLanguage()->escaped() . '\'" />
					<input type="button" class="site-button" value="' . $this->msg( 'g-your-profile' )->plain() . '" size="20" onclick="window.location=\'' . htmlspecialchars( $user->getUserPage()->getFullURL() ) . '\'" />
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
	 * Display the form for sending out a single gift.
	 * Relies on the gift_id URL parameter and bails out if it's not there.
	 *
	 * @return String: HTML
	 */
	function displayFormSingle() {
		global $wgUploadPath;

		$out = $this->getOutput();

		$giftId = $this->getRequest()->getInt( 'gift_id' );

		if ( !$giftId || !is_numeric( $giftId ) ) {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
			return false;
		}

		$gift = Gifts::getGift( $giftId );

		if ( empty( $gift ) ) {
			return false;
		}

		if ( $gift['access'] == 1 && $this->getUser()->getID() != $gift['creator_user_id'] ) {
			return $this->displayFormAll();
		}

		// Safe titles
		$user = Title::makeTitle( NS_USER, $this->user_name_to );
		$giveGiftLink = SpecialPage::getTitleFor( 'GiveGift' );

		$out->setPageTitle( $this->msg( 'g-give-to-user-title', $gift['gift_name'], $this->user_name_to )->parse() );

		$gift_image = "<img id=\"gift_image_{$gift['gift_id']}\" src=\"{$wgUploadPath}/awards/" .
			Gifts::getGiftImage( $gift['gift_id'], 'l' ) .
			'" border="0" alt="" />';

		$output = '<form action="" method="post" enctype="multipart/form-data" name="gift">
			<div class="g-message">' .
				$this->msg(
					'g-give-to-user-message',
					$this->user_name_to,
					htmlspecialchars( $giveGiftLink->getFullURL( 'user=' . $this->user_name_to ) )
				)->text() . "</div>
			<div id=\"give_gift_{$gift['gift_id']}\" class=\"g-container\">
				{$gift_image}
				<div class=\"g-title\">{$gift['gift_name']}</div>";
		if ( $gift['gift_description'] ) {
			$output .= '<div class="g-describe">' .
				$gift['gift_description'] .
			'</div>';
		}
		$output .= '</div>
			<div class="visualClear"></div>
			<div class="g-add-message">' . $this->msg( 'g-add-message' )->plain() . '</div>
			<textarea name="message" id="message" rows="4" cols="50"></textarea>
			<div class="g-buttons">
				<input type="hidden" name="gift_id" value="' . $giftId . '" />
				<input type="hidden" name="user_name" value="' . addslashes( $this->user_name_to ) . '" />
				<input type="button" class="site-button" value="' . $this->msg( 'g-send-gift' )->plain() . '" size="20" onclick="document.gift.submit()" />
				<input type="button" class="site-button" value="' . $this->msg( 'g-cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
			</div>
		</form>';

		return $output;
	}

	/**
	 * Display the form for giving out a gift to a user when there was no user
	 * parameter in the URL.
	 *
	 * @return String: HTML
	 */
	function displayFormNoUser() {
		global $wgFriendingEnabled;

		$this->getOutput()->setPageTitle( $this->msg( 'g-give-no-user-title' )->plain() );

		$output = '<form action="" method="get" enctype="multipart/form-data" name="gift">' .
			Html::hidden( 'title', $this->getPageTitle() ) .
			'<div class="g-message">' .
				$this->msg( 'g-give-no-user-message' )->plain() .
			'</div>
			<div class="g-give-container">';

			// If friending is enabled, build a dropdown menu of the user's
			// friends
			if ( $wgFriendingEnabled ) {
				$rel = new UserRelationship( $this->getUser()->getName() );
				$friends = $rel->getRelationshipList( 1 );

				if ( $friends ) {
					$output .= '<div class="g-give-title">' .
						$this->msg( 'g-give-list-friends-title' )->plain() .
					'</div>
					<div class="g-gift-select">
						<select>
							<option value="#" selected="selected">' .
								$this->msg( 'g-select-a-friend' )->plain() .
							'</option>';
					foreach ( $friends as $friend ) {
						$output .= '<option value="' . urlencode( $friend['user_name'] ) . '">' .
							$friend['user_name'] .
						'</option>' . "\n";
					}
					$output .= '</select>
					</div>
					<div class="g-give-separator">' .
						$this->msg( 'g-give-separator' )->plain() .
					'</div>';
				}
			}

			$output .= '<div class="g-give-title">' .
				$this->msg( 'g-give-enter-friend-title' )->plain() .
			'</div>
			<div class="g-give-textbox">
				<input type="text" width="85" name="user" value="" />
				<input class="site-button" type="button" value="' . $this->msg( 'g-give-gift' )->plain() . '" onclick="document.gift.submit()" />
			</div>
			</div>
		</form>';

		return $output;
	}

	function displayFormAll() {
		global $wgGiveGiftPerRow, $wgUploadPath;

		$out = $this->getOutput();

		$user = Title::makeTitle( NS_USER, $this->user_name_to );

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
		$gifts = Gifts::getGiftList( $per_page, $page, 'gift_name' );
		$output = '';

		if ( $gifts ) {
			$out->setPageTitle( $this->msg( 'g-give-all-title', $this->user_name_to )->parse() );

			$output .= '<div class="back-links">
				<a href="' . htmlspecialchars( $user->getFullURL() ) . '">' .
					$this->msg( 'g-back-link', $this->user_name_to )->parse() .
				'</a>
			</div>
			<div class="g-message">' .
				$this->msg( 'g-give-all', $this->user_name_to )->parse() .
			'</div>
			<form action="" method="post" enctype="multipart/form-data" name="gift">';

			$x = 1;

			foreach ( $gifts as $gift ) {
				$gift_image = "<img id=\"gift_image_{$gift['id']}\" src=\"{$wgUploadPath}/awards/" .
					Gifts::getGiftImage( $gift['id'], 'l' ) .
					'" border="0" alt="" />';

				$output .= "<div id=\"give_gift_{$gift['id']}\" class=\"g-give-all\">
					{$gift_image}
					<div class=\"g-title g-blue\">{$gift['gift_name']}</div>";
				if ( $gift['gift_description'] ) {
					$output .= "<div class=\"g-describe\">{$gift['gift_description']}</div>";
				}
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
			$user_name = $user->getText();

			if ( $numofpages > 1 ) {
				$output .= '<div class="page-nav">';
				if ( $page > 1 ) {
					$output .= Linker::link(
						$giveGiftLink,
						$this->msg( 'g-previous' )->plain(),
						array(),
						array(
							'user' => $user_name,
							'page' => ( $page - 1 )
						)
					) . $this->msg( 'word-separator' )->plain();
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
						$output .= Linker::link(
							$giveGiftLink,
							$i,
							array(),
							array(
								'user' => $user_name,
								'page' => $i
							)
						) . $this->msg( 'word-separator' )->plain();
					}
				}

				if ( ( $total - ( $per_page * $page ) ) > 0 ) {
					$output .= $this->msg( 'word-separator' )->plain() .
						Linker::link(
							$giveGiftLink,
							$this->msg( 'g-next' )->plain(),
							array(),
							array(
								'user' => $user_name,
								'page' => ( $page + 1 )
							)
						);
				}
				$output .= '</div>';
			}

			/**
			 * Build the send/cancel buttons and whatnot
			 */
			$output .= '<div class="g-give-all-message-title">' .
				$this->msg( 'g-give-all-message-title' )->plain() .
			'</div>
				<textarea name="message" id="message" rows="4" cols="50"></textarea>
				<div class="g-buttons">
					<input type="hidden" name="gift_id" value="0" />
					<input type="hidden" name="user_name" value="' . addslashes( $this->user_name_to ) . '" />
					<input type="button" id="send-gift-button" class="site-button" value="' . $this->msg( 'g-send-gift' )->plain() . '" size="20" />
					<input type="button" class="site-button" value="' . $this->msg( 'g-cancel' )->plain() . '" size="20" onclick="history.go(-1)" />
				</div>
			</form>';
		} else {
			$out->setPageTitle( $this->msg( 'g-error-title' )->plain() );
			$out->addHTML( $this->msg( 'g-error-message-invalid-link' )->plain() );
		}

		return $output;
	}
}
