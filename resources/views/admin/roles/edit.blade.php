@extends('admin.Layout.app')
@section('title', 'Modifier le rôle')

@section('content')
<x-admin.page-header :title="'Rôle : ' . $role->name" subtitle="Les changements s'appliquent immédiatement à tous les utilisateurs de ce rôle." :back="route('admin.users.index')" backLabel="Utilisateurs & rôles" />

<x-admin.form :action="route('admin.roles.update', $role->id)" method="PUT" :cancel="route('admin.users.index')" style="max-width: none;">
    <x-admin.form-section title="Rôle" icon="bi-shield-lock">
        <x-admin.field name="name" label="Nom du rôle" col="col-md-6" required :value="$role->name" />
    </x-admin.form-section>

    @include('admin.roles._matrix', ['checked' => $rolePermissions])
</x-admin.form>
@endsection
