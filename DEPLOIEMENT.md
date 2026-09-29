# Mise en ligne de BLASTI — liste de contrôle

À faire sur le serveur de production, dans l'ordre.

## 1. Fichier `.env`

| Clé | Valeur en production |
|---|---|
| `APP_ENV` | `production` (désactive aussi la page de paiement de test) |
| `APP_DEBUG` | `false` (sinon les erreurs affichent le code et les mots de passe) |
| `APP_URL` | l'adresse réelle, en `https://` (les QR codes des billets l'utilisent) |
| `DB_*` | la base MySQL du serveur |
| `MAIL_MAILER` … `MAIL_FROM_ADDRESS` | un vrai serveur SMTP (sinon aucun billet ni rappel n'est envoyé) |
| `CMI_CLIENT_ID`, `CMI_STORE_KEY`, `CMI_GATEWAY_URL` | les clés fournies par le CMI (URL de production, pas `testpayment`) |
| `SAFAR_SOCIETE`, `SAFAR_RC`, `SAFAR_ICE`, `SAFAR_IF`, `SAFAR_CNDP`… | les identifiants légaux (page Mentions légales) |

Ne jamais envoyer `.env` ni `oldenv` sur GitHub.

## 2. Installation

```bash
composer install --no-dev --optimize-autoloader
php artisan key:generate        # seulement la première fois
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

`DEMO_DATA=false` pour ne pas charger les voyages de démonstration.

## 3. Tâches planifiées (obligatoire)

Rappels de départ, libération des paiements abandonnés, confirmation de présence des billets non payés :

```
* * * * * cd /chemin/vers/blasti && php artisan schedule:run >> /dev/null 2>&1
```

## 4. Dans l'administration

1. **Paramètres > Coordonnées** : vrais téléphone, e-mail, adresse, réseaux sociaux ; puis « Envoyer un e-mail de test ».
2. **Paramètres > Modes de règlement** : activer « Paiement en ligne par carte (CMI) » sur le mode Carte bancaire.
3. **Utilisateurs & rôles** : créer un rôle « Contrôleur » avec *Réservations : lire + modifier* et *Voyages : lire*, puis les comptes des contrôleurs (ils scannent les QR codes et encaissent à la porte du bus).
4. Faire relire les pages **CGV**, **Confidentialité** et **Mentions légales** (`/legal/...`) et déclarer le traitement à la **CNDP** (loi 09-08).

## 5. Vérifier

- Réserver un billet avec paiement à l'embarquement : e-mail reçu avec le PDF.
- Payer un billet par carte avec une vraie carte (petit montant) puis l'annuler depuis l'administration.
- Scanner le QR code d'un billet avec un téléphone connecté en compte Contrôleur.
