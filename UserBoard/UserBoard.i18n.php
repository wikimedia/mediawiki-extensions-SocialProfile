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
 */
$messages['bg'] = array(
	'userboard_backprofile'     => 'Връщане към профила на $1',
	'userboard_nomessages'      => 'Няма съобщения.',
	'userboard_showingmessages' => 'Показване на $2-$3 от {{PLURAL:$1|$1 съобщение|$1 съобщения}}',
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

/** Dutch (Nederlands)
 * @author Siebrand
 */
$messages['nl'] = array(
	'messagesenttitle'         => 'Bericht verstuurd',
	'boardlinkselectall'       => 'Alles selecteren',
	'boardlinkunselectall'     => 'Alles deselecteren',
	'boardlinkselectfriends'   => 'Vrienden selecteren',
	'boardlinkunselectfriends' => 'Vrienden deselecteren',
	'boardlinkselectfoes'      => 'Tegenstanders selecteren',
	'boardlinkunselectfoes'    => 'Tegenstanders deselecteren',
	'userboard_yourboard'      => 'Mijn board',
	'userboard_delete'         => 'Verwijderen',
	'userboard_myboard'        => 'Mijn board',
	'userboard_private'        => 'persoonlijk',
	'userboard_public'         => 'publiek',
	'userboard_messagetype'    => 'Berichttype',
	'userboard_nextpage'       => 'volgende',
	'userboard_prevpage'       => 'vorige',
	'userboard_nomessages'     => 'Geen berichten.',
	'userboard_sendbutton'     => 'verzenden',
	'message_received_body'    => 'Hallo $1.

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
	'boardblastlogintitle'     => 'Devètz èsser en session per mandar lo tablèu en mitralhada',
	'boardblastlogintext'      => 'Devètz èsser en session per mandar lo tablèu en mitralhadas. Clicatz <a href="index.php?title=Special:UserLogin">aicí per dobrir una session</a>',
	'messagesenttitle'         => 'Messatges mandats',
	'boardblasttitle'          => 'Mandar lo tablèu en mitralhada',
	'boardblaststep1'          => 'Etapa 1 - Escrivètz vòstre messatge',
	'boardblastprivatenote'    => 'Totes los messatges seràn mandats coma de messatges privats',
	'boardblaststep2'          => 'Etapa 2 - Seleccionatz tanben a qui volètz mandar vòstre messatge',
	'boardlinkselectall'       => 'Seleccionar tot',
	'boardlinkunselectall'     => 'Deseleccionar tot',
	'boardlinkselectfriends'   => 'Seleccionatz los amics',
	'boardlinkunselectfriends' => 'Deseleccionatz los amics',
	'boardlinkselectfoes'      => 'Seleccionatz los enemics',
	'boardlinkunselectfoes'    => 'Deseleccionatz los enemics',
	'boardsendbutton'          => 'Mandaz lo tablèu en mitralhada',
	'boardnofriends'           => "Avètz pas cap d'amic a qui mandar lo messatge",
	'messagesentsuccess'       => 'Vòstre messatge es estat mandat amb succès',
	'userboard_delete'         => 'Suprimir',
	'userboard_private'        => 'privat',
	'userboard_public'         => 'public',
	'userboard_nextpage'       => 'seguent',
	'userboard_prevpage'       => 'precedent',
	'userboard_nomessages'     => 'Pas de messatge.',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'userboard_delete'     => 'తొలగించు',
	'userboard_private'    => 'అంతరంగికం',
	'userboard_public'     => 'బహిరంగం',
	'userboard_nextpage'   => 'తర్వాతి',
	'userboard_nomessages' => 'సందేశాలు లేవు.',
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
