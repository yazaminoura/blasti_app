@extends('admin.Layout.app')
@section('title', 'Nouveau code promo')

@section('content')
<x-admin.page-header title="Nouveau code promo" subtitle="Une réduction que vos clients tapent au paiement." :back="route('promotions.index')" backLabel="Promotions" />

<x-admin.form :action="route('promotions.store')" :cancel="route('promotions.index')" submit="Créer le code" submitIcon="bi-plus-lg">
    @include('admin.promotions._fields')
</x-admin.form>
@endsection
