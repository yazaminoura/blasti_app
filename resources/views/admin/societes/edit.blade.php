@extends('admin.Layout.app')
@section('title', 'Modifier la société')

@section('content')
<x-admin.page-header :title="$societe->raison_social" subtitle="Modifiez les informations de la société." :back="route('societes.index')" backLabel="Sociétés" />

<x-admin.form :action="route('societes.update', $societe->id)" method="PUT" files :cancel="route('societes.index')">
    @include('admin.societes._fields', ['societe' => $societe])
</x-admin.form>
@endsection
