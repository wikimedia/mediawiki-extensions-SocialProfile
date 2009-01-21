<?php
/**
 * Internationalization file for the UserGifts extension.
 *
 * @file
 * @ingroup Extensions
 */

$messages = array();

/** English
 * @author Wikia, Inc.
 */
$messages['en'] = array(
	'giftmanager' => 'Gifts Manager',
	'giftmanager-addgift' => '+ Add New Gift',
	'giftmanager-access' => 'gift access',
	'giftmanager-description' => 'gift description',
	'giftmanager-giftimage' => 'gift image',
	'giftmanager-image' => 'add/replace image',
	'giftmanager-giftcreated' => 'The gift has been created',
	'giftmanager-giftsaved' => 'The gift has been saved',
	'giftmanager-public' => 'public',
	'giftmanager-private' => 'private',
	'giftmanager-view' => 'View Gift List',
	'g-add-message' => 'Add a Message',
	'g-back-edit-gift' => 'Back to Edit This Gift',
	'g-back-gift-list' => 'Back to Gift List',
	'g-back-link' => '<a href="$1">< Back to $2\'s Page</a>',
	'g-choose-file' => 'Choose File:',
	'g-cancel' => 'Cancel',
	'g-count' => '$1 has $2 {{PLURAL:$2|gift|gifts}}.',
	'g-create-gift' => 'Create gift',
	'g-created-by' => 'created by',
	'g-current-image' => 'Current Image',
	'g-delete-message' => 'Are your sure you want to delete the gift "$1"? This will also delete it from users who may have received it.',
	'g-description-title' => '$1\'s gift "$2"',
	'g-error-do-not-own' => 'You do not own this gift.',
	'g-error-message-blocked' => 'You are currently blocked and cannot give gifts',
	'g-error-message-invalid-link' => 'The link you have entered is invalid.',
	'g-error-message-login' => 'You must log-in to give gifts',
	'g-error-message-no-user' => 'The user you are trying to view does not exist.',
	'g-error-message-to-yourself' => 'You cannot give a gift to yourself.',
	'g-error-title' => 'Woops, you took a wrong turn!',
	'g-file-instructions' => 'Your image must be a jpeg, png or gif (no animated gifs), and must be less than 100kb in size.',
	'g-from' => 'from <a href="$1">$2</a>',
	'g-gift' => 'gift',
	'g-gift-name' => 'gift name',
	'g-give-gift' => 'Give Gift',
	'g-give-all' => 'Want to give $1 a gift? Just click one of the gifts below and click "Send Gift." It\'s that easy.',
	'g-give-all-message-title' => 'Add a Message',
	'g-give-all-title' => 'Give a gift to $1',
	'g-give-enter-friend-title' => 'If you know the name of the user, type it in below',
	'g-given' => 'This gift has been given out $1 {{PLURAL:$1|time|times}}',
	'g-give-list-friends-title' => 'Select from your list of friends',
	'g-give-list-select' => 'select a friend',
	'g-give-separator' => 'or',
	'g-give-no-user-message' => 'Gifts and awards are a great way to acknowledge your friends!',
	'g-give-no-user-title' => 'Who would you like to give a gift to?',
	'g-give-to-user-title' => 'Send the gift "$1" to $2',
	'g-give-to-user-message' => 'Want to give $1 a different gift? <a href="$2">Click Here</a>.',
	'g-go-back' => 'Go Back',
	'g-imagesbelow' => 'Below are your images that will be used on the site',
	'g-large' => 'Large',
	'g-list-title' => '$1\'s Gift List',
	'g-main-page' => 'Main Page',
	'g-medium' => 'Medium',
	'g-mediumlarge' => 'Medium-Large',
	'g-new' => 'new',
	'g-next' => 'Next',
	'g-previous' => 'Prev',
	'g-remove' => 'Remove',
	'g-remove-gift' => 'Remove this Gift',
	'g-remove-message' => 'Are your sure you want to remove the gift "$1"?',
	'g-recent-recipients' => 'Other recent recipients of this gift',
	'g-remove-success-title' => 'You have successfully removed the gift "$1"',
	'g-remove-success-message' => 'As requested, we have successfully remove the gift "$1".',
	'g-remove-title' => 'Remove "$1"?',
	'g-send-gift' => 'Send Gift',
	'g-select-a-friend' => 'select a friend',
	'g-sent-title' => 'You have sent a gift to $1',
	'g-sent-message' => 'You have sent the following gift to $1.',
	'g-small' => 'Small',
	'g-to-another' => 'Give to Someone Else',
	'g-uploadsuccess' => 'Upload Success',
	'g-viewgiftlist' => 'View Gift List',
	'g-your-profile' => 'Your Profile',
	'gift_received_subject' => '$1 has sent you the $2 Gift on {{SITENAME}}!',
	'gift_received_body' => 'Hi $1:

$2 just sent you the $3 gift on {{SITENAME}}.   

Want to read the note $2 left you and see your gift?   Click the link below:

$4

We hope you like it!

Thanks,


The {{SITENAME}} Team

---

Hey, want to stop getting emails from us?  

Click $5
and change your settings to disable email notifications.',
	// For Special:ListGroupRights
	'right-giftadmin' => 'Create new and edit existing gifts',
);

/** Finnish (Suomi)
 * @author Jack Phoenix
 */
$messages['fi'] = array(
	'giftmanager' => 'Lahjojen hallinta',
	'giftmanager-addgift' => '+ Lisää uusi lahja',
	'giftmanager-giftimage' => 'lahjan kuva',
	'giftmanager-image' => 'lisää/korvaa kuva',
	'giftmanager-giftcreated' => 'Lahja on luotu',
	'giftmanager-giftsaved' => 'Lahja on tallennettu',
	'giftmanager-public' => 'julkinen',
	'giftmanager-private' => 'yksityinen',
	'giftmanager-view' => 'Katso lahjalista',
	'g-add-message' => 'Lisää viesti',
	'g-back-edit-gift' => 'Takaisin tämän lahjan muokkaamiseen',
	'g-back-gift-list' => 'Takaisin lahjalistaan',
	'g-back-link' => '<a href="$1">< Takaisin käyttäjän $2 sivulle</a>',
	'g-choose-file' => 'Valitse tiedosto:',
	'g-cancel' => 'Peruuta',
	'g-count' => 'Käyttäjällä $1 on $2 {{PLURAL:$2|lahja|lahjaa}}.',
	'g-create-gift' => 'Luo lahja',
	'g-created-by' => 'luoja',
	'g-current-image' => 'Tämänhetkinen kuva',
	'g-delete-message' => 'Oletko varma, että haluat poistaa lahjan "$1"? Tämä poistaa sen myös käyttäjiltä, jotka ovat saattaneet saada sen.',
	'g-description-title' => 'Käyttäjän $1 lahja "$2"',
	'g-error-do-not-own' => 'Et omista tätä lahjaa.',
	'g-error-message-blocked' => 'Olet tällä hetkellä muokkauseston alaisena etkä voi antaa lahjoja',
	'g-error-message-invalid-link' => 'Antamasi linkki ei kelpaa.',
	'g-error-message-login' => 'Sinun tulee kirjautua sisään antaaksesi lahjoja',
	'g-error-message-no-user' => 'Käyttäjää, jota yrität katsoa, ei ole olemassa.',
	'g-error-message-to-yourself' => 'Et voi antaa lahjaa itsellesi.',
	'g-error-title' => 'Hups, astuit harhaan!',
	'g-file-instructions' => 'Kuvasi tulee olla jpeg, png tai gif-muotoinen (ei animoituja gif-kuvia) ja sen tulee olla kooltaan alle 100Kb.',
	'g-from' => 'käyttäjältä <a href="$1">$2</a>',
	'g-gift' => 'lahja',
	'g-gift-name' => 'lahjan nimi',
	'g-give-gift' => 'Anna lahja',
	'g-give-all' => 'Haluatko antaa käyttäjälle $1 lahjan? Napsauta vain yhtä lahjoista alempana ja napsauta "Lähetä lahja." Se on niin helppoa.',
	'g-give-all-message-title' => 'Lisää viesti',
	'g-give-all-title' => 'Anna lahja käyttäjälle $1',
	'g-give-enter-friend-title' => 'Jos tiedät käyttäjän nimen, kirjoita se alapuolelle',
	'g-given' => 'Tämä lahja on annettu $1 {{PLURAL:$1|kerran|kertaa}}',
	'g-give-list-friends-title' => 'Valitse ystävälistaltasi',
	'g-give-list-select' => 'valitse ystävä',
	'g-give-separator' => 'tai',
	'g-give-no-user-message' => 'Lahjat ja palkinnot ovat loistava tapa huomioida ystäviäsi!',
	'g-give-no-user-title' => 'Kenelle haluaisit antaa lahjan?',
	'g-give-to-user-title' => 'Lähetä lahja "$1" käyttäjälle $2',
	'g-give-to-user-message' => 'Haluatko antaa käyttäjälle $1 erilaisen lahjan? <a href="$2">Napsauta tästä</a>.',
	'g-go-back' => 'Palaa takaisin',
	'g-imagesbelow' => 'Alapuolella ovat kuvasi, joita käytetään sivustolla',
	'g-large' => 'Suuri',
	'g-list-title' => 'Käyttäjän $1 lahjalista',
	'g-main-page' => 'Etusivu',
	'g-medium' => 'Keskikokoinen',
	'g-mediumlarge' => 'Keskikokoinen - suuri',
	'g-new' => 'uusi',
	'g-next' => 'Seuraava',
	'g-previous' => 'Edell.',
	'g-remove' => 'Poista',
	'g-remove-gift' => 'Poista tämä lahja',
	'g-remove-message' => 'Oletko varma, että haluat poistaa lahjan "$1"?',
	'g-recent-recipients' => 'Muut tämän lahjan tuoreet saajat',
	'g-remove-success-title' => 'Olet onnistuneesti poistanut lahjan "$1"',
	'g-remove-success-message' => 'Kuten pyydettiin, olemme onnistuneesti poistaneet lahjan "$1".',
	'g-remove-title' => 'Poista "$1"?',
	'g-send-gift' => 'Lähetä lahja',
	'g-select-a-friend' => 'valitse ystävä',
	'g-sent-title' => 'Olet lähettänyt lahjan käyttäjälle $1',
	'g-sent-message' => 'Olet lähettänyt seuraavan lahjan käyttäjälle $1.',
	'g-small' => 'Pieni',
	'g-to-another' => 'Anna jollekulle muulle',
	'g-uploadsuccess' => 'Tallentaminen onnistui',
	'g-viewgiftlist' => 'Katso lahjalista',
	'g-your-profile' => 'Profiilisi',
	'gift_received_subject' => '$1 on lähettänyt sinulle $2-lahjan {{GRAMMAR:inessive|{{SITENAME}}}}!',
	'gift_received_body' => 'Hei $1:

$2 juuri lähetti sinulle $3-lahjan {{GRAMMAR:inessive|{{SITENAME}}}}.   

Haluatko lukea viestin, jonka $2 jätti sinulle ja nähdä lahjasi?   Napsauta linkkiä alapuolella:

$4

Toivomme, että pidät siitä!

Kiittäen,


{{GRAMMAR:genitive|{{SITENAME}}}} tiimi

---

Hei, etkö halua enää saada sähköposteja meiltä?

Napsauta $5
ja muuta asetuksiasi poistaaksesi sähköpostitoiminnot käytöstä.',
	'right-giftadmin' => 'Luoda uusia ja muokata olemassaolevia lahjoja',
);

/** French (Français)
 * @author IAlex
 */
$messages['fr'] = array(
	'giftmanager' => 'Gestionnaire de cadeaux',
	'giftmanager-addgift' => '+ Ajouter un nouveau cadeau',
	'giftmanager-access' => 'accès au cadeau',
	'giftmanager-description' => 'description du cadeau',
	'giftmanager-giftimage' => 'image du cadeau',
	'giftmanager-image' => "ajouter / remplacer l'image",
	'giftmanager-giftcreated' => 'Le cadeau a été créé',
	'giftmanager-giftsaved' => 'Le cadeau a été sauvegardé',
	'giftmanager-public' => 'public',
	'giftmanager-private' => 'privé',
	'giftmanager-view' => 'Voir la liste des cadeaux',
	'g-add-message' => 'Ajouter un message',
	'g-back-edit-gift' => 'Revenir à la modification de ce cadeau',
	'g-back-gift-list' => 'Revenir à la liste des cadeaux',
	'g-back-link' => '<a href="$1">< Revenir à la page de $2</a>',
	'g-choose-file' => 'Choisir le fichier :',
	'g-cancel' => 'Annuler',
	'g-count' => '$1 a $2 {{PLURAL:$2|cadeau|cadeaux}}.',
	'g-create-gift' => 'Créer un cadeau',
	'g-created-by' => 'créé par',
	'g-current-image' => 'Image actuelle',
	'g-delete-message' => "Êtes-vous certain de vouloir supprimer le cadeau « $1 » ? Ceci va également le supprimer des utilisateurs qui l'ont reçu.",
	'g-description-title' => 'Cadeau « $2 » de $1',
	'g-error-do-not-own' => 'Vous ne possédez pas ce cadeau.',
	'g-error-message-blocked' => 'Vous êtes bloqué et ne pouvez donc pas donner des cadeaux',
	'g-error-message-invalid-link' => 'Le lien que vous avez fourni est invalide.',
	'g-error-message-login' => 'Vous devez vous connecter pour donner des cadeaux',
	'g-error-message-no-user' => "L'utilisateur que vous essayez de voir n'existe pas.",
	'g-error-message-to-yourself' => 'Vous ne pouvez pas vous donner un cadeau à vous-même.',
	'g-error-title' => 'Oups, vous avez pris un mauvais tour !',
	'g-file-instructions' => 'Voir image doit être jpeg, png ou gif (mais pas animée) et doit être plus petite que 100 Ko.',
	'g-from' => 'de <a href="$1">$2</a>',
	'g-gift' => 'cadeau',
	'g-gift-name' => 'nom du cadeau',
	'g-give-gift' => 'Donner le cadeau',
	'g-give-all' => "Envie de donner un cadeau à $1 ? Cliquez sur un cadeau ci-dessous et cliquez ensuite sur « Envoyer le cadeau ». C'est facile.",
	'g-give-all-message-title' => 'Ajouter un message',
	'g-give-all-title' => 'Donner un cadeau à $1',
	'g-give-enter-friend-title' => "Si vous connaissez le nom de l'utilisateur, entrez-le ci-dessous",
	'g-given' => 'Le cadeau a été donné $1 fois',
	'g-give-list-friends-title' => 'Sélectionnez depuis la liste de vos amis',
	'g-give-list-select' => 'sélectionnez un ami',
	'g-give-separator' => 'ou',
	'g-give-no-user-message' => 'Les cadeaux et les prix sont bien pour faire connaitre vos amis !',
	'g-give-no-user-title' => 'A qui voulez-vous donner un cadeau ?',
	'g-give-to-user-title' => 'Envoyer le cadeau « $1 » à $2',
	'g-give-to-user-message' => 'Envie de donner un cadeau différent à $1 ? <a href="$2">Cliquez ici</a>.',
	'g-go-back' => 'Revenir',
	'g-imagesbelow' => 'Les images qui seront utilisées sur le site sont affichées ci-dessous',
	'g-large' => 'Grand',
	'g-list-title' => 'Liste des cadeaux de $1',
	'g-main-page' => 'Accueil',
	'g-medium' => 'Moyen',
	'g-mediumlarge' => 'Moyen-Grand',
	'g-new' => 'nouveau',
	'g-next' => 'Prochain',
	'g-previous' => 'Précédent',
	'g-remove' => 'Enlever',
	'g-remove-gift' => 'Enlever ce cadeau',
	'g-remove-message' => 'Êtes-vous sur de vouloir enlever le cadeau « $1 » ?',
	'g-recent-recipients' => 'Autres bénéficiaires récents de ce cadeau',
	'g-remove-success-title' => 'Vous avez enlevé avec succès le cadeau « $1 »',
	'g-remove-success-message' => 'Comme demandé, nous avons enlevé avec succès le cadeau « $1 ».',
	'g-remove-title' => 'Enlever « $1 » ?',
	'g-send-gift' => 'Envoyer le cadeau',
	'g-select-a-friend' => 'sélectionnez un ami',
	'g-sent-title' => 'Vous avez envoyé le cadeau à $1',
	'g-sent-message' => 'Vous avez envoyé le cadeau suivant à $1.',
	'g-small' => 'Petit',
	'g-to-another' => "Donner à quelqu'un d'autre",
	'g-uploadsuccess' => 'Téléchargement effectué avec succès',
	'g-viewgiftlist' => 'Voir la liste des cadeaux',
	'g-your-profile' => 'Votre profil',
	'gift_received_subject' => '$1 vous a envoyé le cadeau $1 sur {{SITENAME}} !',
	'gift_received_body' => "Bonjour $1,

$2 vous a juste envoyé le cadeau $2 sur {{SITENAME}}.

Voulez-vous voir la note $2 qui vous est adressée et voir votre cadeau ? Cliquez sur le lien ci-dessous :

$4

Nous espérons que vous l'apprécierez !

Merci,


L'équipe de {{SITENAME}}

---

Vous ne voulez plus recevoir de courriels de notre part ?

Cliquez $5
et modifiez vos préférences pour désactiver les notifications par courriel.",
	'right-giftadmin' => 'Créer de nouveaux cadeaux et modifier ceux existant',
);

/** Galician (Galego)
 * @author Toliño
 */
$messages['gl'] = array(
	'giftmanager' => 'Xestor de agasallos',
	'giftmanager-addgift' => '+ Engadir un novo agasallo',
	'giftmanager-access' => 'acceso ao agasallo',
	'giftmanager-description' => 'descrición do agasallo',
	'giftmanager-giftimage' => 'imaxe do agasallo',
	'giftmanager-image' => 'engadir/substituír a imaxe',
	'giftmanager-giftcreated' => 'O agasallo foi creado',
	'giftmanager-giftsaved' => 'O agasallo foi gardado',
	'giftmanager-public' => 'público',
	'giftmanager-private' => 'privado',
);

/** Dutch (Nederlands)
 * @author Siebrand
 * @author Tvdm
 */
$messages['nl'] = array(
	'giftmanager' => 'Giftenbeheer',
	'giftmanager-addgift' => '+ Nieuwe gift toevoegen',
	'giftmanager-access' => 'gifttoegang',
	'giftmanager-description' => 'giftomschrijving',
	'giftmanager-giftimage' => 'giftafbeelding',
	'giftmanager-image' => 'afbeelding toevoegen/vervangen',
	'giftmanager-public' => 'publiek',
	'giftmanager-private' => 'privé',
	'giftmanager-view' => 'Giftenlijst weergeven',
	'g-add-message' => 'Een bericht toevoegen',
	'g-back-edit-gift' => 'Terug naar gift bewerken',
	'g-back-gift-list' => 'Terug naar giftenlijst',
	'g-choose-file' => 'Bestand kiezen:',
	'g-cancel' => 'Annuleren',
	'g-create-gift' => 'Gift aanmaken',
	'g-created-by' => 'aangemaakt door',
	'g-current-image' => 'Huidige afbeelding',
	'g-gift' => 'gift',
	'g-gift-name' => 'giftnaam',
	'g-give-gift' => 'Gift geven',
	'g-give-all-message-title' => 'Een bericht toevoegen',
	'g-give-all-title' => 'Een gift aan $1 geven',
	'g-give-list-select' => 'selecteer een vriend',
	'g-give-separator' => 'of',
	'g-go-back' => 'Teruggaan',
	'g-large' => 'Groot',
	'g-list-title' => 'Giftenlijst van $1',
	'g-main-page' => 'Hoofdpagina',
	'g-medium' => 'Middelmatig',
	'g-mediumlarge' => 'Middelgroot',
	'g-new' => 'nieuw',
	'g-next' => 'Volgende',
	'g-previous' => 'Vorige',
	'g-remove' => 'Verwijderen',
	'g-remove-gift' => 'Deze gift verwijderen',
	'g-remove-title' => '"$1" verwijderen?',
	'g-send-gift' => 'Gift verzenden',
	'g-select-a-friend' => 'selecteer een vriend',
	'g-small' => 'Klein',
	'g-to-another' => 'Aan iemand anders geven',
	'g-uploadsuccess' => 'Uploaden voltooid',
	'g-viewgiftlist' => 'Giftenlijst weergeven',
	'g-your-profile' => 'Uw profiel',
	'gift_received_subject' => '$1 hebt u de $2-gift gezonden op {{SITENAME}}!',
	'gift_received_body' => 'Hallo $1,

$2 hebt u zojuist de $3-gift gestuurd op {{SITENAME}}.   

Wilt u het bericht lezen dat $2 voor u gemaakt heeft en uw gift weergeven? Klik dan op de onderstaande link:

$4

We hopen dat u er blij mee bent!

Bedankt,


Het Betawiki-team

---

Wilt u geen e-mails meer van ons ontvangen?

Klik op $5 en wijzig uw instellingen om e-mailwaarschuwingen uit te schakelen.',
);

/** Occitan (Occitan)
 * @author Cedric31
 */
$messages['oc'] = array(
	'giftmanager' => 'Gestionari de presents',
	'giftmanager-addgift' => '+ Apondre un present novèl',
	'giftmanager-access' => 'accès al present',
);

/** Portuguese (Português)
 * @author Vanessa Sabino
 */
$messages['pt'] = array(
	'giftmanager' => 'Gerenciador de Presentes',
	'giftmanager-addgift' => '+ Adicionar Novo Presente',
	'giftmanager-access' => 'acesso ao presente',
	'giftmanager-description' => 'descrição do presente',
	'giftmanager-giftimage' => 'imagem do presente',
	'giftmanager-image' => 'adicionar/substituir imagem',
	'giftmanager-giftcreated' => 'O presente foi criado',
	'giftmanager-giftsaved' => 'O presente foi salvo',
	'giftmanager-public' => 'público',
	'giftmanager-private' => 'privado',
	'giftmanager-view' => 'Ver Lista de Presentes',
	'g-add-message' => 'Adicionar Mensagem',
	'g-back-edit-gift' => 'Volar para Editar Este Presente',
	'g-back-gift-list' => 'Voltar para Lista de Presentes',
	'g-back-link' => '<a href="$1">< Voltar para página de $2</a>',
	'g-choose-file' => 'Escolher Arquivo:',
	'g-cancel' => 'Cancelar',
	'g-count' => '$1 tem $2 {{PLURAL:$2|presente|presentes}}.',
	'g-create-gift' => 'Presente criado',
	'g-created-by' => 'criado por',
	'g-current-image' => 'Imagem Atual',
	'g-delete-message' => 'Você tem certeza de que quer excluir o presente "$1"? Isto também irá excluí-lo que usuários que podem tê-lo recebido.',
	'g-description-title' => 'presente "$2" de $1',
	'g-error-do-not-own' => 'Você não possui este presente.',
	'g-error-message-blocked' => 'Você está bloqueado atualmente e não pode dar presentes',
	'g-error-message-invalid-link' => 'O link que você entrou é inválido.',
	'g-error-message-login' => 'Você precisa estar logado para enviar presentes',
	'g-error-message-no-user' => 'O usuário que você está tentando ver não existe.',
	'g-error-message-to-yourself' => 'Você não pode dar um presente a si mesmo',
	'g-error-title' => 'Ops, você entrou no lugar errado!',
	'g-file-instructions' => 'Sua imagem precisa ser um jpeg, png or gif (sem gifs animados), e precisa ter tamanho menor que 100kb.',
	'g-from' => 'de <a href="$1">$2</a>',
	'g-gift' => 'presente',
	'g-gift-name' => 'nome do presente',
	'g-give-gift' => 'Dar Presente',
	'g-give-all' => 'Quer dar um presente para $1? Apenas clique em um dos presentes abaixo e clique em "Enviar Presente". É fácil assim.',
	'g-give-all-message-title' => 'Adicionar Mensagem',
	'g-give-all-title' => 'Dar um Presente para $1',
	'g-give-enter-friend-title' => 'Se você sabe o nome do usuário, digite abaixo',
	'g-given' => 'Este presente foi dado $1 {{PLURAL:$1|vez|vezes}}',
	'g-give-list-friends-title' => 'Selecione da sua lista de amigos',
	'g-give-list-select' => 'selecione um amigo',
	'g-give-separator' => 'ou',
	'g-give-no-user-message' => 'Presentes e prêmios são uma ótima maneira de dar reconhecimento aos seus amigos!',
	'g-give-no-user-title' => 'Para quem você gostaria de dar um presente?',
	'g-give-to-user-title' => 'Enviar presente "$1" para $2',
	'g-give-to-user-message' => 'Quer dar a $1 um presente diferente? <a href="$2">Clique Aqui</a>.',
	'g-go-back' => 'Voltar',
	'g-imagesbelow' => 'Abaixo estão as imagens que serão usadas no site',
	'g-large' => 'Grande',
	'g-list-title' => 'Lista de Presentes de$1',
	'g-main-page' => 'Página Principal',
	'g-medium' => 'Médio',
	'g-mediumlarge' => 'Médio-Grande',
	'g-new' => 'novo',
	'g-next' => 'Próximo',
	'g-previous' => 'Anterior',
	'g-remove' => 'Remover',
	'g-remove-gift' => 'Remover este Presente',
	'g-remove-message' => 'Tem certeza de que deseja remover o presente "$1"?',
	'g-recent-recipients' => 'Outros ganhadores deste presente',
	'g-remove-success-title' => 'Você removeu com sucesso o presente "$1"',
	'g-remove-success-message' => 'Conforme pedido, nós removemos o presente "$1".',
	'g-remove-title' => 'Remover "$1"?',
	'g-send-gift' => 'Enviar Presente',
	'g-select-a-friend' => 'selecionar um amigo',
	'g-sent-title' => 'Você enviou um presente para $1',
	'g-sent-message' => 'Você enviou o presente seguinte para $1.',
	'g-small' => 'Pequeno',
	'g-to-another' => 'Dar para Outra Pessoa',
	'g-uploadsuccess' => 'Upload bem sucedido',
	'g-viewgiftlist' => 'Ver Lista de Presentes',
	'g-your-profile' => 'Seu Perfil',
	'gift_received_subject' => '$1 enviou para você o Presente $2 Gift em {{SITENAME}}!',
	'gift_received_body' => 'Oi $1:

$2 acabou de enviar o presente $3 em {{SITENAME}}.   

Quer ler o recado que $2 deixou e ver seu presente? Clique no link abaixo:

$4

Esperamos que tenha gostado!

Obrigado,


O Time de {{SITENAME}}

---

Ei, quer parer de receber e-mails de nós?

Clique $5
e altere suas preferências para desabilitar e-mails de notificação.',
);

