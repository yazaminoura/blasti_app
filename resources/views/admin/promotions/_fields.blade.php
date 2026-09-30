<x-admin.form-section title="Code" description="Le client le tape sur la page de paiement. Lettres et chiffres, sans espace." icon="bi-ticket-perforated">
    <x-admin.field name="code" label="Code" col="col-md-6" required maxlength="30" :value="$promotion->code" placeholder="ETE2026" style="text-transform: uppercase;" />
    <div class="col-md-6 d-flex align-items-end">
        <div class="form-check form-switch mb-2">
            <input type="hidden" name="actif" value="0">
            <input class="form-check-input" type="checkbox" role="switch" name="actif" id="actif" value="1" @checked(old('actif', $promotion->actif))>
            <label class="form-check-label fw-semibold" for="actif">Code actif</label>
        </div>
    </div>
</x-admin.form-section>

<x-admin.form-section title="Réduction" description="Sur le total de la commande (tous les sièges)." icon="bi-percent">
    <x-admin.field name="type" label="Type" type="select" col="col-md-6" required :value="$promotion->type"
                   :options="['pourcentage' => 'Pourcentage (%)', 'montant' => 'Montant fixe (DH)']" />
    <x-admin.field name="valeur" label="Valeur" type="number" col="col-md-6" required step="0.01" min="0.01" :value="$promotion->valeur" placeholder="10" />
    <x-admin.field name="min_montant" label="Commande minimum" type="number" col="col-md-6" step="0.01" min="0" suffix="DH" :value="$promotion->min_montant" hint="Vide : aucun minimum." />
    <x-admin.field name="max_utilisations" label="Nombre d'utilisations maximum" type="number" col="col-md-6" min="1" :value="$promotion->max_utilisations" hint="Vide : illimité." />
</x-admin.form-section>

<x-admin.form-section title="Période" description="Vide : valable tout de suite et sans fin." icon="bi-calendar-range">
    <x-admin.field name="debut" label="À partir du" type="date" col="col-md-6" :value="$promotion->debut?->toDateString()" />
    <x-admin.field name="fin" label="Jusqu'au" type="date" col="col-md-6" :value="$promotion->fin?->toDateString()" />
</x-admin.form-section>
