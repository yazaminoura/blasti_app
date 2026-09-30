<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use App\Support\Tarif;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** The price goes up as the departure gets closer (Admin > Paramètres > Tarifs selon la date). */
class TarifsTest extends TestCase
{
    use RefreshDatabase;

    private function voyage(int $jours): Voyage
    {
        $at = now()->addDays($jours)->setTime(15, 0);

        return Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '15:00:00', 'heure_arrivee' => '18:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    public function test_the_super_admin_sets_the_rules_and_prices_follow_the_date(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $this->actingAs($admin)->put(route('admin.tarifs.update'), ['regles' => [
            ['jours' => 7, 'type' => 'montant', 'valeur' => 10],
            ['jours' => 4, 'type' => 'montant', 'valeur' => 20],
            ['jours' => '', 'type' => 'montant', 'valeur' => ''], // empty line: ignored
        ]])->assertSessionHas('success');
        \App\Models\Parametre::appliquerALaConfig();

        $this->assertEquals(100, $this->voyage(14)->prixActuel()); // far away: normal price
        $this->assertEquals(110, $this->voyage(6)->prixActuel());  // 7 days or less: +10
        $this->assertEquals(120, $this->voyage(3)->prixActuel());  // 4 days or less: +20
        $this->assertEquals(120, $this->voyage(0)->prixActuel());  // departure day

        // percentage rule
        config(['safar.majorations' => [['jours' => 2, 'type' => 'pourcentage', 'valeur' => 15]]]);
        $this->assertEquals(115, $this->voyage(1)->prixActuel());

        $this->actingAs($admin)->get(route('admin.tarifs.edit'))->assertOk()->assertSee('Tarifs par défaut');
    }

    public function test_a_booking_pays_the_price_of_the_day_it_is_made(): void
    {
        config(['safar.majorations' => [['jours' => 7, 'type' => 'montant', 'valeur' => 10]]]);
        $voyage = $this->voyage(5);
        $cash = \App\Models\ModeReglement::create(['mode_reglement' => 'Espèces']);
        $client = User::factory()->create();

        $this->actingAs($client)->post(route('client.reservations.store'), ['voyage_id' => $voyage->id, 'seats' => [3], 'mode_reglement_id' => $cash->id]);

        $this->assertEquals(110, \App\Models\Reservation::sole()->prix);
    }

    public function test_each_voyage_can_have_its_own_rules(): void
    {
        config(['safar.majorations' => [['jours' => 7, 'type' => 'montant', 'valeur' => 10]]]);
        $defaut = $this->voyage(5);
        $propre = $this->voyage(5);
        $propre->update(['majorations' => [['jours' => 10, 'type' => 'montant', 'valeur' => 50]]]);
        $fixe = $this->voyage(5);
        $fixe->update(['majorations' => []]);

        $this->assertEquals(110, $defaut->fresh()->prixActuel()); // default rules
        $this->assertEquals(150, $propre->fresh()->prixActuel()); // its own
        $this->assertEquals(100, $fixe->fresh()->prixActuel());   // own = none: fixed price
    }

    public function test_the_voyage_form_saves_its_own_rules_or_the_default(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $voyage = $this->voyage(20);
        $champs = fn (array $plus) => array_merge($voyage->only(['ville_depart_id', 'ville_arrivee_id', 'autocar_id', 'type_voyage_id', 'prix']), [
            'date_depart' => $voyage->date_depart, 'date_arrivee' => $voyage->date_arrivee,
            'heure_depart' => '15:00', 'heure_arrivee' => '18:00',
        ], $plus);

        $this->actingAs($admin)->get(route('voyages.edit', $voyage))->assertOk()->assertSee('Tarifs propres à ce voyage');

        $this->actingAs($admin)->put(route('voyages.update', $voyage), $champs(['tarif_mode' => 'propre', 'majorations' => [
            ['jours' => 3, 'type' => 'pourcentage', 'valeur' => 20], ['jours' => '', 'type' => 'montant', 'valeur' => ''],
        ]]))->assertSessionHasNoErrors();
        $this->assertEquals([['jours' => 3, 'type' => 'pourcentage', 'valeur' => 20]], $voyage->fresh()->majorations);

        $this->actingAs($admin)->put(route('voyages.update', $voyage), $champs(['tarif_mode' => 'defaut']))->assertSessionHasNoErrors();
        $this->assertNull($voyage->fresh()->majorations);
    }

    public function test_only_the_super_admin_edits_prices(): void
    {
        $staff = User::factory()->create(['isadmin' => 1]);
        $staff->roles()->attach(\App\Models\Role::where('slug', 'directeur')->firstOrFail());

        $this->actingAs($staff)->get(route('admin.tarifs.edit'))->assertForbidden();
        $this->assertNull(Tarif::regle(now()->addDay()));
    }
}
