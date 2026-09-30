{{-- Frequently asked questions (contact + help pages). The answers describe how the site really works. --}}
@php
    $faqs = [
        [__('Comment réserver un billet ?'), __('Cherchez votre trajet (ville de départ, d\'arrivée et date), choisissez un départ, puis votre siège sur le plan de l\'autocar et votre mode de règlement. Votre billet est créé tout de suite.')],
        [__('Dois-je créer un compte ?'), __('Vous pouvez chercher et comparer les départs sans compte. Un compte gratuit est demandé au moment de réserver, pour retrouver vos billets dans votre espace.')],
        [__('Où trouver mon billet ?'), __('Dans « Mon espace » > « Mes réservations », et en pièce jointe de l\'e-mail de confirmation. Présentez-le à l\'embarquement, sur votre téléphone ou imprimé.')],
        [__('Puis-je choisir ma place ?'), __('Oui. Le plan de l\'autocar montre les sièges libres et occupés : le siège que vous choisissez vous est réservé.')],
        [__('Comment payer ?'), __('Selon le mode choisi : à l\'embarquement ou en agence, ou par carte bancaire sur la page sécurisée du CMI quand ce mode est proposé.')],
        [__('Puis-je annuler mon billet ?'), __('Oui, depuis votre espace, jusqu\'au départ du bus. Un billet payé est remboursé à 100 % jusqu\'à 2 jours avant le départ, à 90 % la veille et à 50 % le jour du départ. Après le départ, l\'annulation n\'est plus possible.')],
        [__('Que se passe-t-il si l\'horaire change ?'), __('Si la société modifie le départ, votre billet est mis à jour automatiquement : consultez-le dans votre espace avant de partir.')],
        [__('À quelle heure dois-je me présenter ?'), __('Présentez-vous au moins 15 minutes avant l\'heure de départ indiquée sur votre billet.')],
    ];
@endphp
<div class="accordion mz-faq" id="faqAccordion">
    @foreach ($faqs as $i => [$question, $answer])
        <div class="accordion-item mb-2 border rounded-3 overflow-hidden">
            <h3 class="accordion-header">
                <button class="accordion-button {{ $i ? 'collapsed' : '' }} fs-16" type="button" data-bs-toggle="collapse" data-bs-target="#faq{{ $i }}" aria-expanded="{{ $i ? 'false' : 'true' }}">
                    {{ $question }}
                </button>
            </h3>
            <div id="faq{{ $i }}" class="accordion-collapse collapse {{ $i ? '' : 'show' }}" data-bs-parent="#faqAccordion">
                <div class="accordion-body text-muted">{{ $answer }}</div>
            </div>
        </div>
    @endforeach
</div>
