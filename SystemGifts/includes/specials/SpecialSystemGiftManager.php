<?php
/**
 * Special:SystemGiftManager -- a special page to create new system gifts
 * (awards)
 *
 * @file
 * @ingroup Extensions
 */

use MediaWiki\MediaWikiServices;

class SystemGiftManager extends SpecialPage {

	public function __construct() {
		parent::__construct( 'SystemGiftManager'/*class*/, 'awardsmanage'/*restriction*/ );
	}

	public function doesWrites() {
		return true;
	}

	/**
	 * Show the special page
	 *
	 * @param string|null $par
	 */
	public function execute( $par ) {
		$out = $this->getOutput();
		$request = $this->getRequest();
		$user = $this->getUser();

		// make sure user has the correct permissions
		$this->checkPermissions();

		// Show a message if the database is in read-only mode
		$this->checkReadOnly();

		// If user is blocked, s/he doesn't need to access this page
		$block = $user->getBlock();
		if ( $block ) {
			throw new UserBlockedError( $block );
		}

		// Set the page title, robot policies, etc.
		$out->setArticleRelated( false );
		$out->setRobotPolicy( 'noindex,nofollow' );

		// Add CSS
		$out->addModuleStyles( 'ext.socialprofile.special.systemgiftmanager.css' );

		if ( $request->wasPosted() && $user->matchEditToken( $request->getVal( 'wpEditToken' ) ) ) {
			$g = new SystemGifts();

			if ( !$request->getInt( 'id' ) ) {
				// Add the new system gift to the database
				$gift_id = $g->addGift(
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'gift_category' ),
					$request->getInt( 'gift_threshold' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'ga-created' )->escaped() .
					'</span><br /><br />'
				);
			} else {
				$gift_id = $request->getInt( 'id' );
				$g->updateGift(
					$gift_id,
					$request->getVal( 'gift_name' ),
					$request->getVal( 'gift_description' ),
					$request->getInt( 'gift_category' ),
					$request->getInt( 'gift_threshold' )
				);
				$out->addHTML(
					'<span class="view-status">' .
					$this->msg( 'ga-saved' )->escaped() .
					'</span><br /><br />'
				);
			}
			$g->updateSystemGifts();
			$out->addHTML( $this->displayForm( $gift_id ) );
		} else {
			$gift_id = $request->getInt( 'id' );
			if ( $gift_id || $request->getVal( 'method' ) == 'edit' ) {
				$out->addHTML( $this->displayForm( $gift_id ) );
			} else {
				$out->addHTML(
					'<div><b><a href="' .
					htmlspecialchars( $this->getPageTitle()->getFullURL( 'method=edit' ) ) . '">' .
						$this->msg( 'ga-addnew' )->escaped() . '</a></b></div>'
				);
				$out->addHTML( $this->displayGiftList() );
			}
		}
	}

	/**
	 * Display the text list of all existing system gifts and a delete link to
	 * users who are allowed to delete gifts.
	 *
	 * @return string HTML
	 */
	function displayGiftList() {
		$output = ''; // Prevent E_NOTICE
		$request = $this->getRequest();
		$page = $request->getInt( 'page', 1 );
		$per_page = $request->getInt( 'per_page', 50 );
		$listLookup = new SystemGiftListLookup( $per_page, $page );
		$gifts = $listLookup->getGiftList();
		$user = $this->getUser();

		if ( $gifts ) {
			foreach ( $gifts as $gift ) {
				$deleteLink = '';
				if ( $user->isAllowed( 'awardsmanage' ) ) {
					$removePage = SpecialPage::getTitleFor( 'RemoveMasterSystemGift' );
					$deleteLink = '<a class="ga-remove-link" href="' .
						htmlspecialchars( $removePage->getFullURL( "gift_id={$gift['id']}" ) ) .
						'">' . $this->msg( 'delete' )->escaped() . '</a>';
				}

				$output .= '<div class="Item">
					<a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL( 'id=' . $gift['id'] ) ) . '">' .
						htmlspecialchars( $gift['gift_name'], ENT_QUOTES ) . '</a> ' .
						$deleteLink . '</div>' . "\n";
			}
		}

		$total = SystemGifts::getGiftCount();
		if ( ( $total > $per_page ) ) {
			$output .= $this->renderPagination( $total, $per_page, $page );
		}

		return '<div id="views">' . $output . '</div>';
	}

	/**
	 * Build the pagination links
	 *
	 * @see https://phabricator.wikimedia.org/T306748
	 *
	 * @param int $total Total amount of entries
	 * @param int $perPage Show this many entries per page
	 * @param int $page Current page number
	 * @return string HTML
	 */
	private function renderPagination( int $total, int $perPage, int $page ) {
		$linkRenderer = MediaWikiServices::getInstance()->getLinkRenderer();
		$pageTitle = $this->getPageTitle();

		// Quick sanity check first...
		if ( !$perPage || $perPage > 500 ) {
			$perPage = 50;
		}

		$numOfPages = $total / $perPage;
		$prevLink = [
			'page' => ( $page - 1 ),
			'per_page' => $perPage
		];
		$nextLink = [
			'page' => ( $page + 1 ),
			'per_page' => $perPage
		];

		$output = '';

		if ( $numOfPages > 1 ) {
			$output .= '<div class="mw-system-gift-manager-navigation">';

			if ( $page > 1 ) {
				$output .= $linkRenderer->makeLink(
					$pageTitle,
					// Yes, I'm intentionally reusing the i18n msgs from UserGifts
					// instead of bothering to copy 'em over to SystemGifts
					$this->msg( 'g-previous' )->plain(),
					[],
					$prevLink
				) . ' ';
			}

			if ( ( $total % $perPage ) != 0 ) {
				$numOfPages++;
			}

			if ( $numOfPages >= 9 && $page < $total ) {
				$numOfPages = 9 + $page;
			}

			if ( $numOfPages >= ( $total / $perPage ) ) {
				$numOfPages = ( $total / $perPage ) + 1;
			}

			// @note I don't quite understand why I had to change the condition
			// to have ( $numOfPages - 1 ) instead of just what it was, which was
			// plain $numOfPages...but on my test wiki I had 6 awards so with $perPage = 3,
			// that meant two pages, right? Except prior to changing this condition this
			// code would render a link to page 3, too, except said page was obviously
			// empty.
			// Note that I "borrowed" this code from ImageRating so the bug might still be present there?
			for ( $i = 1; $i <= ( $numOfPages - 1 ); $i++ ) {
				if ( $i == $page ) {
					$output .= ( $i . ' ' );
				} else {
					$output .= $linkRenderer->makeLink(
						$pageTitle,
						"$i",
						[],
						[
							'page' => $i,
							'per_page' => $perPage
						]
					) . ' ';
				}
			}

			if ( ( $total - ( $perPage * $page ) ) > 0 ) {
				$output .= ' ' . $linkRenderer->makeLink(
					$pageTitle,
					// Yes, I'm intentionally reusing the i18n msgs from UserGifts
					// instead of bothering to copy 'em over to SystemGifts
					$this->msg( 'g-next' )->plain(),
					[],
					$nextLink
				);
			}

			$output .= '</div>';
		}

		return $output;
	}

	/**
	 * Display the form for editing an existing system gift (if $gift_id is given) or creating a brand new one.
	 *
	 * @param int $gift_id ID of the system gift to edit, if not creating a brand new system gift
	 * @return string HTML
	 */
	function displayForm( int $gift_id ) {
		$form = '<div><b><a href="' . htmlspecialchars( $this->getPageTitle()->getFullURL() ) .
			'">' . $this->msg( 'ga-viewlist' )->escaped() . '</a></b></div>';

		if ( $gift_id ) {
			$gift = SystemGifts::getGift( $gift_id );
		}

		$form .= '<form action="" method="post" enctype="multipart/form-data" name="gift">
		<table>
			<tr>
				<td class="view-form">' . $this->msg( 'ga-giftname' )->escaped() . '</td>
				<td class="view-container"><input type="text" size="45" class="createbox" name="gift_name" value="' .
					( isset( $gift['gift_name'] ) && $gift['gift_name'] ? htmlspecialchars( $gift['gift_name'], ENT_QUOTES ) : '' ) .
					'"/></td>
			</tr>
			<tr>
				<td class="view-form" valign="top">' . $this->msg( 'ga-giftdesc' )->escaped() . '</td>
				<td class="view-container"><textarea class="createbox" name="gift_description" rows="2" cols="30">' .
					( isset( $gift['gift_description'] ) && $gift['gift_description'] ? htmlspecialchars( $gift['gift_description'], ENT_QUOTES ) : '' ) .
			'</textarea></td>
			</tr>
			<tr>
				<td class="view-form">' . $this->msg( 'ga-gifttype' )->escaped() . '</td>
				<td class="view-container">
					<select name="gift_category">' . "\n";
			$g = new SystemGifts();
			foreach ( $g->getCategories() as $category => $id ) {
				$sel = '';
				if ( isset( $gift['gift_category'] ) && $gift['gift_category'] == $id ) {
					$sel = ' selected="selected"';
				}
				$indent = "\t\t\t\t\t\t";
				$form .= $indent . '<option' . $sel .
					" value=\"{$id}\">{$category}</option>\n";
			}
			$form .= "\t\t\t\t\t" . '</select>
				</td>
			</tr>
		<tr>
			<td class="view-form">' . $this->msg( 'ga-threshold' )->escaped() . '</td>
			<td class="view-container"><input type="text" size="25" class="createbox" name="gift_threshold" value="' .
				( isset( $gift['gift_threshold'] ) && $gift['gift_threshold'] ? (int)$gift['gift_threshold'] : '' ) . '"/></td>
		</tr>';

		if ( $gift_id ) {
			$sgml = SpecialPage::getTitleFor( 'SystemGiftManagerLogo' );
			$systemGiftIcon = new SystemGiftIcon( $gift_id, 'l' );
			$icon = $systemGiftIcon->getIconHTML();

			$form .= '<tr>
			<td class="view-form" valign="top">' . $this->msg( 'ga-giftimage' )->escaped() . '</td>
			<td class="view-container">' .
				$icon .
				'<a href="' . htmlspecialchars( $sgml->getFullURL( 'gift_id=' . $gift_id ) ) . '">' .
					$this->msg( 'ga-img' )->escaped() . '</a>
				</td>
			</tr>';
		}

		if ( isset( $gift['gift_id'] ) ) {
			$button = $this->msg( 'edit' )->escaped();
		} else {
			$button = $this->msg( 'ga-create-gift' )->escaped();
		}

		$form .= '<tr>
		<td colspan="2">
			<input type="hidden" name="id" value="' . ( isset( $gift['gift_id'] ) ? (int)$gift['gift_id'] : '' ) . '" />
			<input type="hidden" name="wpEditToken" value="' . htmlspecialchars( $this->getUser()->getEditToken(), ENT_QUOTES ) . '" />
			<input type="submit" class="createbox" value="' . $button . '" size="20" />
			<input type="button" class="createbox" value="' . $this->msg( 'cancel' )->escaped() . '" size="20" onclick="history.go(-1)" />
		</td>
		</tr>
		</table>

		</form>';
		return $form;
	}
}
