<?php
/**
 * Internationalisation file for User Board pages
 *
 * @addtogroup Extensions
 */

$messages = array();

$messages['en'] = array(
	'boardblastlogintitle'           => 'You must be logged in to send board blasts',
	'boardblastlogintext'       => 'You must be logged in to send board blasts.  Click <a href="index.php?title=Special:UserLogin">here to login</a>',
	'messagesenttitle'          => 'Messages Sent',
	'boardblasttitle'          => 'Send Board Blast',
	'boardblaststep1'          => 'Step 1 - Write Your Message',
	'boardblastprivatenote'	=> 'All messages will be sent as private messages',
	'boardblaststep2'          => 'Step 2 - Select who you want to send your message too',
	'boardlinkselectall'          => 'Select All',
	'boardlinkunselectall'          => 'Unselect All',
	'boardlinkselectfriends'          => 'Select Friends',
	'boardlinkunselectfriends'          => 'Unselect Friends',
	'boardlinkselectfoes'          => 'Select Foes',
	'boardlinkunselectfoes'          => 'Unselect Foes',
	'boardsendbutton'          => 'Send Board Blast',
	'boardnofriends' 	=> 'You have no friends to send a message to!',
	'messagesentsuccess'	=> 'Your message was successfully sent',
	'userboard' => 'User Board',
	'userboard_noexist' => 'The user you are trying to view does not exist.',
	'userboard_yourboard' => 'Your Board',
	'userboard_owner' => '$1\'s Board',
	'userboard_yourboardwith' => 'Your Board-To-Board with $1',
	'userboard_otherboardwith' => '$1\'s Board-To-Board with $2',
	'userboard_backprofile' => 'Back to $1\'s Profile',
	'userboard_backyourprofile' => 'Back to Your Profile',
	'userboard_boardtoboard' => 'Board-to-Board',
	'userboard_confirmdelete' => 'Are you sure you want to delete this message?',
	'userboard_sendmessage' => 'Send $1 a Message',
	'userboard_delete' => 'Delete',
	'userboard_myboard' => 'My Board',
	'userboard_private' => 'private',
	'userboard_public' => 'public',
	'userboard_messagetype' => 'Message Type',
	'userboard_nextpage' => 'next',
	'userboard_prevpage' => 'prev',
	'userboard_nomessages' => 'No messages.',
	'userboard_sendbutton' => 'send',
	'userboard_loggedout' => 'You must be <a href="$1">logged in</a> to post messages to other users.',
	'userboard_showingmessages' => 'Showing $2-$3 of {{PLURAL:$1|$1 Message|$1 Messages}}',
	'message_received_subject' => '$1 wrote on your board on {{SITENAME}}',
	'message_received_body' => 'Hi $1:

$2 just wrote on your board on {{SITENAME}}!

Click below to check out your board!

$3

---

Hey, want to stop getting emails from us?

Click $4
and change your settings to disable email notifications.'
);

/** Bulgarian (Български)
 * @author DCLXVI
 * @author Borislav
 */
$messages['bg'] = array(
	'messagesenttitle'          => 'Изпратени съобщения',
	'boardblaststep1'           => 'Стъпка 1 - Писане на съобщение',
	'boardblastprivatenote'     => 'Всички съобщения ще бъдат изпращани като лични съобщения',
	'boardblaststep2'           => 'Стъпка 2 - Избиране на потребители, до които да бъде изпратено съобщението',
	'boardnofriends'            => 'Нямате приятели, на които да изпращате съобщения!',
	'messagesentsuccess'        => 'Съобщението беше изпратено успешно',
	'userboard_noexist'         => 'Потребителят, който се опитахте да видите, не съществува.',
	'userboard_backprofile'     => 'Връщане към профила на $1',
	'userboard_backyourprofile' => 'Обратно към профила ми',
	'userboard_confirmdelete'   => 'Необходимо е потвърждение за изтриване на съобщението.',
	'userboard_sendmessage'     => 'Изпращане на съобщение до $1',
	'userboard_delete'          => 'Изтриване',
	'userboard_private'         => 'лично',
	'userboard_public'          => 'публично',
	'userboard_messagetype'     => 'Тип съобщение',
	'userboard_nextpage'        => 'следващи',
	'userboard_prevpage'        => 'предишни',
	'userboard_nomessages'      => 'Няма съобщения.',
	'userboard_sendbutton'      => 'изпращане',
	'userboard_loggedout'       => 'За изпращане на съобщения до другите потребители е необходимо <a href="$1">влизане</a> в системата.',
	'userboard_showingmessages' => 'Показване на $2–$3 от {{PLURAL:$1|$1 съобщение|$1 съобщения}}',
);

/** Finnish (Suomi)
 * @author Jack Phoenix
 * @author Crt
 */
$messages['fi'] = array(
	'boardblastlogintitle'      => 'Sinun tulee olla sisäänkirjautunut lähettääksesi keskustelupläjäyksiä',
	'boardblastlogintext'       => 'Sinun tulee olla sisäänkirjautunut lähettääksesi keskustelupläjäyksiä.  Napsauta <a href="index.php?title=Special:Userlogin">tästä kirjautuaksesi sisään</a>',
	'messagesenttitle'          => 'Viestit lähetetty',
	'boardblasttitle'           => 'Lähetä keskustelupläjäys',
	'boardblaststep1'           => 'Vaihe 1 – Kirjoita viestisi',
	'boardblastprivatenote'     => 'Kaikki viestit lähetetään yksityisviesteinä',
	'boardblaststep2'           => 'Vaihe 2 – Valitse kenelle haluat lähettää viestisi',
	'boardlinkselectall'        => 'Valitse kaikki',
	'boardlinkunselectall'      => 'Poista valinta kaikista',
	'boardlinkselectfriends'    => 'Valitse ystäviä',
	'boardlinkunselectfriends'  => 'Poista valinta ystävistä',
	'boardlinkselectfoes'       => 'Valitse vihollisia',
	'boardlinkunselectfoes'     => 'Poista valinta vihollisista',
	'boardsendbutton'           => 'Lähetä keskustelupläjäys',
	'boardnofriends'            => 'Sinulla ei ole ystäviä, joille lähettää viestejä!',
	'messagesentsuccess'        => 'Viestisi lähetettiin onnistuneesti',
	'userboard'                 => 'Käyttäjän keskustelualue',
	'userboard_noexist'         => 'Käyttäjää, jota yrität katsoa ei ole olemassa.',
	'userboard_yourboard'       => 'Oma keskustelualueeni',
	'userboard_owner'           => '{{GRAMMAR:genitive|$1}} keskustelualue',
	'userboard_yourboardwith'   => 'Sinun keskustelualueelta-keskustelualueelle käyttäjän $1 kanssa',
	'userboard_otherboardwith'  => '{{GRAMMAR:genitive|$1}} keskustelualueelta-keskustelualueelle käyttäjän $2 kanssa',
	'userboard_backprofile'     => 'Takaisin {{GRAMMAR:genitive|$1}} profiiliin',
	'userboard_backyourprofile' => 'Takaisin käyttäjäprofiiliisi',
	'userboard_boardtoboard'    => 'Keskustelualueelta-keskustelualueelle',
	'userboard_confirmdelete'   => 'Oletko varma, että haluat poistaa tämän viestin?',
	'userboard_sendmessage'     => 'Lähetä käyttäjälle $1 viesti',
	'userboard_delete'          => 'Poista',
	'userboard_myboard'         => 'Keskustelualueeni',
	'userboard_private'         => 'yksityinen',
	'userboard_public'          => 'julkinen',
	'userboard_messagetype'     => 'Viestin tyyppi',
	'userboard_nextpage'        => 'seuraava',
	'userboard_prevpage'        => 'edellinen',
	'userboard_nomessages'      => 'Ei viestejä.',
	'userboard_sendbutton'      => 'lähetä',
	'userboard_loggedout'       => 'Sinun tulee olla <a href="$1">kirjautunut sisään</a> lähettääksesi viestejä toisille käyttäjille.',
	'userboard_showingmessages' => 'Näkyvillä $2-$3 viestiä (yhteensä {{PLURAL:$1|$1 viesti|$1 viestiä}})',
	'message_received_subject'  => '$1 kirjoitti keskustelualueellesi {{GRAMMAR:inessive|{{SITENAME}}}}',
	'message_received_body'     => 'Hei $1:
 
$2 juuri kirjoitti keskustelualueellesi {{GRAMMAR:inessive|{{SITENAME}}}}!
 
Napsauta alapuolella olevaa linkki tarkistaaksesi keskustelualueesi!
 
$3
 
---
 
Hei, etkö halua enää saada sähköposteja meiltä?  
 
Napsauta $4
ja muuta asetuksiasi poistaaksesi sähköpostitoiminnot käytöstä.',
);

/** French (Français)
 * @author Grondin
 */
$messages['fr'] = array(
	'boardblastlogintitle'      => 'Vous devez être connecté pour envoyer le tableau en rafale',
	'boardblastlogintext'       => 'Vous devez être connecté pour envoyer le tableau en rafales. Cliquez <a href="index.php?title=Special:UserLogin">ici pour ouvrir vous connecter</a>',
	'messagesenttitle'          => 'Messages envoyés',
	'boardblasttitle'           => 'Envoyer le tableau en rafale',
	'boardblaststep1'           => 'Étape 1 - Écrivez votre message',
	'boardblastprivatenote'     => 'Tous les messages seront envoyés comme des messages privés',
	'boardblaststep2'           => 'Étape 2 - Sélectionnez aussi à qui vous voulez envoyer votre message',
	'boardlinkselectall'        => 'Tout sélectionner',
	'boardlinkunselectall'      => 'Tout déselectionner',
	'boardlinkselectfriends'    => 'Sélectionnez les amis',
	'boardlinkunselectfriends'  => 'Désélectionner les amis',
	'boardlinkselectfoes'       => 'Sélectionner les ennemis',
	'boardlinkunselectfoes'     => 'Désélectionner les ennemis',
	'boardsendbutton'           => 'Envoyez le tableau en rafale',
	'boardnofriends'            => 'Vous n’avez aucun ami à qui envoyer le message',
	'messagesentsuccess'        => 'Votre message a été envoyé avec succès',
	'userboard'                 => 'Tableau utilisateur',
	'userboard_noexist'         => 'L’utilisateur que vous êtes en train d’essayer de visionner n’existe pas.',
	'userboard_yourboard'       => 'Votre tableau',
	'userboard_owner'           => 'Le tableau de $1',
	'userboard_yourboardwith'   => 'Votre tableau à tableau avec $1',
	'userboard_otherboardwith'  => 'Le tableau à tableau de $1 avec $2',
	'userboard_backprofile'     => 'Retour vers le profil de $1',
	'userboard_backyourprofile' => 'Retour vers votre profil',
	'userboard_boardtoboard'    => 'Tableau à tableau',
	'userboard_confirmdelete'   => 'Êtes-vous certain de vouloir supprimer ce message ?',
	'userboard_sendmessage'     => 'Envoyer un message à $1',
	'userboard_delete'          => 'Supprimer',
	'userboard_myboard'         => 'Mon tableau',
	'userboard_private'         => 'privé',
	'userboard_public'          => 'public',
	'userboard_messagetype'     => 'Type de message',
	'userboard_nextpage'        => 'suivant',
	'userboard_prevpage'        => 'précédent',
	'userboard_nomessages'      => 'Aucun message.',
	'userboard_sendbutton'      => 'envoyé',
	'userboard_loggedout'       => 'Vous devez être <a href="$1">connecté</a> pour poster des messages à d’autres utilisateurs.',
	'userboard_showingmessages' => 'Visionnement de $2-$3 de {{PLURAL:$1|$1 message|$1 messages}}',
	'message_received_subject'  => '$1 a écrit sur votre tableau sur {{SITENAME}}',
	'message_received_body'     => "Salut $1 : 

$2 vient juste d'écrire sur votre tableau sur {{SITENAME}} !

Cliquez sur le lien ci-dessous pour allez sur votre tableau !

$3

---

Hé ! Voulez-vous arrêter d’obtenir, de nous, les courriels ?

Cliquer $4
et modifiez vos paramètres pour désactiver les notifications des courriels.",
);

/** Luxembourgish (Lëtzebuergesch)
 * @author Robby
 */
$messages['lb'] = array(
	'messagesenttitle'        => 'Geschéckte Messagen',
	'boardblaststep1'         => '1. Schrëtt: Schreiwt äre Message',
	'boardblastprivatenote'   => 'All Message ginn als privat Message verschéckt',
	'boardblaststep2'         => '2. Schrëtt: Wielt aus wien Dir äre Message schöcke wellt',
	'boardlinkselectall'      => 'Alles uwielen',
	'boardlinkselectfriends'  => 'Frënn auswielen',
	'boardlinkselectfoes'     => 'Géigner auswielen',
	'boardnofriends'          => 'Dir hutt keng Frënn deenen dir ee Message schécke kënnt!',
	'userboard'               => 'Benotzertafel',
	'userboard_noexist'       => 'De Benotzer den Dir wëllt gesi gëtt et net.',
	'userboard_yourboard'     => 'Är Tafel',
	'userboard_owner'         => 'Dem $1 seng Tafel',
	'userboard_confirmdelete' => 'Sidd Dir sécher datt Dir dëse Message läsche wellt?',
	'userboard_sendmessage'   => 'Dem $1 ee Message schécken',
	'userboard_delete'        => 'Läschen',
	'userboard_myboard'       => 'Meng Tafel',
	'userboard_private'       => 'privat',
	'userboard_public'        => 'ëffentlech',
	'userboard_messagetype'   => 'Typ vu Message',
	'userboard_nextpage'      => 'nächst',
	'userboard_prevpage'      => 'vireg',
	'userboard_nomessages'    => 'Keng Messagen',
	'userboard_sendbutton'    => 'geschéckt',
);

/** Marathi (मराठी)
 * @author Mahitgar
 */
$messages['mr'] = array(
	'messagesenttitle'      => 'संदेश पाठवले',
	'boardblastprivatenote' => 'सर्व संदेश खाजगी संदेश स्वरूपात पाठवले जातील',
	'boardlinkselectall'    => 'सगळे निवडा',
	'boardlinkunselectall'  => 'सगळी निवड रद्द करा',
	'userboard_delete'      => 'वगळा',
	'userboard_private'     => 'खासगी',
	'userboard_public'      => 'सार्वजनीक',
	'userboard_nextpage'    => 'पुढे',
	'userboard_prevpage'    => 'मागे',
	'userboard_sendbutton'  => 'पाठवा',
);

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'boardblastlogintitle'      => 'U moet aangemeld zijn om berichten naar meerdere gebruikers te kunnen verzenden',
	'boardblastlogintext'       => 'U moet aangemeld zijn om berichten naar meerdere gebruikers te kunnen verzenden. Klik <a href="index.php?title=Special:UserLogin">hier om aan te melden</a>',
	'messagesenttitle'          => 'Bericht verstuurd',
	'boardblasttitle'           => 'Bericht aan meerdere gebruikers verzenden',
	'boardblaststep1'           => 'Stap 1: uw bericht schrijven',
	'boardblastprivatenote'     => 'Alle berichten worden verzonden als privéberichten',
	'boardblaststep2'           => 'Stap 2: ontvangers van uw bericht selecteren',
	'boardlinkselectall'        => 'Alles selecteren',
	'boardlinkunselectall'      => 'Alles deselecteren',
	'boardlinkselectfriends'    => 'Vrienden selecteren',
	'boardlinkunselectfriends'  => 'Vrienden deselecteren',
	'boardlinkselectfoes'       => 'Tegenstanders selecteren',
	'boardlinkunselectfoes'     => 'Tegenstanders deselecteren',
	'boardsendbutton'           => 'Bericht naar meerdere gebruikers verzenden',
	'boardnofriends'            => 'U hebt geen vrienden om een bericht aan te zenden!',
	'messagesentsuccess'        => 'Uw bericht is verzonden',
	'userboard'                 => 'Gebruikersboard',
	'userboard_noexist'         => 'De gebruiker die u wilt bekijken bestaat niet.',
	'userboard_yourboard'       => 'Mijn board',
	'userboard_owner'           => 'Board van $1',
	'userboard_yourboardwith'   => 'Uw board-naar-board met $1',
	'userboard_otherboardwith'  => 'Board-naar-board van $1 met $2',
	'userboard_backprofile'     => 'Terug naar het profiel van $1',
	'userboard_backyourprofile' => 'Terug naar uw profiel',
	'userboard_boardtoboard'    => 'Board-naar-board',
	'userboard_confirmdelete'   => 'Wilt u dit bericht inderdaad verwijderen?',
	'userboard_sendmessage'     => '$1 een bericht zenden',
	'userboard_delete'          => 'Verwijderen',
	'userboard_myboard'         => 'Mijn board',
	'userboard_private'         => 'persoonlijk',
	'userboard_public'          => 'publiek',
	'userboard_messagetype'     => 'Berichttype',
	'userboard_nextpage'        => 'volgende',
	'userboard_prevpage'        => 'vorige',
	'userboard_nomessages'      => 'Geen berichten.',
	'userboard_sendbutton'      => 'verzenden',
	'userboard_loggedout'       => 'U moet <a href="$1">aangemeld</a> zijn om berichten naar andere gebruikers te verzenden.',
	'userboard_showingmessages' => 'Berichten $2 tot $3 van {{PLURAL:$1|$1 bericht|$1 berichten}} worden getoond',
	'message_received_subject'  => '$1 heeft op uw board op {{SITENAME}} geschreven',
	'message_received_body'     => 'Hallo $1.

$2 heeft net een bericht achtergelaten op uw board op {{SITENAME}}!

Klik op de onderstaande link om uw board te beijken!

$3

---

Wilt u niet langer e-mails van ons ontvangen?

Klik $4
en wijzig uw instellingen om e-mailberichten uit te schakelen.',
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'boardblastlogintitle'      => 'Du må være logget inn for å sende meldinger',
	'boardblastlogintext'       => 'Du må være logget inn for å sende meldinger. Gå <a href="index.php?title=Special:Userlogin">hit for å logge inn</a>',
	'messagesenttitle'          => 'Sendte beskjeder',
	'boardblasttitle'           => 'Send melding',
	'boardblaststep1'           => 'Steg 1 &ndash; skriv beskjeden din',
	'boardblastprivatenote'     => 'Alle meldinger vil være private',
	'boardblaststep2'           => 'Steg 2 &ndash; velg hvem du vil sende meldingen til',
	'boardlinkselectall'        => 'Merk alle',
	'boardlinkunselectall'      => 'Fjern all merking',
	'boardlinkselectfriends'    => 'Merk venner',
	'boardlinkunselectfriends'  => 'Fjern merking av venner',
	'boardlinkselectfoes'       => 'Merk fiender',
	'boardlinkunselectfoes'     => 'Fjern merking av fiender',
	'boardsendbutton'           => 'Send melding',
	'boardnofriends'            => 'Du har ingen venner å sende beskjed til.',
	'messagesentsuccess'        => 'Beskjeden din ble sendt',
	'userboard'                 => 'Brukerdiskusjon',
	'userboard_noexist'         => 'Brukeren du prøver å se finnes ikke.',
	'userboard_yourboard'       => 'Din diskusjonsside',
	'userboard_owner'           => 'Diskusjonssiden til $1',
	'userboard_yourboardwith'   => 'Din delte diskusjonsside med $1',
	'userboard_otherboardwith'  => 'Delt diskusjonsside mellom $1 og $2',
	'userboard_backprofile'     => 'Tilbake til profilen til $1',
	'userboard_backyourprofile' => 'Tilbake til profilen din',
	'userboard_boardtoboard'    => 'Delt diskusjonsside',
	'userboard_confirmdelete'   => 'Er du sikker på at du vil slette denne beskjeden?',
	'userboard_sendmessage'     => 'Sendte en beskjed til $1',
	'userboard_delete'          => 'Slett',
	'userboard_myboard'         => 'Min diskusjonsside',
	'userboard_private'         => 'privat',
	'userboard_public'          => 'offentlig',
	'userboard_messagetype'     => 'Beskjedtype',
	'userboard_nextpage'        => 'neste',
	'userboard_prevpage'        => 'forrige',
	'userboard_nomessages'      => 'Ingen beskjeder.',
	'userboard_sendbutton'      => 'send',
	'userboard_loggedout'       => 'Du må være <a href="$1">logget inn</a> for å sende beskjeder til andre brukere.',
	'userboard_showingmessages' => 'Viser $2&ndash;$3 av {{PLURAL:$1|$1 beskjed|$1 beskjeder}}',
	'message_received_subject'  => '$1 har skrevet på diskusjonssiden din på {{SITENAME}}',
	'message_received_body'     => 'Hei, $1.

$2 har skrevet på diskusjonssiden din på {{SITENAME}}.

Følg lenken nedenfor for å se diskusjonssiden din.

$3

---

Vil du ikke motta flere e-poster fra oss?

Klikk $4 og endre innstillingene dine for å slå av e-postbeskjeder.',
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'boardblastlogintitle'      => 'Devètz èsser en session per mandar lo tablèu en mitralhada',
	'boardblastlogintext'       => 'Devètz èsser en session per mandar lo tablèu en mitralhadas. Clicatz <a href="index.php?title=Special:UserLogin">aicí per dobrir una session</a>',
	'messagesenttitle'          => 'Messatges mandats',
	'boardblasttitle'           => 'Mandar lo tablèu en mitralhada',
	'boardblaststep1'           => 'Etapa 1 - Escrivètz vòstre messatge',
	'boardblastprivatenote'     => 'Totes los messatges seràn mandats coma de messatges privats',
	'boardblaststep2'           => 'Etapa 2 - Seleccionatz tanben a qui volètz mandar vòstre messatge',
	'boardlinkselectall'        => 'Seleccionar tot',
	'boardlinkunselectall'      => 'Deseleccionar tot',
	'boardlinkselectfriends'    => 'Seleccionatz los amics',
	'boardlinkunselectfriends'  => 'Deseleccionatz los amics',
	'boardlinkselectfoes'       => 'Seleccionatz los enemics',
	'boardlinkunselectfoes'     => 'Deseleccionatz los enemics',
	'boardsendbutton'           => 'Mandaz lo tablèu en mitralhada',
	'boardnofriends'            => "Avètz pas cap d'amic a qui mandar lo messatge",
	'messagesentsuccess'        => 'Vòstre messatge es estat mandat amb succès',
	'userboard'                 => "Tablèu d'utilizaire",
	'userboard_noexist'         => 'L’utilizaire que sètz a ensajar de visionar existís pas.',
	'userboard_yourboard'       => 'Vòstre tablèu',
	'userboard_owner'           => 'Lo tablèu de $1',
	'userboard_yourboardwith'   => 'Vòstre tablèu a tablèu amb $1',
	'userboard_otherboardwith'  => 'Lo tablèu a tablèu de $1 amb $2',
	'userboard_backprofile'     => 'Retorn vèrs lo perfil de $1',
	'userboard_backyourprofile' => 'Retorn vèrs vòstre perfil',
	'userboard_boardtoboard'    => 'Tablèu a tablèu',
	'userboard_confirmdelete'   => 'Sètz segur que volètz suprimir aqueste messatge ?',
	'userboard_sendmessage'     => 'Mandar un messatge a $1',
	'userboard_delete'          => 'Suprimir',
	'userboard_myboard'         => 'Mon tablèu',
	'userboard_private'         => 'privat',
	'userboard_public'          => 'public',
	'userboard_messagetype'     => 'Tipe de messatge',
	'userboard_nextpage'        => 'seguent',
	'userboard_prevpage'        => 'precedent',
	'userboard_nomessages'      => 'Pas de messatge.',
	'userboard_sendbutton'      => 'mandat',
	'userboard_loggedout'       => 'Devètz èsser <a href="$1">connectat</a> per mandar de messatges a d’autres utilizaires.',
	'userboard_showingmessages' => 'Visionament de $2-$3 de {{PLURAL:$1|$1 messatge|$1 messatges}}',
	'message_received_subject'  => '$1 a escrich sus vòstre tablèu sus {{SITENAME}}',
	'message_received_body'     => "Adiu $1 : 

$2 ven just d'escriure sus vòstre tablèu sus {{SITENAME}} !

Clicatz sul ligam çaijós per anar sus vòstre tablèu !

$3

---

E ! Volètz arrestar d’obténer de corrièrs de nòstra part ?

Clicatz $4
e modificatz vòstres paramètres per desactivar las notificacions dels corrièrs electronics.",
);

/** Russian (Русский)
 * @author .:Ajvol:.
 */
$messages['ru'] = array(
	'boardblastlogintitle'      => 'Нужно представиться системе',
	'boardblastlogintext'       => 'Вы должны представиться системе, чтобы отправлять высказывания на доски. Щёлкните <a href="index.php?title=Special:UserLogin">здесь, чтобы войти в систему</a>.',
	'messagesenttitle'          => 'Сообщение отправлено',
	'boardblasttitle'           => 'Отправка высказывания на доску',
	'boardblaststep1'           => 'Шаг 1 - Напишите ваше сообщение',
	'boardblastprivatenote'     => 'Все сообщения буду отправляться как личные',
	'boardblaststep2'           => 'Шаг 2 - Выберите комы вы хотите отправить сообщение',
	'boardlinkselectall'        => 'Выбрать всех',
	'boardlinkunselectall'      => 'Снять выделение',
	'boardlinkselectfriends'    => 'Выбрать друзей',
	'boardlinkunselectfriends'  => 'Исключить друзей',
	'boardlinkselectfoes'       => 'Выбрать непрителей',
	'boardlinkunselectfoes'     => 'Исключить неприятелей',
	'boardsendbutton'           => 'Отправить высказывание на доску',
	'boardnofriends'            => 'У вас нет друзей, для которых можно отправить сообщение.',
	'messagesentsuccess'        => 'Ваше сообщение было успешно отправлено',
	'userboard'                 => 'Доска участника',
	'userboard_noexist'         => 'Участника, которого вы пытаетесь просмотреть, не существует.',
	'userboard_yourboard'       => 'Ваша доска',
	'userboard_owner'           => 'Доска участника $1',
	'userboard_yourboardwith'   => 'Ваше доска-на-доску с $1',
	'userboard_otherboardwith'  => 'Доска-на-доску участника $1 с $2',
	'userboard_backprofile'     => 'Назад к очерку участника $1',
	'userboard_backyourprofile' => 'Назад к вашему очерку',
	'userboard_boardtoboard'    => 'Доска-на-доску',
	'userboard_confirmdelete'   => 'Вы уверены, что хотите удалить это сообщение?',
	'userboard_sendmessage'     => 'Отправить сообщение $1',
	'userboard_delete'          => 'Удалить',
	'userboard_myboard'         => 'Моя доска',
	'userboard_private'         => 'личное',
	'userboard_public'          => 'общедоступное',
	'userboard_messagetype'     => 'Тип сообщения',
	'userboard_nextpage'        => 'след.',
	'userboard_prevpage'        => 'пред.',
	'userboard_nomessages'      => 'Нет сообщений.',
	'userboard_sendbutton'      => 'отправить',
	'userboard_loggedout'       => 'Вы должны быть <a href="$1">представлены системе</a>, чтобы отправлять сообщения другим участникам.',
	'userboard_showingmessages' => 'Отображение $2-$3 из {{PLURAL:$1|$1 сообщения|$1 сообщений|$1 сообщений}}',
	'message_received_subject'  => '$1 написал(а) на вашу доску на сайте {{SITENAME}}',
	'message_received_body'     => 'Привет, $1:

$1 написал(а) на вашу доску на сайте {{SITENAME}}!

Щёлкните ниже, чтобы просмотреть вашу доску!

$3

---

Не хотите больше получать писем от нас?

Нажмите $4
и измените ваши настройки, отключив отправку уведомлений.',
);

/** Slovak (Slovenčina)
 * @author Helix84
 */
$messages['sk'] = array(
	'boardblastlogintitle'      => 'Musíte sa prihlásiť, aby ste mohli posielať správy fóra',
	'boardblastlogintext'       => 'Musíte sa prihlásiť, aby ste mohli posielať správy fóra. Kliknutím sem <a href="index.php?title=Special:UserLogin">sa prihlásite</a>',
	'messagesenttitle'          => 'Poslaných správ',
	'boardblasttitle'           => 'Poslať správu fóra',
	'boardblaststep1'           => 'Krok 1 - Napíšte svoju správu',
	'boardblastprivatenote'     => 'Všetky správy sa pošlú ako súkromné správy',
	'boardblaststep2'           => 'Krok 2 - Vybete, komu svoju správu chcete poslať',
	'boardlinkselectall'        => 'Vybrať všetkých',
	'boardlinkunselectall'      => 'Zrušiť výber',
	'boardlinkselectfriends'    => 'Vybrať priateľov',
	'boardlinkunselectfriends'  => 'Zrušiť výber priateľov',
	'boardlinkselectfoes'       => 'Vybrať nepriateľov',
	'boardlinkunselectfoes'     => 'Zrušiť výber nepriateľov',
	'boardsendbutton'           => 'Poslať správu fóra',
	'boardnofriends'            => 'Nemáte žiadnych priateľov, ktorým by ste mohli poslať správu!',
	'messagesentsuccess'        => 'Vaša správa bola úspešne odoslaná',
	'userboard'                 => 'Používateľské fórum',
	'userboard_noexist'         => 'Používateľ, ktorého sa pokúšate zobraziť, neexistuje.',
	'userboard_yourboard'       => 'Vaše fórum',
	'userboard_owner'           => 'Fórum používateľa $1',
	'userboard_yourboardwith'   => 'Vaše fórum s používateľom $1',
	'userboard_otherboardwith'  => 'Fórum používateľa $1 s používateľom $2',
	'userboard_backprofile'     => 'Späť na profil používateľa $1',
	'userboard_backyourprofile' => 'Späť na váš profil',
	'userboard_boardtoboard'    => 'Fórum s používateľom',
	'userboard_confirmdelete'   => 'Ste si istý, že chcete zmazať túto správu?',
	'userboard_sendmessage'     => 'Poslať správu používateľovi $1',
	'userboard_delete'          => 'Zmazať',
	'userboard_myboard'         => 'Moje fórum',
	'userboard_private'         => 'súkromné',
	'userboard_public'          => 'verejné',
	'userboard_messagetype'     => 'Typ správy',
	'userboard_nextpage'        => 'ďal',
	'userboard_prevpage'        => 'pred',
	'userboard_nomessages'      => 'Žiadne správy.',
	'userboard_sendbutton'      => 'poslať',
	'userboard_loggedout'       => 'Musíte <a href="$1">sa prihlásiť</a>, aby ste mohli posielať správy iným používateľom.',
);

/** Swedish (Svenska)
 * @author M.M.S.
 */
$messages['sv'] = array(
	'userboard_delete'     => 'Ta bort',
	'userboard_nextpage'   => 'nästa',
	'userboard_nomessages' => 'Inga meddelanden.',
	'userboard_sendbutton' => 'sänd',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'messagesenttitle'        => 'సందేశాలను పంపించాం',
	'boardlinkselectall'      => 'అందరినీ ఎంచుకోండి',
	'boardlinkselectfriends'  => 'స్నేహితులను ఎంచుకోండి',
	'boardlinkselectfoes'     => 'శత్రువులను ఎంచుకోండి',
	'messagesentsuccess'      => 'మీ సందేశాన్ని విజయవంతంగా పంపించాం',
	'userboard_confirmdelete' => 'ఈ సందేశాన్ని మీరు తొలగించాలనుకుంటున్నారా?',
	'userboard_delete'        => 'తొలగించు',
	'userboard_private'       => 'అంతరంగికం',
	'userboard_public'        => 'బహిరంగం',
	'userboard_messagetype'   => 'సందేశపు రకం',
	'userboard_nextpage'      => 'తర్వాతి',
	'userboard_nomessages'    => 'సందేశాలు లేవు.',
	'userboard_sendbutton'    => 'పంపించు',
);

/** Vietnamese (Tiếng Việt)
 * @author Minh Nguyen
 */
$messages['vi'] = array(
	'boardlinkselectall'   => 'Chọn tất cả',
	'userboard_delete'     => 'Xóa',
	'userboard_public'     => 'công khai',
	'userboard_nextpage'   => 'sau',
	'userboard_prevpage'   => 'trước',
	'userboard_nomessages' => 'Không có tin nhắn.',
	'userboard_sendbutton' => 'gửi',
);

