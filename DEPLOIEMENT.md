# Mise en ligne de BLASTI — liste de contrôle

À faire sur le serveur de production, dans l'ordre. Serveur conseillé : Nginx + PHP 8.3 **FPM** (les e-mails et
les PDF partent après la réponse au client, ce que `php artisan serve` ne sait pas faire), MySQL 8, HTTPS.

## 1. Fichier `.env`

Partir de `.env.example` : ses valeurs sont déjà celles de la production.

| Clé | Valeur en production |
|---|---|
| `APP_ENV` | `production` (désactive aussi la page de paiement de test et les données de démonstration) |
| `APP_DEBUG` | `false` (sinon les erreurs affichent le code et les mots de passe) |
| `APP_URL` | l'adresse réelle, en `https://` (les QR codes des billets l'utilisent ; seul ce nom de domaine est accepté) |
| `TRUSTED_PROXIES` | vide si le site est servi directement ; sinon l'IP du proxy / load balancer (ou `*` s'il est le seul accès au serveur). Ne jamais mettre `*` si le serveur est joignable directement |
| `SESSION_SECURE_COOKIE` | `true` (cookie de connexion envoyé seulement en HTTPS) |
| `LOG_STACK`, `LOG_LEVEL` | `daily`, `warning` (un fichier par jour, 14 jours gardés) |
| `DB_*` | la base MySQL du serveur |
| `MAIL_MAILER` … `MAIL_FROM_ADDRESS` | un vrai serveur SMTP (sinon aucun billet ni rappel n'est envoyé) |
| `CMI_CLIENT_ID`, `CMI_STORE_KEY`, `CMI_GATEWAY_URL` | les clés fournies par le CMI (URL de production, pas `testpayment`) |
| `SAFAR_SOCIETE`, `SAFAR_RC`, `SAFAR_ICE`, `SAFAR_IF`, `SAFAR_CNDP`… | les identifiants légaux (page Mentions légales) |
| `DEMO_DATA`, `SAFAR_PAIEMENT_TEST` | `false` (ignorés de toute façon en production) |

Ne jamais envoyer `.env` ni `oldenv` sur GitHub.

## 2. Première installation

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate        # seulement la première fois
php artisan migrate --force
php artisan db:seed --force     # villes, types de voyage, modes de règlement, options (pas de faux voyages)
php artisan blasti:admin        # premier compte super admin (demande l'e-mail et le mot de passe)
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Droits d'écriture pour l'utilisateur du serveur web (ex. `www-data`) :

```bash
chown -R www-data:www-data storage bootstrap/cache
chmod -R ug+rwX storage bootstrap/cache
```

`php artisan blasti:admin` sert aussi à reprendre la main si le mot de passe du super admin est perdu.

## 3. Tâches planifiées (obligatoire)

Rappels de départ, libération des paiements abandonnés, confirmation de présence des billets non payés,
annulation des billets agence non payés, demandes d'avis :

```
* * * * * cd /chemin/vers/blasti && php artisan schedule:run >> /dev/null 2>&1
```

Chaque tâche ne tourne jamais deux fois en même temps (`withoutOverlapping`).

## 4. Sauvegardes (obligatoire)

- **Base MySQL** chaque nuit, gardée 30 jours, copiée hors du serveur :
  `mysqldump --single-transaction -u USER -p BASE | gzip > blasti-$(date +%F).sql.gz`
- **Fichiers envoyés** (photos des villes, logos) : `storage/app/public`.
- Tester une restauration au moins une fois avant l'ouverture.

## 5. Journaux

- `storage/logs/laravel-AAAA-MM-JJ.log` : erreurs (14 jours).
- `storage/logs/payments-AAAA-MM-JJ.log` : chaque retour du CMI (commande, transaction, montant, résultat ;
  jamais de numéro de carte), gardé 1 an pour les litiges.

## 6. Mise à jour du site

```bash
php artisan down --retry=60
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan config:cache && php artisan route:cache && php artisan view:cache
php artisan up
```

Toujours faire une sauvegarde de la base juste avant `migrate`.

## 7. Dans l'administration

1. **Paramètres > Coordonnées** : vrais téléphone, e-mail, adresse, réseaux sociaux ; puis « Envoyer un e-mail de test ».
2. **Paramètres > Modes de règlement** : activer « Paiement en ligne par carte (CMI) » sur le mode Carte bancaire.
3. **Espace compagnie** : pour chaque compagnie partenaire, créer un compte administrateur avec un rôle (ex. *Compagnie* : Voyages, Autocars, Réservations, Avis, Statistiques) et choisir sa compagnie : il ne verra que ses autocars, voyages, billets, avis et chiffres.
4. **Utilisateurs & rôles** : créer un rôle « Contrôleur » avec *Réservations : lire + modifier* et *Voyages : lire*, puis les comptes des contrôleurs (ils scannent les QR codes et encaissent à la porte du bus).
5. **Modes de règlement** : pour le paiement en agence (Wafacash, Cash Plus…), créer un mode avec l'option « Paiement en agence ».
6. **Promotions** : chaque code est limité à 1 commande par client par défaut (champ « Utilisations par client »).
7. Faire relire les pages **CGV**, **Confidentialité** et **Mentions légales** (`/legal/...`) et déclarer le traitement à la **CNDP** (loi 09-08).

## 8. Vérifier

- Réserver un billet avec paiement à l'embarquement : e-mail reçu avec le PDF.
- Payer un billet par carte avec une vraie carte (petit montant) puis l'annuler depuis l'administration ; la ligne apparaît dans `payments-*.log`.
- Scanner le QR code d'un billet avec un téléphone connecté en compte Contrôleur.
- Ouvrir `https://ADRESSE/up` : doit répondre « OK ».
