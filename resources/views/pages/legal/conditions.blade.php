{{-- Conditions générales de vente. The numbers come from config/safar.php, so the text always matches the site's rules.
     To be reviewed by a lawyer before opening to the public. --}}
@php
    $nom = config('safar.nom');
    $societe = config('safar.legal.societe') ?: $nom;
    $modif = (int) config('safar.modification_heures');
    $conf = config('safar.confirmation');
@endphp
<x-app-layout>
    @include('partials.page-banner', ['title' => __('Conditions générales de vente'), 'subtitle' => __('Les règles de réservation, de paiement, de modification et d\'annulation de vos billets.')])

    <section class="section">
        <div class="container">
            <article class="bl-legal">
                <p class="bl-legal-updated">{{ __('Dernière mise à jour : :date', ['date' => '29/09/2026']) }}</p>

                <h2>1. {{ __('Objet') }}</h2>
                <p>{{ __(':site est une plateforme de réservation de billets de bus exploitée par :societe. Elle permet de réserver des places sur les départs proposés par des compagnies de transport partenaires. Le transport lui-même est assuré par la compagnie indiquée sur le billet.', ['site' => $nom, 'societe' => $societe]) }}</p>
                <p>{{ __('Toute réservation vaut acceptation des présentes conditions.') }}</p>

                <h2>2. {{ __('Réservation') }}</h2>
                <ul>
                    <li>{{ __('La réservation nécessite un compte avec une adresse e-mail vérifiée : les billets y sont envoyés.') }}</li>
                    <li>{{ __('Le client choisit ses sièges sur le plan de l\'autocar, dans la limite de :max sièges par réservation. Chaque siège fait l\'objet d\'un billet nominatif avec son propre QR code.', ['max' => config('safar.max_sieges')]) }}</li>
                    <li>{{ __('Un siège est attribué définitivement au moment de la confirmation ; un siège déjà vendu ne peut pas être réservé.') }}</li>
                </ul>

                <h2>3. {{ __('Prix et paiement') }}</h2>
                <ul>
                    <li>{{ __('Les prix sont indiqués en dirhams (DH), toutes taxes comprises, pour le trajet choisi (de l\'arrêt de montée à l\'arrêt de descente).') }}</li>
                    <li>{{ __('Paiement par carte bancaire en ligne, sur la page sécurisée du Centre Monétique Interbancaire (CMI), quand ce mode est proposé : :site ne reçoit ni ne conserve jamais les données de votre carte.', ['site' => $nom]) }}</li>
                    <li>{{ __('Paiement à l\'embarquement : le billet est réservé mais n\'est pas payé ; le montant est réglé au contrôleur avant de monter. Un même compte ne peut détenir plus de :max sièges non payés sur ses prochains voyages.', ['max' => config('safar.max_non_payes')]) }}</li>
                    @if ($conf['active'])
                        <li>{{ __('Pour un billet non payé, le client reçoit :h heures avant le départ un e-mail lui demandant de confirmer sa présence. Sans confirmation :l heures avant le départ, le billet est annulé sans frais et le siège remis en vente.', ['h' => $conf['demande_heures'], 'l' => $conf['limite_heures']]) }}</li>
                    @endif
                </ul>

                <h2>4. {{ __('Billet et embarquement') }}</h2>
                <ul>
                    <li>{{ __('Le billet (PDF) est envoyé par e-mail et reste disponible dans l\'espace client. Il peut être présenté imprimé ou sur téléphone.') }}</li>
                    <li>{{ __('Le voyageur se présente au moins 15 minutes avant le départ, avec une pièce d\'identité. Le contrôleur scanne le QR code du billet.') }}</li>
                    <li>{{ __('Un billet non payé doit être réglé avant de monter ; à défaut, l\'embarquement est refusé.') }}</li>
                </ul>

                <h2>5. {{ __('Modification') }}</h2>
                <p>{{ __('Un billet peut être déplacé gratuitement sur un autre départ du même trajet (autre date, autre heure, autre siège) jusqu\'à :h heures avant le départ, depuis l\'espace client. Si le nouveau départ est plus cher, la différence est réglée à l\'embarquement ; s\'il est moins cher, la différence n\'est pas remboursée.', ['h' => $modif]) }}</p>

                <h2>6. {{ __('Annulation et remboursement') }}</h2>
                <p>{{ __('Le client peut annuler son billet depuis son espace jusqu\'au départ du bus. Un billet non payé est annulé sans frais. Un billet payé est remboursé selon le délai restant avant le départ :') }}</p>
                @include('partials.refund-policy')
                <p>{{ __('Si la compagnie annule le départ, le billet payé est remboursé intégralement. Le remboursement est effectué par le même moyen que le paiement.') }}</p>

                <h2>7. {{ __('Absence au départ') }}</h2>
                <p>{{ __('Un billet payé et non utilisé n\'est pas remboursé après le départ. Après :n billets non payés et non utilisés, le paiement à l\'embarquement n\'est plus proposé au compte : seul le paiement par carte reste possible.', ['n' => config('safar.absences_max')]) }}</p>

                <h2>8. {{ __('Responsabilité') }}</h2>
                <p>{{ __(':site agit comme intermédiaire de réservation. Les horaires sont communiqués par les compagnies et peuvent varier (circulation, météo, contrôle). Les bagages, le confort et la sécurité à bord relèvent de la compagnie de transport.', ['site' => $nom]) }}</p>

                <h2>9. {{ __('Données personnelles') }}</h2>
                <p>{!! __('Les données nécessaires à la réservation sont traitées conformément à la loi 09-08. Voir notre :lien.', ['lien' => '<a href="' . route('pages.legal', 'confidentialite') . '">' . e(__('politique de confidentialité')) . '</a>']) !!}</p>

                <h2>10. {{ __('Réclamations et droit applicable') }}</h2>
                <p>{{ __('Pour toute question ou réclamation : :email ou :telephone. Les présentes conditions sont soumises au droit marocain, notamment la loi 31-08 édictant des mesures de protection du consommateur.', ['email' => config('safar.contact.email'), 'telephone' => config('safar.contact.telephone')]) }}</p>
            </article>
        </div>
    </section>
</x-app-layout>
