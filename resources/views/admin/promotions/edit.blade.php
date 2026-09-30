@extends('admin.Layout.app')
@section('title', 'Code ' . $promotion->code)

@section('content')
<x-admin.page-header :title="'Code ' . $promotion->code" :subtitle="'Utilisé ' . $promotion->utilisations . ' fois.'" :back="route('promotions.index')" backLabel="Promotions" />

<x-admin.form :action="route('promotions.update', $promotion)" method="PUT" :cancel="route('promotions.index')" submit="Enregistrer">
    @include('admin.promotions._fields')
</x-admin.form>
@endsection
