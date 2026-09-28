@extends('admin.Layout.app')
@section('title', 'Modifier l\'autocar')

@section('content')
<x-admin.page-header :title="'Autocar ' . $autocar->matricule" subtitle="Modifiez la société, la capacité ou la photo." :back="route('autocars.index')" backLabel="Autocars" />

<x-admin.form :action="route('autocars.update', $autocar->id)" method="PUT" files :cancel="route('autocars.index')">
    @include('admin.autocars._fields', ['autocar' => $autocar])
</x-admin.form>
@endsection
