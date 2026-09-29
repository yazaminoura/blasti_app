@extends('admin.Layout.app')
@section('title', 'Coordonnées')

@section('content')
@php
    // current value: saved in the admin, else the .env / config default
    $v = fn ($champ) => $parametre->$champ ?: config(\App\Models\Parametre::CONFIG[$champ]);
@endphp

<x-admin.page-header title="Coordonnées" subtitle="Téléphone, e-mail et adresse affichés aux clients : pied de page, page Contact, e-mails et billets PDF.">
    {{-- checks the SMTP settings of .env: billets, rappels and confirmations depend on it --}}
    <form action="{{ route('admin.coordonnees.test-mail') }}" method="POST" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-soft"><i class="bi bi-envelope-check"></i> Envoyer un e-mail de test</button>
    </form>
</x-admin.page-header>

@if (in_array(config('mail.default'), ['log', 'array'], true))
    <div class="alert alert-warning">
        <i class="bi bi-exclamation-triangle"></i>
        <strong>Les e-mails ne sont pas envoyés</strong> (MAIL_MAILER={{ config('mail.default') }}) : billets, rappels et confirmations sont seulement écrits dans <code>storage/logs/laravel.log</code>.
        Renseignez un serveur SMTP dans le fichier <code>.env</code> (MAIL_MAILER=smtp, MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS).
    </div>
@endif

<x-admin.form :action="route('admin.coordonnees.update')" method="PUT" submit="Enregistrer les coordonnées"
              hint="Visible sur tout le site dès l'enregistrement.">
    <x-admin.form-section title="Contact" description="Mettez de vraies coordonnées : elles sont imprimées sur les billets." icon="bi-telephone">
        <x-admin.field name="telephone" label="Téléphone" col="col-md-6" required icon="bi-telephone" :value="$v('telephone')" placeholder="+212 5 22 00 00 00" />
        <x-admin.field name="email" label="E-mail" type="email" col="col-md-6" required icon="bi-envelope" :value="$v('email')" placeholder="contact@blasti.ma" />
        <x-admin.field name="adresse" label="Adresse" col="col-12" required icon="bi-geo-alt" :value="$v('adresse')" placeholder="Adresse, ville" />
    </x-admin.form-section>

    <x-admin.form-section title="Réseaux sociaux" description="Laissez vide pour masquer l'icône dans le pied de page." icon="bi-share">
        <x-admin.field name="facebook" label="Facebook" type="url" col="col-md-6" icon="bi-facebook" :value="$v('facebook')" placeholder="https://facebook.com/..." />
        <x-admin.field name="instagram" label="Instagram" type="url" col="col-md-6" icon="bi-instagram" :value="$v('instagram')" placeholder="https://instagram.com/..." />
        <x-admin.field name="tiktok" label="TikTok" type="url" col="col-md-6" icon="bi-tiktok" :value="$v('tiktok')" placeholder="https://tiktok.com/@..." />
        <x-admin.field name="x" label="X (Twitter)" type="url" col="col-md-6" icon="bi-twitter-x" :value="$v('x')" placeholder="https://x.com/..." />
        <x-admin.field name="linkedin" label="LinkedIn" type="url" col="col-md-6" icon="bi-linkedin" :value="$v('linkedin')" placeholder="https://linkedin.com/company/..." />
    </x-admin.form-section>
</x-admin.form>
@endsection
