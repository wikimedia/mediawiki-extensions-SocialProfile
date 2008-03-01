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

/** French (Français)
 * @author Grondin
 */
$messages['fr'] = array(
	'boardblastlogintitle'      => 'Vous devez être en session pour envoyer le tableau en rafale',
	'boardblastlogintext'       => 'Vous devez être en session pour envoyer le tableau en rafales. Cliquez <a href="index.php?title=Special:UserLogin">ici pour ouvrir une session</a>',
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
	'userboard_loggedout'       => 'Vous devez être <a href="$1">en session</a> pour poster des messages à d’autres utilisateurs.',
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

