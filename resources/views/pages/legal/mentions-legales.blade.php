{{-- Legal notice. The identifiers come from .env (config safar.legal): empty ones show "à compléter". --}}
@php
    $nom = config('safar.nom');
    $l = config('safar.legal');
    $v = fn ($value) => filled($value) ? e($value) : '<span class="text-muted fst-italic">' . e(__('à compléter')) . '</span>';
@endphp
<x-app-layout>
    @include('partials.page-banner', ['title' => __('Mentions légales'), 'subtitle' => __('Qui édite et héberge ce site.')])

    <section class="section">
        <div class="container">
            <article class="bl-legal">
                <h2>{{ __('Éditeur du site') }}</h2>
                <dl class="bl-legal-dl">
                    <dt>{{ __('Raison sociale') }}</dt><dd>{!! $v($l['societe'] ?: null) !!}</dd>
                    <dt>{{ __('Forme juridique') }}</dt><dd>{!! $v($l['forme']) !!}@if ($l['capital']) · {{ __('capital de :c', ['c' => $l['capital']]) }}@endif</dd>
                    <dt>{{ __('Siège social') }}</dt><dd>{{ config('safar.contact.adresse') }}</dd>
                    <dt>{{ __('Registre du commerce') }}</dt><dd>{!! $v($l['rc']) !!}</dd>
                    <dt>ICE</dt><dd>{!! $v($l['ice']) !!}</dd>
                    <dt>{{ __('Identifiant fiscal') }}</dt><dd>{!! $v($l['if']) !!}</dd>
                    <dt>{{ __('Contact') }}</dt><dd>{{ config('safar.contact.telephone') }} · {{ config('safar.contact.email') }}</dd>
                    <dt>{{ __('Directeur de la publication') }}</dt><dd>{!! $v($l['directeur']) !!}</dd>
                </dl>

                <h2>{{ __('Hébergement') }}</h2>
                <p>{!! $v($l['hebergeur']) !!}</p>

                <h2>{{ __('Données personnelles') }}</h2>
                <p>{!! __('Traitement déclaré auprès de la CNDP :n. Détails dans la :lien.', [
                    'n' => $l['cndp'] ? '(' . e($l['cndp']) . ')' : '',
                    'lien' => '<a href="' . route('pages.legal', 'confidentialite') . '">' . e(__('politique de confidentialité')) . '</a>',
                ]) !!}</p>

                <h2>{{ __('Propriété intellectuelle') }}</h2>
                <p>{{ __('Le nom :site, son logo et les contenus du site sont protégés. Toute reproduction sans autorisation est interdite. Les photos des villes et des compagnies restent la propriété de leurs auteurs.', ['site' => $nom]) }}</p>
            </article>
        </div>
    </section>
</x-app-layout>
