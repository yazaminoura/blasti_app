@extends('admin.Layout.app')
@section('title', 'Mon profil')

@section('content')
@php
    $me = Auth::user();
    $pwdErrors = $errors->updatePassword;
@endphp

<x-admin.page-header title="Mon profil" subtitle="Vos informations de connexion à l'administration." />

<div class="row g-4">
    <div class="col-xl-4">
        <div class="sa-card text-center p-4">
            <img src="{{ $me->profile_image_path }}" alt="" class="rounded-circle mb-3" style="width: 96px; height: 96px; object-fit: cover; background: var(--sa-surface-3);">
            <h2 class="h5 fw-bold mb-1">{{ $me->name }}</h2>
            <div class="text-body-secondary small mb-3">{{ $me->email }}</div>
            <span class="sa-chip brand dot">{{ $me->isSuperAdmin() ? 'Super administrateur' : ($me->roles->first()?->name ?? 'Administrateur') }}</span>
            <div class="sa-divider"></div>
            <div class="d-flex justify-content-around text-center">
                <div>
                    <div class="fw-bold">{{ $me->created_at?->format('d/m/Y') ?? '—' }}</div>
                    <div class="small text-body-secondary">Membre depuis</div>
                </div>
                <div>
                    <div class="fw-bold">{{ $me->email_verified_at ? 'Vérifié' : 'Non vérifié' }}</div>
                    <div class="small text-body-secondary">Email</div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-8 sa-gap">
        <x-admin.form :action="route('profile.update')" method="PATCH" style="max-width: none;"
                      :hint="session('status') === 'profile-updated' ? 'Modifications enregistrées.' : null">
            <x-admin.form-section title="Informations" description="Votre nom et l'adresse email utilisée pour vous connecter." icon="bi-person-vcard">
                <x-admin.field name="name" label="Nom complet" col="col-md-6" required :value="$me->name" />
                <x-admin.field type="email" name="email" label="Email" col="col-md-6" required icon="bi-envelope" :value="$me->email" />
            </x-admin.form-section>
        </x-admin.form>

        <form method="POST" action="{{ route('password.update') }}" class="sa-card">
            @csrf
            @method('PUT')
            <div class="sa-form-section">
                <div class="sa-form-section-intro">
                    <span class="sa-tile warning"><i class="bi bi-shield-lock"></i></span>
                    <div>
                        <h3>Mot de passe</h3>
                        <p>Utilisez un mot de passe long que vous n'utilisez nulle part ailleurs.</p>
                    </div>
                </div>
                <div class="row g-3">
                    <div class="col-12">
                        <label class="form-label" for="current_password">Mot de passe actuel<span class="sa-req">*</span></label>
                        <input type="password" name="current_password" id="current_password" autocomplete="current-password"
                               class="form-control {{ $pwdErrors->has('current_password') ? 'is-invalid' : '' }}">
                        @if ($pwdErrors->has('current_password'))<div class="invalid-feedback">{{ $pwdErrors->first('current_password') }}</div>@endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password">Nouveau mot de passe<span class="sa-req">*</span></label>
                        <input type="password" name="password" id="password" autocomplete="new-password"
                               class="form-control {{ $pwdErrors->has('password') ? 'is-invalid' : '' }}">
                        @if ($pwdErrors->has('password'))<div class="invalid-feedback">{{ $pwdErrors->first('password') }}</div>@endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" for="password_confirmation">Confirmation<span class="sa-req">*</span></label>
                        <input type="password" name="password_confirmation" id="password_confirmation" autocomplete="new-password" class="form-control">
                    </div>
                </div>
            </div>
            <div class="sa-form-actions">
                <span class="sa-form-hint">{{ session('status') === 'password-updated' ? 'Mot de passe mis à jour.' : '' }}</span>
                <button type="submit" class="btn btn-primary px-4"><i class="bi bi-key"></i> Mettre à jour</button>
            </div>
        </form>

        <div class="sa-card" style="border-color: color-mix(in srgb, var(--sa-danger) 35%, var(--sa-border));">
            <div class="sa-card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex gap-3 align-items-start">
                    <span class="sa-tile danger"><i class="bi bi-exclamation-triangle"></i></span>
                    <div>
                        <h3 class="h6 fw-semibold mb-1">Supprimer mon compte</h3>
                        <p class="small text-body-secondary mb-0">La suppression est définitive. Toutes vos données seront effacées.</p>
                    </div>
                </div>
                <button type="button" class="btn btn-danger-soft" data-bs-toggle="modal" data-bs-target="#confirmDeletion">Supprimer le compte</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="confirmDeletion" tabindex="-1" aria-labelledby="confirmDeletionTitle" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" action="{{ route('profile.destroy') }}" class="modal-content sa-card border-0 p-4">
            @csrf
            @method('DELETE')
            <div class="text-center mb-3">
                <span class="sa-tile danger lg mx-auto mb-3"><i class="bi bi-exclamation-triangle"></i></span>
                <h2 class="h5 fw-bold" id="confirmDeletionTitle">Supprimer votre compte ?</h2>
                <p class="text-body-secondary small mb-0">Saisissez votre mot de passe pour confirmer.</p>
            </div>
            <input type="password" name="password" class="form-control {{ $errors->userDeletion->has('password') ? 'is-invalid' : '' }}" placeholder="Mot de passe actuel">
            @if ($errors->userDeletion->has('password'))<div class="invalid-feedback">{{ $errors->userDeletion->first('password') }}</div>@endif
            <div class="d-flex gap-2 mt-4">
                <button type="button" class="btn btn-soft flex-fill" data-bs-dismiss="modal">Annuler</button>
                <button type="submit" class="btn btn-danger flex-fill">Supprimer</button>
            </div>
        </form>
    </div>
</div>
@endsection
