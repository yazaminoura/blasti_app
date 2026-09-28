@props([
    'action',
    'method' => 'POST',
    'files' => false,
    'cancel' => null,
    'submit' => 'Enregistrer',
    'submitIcon' => 'bi-check2',
    'hint' => null,
])

{{-- Every admin form: a card made of <x-admin.form-section> blocks and a sticky action bar --}}
<form action="{{ $action }}" method="POST" @if ($files) enctype="multipart/form-data" @endif
      {{ $attributes->merge(['class' => 'sa-card sa-form-narrow']) }}>
    @csrf
    @if (strtoupper($method) !== 'POST')
        @method($method)
    @endif

    @if ($errors->any())
        <div class="alert alert-danger d-flex gap-2 align-items-start m-3 mb-0 border-0" style="background: var(--sa-danger-soft); color: var(--sa-danger);">
            <i class="bi bi-exclamation-circle mt-1"></i>
            <div>Veuillez corriger {{ $errors->count() > 1 ? 'les ' . $errors->count() . ' champs indiqués' : 'le champ indiqué' }} ci-dessous.</div>
        </div>
    @endif

    {{ $slot }}

    <div class="sa-form-actions">
        <span class="sa-form-hint">{{ $hint ?? 'Les champs marqués * sont obligatoires.' }}</span>
        @if ($cancel)
            <a href="{{ $cancel }}" class="btn btn-soft">Annuler</a>
        @endif
        <button type="submit" class="btn btn-primary px-4"><i class="bi {{ $submitIcon }}"></i> {{ $submit }}</button>
    </div>
</form>
