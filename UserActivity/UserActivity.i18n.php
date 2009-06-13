<?php
/**
 * Internationalization file for UserActivity extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Aaron Wright
 * @author David Pean
 */
$messages['en'] = array(
	'useractivity' => 'Friends activity',
	'useractivity-award' => 'received an award', // FIXME: message lego
	'useractivity-all' => 'View all',
	'useractivity-gift' => 'received a gift from', // FIXME: message logo
	'useractivity-group-edit' => 'edits', // CHECKME: message lego (as well as the messages below)?
	'useractivity-group-comment' => 'comments',
	'useractivity-group-user_message' => 'messages',
	'useractivity-group-friend' => 'friends',
	'useractivity-filter' => 'Filter',
	'useractivity-network-thought' => 'has a thought for the $1 network', // FIXME: message lego
	'useractivity-title' => "Friends activity",
	'useractivity-siteactivity' => 'Site activity',
	'useractivity-edit' => '{{PLURAL:$1|edited the page|edited the following pages: }}', // CHECKME: message lego?
	'useractivity-comment' => '{{PLURAL:$1|commented on the page|commented on the following pages: }}', // CHECKME: message lego?
	'useractivity-user_message' => '{{PLURAL:$1|sent a message to|sent messages to}}',
	'useractivity-votedpage' => 'voted for the page', // FIXME: message lego
	'useractivity-commentedpage' => 'commented on the page', // FIXME: message lego
	'useractivity-giftsent' => 'sent a gift to', // FIXME: message lego
	'useractivity-friend' => '{{PLURAL:$2|is now friends with|are now friends with}}', // FIXME: message lego
	'useractivity-foe' => '{{PLURAL:$2|is now foes with|are now foes with}}', // FIXME: message lego
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'useractivity' => 'Ystävien aktiivisuus',
	'useractivity-award' => 'sai palkinnon',
	'useractivity-all' => 'Katso kaikki',
	'useractivity-gift' => 'sai lahjan käyttäjältä',
	'useractivity-group-edit' => 'muokkausta',
	'useractivity-group-comment' => 'kommenttia',
	'useractivity-group-user_message' => 'viestiä',
	'useractivity-group-friend' => 'ystävät',
	'useractivity-title' => 'Ystävien aktiivisuus',
	'useractivity-siteactivity' => 'Sivuston aktiivisuus',
	'useractivity-edit' => '{{PLURAL:$2|muokkasi|muokkasivat}} {{PLURAL:$1|sivua|seuraavia sivuja: }}',
	'useractivity-comment' => '{{PLURAL:$1|kommentoi sivua|kommentoi seuraavia sivuja: }}',
	'useractivity-user_message' => '{{PLURAL:$1|lähetti viestin käyttäjälle|lähetti viestejä käyttäjille}}',
	'useractivity-votedpage' => 'äänesti sivua',
	'useractivity-commentedpage' => 'kommentoi sivua',
	'useractivity-giftsent' => 'lähetti lahjan käyttäjälle',
	'useractivity-friend' => '{{PLURAL:$2|on nyt ystävä käyttäjälle|ovat nyt ystäviä käyttäjille}}',
	'useractivity-foe' => '{{PLURAL:$2|on nyt vihollinen käyttäjälle|ovat nyt vihollisia käyttäjille}}',
);
