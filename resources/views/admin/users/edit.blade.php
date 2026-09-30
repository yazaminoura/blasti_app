@extends('admin.Layout.app')
@section('title', 'Modifier l\'utilisateur')

@section('content')
<x-admin.page-header :title="$user->name" :subtitle="$user->email" :back="$user->isadmin ? route('admin.users.index') : route('admin.clients.index')"
                     :backLabel="$user->isadmin ? 'Utilisateurs & rôles' : 'Clients'" />

<div class="sa-gap">
    @if ($user->isadmin)
        @php
            $limite = \App\Support\SessionUnique::limite($user);
            $enLigne = \App\Support\SessionUnique::enLigne($user);
        @endphp
        <x-admin.card title="Connexion" icon="bi-laptop"
            :subtitle="$limite ? 'Ce compte ne peut être ouvert qu\'à un seul endroit à la fois : une nouvelle connexion ferme l\'ancienne.' : 'Super administrateur : pas de limite de connexions.'">
            <div class="d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex flex-wrap gap-4">
                    <div><div class="sa-sub">État</div>
                        @if ($user->desactive_le)
                            <span class="sa-chip danger dot">Désactivé le {{ \Carbon\Carbon::parse($user->desactive_le)->format('d/m/Y') }}</span>
                        @else
                            <span class="sa-chip {{ $enLigne ? 'success' : 'muted' }} dot">{{ $enLigne ? 'Connecté' : 'Hors ligne' }}</span>
                        @endif
                    </div>
                    <div><div class="sa-sub">Dernière connexion</div>
                        <div class="sa-strong">{{ $user->derniere_connexion_le ? \Carbon\Carbon::parse($user->derniere_connexion_le)->format('d/m/Y H:i') : 'Jamais' }}</div></div>
                    <div><div class="sa-sub">Appareil</div><div class="sa-strong">{{ $user->derniere_connexion_appareil ?? '—' }}</div></div>
                    <div><div class="sa-sub">Adresse IP</div><div class="sa-strong sa-mono">{{ $user->derniere_connexion_ip ?? '—' }}</div></div>
                </div>
                @if (auth()->user()->isSuperAdmin() && ! $user->is(auth()->user()))
                    <div class="d-flex flex-wrap gap-2">
                    <form method="POST" action="{{ route('admin.users.desactiver', $user) }}" onsubmit="confirmDelete(event, this)"
                          @if ($user->desactive_le)
                              data-confirm="Réactiver le compte de {{ $user->name }} ?" data-confirm-text="Il pourra de nouveau se connecter avec son mot de passe." data-confirm-button="Oui, réactiver"
                          @else
                              data-confirm="Désactiver le compte de {{ $user->name }} ?" data-confirm-text="Il est déconnecté tout de suite et ne peut plus se connecter. Son historique (ventes, scans, encaissements) est conservé. Vous pourrez le réactiver." data-confirm-button="Oui, désactiver"
                          @endif>
                        @csrf
                        @method('PATCH')
                        @if ($user->desactive_le)
                            <button class="btn btn-soft"><i class="bi bi-unlock"></i> Réactiver le compte</button>
                        @else
                            <button class="btn btn-soft text-danger"><i class="bi bi-lock"></i> Désactiver le compte</button>
                        @endif
                    </form>
                    <form method="POST" action="{{ route('admin.users.deconnecter', $user) }}" onsubmit="confirmDelete(event, this)"
                          data-confirm="Déconnecter {{ $user->name }} de tous ses appareils ?" data-confirm-text="Il devra se reconnecter avec son mot de passe." data-confirm-button="Oui, déconnecter">
                        @csrf
                        <button class="btn btn-soft text-danger"><i class="bi bi-box-arrow-right"></i> Déconnecter partout</button>
                    </form>
                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="confirmDelete(event, this)"
                          data-confirm="Supprimer définitivement le compte de {{ $user->name }} ?"
                          data-confirm-text="Le compte disparaît pour de bon. Ses ventes, scans et encaissements restent, mais sans son nom (« — »). Pour garder son nom dans l'historique, choisissez plutôt « Désactiver »."
                          data-confirm-button="Oui, supprimer">
                        @csrf
                        @method('DELETE')
                        <button class="btn btn-danger"><i class="bi bi-trash3"></i> Supprimer le compte</button>
                    </form>
                    </div>
                @endif
            </div>
        </x-admin.card>
    @endif

    <x-admin.form :action="route('admin.users.update', $user->id)" method="PUT" :cancel="$user->isadmin ? route('admin.users.index') : route('admin.clients.index')">
        <x-admin.form-section title="Identité" icon="bi-person">
            <x-admin.field name="name" label="Nom complet" col="col-md-6" required :value="$user->name" />
            <x-admin.field type="email" name="email" label="Email" col="col-md-6" required icon="bi-envelope" :value="$user->email" />
        </x-admin.form-section>

        @if ($canManageAccess)
            <x-admin.form-section title="Accès à l'administration" description="Administrateur sans rôle = super administrateur (accès complet). Avec un rôle = accès limité aux permissions du rôle." icon="bi-shield-lock">
                <div class="col-12">
                    <div class="form-check form-switch">
                        <input type="hidden" name="isadmin" value="0">
                        <input class="form-check-input" type="checkbox" role="switch" name="isadmin" id="isadmin" value="1" @checked(old('isadmin', $user->isadmin))>
                        <label class="form-check-label fw-semibold" for="isadmin">Peut accéder à l'administration</label>
                    </div>
                </div>
                <x-admin.field type="select" name="role" label="Rôle" col="col-md-6" empty="Aucun rôle"
                               :options="$roles->pluck('name', 'id')" :value="$user->roles->first()?->id" />
                <x-admin.field type="select" name="societe_id" label="Compagnie (espace compagnie)" col="col-md-6" :empty="'Toutes (équipe ' . config('safar.nom') . ')'"
                               :options="$societes->pluck('raison_social', 'id')" :value="$user->societe_id" hint="Un compte lié à une compagnie ne voit que ses autocars, voyages, billets et avis." />
            </x-admin.form-section>
        @endif
    </x-admin.form>

    <x-admin.form :action="route('admin.users.update-password', $user->id)" method="PUT" submit="Changer le mot de passe" submitIcon="bi-key"
                  hint="Le nouveau mot de passe remplace l'actuel immédiatement.">
        <x-admin.form-section title="Mot de passe" description="8 caractères minimum." icon="bi-key">
            <x-admin.field type="password" name="password" label="Nouveau mot de passe" col="col-md-6" required autocomplete="new-password" />
            <x-admin.field type="password" name="password_confirmation" label="Confirmation" col="col-md-6" required autocomplete="new-password" />
        </x-admin.form-section>
    </x-admin.form>
</div>
@endsection
