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

/** Afrikaans (Afrikaans)
 * @author Naudefj
 */
$messages['af'] = array(
	'useractivity' => 'Vriende se aktiwiteit',
	'useractivity-award' => "$1 het 'n prys ontvang",
	'useractivity-all' => 'Wys almal',
	'useractivity-edit' => '$1 het die volgende {{PLURAL:$4|bladsy|bladsye}} wysig: $3',
	'useractivity-foe' => "$1 is nou {{PLURAL:$2|'n teenstander|teenstanders}} van $3",
	'useractivity-friend' => '$1 {{PLURAL:$2|is|is}} nou vriende met $3',
	'useractivity-gift' => "$1 het 'n geskenk van $2 ontvang",
	'useractivity-group-edit' => '$1 {{PLURAL:$1|wysiging|wysigings}}',
	'useractivity-group-comment' => '$1 {{PLURAL:$1|opmerking|opmerkings}}',
	'useractivity-group-user_message' => '$1 {{PLURAL:$1|boodskap|boodskappe}}',
	'useractivity-group-friend' => '$1 {{PLURAL:$1|vriend|vriende}}',
	'useractivity-siteactivity' => 'Werf-aktiwiteit',
	'useractivity-title' => 'Vriende se aktiwiteit',
	'useractivity-user_message' => "$1 het {{PLURAL:$4|'n boodskap|boodskappe}} aan $3 gestuur",
);

/** Arabic (العربية)
 * @author Meno25
 */
$messages['ar'] = array(
	'useractivity' => 'نشاط الأصدقاء',
	'useractivity-award' => '$1 تلقى جائزة',
	'useractivity-all' => 'عرض الكل',
	'useractivity-edit' => '$1 {{PLURAL:$4|عدل الصفحة|عدل الصفحات التالية:}} $3',
	'useractivity-foe' => '$1 {{PLURAL:$2|هو الآن عدو مع|هم الآن أعداء مع}} $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|هو الآن صديق مع|هم الآن أصدقاء مع}} $3',
	'useractivity-gift' => '$1 تلقى هدية من $2',
	'useractivity-group-edit' => '{{PLURAL:$1|تعديل واحد|$1 تعديل}}',
	'useractivity-group-comment' => '{{PLURAL:$1|تعليق واحد|$1 تعليق}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|رسالة واحدة|$1 رسالة}}',
	'useractivity-group-friend' => '{{PLURAL:$1|صديق واحد|$1 صديق}}',
	'useractivity-siteactivity' => 'نشاط الموقع',
	'useractivity-title' => 'نشاط الأصدقاء',
	'useractivity-user_message' => '$1 {{PLURAL:$4|أرسل رسالة إلى|أرسل رسائل إلى}} $3',
);

/** Finnish (Suomi)
 * @author Jack Phoenix <jack@countervandalism.net>
 */
$messages['fi'] = array(
	'useractivity' => 'Ystävien aktiivisuus',
	'useractivity-award' => '$1 sai palkinnon',
	'useractivity-all' => 'Katso kaikki',
	'useractivity-edit' => '$1 {{PLURAL:$2|muokkasi|muokkasivat}} {{PLURAL:$4|sivua|seuraavia sivuja:}} $3',
	'useractivity-foe' => '$1 {{PLURAL:$2|on nyt vihollinen käyttäjälle|ovat nyt vihollisia käyttäjille}} $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|on nyt ystävä käyttäjälle|ovat nyt ystäviä käyttäjille}} $3',
	'useractivity-gift' => '$1 sai lahjan käyttäjältä $2',
	'useractivity-group-edit' => '{{PLURAL:$1|yksi muokkaus|$1 muokkausta}}',
	'useractivity-group-comment' => '{{PLURAL:$1|yksi kommentti|$1 kommenttia}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|yksi viesti|$1 viestiä}}',
	'useractivity-group-friend' => '{{PLURAL:$1|yksi ystävä|$1 ystävät}}',
	'useractivity-siteactivity' => 'Sivuston aktiivisuus',
	'useractivity-title' => 'Ystävien aktiivisuus',
	'useractivity-user_message' => '$1 {{PLURAL:$4|lähetti viestin käyttäjälle|lähetti viestejä käyttäjille}} $3',
);

/** French (Français)
 * @author IAlex
 * @author Y-M D
 */
$messages['fr'] = array(
	'useractivity' => 'Activité des amis',
	'useractivity-award' => '$1 a reçu une récompense',
	'useractivity-all' => 'Gwelet pep tra',
	'useractivity-edit' => '$1 a modifié {{PLURAL:$4|la page|les pages suivantes :}} $3',
	'useractivity-foe' => '$1 {{PLURAL:$2|est maintenant ennemi|sont maintenant ennemis}} avec $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|est maintenant ami avec|sont maintenant amis avec}} $3',
	'useractivity-gift' => '$1 en deus resevet ur prof a-berzh $2',
	'useractivity-group-edit' => '$1 {{PLURAL:$1|modification|modifications}}',
	'useractivity-group-comment' => '$1 {{PLURAL:$1|commentaire|commentaires}}',
	'useractivity-group-user_message' => '$1 {{PLURAL:$1|message|messages}}',
	'useractivity-group-friend' => '$1 {{PLURAL:$1|ami|amis}}',
	'useractivity-siteactivity' => 'Activité du site',
	'useractivity-title' => 'Activité des amis',
	'useractivity-user_message' => '$1 a envoyé {{PLURAL:$4|un message|des messages}} à $3',
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'useractivity' => 'Aktivitéit vu Frënn',
	'useractivity-award' => '$1 huet eng Auszeechnung kritt',
	'useractivity-all' => 'Alles weisen',
	'useractivity-edit' => '$1 huet dës {{PLURAL:$4|Säit|Säite}} geännert: $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|ass elo e Frënd vum|sinn elo Frënn vum}} $3',
	'useractivity-gift' => '$1 huet e Cadeau vum $2 kritt',
	'useractivity-group-edit' => '{{PLURAL:$1|eng Ännerung|$1 Ännerungen}}',
	'useractivity-group-comment' => '{{PLURAL:$1|eng Bemierkung|$1 Bemierkungen}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|ee Message|$1 Messagen}}',
	'useractivity-group-friend' => '{{PLURAL:$1|ee Frënd|$1 Frënn}}',
	'useractivity-user_message' => '$1 huet dem $3 {{PLURAL:$4|ee Message|Message}} geschéckt',
);

/** Macedonian (Македонски)
 * @author Bjankuloski06
 */
$messages['mk'] = array(
	'useractivity' => 'Активности на пријателите',
	'useractivity-award' => '$1 доби награда',
	'useractivity-all' => 'Види ги сите',
	'useractivity-edit' => '$1 {{PLURAL:$4|ја уреди страницата|ги уреди следниве страници:}} $3',
	'useractivity-foe' => '$1 {{PLURAL:$2|стана непријател со|станаа непријатели со}} $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|се спријатели со|се спријателија со}} $3',
	'useractivity-gift' => '$1 прими подарок од $2',
	'useractivity-group-edit' => '{{PLURAL:$1|едно уредување|$1 уредувања}}',
	'useractivity-group-comment' => '{{PLURAL:$1|еден коментар|$1 коментари}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|една порака|$1 пораки}}',
	'useractivity-group-friend' => '{{PLURAL:$1|еден пријател|$1 пријатели}}',
	'useractivity-siteactivity' => 'Активност на веб-страницата',
	'useractivity-title' => 'Активности на пријателите',
	'useractivity-user_message' => '$1 {{PLURAL:$4|испрати порака на|испрати пораки на}} $3',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'useractivity' => 'Acitiviteit van vrienden',
	'useractivity-award' => '$1 heeft een prijs ontvangen',
	'useractivity-all' => 'Allemaal bekijken',
	'useractivity-edit' => "$1 bewerkte de volgende {{PLURAL:$4|pagina|pagina's}}: $3",
	'useractivity-foe' => '$1 {{PLURAL:$2|is nu een tegenstander|zijn nu tegenstanders}} $3',
	'useractivity-friend' => '$1 {{PLURAL:$2|is|zijn}} nu vrienden met $3',
	'useractivity-gift' => '$1 heeft een gift van $2 ontvangen',
	'useractivity-group-edit' => '$1 {{PLURAL:$1|bewerking|bewerkingen}}',
	'useractivity-group-comment' => '$1 {{PLURAL:$1|opmerking|opmerkingen}}',
	'useractivity-group-user_message' => '$1 {{PLURAL:$1|bericht|berichten}}',
	'useractivity-group-friend' => '$1 {{PLURAL:$1|vriend|vrienden}}',
	'useractivity-siteactivity' => 'Siteactiviteit',
	'useractivity-title' => 'Acitiviteit van vrienden',
	'useractivity-user_message' => '$1 heeft {{PLURAL:$4|een bericht|berichten}} verzonden aan $3',
);

/** Brazilian Portuguese (Português do Brasil)
 * @author Luckas Blade
 */
$messages['pt-br'] = array(
	'useractivity-edit' => '$1 {{PLURAL:$4|editou a página|editou as seguintes páginas:}} $3',
	'useractivity-gift' => '$1 recebeu um presente de $2',
	'useractivity-group-edit' => '{{PLURAL:$1|uma edição|$1 edições}}',
	'useractivity-group-comment' => '{{PLURAL:$1|um comentário|$1 comentários}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|uma mensagem|$1 mensagens}}',
	'useractivity-group-friend' => '{{PLURAL:$1|um amigo|$1 amigos}}',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'useractivity-all' => 'అన్నీ చూడండి',
	'useractivity-edit' => '$1 {{PLURAL:$4|ఈ పేజీని|ఈ పేజీలను}} మార్చారు: $3',
	'useractivity-gift' => '$1 $2 నుండి ఒక బహుమతిని అందుకున్నారు',
	'useractivity-group-edit' => '{{PLURAL:$1|ఒక మార్పు|$1 మార్పులు}}',
	'useractivity-group-comment' => '{{PLURAL:$1|ఒక వ్యాఖ్య|$1 వ్యాఖ్యలు}}',
	'useractivity-group-user_message' => '{{PLURAL:$1|ఒక సందేశం|$1 సందేశాలు}}',
	'useractivity-group-friend' => '{{PLURAL:$1|ఒక స్నేహితుడు|$1 స్నేహితులు}}',
);

