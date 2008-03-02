<?php
/**
 * Internationalisation file for UserRelantionShip extension pages.
 *
 * @addtogroup Extensions
 */

$messages = array();

$messages['en'] = array(
		'viewrelationships' => 'View Relationship',
		'viewrelationshiprequests' => 'View Relationship Requests',
		'ur-error-title'=>'Woops, you took a wrong turn!',
		'ur-error-message-no-user'=>'We cannot complete your request, because no user with this name exists.',
		'ur-main-page'=>'Main Page',
		'ur-your-profile'=>'Your Profile',
		'ur-backlink'=>'&lt; Back to $1\'s Profile',
		'ur-friend'=>'friend',
		'ur-foe'=>'foe',
		'ur-relationship-count'=>'$1 has $2 {{PLURAL:$2|$3|$3s}}.',
		'ur-add-friends'=>' Want more friends? <a href="$1">Invite Them</a>',
		'ur-add-friend'=>'Add as Friend',
		'ur-add-foe'=>'Add as Foe',
		'ur-remove-relationship'=>'Remove as $1',
		'ur-give-gift'=>'Give a Gift',
		'ur-previous'=>'prev',
		'ur-next'=>'next',
		'ur-remove-relationship-title'=>'Do you want to remove $1 as your $2?',
		'ur-remove-relationship-title-confirm'=>'You have removed $1 as your $2',
		'ur-remove-relationship-message'=>'You have requested to remove $1 as your $2, press "$3" to confirm.',
		'ur-remove-relationship-message-confirm'=>'You have successfully removed $1 as your $2.',
		'ur-remove-error-message-no-relationship'=>'You do not have a relationship with $1.',
		'ur-remove-error-message-remove-yourself'=>'You cannot remove yourself.',
		'ur-remove-error-message-pending-request'=>'You have a pending $1 request with $2.',
		'ur-remove-error-not-loggedin'=>'You have to be logged in to remove a $1.',
		'ur-remove'=>'Remove',
		'ur-cancel'=>'Cancel',
		'ur-login'=>"Login",
		'ur-add-title'=>'Do you want to add $1 as your $2?',
		'ur-add-message'=>'You are about to add $1 as your $2.  We will notify $1 to confirm your $3.',
		'ur-friendship'=>'friendship',
		'ur-grudge'=>'grudge',
		'ur-add-button'=>"Add as $1",
		'ur-add-sent-title'=>'We have sent your $1 request to $2!',
		'ur-add-sent-message'=>'Your $1 request has been sent to $2 for confirmation.  If $2 confirms your request, you will receive a follow-up e-mail',
		'ur-add-error-message-no-user'=>'The user you are trying to add does not exist.',
		'ur-add-error-message-blocked'=>'You are currently blocked and cannot add friends or foes.',
		'ur-add-error-message-yourself'=>'You cannot add yourself as a friend or foe.',
		'ur-add-error-message-existing-relationship'=>'You are already $1 with $2.',
		'ur-add-error-message-pending-request-title'=>'Patience!',
		'ur-add-error-message-pending-request'=>'You have a pending $1 request with $2.  We will notify you when $2 confirms your request.',
		'ur-add-error-message-not-loggedin'=>'You must be logged in to add a $1',
		'ur-requests-title'=>'Relationship Requests',
		'ur-requests-message'=>'<a href="$1">$2</a> wants to be your $3.',
		'ur-accept'=>'Accept',
		'ur-reject'=>'Reject',
		'ur-no-requests-message'=>'You have no friend or foe requests.  If you want more friends, <a href="$1">invite them!</a>',
		'ur-requests-added-message'=>'You have added $1 as your $2.',
		'ur-requests-reject-message'=>'You have rejected $1 as your $2.',
		'friend_request_subject' => '$1 has added you as a friend on {{SITENAME}}!',
		'friend_request_body' => 'Hi $1:

$2 has added you as a friend on {{SITENAME}}.  We want to make sure that you two are actually friends.

Please click this link to confirm your friendship:
$3

Thanks

---

Hey, want to stop getting emails from us?

Click $4
and change your settings to disable email notifications.',
		'foe_request_subject' => 'It\'s war! $1 has added you to as a foe on {{SITENAME}}!',
		'foe_request_body' => 'Hi $1:

$2 just listed you as a foe on {{SITENAME}}.  We want to make sure that you two are actually mortal enemies  or at least having an argument.

Please click this link to confirm the grudge match.

$3

Thanks

---

Hey, want to stop getting emails from us?',
		'friend_accept_subject' => '$1 has accepted your friend request on {{SITENAME}}!',
		'friend_accept_body' => 'Hi $1:

$2 has accepted your friend request on {{SITENAME}}!

Check out $2\'s page at $3

Thanks,

---

Hey, want to stop getting emails from us?

Click $4
and change your settings to disable email notifications.',
		'foe_accept_subject' => 'It\'s on! $1 has accepted your foe request on {{SITENAME}}!',
		'foe_accept_body' => 'Hi $1:

$2 has accepted your foe request on {{SITENAME}}!

Check out $2\'s page at $3

Thanks

---

Hey, want to stop getting emails from us?

Click $4
and change your settings to disable email notifications.',
		'friend_removed_subject' => 'Oh No! $1 has removed you as a friend on {{SITENAME}}!',
		'friend_removed_body' => 'Hi $1:

$2 has removed you as a friend on {{SITENAME}}!

Thanks

---

Hey, want to stop getting emails from us?

Click $4
and change your settings to disable email notifications.',
		'foe_removed_subject' => 'Woohoo! $1 has removed you as a foe on {{SITENAME}}!',
		'foe_removed_body' => 'Hi $1:

		$2 has removed you as a foe on {{SITENAME}}!

Perhaps you two are on your way to becoming friends?

Thanks

---

Hey, want to stop getting emails from us?

Click $4
and change your settings to disable email notifications.',
);

/** French (Français)
 * @author Grondin
 */
$messages['fr'] = array(
	'viewrelationships'                          => 'Voir les relations',
	'viewrelationshiprequests'                   => 'Voir les requêtes des relations',
	'ur-error-title'                             => 'Houla, vous avez pris un mauvais virage !',
	'ur-error-message-no-user'                   => 'Nous ne pouvons compléter votre requête, car aucun utilisateur ne porte ce nom.',
	'ur-main-page'                               => 'Accueil',
	'ur-your-profile'                            => 'Votre profile',
	'ur-backlink'                                => '&lt; retour vers le profil de $1',
	'ur-friend'                                  => 'ami',
	'ur-foe'                                     => 'ennemi',
	'ur-relationship-count'                      => '$1 a $2 {{PLURAL:$2|$3|$3s}}.',
	'ur-add-friends'                             => 'Vouloir plus d’amis ? <a href="$1">Inviter les</a>.',
	'ur-add-friend'                              => 'Ajouter comme ami',
	'ur-add-foe'                                 => 'Ajouter comme ennemi',
	'ur-remove-relationship'                     => 'Enlever comme $1',
	'ur-give-gift'                               => 'Envoyer un cadeau',
	'ur-previous'                                => 'préc.',
	'ur-next'                                    => 'suiv.',
	'ur-remove-relationship-title'               => 'Voulez-vous enlever $1 comme votre $2 ?',
	'ur-remove-relationship-title-confirm'       => 'Vous avez enlevez $1 comme votre $2',
	'ur-remove-relationship-message'             => 'Vous avez demandé la suppression de $1 comme votre $2, appuyer sur « $3 » pour confirmer.',
	'ur-remove-relationship-message-confirm'     => 'Vous avez supprimé avec succès $1 comme votre $2.',
	'ur-remove-error-message-no-relationship'    => "Vous n'avez aucune relation avec $1.",
	'ur-remove-error-message-remove-yourself'    => 'Vous ne pouvez pas vous supprimer vous-même.',
	'ur-remove-error-message-pending-request'    => 'Vous avez une requête de $1 en cours avec $2.',
	'ur-remove-error-not-loggedin'               => 'Vous devez être en session pour supprimer un $1.',
	'ur-remove'                                  => 'Enlever',
	'ur-cancel'                                  => 'Annuler',
	'ur-login'                                   => 'Connexion',
	'ur-add-title'                               => 'Voulez-vous ajouter $1 comme votre $2 ?',
	'ur-add-message'                             => 'Vous avez l’intention d’ajouter $1 comme votre $2. Nous le notifierons à $1 pour confirmer votre $3.',
	'ur-friendship'                              => 'amitié',
	'ur-grudge'                                  => 'rancœur',
	'ur-add-button'                              => 'Ajouter comme $1',
	'ur-add-sent-title'                          => 'Vous avez envoyé votre requête en $1 à $2 !',
	'ur-add-sent-message'                        => 'Votre requête en $1 a été à $2 aux fins de confirmation. Si $2 confirme votre demande, vous recevrez un courriel en retour.',
	'ur-add-error-message-no-user'               => 'L’utilisateur que vous être en train d’ajouter n’existe pas.',
	'ur-add-error-message-blocked'               => 'Vous êtes actuellement bloqué et vous ne pouvez donc ajouter ni amis ni ennemis.',
	'ur-add-error-message-yourself'              => 'Vous ne pouvez vous-même vous ajouter comme ennemi ou ami.',
	'ur-add-error-message-existing-relationship' => 'Vous êtes déjà $1 avec $2.',
	'ur-add-error-message-pending-request-title' => 'Patience !',
	'ur-add-error-message-pending-request'       => 'Vous avez une requête en $1 pendante avec $2. Nous vous notifierons quand $2 aura confirmé votre demande.',
	'ur-add-error-message-not-loggedin'          => 'Vous devez être connecté pour ajouter un $1.',
	'ur-requests-title'                          => 'Demandes de relations.',
	'ur-requests-message'                        => '<a href="$1">$2</a> désire être votre $3.',
	'ur-accept'                                  => 'Accepter',
	'ur-reject'                                  => 'Rejeter',
	'ur-no-requests-message'                     => 'Vous n’avez aucune requête en ami ou ennemi. Si vous désirez plus d\'amis, <a href="$1">invitez les !</a>',
	'ur-requests-added-message'                  => 'Vous avez ajouté $1 comme votre $2.',
	'ur-requests-reject-message'                 => 'Vous avez rejeté $1 comme votre $2.',
	'friend_request_subject'                     => '$1 vous a ajouté comme un ami sur {{SITENAME}} !',
	'friend_request_body'                        => 'Salut $1 :

$2 vous a ajouté comme un ami sur {{SITENAME}}. Nous voulons nous assurer que vous êtes tous deux actuellement amis.

Veuillez cliquer sur ce lien pour confirmer votre amitié :
$3

Merci.

---

Hé ! Voulez-vous vous arrêter de recevoir des courriels de notre part ?

Cliquez $4
et modifiez vos préférences pour désactiver les notifications par courriel.',
	'foe_request_subject'                        => "C'est la guerre ! $1 vous a ajouté comme ennemi sur {{SITENAME}} !",
	'foe_request_body'                           => 'Salut $1 :

$2 vient juste de vous répertorier comme un ennemi sur {{SITENAME}}. Nous voulons nous assurer que vous êtes vraiement des emmenis mortel ou avoir au moins des griefs l’un envers l’autre/

Veuillez cliquer sur ce lien, pour accepter, à contrecœur, cet état de fait.

$3

Merci

---

Hé ! Voulez-vous vous arrêter de recevoir des courriels de notre part ?

Cliquez $4 et modifiez vos préférences pour désactiver les notifications par courriel.',
	'friend_accept_subject'                      => '$1 a accepté votre requête en amitié sur {{SITENAME}} !',
	'friend_accept_body'                         => 'Salut $1 : 

$2 a accepté votre requête en amitié sur {{SITENAME}} !

Allez sur la page de $2 sur $3

Merci.

---

Hé ! Voulez-vous vous arrêter de recevoir des courriels de notre part ?

Cliquez $4
et modifiez vos préférences pour désactiver les notifications par courriel.',
	'foe_accept_subject'                         => "C'est fait ! $1 a accepté votre déclaration de guerre sur  {{SITENAME}} !",
	'foe_accept_body'                            => 'Salut $1 : 

$2 a accepté votre déclaration de guerre sur  {{SITENAME}} !

Visitez la page de $2 sur $3.

Merci

---

Hé ! Voulez-vous vous arrêter de recevoir des courriels de notre part ?

Cliquez $4 et modifiez vos préférences pour désactiver les notifications par courriel.',
	'friend_removed_subject'                     => 'Saperlipopette ! $1 vous a retiré de la liste de ses amis sur {{SITENAME}} !',
	'friend_removed_body'                        => 'Salut $1 :

$2 vous a retiré de la liste de ses amis sur {{SITENAME}} !

Merci

---

Hé ! Voulez-vous vous arrêter de recevoir des courriels de notre part ?

Cliquez $4 et modifiez vos préférences pour désactiver les notifications par courriel.',
	'foe_removed_subject'                        => 'Par Jupiter ! $1 vous a retiré de la liste de ses ennemis {{SITENAME}} !',
	'foe_removed_body'                           => 'Salut $1 :

$2 vous a retiré de la liste de ses ennemis sur {{SITENAME}} !

Ne seriez-vous pas, peut-être, sur le chemin pour devenir amis ?

Merci

---

Hé ! Voulez-vous vous arrêter de recevoir des courriels de notre part ?

Cliquez $4
et modifiez vos préférences pour désactiver les notifications par courriel.',
);

/** Khmer (ភាសាខ្មែរ)
 * @author Chhorran
 */
$messages['km'] = array(
	'ur-your-profile' => 'ពត៌មានផ្ទាល់ខ្លួន របស់អ្នក',
	'ur-friend'       => 'មិត្តភក្តិ',
	'ur-add-friend'   => 'បន្ថែម ជា មិត្តភក្តិ',
	'ur-previous'     => 'មុន',
	'ur-next'         => 'បន្ទាប់',
	'ur-remove'       => 'ដកចេញ',
	'ur-cancel'       => 'បោះបង់',
	'ur-login'        => 'ពិនិត្យចូល',
	'ur-friendship'   => 'មិត្តភាព',
	'ur-add-button'   => 'បន្ថែម ជា $1',
	'ur-accept'       => 'ព្រមទទួល',
	'ur-reject'       => 'ទាត់ចោល',
);

/** Dutch (Nederlands)
 * @author Siebrand
 * @author SPQRobin
 */
$messages['nl'] = array(
	'viewrelationships'                          => 'Relaties bekijken',
	'ur-main-page'                               => 'Hoofdpagina',
	'ur-your-profile'                            => 'Uw profiel',
	'ur-backlink'                                => "&lt; Terug naar $1's profiel",
	'ur-friend'                                  => 'vriend',
	'ur-foe'                                     => 'tegenstander',
	'ur-add-friend'                              => 'Als vriend toevoegen',
	'ur-add-foe'                                 => 'Als tegenstander toevoegen',
	'ur-previous'                                => 'vorige',
	'ur-next'                                    => 'volgende',
	'ur-remove-relationship-title-confirm'       => 'U hebt $1 als $2 verwijderd',
	'ur-remove-relationship-message-confirm'     => 'U hebt $1 als $2 verwijderd.',
	'ur-remove'                                  => 'Verwijderen',
	'ur-cancel'                                  => 'Annuleren',
	'ur-login'                                   => 'Aanmelden',
	'ur-friendship'                              => 'vriendschap',
	'ur-grudge'                                  => 'wrok',
	'ur-add-button'                              => 'Als $1 toevoegen',
	'ur-add-error-message-pending-request-title' => 'Even geduld alstublieft.',
	'ur-requests-message'                        => '<a href="$1">$2</a> wil uw $3 zijn.',
	'ur-accept'                                  => 'Aanvaarden',
	'ur-reject'                                  => 'Weigeren',
	'ur-requests-reject-message'                 => 'U hebt $1 geweigerd als $2.',
	'friend_request_subject'                     => '$1 heeft u als vriend toegevoegd op {{SITENAME}}.',
	'foe_request_subject'                        => '$1 heeft u toegevoegd als tegenstander op {{SITENAME}}!',
	'friend_accept_subject'                      => '$1 heeft uw verzoek om vrienden te worden op {{SITENAME}} aanvaard.',
	'foe_accept_subject'                         => '$1 heeft u als tegenstander aanvaard op {{SITENAME}}.',
	'friend_removed_subject'                     => '$1 heeft u helaas verwijderd als vriend op {{SITENAME}}!',
	'foe_removed_subject'                        => '$1 heeft u verwijderd als tegenstander op {{SITENAME}}!',
);

/** Norwegian (bokmål)‬ (‪Norsk (bokmål)‬)
 * @author Jon Harald Søby
 */
$messages['no'] = array(
	'viewrelationships'                          => 'Vis forbindelse',
	'viewrelationshiprequests'                   => 'Vis forespørsler om forbindelse',
	'ur-error-title'                             => 'Ops, du svingte feil.',
	'ur-error-message-no-user'                   => 'Vi kan ikke fullføre forespørselen din fordi det ikke finnes noen brukere ved dette navnet.',
	'ur-main-page'                               => 'Hovedside',
	'ur-your-profile'                            => 'Profilen din',
	'ur-backlink'                                => '&lt; Tilbake til profilen til $1',
	'ur-friend'                                  => 'venn',
	'ur-foe'                                     => 'fiende',
	'ur-relationship-count'                      => '$1 er $3 med $2 andre brukere.',
	'ur-add-friends'                             => 'Vil du ha flere venner? <a href="$1">Inviter dem</a>',
	'ur-add-friend'                              => 'Legg til som venn',
	'ur-add-foe'                                 => 'Legg til som fiende',
	'ur-remove-relationship'                     => 'Fjern som $1',
	'ur-give-gift'                               => 'Gi gave',
	'ur-previous'                                => 'forrige',
	'ur-next'                                    => 'neste',
	'ur-remove-relationship-title'               => 'Vil du fjerne $1 som $2?',
	'ur-remove-relationship-title-confirm'       => 'Du har fjernet $1 som $2',
	'ur-remove-relationship-message'             => 'Du har spurt om å fjerne $1 som $2, trykk «$3» for å bekrefte.',
	'ur-remove-relationship-message-confirm'     => 'Du har fjernet $1 som $2.',
	'ur-remove-error-message-no-relationship'    => 'Du har ingen forbindelse med $1.',
	'ur-remove-error-message-remove-yourself'    => 'Du kan ikke fjerne deg selv.',
	'ur-remove-error-message-pending-request'    => 'Du har en ventende forespørsel om å bli $1 med $2 hos $2.',
	'ur-remove-error-not-loggedin'               => 'Du må logge inn for å fjerne en $1.',
	'ur-remove'                                  => 'Fjern',
	'ur-cancel'                                  => 'Avbryt',
	'ur-login'                                   => 'Logg inn',
	'ur-add-title'                               => 'Vil du legge til $1 som $2?',
	'ur-add-message'                             => 'Du er i ferd med å legge til $1 som $2. Vi vil gi beskjed til $1 slik at vedkommende kan bekrefte deres $3.',
	'ur-friendship'                              => 'vennskap',
	'ur-grudge'                                  => 'fiendeskap',
	'ur-add-button'                              => 'Legg til som $1',
	'ur-add-sent-title'                          => 'Vi har sendt forespørselen din om å bli $1 med $2 til vedkommende.',
	'ur-add-sent-message'                        => 'Forespørselen din om å bli $1 med $2 har blitt sendt til vedkommende for godkjenning. Hvis $2 godkjenner forespørselen vil du få beskjed om det.',
	'ur-add-error-message-no-user'               => 'Brukeren du prøvde å legge til finnes ikke.',
	'ur-add-error-message-blocked'               => 'Du er blokkert, og kan ikke legge til venner eller fiender.',
	'ur-add-error-message-yourself'              => 'Du kan ikke legge til deg selv som venn eller fiende.',
	'ur-add-error-message-existing-relationship' => 'Du er allerede $1 med $2.',
	'ur-add-error-message-pending-request-title' => 'Tålmodighet ...',
	'ur-add-error-message-pending-request'       => 'Du har en ventende forespørsel om å bli $1 med $2. Du vil få beskjed når $2 godkjenner forespørselen.',
	'ur-add-error-message-not-loggedin'          => 'Du må være logget inn for å legge til en $1',
	'ur-requests-title'                          => 'Forbindelsesforespørsler',
	'ur-requests-message'                        => '<a href="$1">$2</a> ønsker å bli $3 med deg.',
	'ur-accept'                                  => 'Godta',
	'ur-reject'                                  => 'Avvis',
	'ur-no-requests-message'                     => 'Du har ingen venne- eller fiendeforespørsler. Om du vil ha flere venner, <a href="$1">inviter dem</a>!',
	'ur-requests-added-message'                  => 'Du har lagt til $1 som $2.',
	'ur-requests-reject-message'                 => 'Du har avvis forespørselen fra $1 om å bli $2 med deg.',
	'friend_request_subject'                     => '$1 har lagt deg til som venn på {{SITENAME}}!',
	'friend_request_body'                        => 'Hei, $1.

$2 har lagt deg til som venn på {{SITENAME}}. Vi vil være sikre på at dere faktisk er venner.

Følg denne lenken for å bekrefte vennskapet deres:
$3

Takk

---

Vil du ikke motta flere e-poster fra oss?

Klikk $4 og endre innstillingene dine for å slå av e-postbeskjeder.',
	'foe_request_subject'                        => 'Det er krig! $1 har lagt deg til som fiende på {{SITENAME}}!',
	'foe_request_body'                           => 'Hei, $1.

$2 har lagt deg til som fiende på {{SITENAME}}. Vi vil forsikre oss om at dere faktisk er svorne fiender &ndash; eller i hvert fall krangler.

Følg lenken nedenunder for å bekrefte fiendeskapet.

$3

Takk

---

Vil du ikke motta flere e-poster fra oss?',
	'friend_accept_subject'                      => '$1 har godtatt din venneforespørsel på {{SITENAME}}.',
	'friend_accept_body'                         => 'Hei, $1.

$2 har godtatt din venneforespørsel på {{SITENAME}}.

Sjekk ut siden til $2 på $3.

Takk.

---

Vil du ikke motta flere e-poster fra oss??

Klikk $4 og endre innstillingene dine for å slå av e-postbeskjeder.',
	'foe_accept_subject'                         => '$1 har godtatt din fiendeforespørsel på {{SITENAME}}.',
	'foe_accept_body'                            => 'Hei, $1.

$2 har godtatt din fiendeforespørsel på {{SITENAME}}.

Sjekk ut siden til $2 på $3

Takk

---

Vil du ikke motta flere e-poster fra oss?',
	'friend_removed_subject'                     => 'Å nei! $1 har fjernet deg som venn på {{SITENAME}}.',
	'friend_removed_body'                        => 'Hei, $1

$2 har fjernet deg som venn på {{SITENAME}}.

---

Vil du ikke motta flere e-poster fra oss?

Klikk $4 og endre innstillingene dine for å slå av e-postbeskjeder.',
	'foe_removed_subject'                        => 'Jippi! $1 har fjernet deg som fiende på {{SITENAME}}.',
	'foe_removed_body'                           => 'Hei, $1.

$2 har fjernet deg som fiende på {{SITENAME}}.

Kanskje dere er på vei til å bli venner?

---

Vil du ikke motta flere e-poster fra oss?

Klikk $4 og endre innstillingene dine for å slå av e-postbeskjeder.',
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'ur-cancel'                                  => 'Anullar',
	'ur-login'                                   => 'Senhal',
	'ur-add-error-message-pending-request-title' => 'Paciéncia!',
	'ur-accept'                                  => 'Acceptar',
	'ur-reject'                                  => 'Regetar',
);

/** Telugu (తెలుగు)
 * @author Veeven
 */
$messages['te'] = array(
	'ur-friend' => 'స్నేహితులు',
	'ur-foe'    => 'శత్రువు',
);

