{{-- Privacy policy (Moroccan law 09-08). To be reviewed by a lawyer before opening to the public. --}}
@php
    $nom = config('safar.nom');
    $societe = config('safar.legal.societe') ?: $nom;
    $cndp = config('safar.legal.cndp');
@endphp
<x-app-layout>
    @include('partials.page-banner', ['title' => __('Politique de confidentialité'), 'subtitle' => __('Quelles données nous utilisons, pourquoi, et comment exercer vos droits.')])

    <section class="section">
        <div class="container">
            <article class="bl-legal">
                <p class="bl-legal-updated">{{ __('Dernière mise à jour : :date', ['date' => '29/09/2026']) }}</p>

                <h2>1. {{ __('Responsable du traitement') }}</h2>
                <p>{{ __(':societe, joignable à :email. Le traitement est déclaré auprès de la Commission nationale de contrôle de la protection des données à caractère personnel (CNDP).', ['societe' => $societe, 'email' => config('safar.contact.email')]) }}
                    @if ($cndp) {{ __('Numéro : :n.', ['n' => $cndp]) }} @endif</p>

                <h2>2. {{ __('Données collectées') }}</h2>
                <ul>
                    <li>{{ __('Compte : nom, adresse e-mail, mot de passe (chiffré), et si vous les ajoutez : téléphone, adresse, photo.') }}</li>
                    <li>{{ __('Réservations : trajets, dates, sièges, montants, mode et état du paiement, présence à l\'embarquement.') }}</li>
                    <li>{{ __('Paiement par carte : saisi uniquement sur la page du CMI ; nous recevons seulement le résultat et une référence de transaction, jamais le numéro de carte.') }}</li>
                    <li>{{ __('Messages envoyés depuis la page Contact.') }}</li>
                </ul>

                <h2>3. {{ __('Pourquoi') }}</h2>
                <ul>
                    <li>{{ __('Créer et gérer vos réservations, vous envoyer vos billets, rappels et confirmations.') }}</li>
                    <li>{{ __('Permettre au contrôleur de vérifier votre billet et votre paiement à l\'embarquement.') }}</li>
                    <li>{{ __('Répondre à vos demandes, traiter les remboursements, prévenir les réservations abusives.') }}</li>
                    <li>{{ __('Respecter nos obligations légales et comptables.') }}</li>
                </ul>

                <h2>4. {{ __('Destinataires') }}</h2>
                <p>{{ __('La compagnie de transport de votre voyage (liste des passagers), le CMI pour les paiements par carte, notre hébergeur et notre service d\'envoi d\'e-mails. Vos données ne sont ni vendues ni louées.') }}</p>

                <h2>5. {{ __('Durée de conservation') }}</h2>
                <p>{{ __('Les données du compte sont conservées tant que le compte existe ; vous pouvez le supprimer à tout moment depuis Paramètres. Les données de réservation et de paiement sont conservées pendant la durée imposée par la loi (comptabilité).') }}</p>

                <h2>6. {{ __('Vos droits') }}</h2>
                <p>{{ __('Conformément à la loi 09-08, vous disposez d\'un droit d\'accès, de rectification et d\'opposition. Écrivez-nous à :email ; nous répondons dans les meilleurs délais. Vous pouvez aussi saisir la CNDP (www.cndp.ma).', ['email' => config('safar.contact.email')]) }}</p>

                <h2>7. {{ __('Cookies') }}</h2>
                <p>{{ __('Le site utilise uniquement les cookies nécessaires à son fonctionnement (session de connexion, sécurité des formulaires) et mémorise dans votre navigateur vos préférences de langue et de thème (clair / sombre). Aucun cookie publicitaire.') }}</p>

                <h2>8. {{ __('Sécurité') }}</h2>
                <p>{{ __('Les mots de passe sont chiffrés, les liens des billets et des QR codes sont signés, et l\'accès à l\'administration est limité par des rôles et permissions.') }}</p>
            </article>
        </div>
    </section>
</x-app-layout>
