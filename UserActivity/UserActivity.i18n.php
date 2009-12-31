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
	'useractivity' => "Friends' activity",
	'useractivity-award' => '$1 received an award',
	'useractivity-all' => 'View all',
	#'useractivity-comment' => '{{PLURAL:$1|commented on the page|commented on the following pages: }}',
	#'useractivity-commentedpage' => 'commented on the page',
	'useractivity-edit' => '$1 {{PLURAL:$4|edited the page|edited the following pages:}} $3',
	'useractivity-foe' => '$1 {{PLURAL:$2|is now foes with|are now foes with}} $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|is now friends with|are now friends with}} $3',
	'useractivity-gift' => '$1 received a gift from $2',
	#'useractivity-gift-sent' => 'sent a gift to',
	'useractivity-group-edit' => '{{PLURAL:$1|one edit|$1 edits}}',
	'useractivity-group-comment' => '{{PLURAL:$1|one comment|$1 comments}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|one message|$1 messages}}',
	'useractivity-group-friend' => '{{PLURAL:$1|one friend|$1 friends}}',
	#'useractivity-filter' => 'Filter',
	'useractivity-siteactivity' => 'Site activity',
	'useractivity-title' => "Friends' activity",
	'useractivity-user_message' => '$1 {{PLURAL:$4|sent a message to|sent messages to}} $3',
	#'useractivity-votedpage' => 'voted for the page',
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'useractivity' => 'Ystävien aktiivisuus',
	'useractivity-award' => '$1 sai palkinnon',
	'useractivity-all' => 'Katso kaikki',
	#'useractivity-comment' => '{{PLURAL:$1|kommentoi sivua|kommentoi seuraavia sivuja: }}',
	#'useractivity-commentedpage' => 'kommentoi sivua',
	'useractivity-edit' => '$1 {{PLURAL:$2|muokkasi|muokkasivat}} {{PLURAL:$4|sivua|seuraavia sivuja:}} $3',
	'useractivity-foe' => '$1 {{PLURAL:$2|on nyt vihollinen käyttäjälle|ovat nyt vihollisia käyttäjille}} $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|on nyt ystävä käyttäjälle|ovat nyt ystäviä käyttäjille}} $3',
	'useractivity-gift' => '$1 sai lahjan käyttäjältä $2',
	#'useractivity-gift-sent' => 'lähetti lahjan käyttäjälle',
	'useractivity-group-edit' => '{{PLURAL:$1|yksi muokkaus|$1 muokkausta}}',
	'useractivity-group-comment' => '{{PLURAL:$1|yksi kommentti|$1 kommenttia}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|yksi viesti|$1 viestiä}}',
	'useractivity-group-friend' => '{{PLURAL:$1|yksi ystävä|$1 ystävät}}',
	'useractivity-siteactivity' => 'Sivuston aktiivisuus',
	'useractivity-title' => 'Ystävien aktiivisuus',
	'useractivity-user_message' => '$1 {{PLURAL:$4|lähetti viestin käyttäjälle|lähetti viestejä käyttäjille}} $3',
	#'useractivity-votedpage' => 'äänesti sivua',
);
