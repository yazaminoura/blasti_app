{{-- Browser tab icon on every page: the current bus, in the Apparence colour (App\Support\BrandImages) --}}
@php $icone = \App\Support\BrandImages::url('blasti-icon.png'); @endphp
<link rel="icon" type="image/png" href="{{ $icone }}">
<link rel="shortcut icon" type="image/png" href="{{ $icone }}">
<link rel="apple-touch-icon" sizes="180x180" href="{{ $icone }}">
