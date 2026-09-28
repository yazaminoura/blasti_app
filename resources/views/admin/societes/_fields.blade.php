{{-- Société fields, shared by create and edit. $societe is null on create. --}}
<x-admin.form-section title="Entreprise" description="Raison sociale et identifiant fiscal." icon="bi-buildings">
    <x-admin.field name="raison_social" label="Raison sociale" col="col-md-8" required :value="$societe?->raison_social" />
    <x-admin.field name="ice" label="ICE" col="col-md-4" required maxlength="15" class="sa-mono" placeholder="15 chiffres" :value="$societe?->ice" />
</x-admin.form-section>

<x-admin.form-section title="Adresse" icon="bi-geo-alt">
    <x-admin.field name="adresse" label="Adresse" col="col-md-8" required :value="$societe?->adresse" />
    <x-admin.field name="ville" label="Ville" col="col-md-4" required :value="$societe?->ville" />
</x-admin.form-section>

<x-admin.form-section title="Contact" description="Personne à joindre pour cette société." icon="bi-person-lines-fill">
    <x-admin.field name="nom_contact" label="Nom du contact" col="col-12" required :value="$societe?->nom_contact" />
    <x-admin.field type="email" name="email" label="Email" col="col-md-6" required icon="bi-envelope" placeholder="contact@societe.ma" :value="$societe?->email" />
    <x-admin.field type="tel" name="tel" label="Téléphone" col="col-md-6" required maxlength="20" icon="bi-telephone" placeholder="06 00 00 00 00" :value="$societe?->tel" />
</x-admin.form-section>

<x-admin.form-section title="Logo" description="Affiché sur le site à côté des voyages de la société." icon="bi-image">
    <x-admin.upload name="logo" label="Logo" :current="$societe?->logo" contain icon="bi-buildings" hint="PNG ou SVG." />
</x-admin.form-section>
