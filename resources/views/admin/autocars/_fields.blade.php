{{-- Autocar fields, shared by create and edit. $autocar is null on create. --}}
@php $societes = \App\Models\Societe::orderBy('raison_social')->pluck('raison_social', 'id'); @endphp

<x-admin.form-section title="Identification" description="La société propriétaire et la plaque d'immatriculation." icon="bi-bus-front">
    <x-admin.field type="select" name="societe_id" label="Société" col="col-md-6" required
                   :options="$societes" empty="Choisir une société" :value="$autocar?->societe_id" />
    <x-admin.field name="matricule" label="Matricule" col="col-md-6" required placeholder="12345-A-6" class="sa-mono" :value="$autocar?->matricule" />
</x-admin.form-section>

<x-admin.form-section title="Capacité" description="Nombre de sièges vendables sur chaque voyage." icon="bi-people">
    <x-admin.field type="number" name="nbr_siege" label="Nombre de sièges" col="col-md-6" required min="1" suffix="sièges" :value="$autocar?->nbr_siege" />
</x-admin.form-section>

<x-admin.form-section title="Photo" description="Affichée dans la liste de la flotte." icon="bi-image">
    <x-admin.upload name="image" label="Photo de l'autocar" :current="$autocar?->image" icon="bi-bus-front" />
</x-admin.form-section>
