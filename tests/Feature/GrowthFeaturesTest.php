<?php

namespace Tests\Feature;

use App\Mail\PlaceLibreMail;
use App\Mail\ReservationMail;
use App\Models\AlertePlace;
use App\Models\Autocar;
use App\Models\Avis;
use App\Models\ModeReglement;
use App\Models\Promotion;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Promo codes, round trip, "Prévenez-moi", reviews, statistics, company space, scanner, agency payment. */
class GrowthFeaturesTest extends TestCase
{
    use RefreshDatabase;

    private ModeReglement $cash;
    private User $client;
    private Ville $casa;
    private Ville $rak;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->cash = ModeReglement::create(['mode_reglement' => 'Espèces']);
        $this->client = User::factory()->create();
        $this->casa = Ville::firstOrCreate(['ville' => 'Casablanca']);
        $this->rak = Ville::firstOrCreate(['ville' => 'Marrakech']);
    }

    private function voyage(int $days, ?Ville $from = null, ?Ville $to = null, int $seats = 40, ?Societe $societe = null, float $prix = 100): Voyage
    {
        $at = now()->addDays($days);

        return Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '08:00:00', 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => ($from ?? $this->casa)->id, 'ville_arrivee_id' => ($to ?? $this->rak)->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => ($societe ?? Societe::factory()->create())->id, 'nbr_siege' => $seats])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => $prix,
        ]);
    }

    private function book(Voyage $voyage, array $seats, array $extra = [], ?User $user = null, ?ModeReglement $mode = null)
    {
        return $this->actingAs($user ?? $this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $voyage->id, 'seats' => $seats, 'mode_reglement_id' => ($mode ?? $this->cash)->id,
        ] + $extra);
    }

    // ---- promo codes ----

    public function test_a_promo_code_lowers_the_order(): void
    {
        Promotion::create(['code' => 'ete2026', 'type' => 'pourcentage', 'valeur' => 10, 'actif' => true]);
        $this->book($this->voyage(3), [1, 2, 3], ['code_promo' => 'ETE2026']);

        $billets = Reservation::all();
        $this->assertEqualsWithDelta(30.0, $billets->sum('remise'), 0.001);
        $this->assertEqualsWithDelta(270.0, $billets->sum(fn ($b) => $b->total()), 0.001);
        $this->assertSame(1, Promotion::first()->utilisations);
    }

    public function test_an_expired_or_unknown_code_is_refused(): void
    {
        Promotion::create(['code' => 'VIEUX', 'type' => 'montant', 'valeur' => 20, 'actif' => true, 'fin' => now()->subDay()]);

        $this->book($this->voyage(3), [1], ['code_promo' => 'VIEUX'])->assertSessionHas('error');
        $this->book($this->voyage(3), [1], ['code_promo' => 'NEXISTEPAS'])->assertSessionHas('error');
        $this->assertSame(0, Reservation::count());
    }

    public function test_the_admin_manages_promo_codes(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $this->actingAs($admin)->post(route('promotions.store'), ['code' => 'noel', 'type' => 'montant', 'valeur' => 25, 'actif' => 1])
            ->assertRedirect(route('promotions.index'));
        $this->assertSame('NOEL', Promotion::sole()->code);
        $this->actingAs($admin)->get(route('promotions.index'))->assertOk()->assertSee('NOEL');
    }

    // ---- round trip ----

    public function test_the_return_trip_gets_the_round_trip_discount(): void
    {
        config(['safar.remise_retour_pourcent' => 10]);
        $this->book($this->voyage(3), [5]);
        $aller = Reservation::sole();

        $retour = $this->voyage(6, $this->rak, $this->casa);
        $this->book($retour, [8], ['retour_de' => $aller->commande]);

        $billetRetour = Reservation::where('voyage_id', $retour->id)->sole();
        $this->assertSame($aller->commande, $billetRetour->retour_de);
        $this->assertEqualsWithDelta(10.0, (float) $billetRetour->remise, 0.001);
    }

    public function test_no_round_trip_discount_on_the_same_direction(): void
    {
        $this->book($this->voyage(3), [5]);
        $aller = Reservation::sole();

        $this->book($this->voyage(6), [8], ['retour_de' => $aller->commande]);

        $this->assertEqualsWithDelta(0.0, (float) Reservation::latest('id')->first()->remise, 0.001);
    }

    // ---- "Prévenez-moi" ----

    public function test_waiting_people_are_told_when_a_seat_frees_up(): void
    {
        $voyage = $this->voyage(3, null, null, 2);
        $this->book($voyage, [1, 2]);
        $this->post(route('client.reservations.alerte', $voyage), ['email' => 'attente@example.com'])->assertSessionHas('success');

        Reservation::first()->cancel('client');

        Mail::assertSent(PlaceLibreMail::class, fn ($m) => $m->hasTo('attente@example.com'));
        $this->assertNotNull(AlertePlace::sole()->envoyee_le);
    }

    // ---- reviews ----

    public function test_a_finished_trip_can_be_rated_once_and_the_rating_shows(): void
    {
        $voyage = $this->voyage(3);
        $this->book($voyage, [4]);
        $billet = Reservation::sole();

        $this->actingAs($this->client)->get(route('client.avis.create', $billet))->assertNotFound(); // not travelled yet
        $this->travelTo(now()->addDays(4));
        $this->artisan('reservations:avis')->assertSuccessful();
        Mail::assertSent(ReservationMail::class, fn ($m) => $m->type === 'avis');

        $this->actingAs($this->client)->post(route('client.avis.store', $billet), ['note' => 4, 'commentaire' => 'Ponctuel et propre'])
            ->assertRedirect(route('client.profile.reservations.index'));
        $this->assertSame(4, Avis::sole()->note);
        $this->actingAs($this->client)->get(route('client.avis.create', $billet))->assertNotFound(); // once only

        $this->get(route('pages.compagnies'))->assertSee('bl-rating', false)->assertSee('4,0');
    }

    public function test_the_admin_can_hide_a_review(): void
    {
        $voyage = $this->voyage(3);
        $this->book($voyage, [4]);
        $avis = Avis::create(['reservation_id' => Reservation::sole()->id, 'user_id' => $this->client->id, 'societe_id' => $voyage->autocar->societe_id, 'note' => 1]);
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->get(route('avis.index'))->assertOk();
        $this->actingAs($admin)->patch(route('avis.publier', $avis));
        $this->assertFalse($avis->fresh()->publie);
    }

    // ---- statistics ----

    public function test_the_sales_dashboard_counts_the_sales(): void
    {
        $this->book($this->voyage(3), [1, 2]);
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->get(route('admin.statistiques'))->assertOk()->assertSee('200,00 DH');
    }

    // ---- company space ----

    public function test_a_company_account_only_sees_its_company(): void
    {
        $ctm = Societe::factory()->create(['raison_social' => 'CTM']);
        $autre = Societe::factory()->create(['raison_social' => 'Supratours']);
        $mien = $this->voyage(3, null, null, 40, $ctm);
        $pasMien = $this->voyage(3, null, null, 40, $autre);
        $this->book($pasMien, [1]);

        $role = Role::create(['name' => 'Compagnie test', 'slug' => 'compagnie-test']);
        $perms = collect(['voyages.read', 'voyages.update', 'voyages.create', 'reservations.read', 'reservations.update'])
            ->map(fn ($p) => \App\Models\Permission::firstOrCreate(['name' => $p], ['slug' => \Illuminate\Support\Str::slug($p)])->id);
        $role->permissions()->sync($perms);
        $staff = User::factory()->create(['isadmin' => 1, 'societe_id' => $ctm->id]);
        $staff->roles()->attach($role->id);
        $this->assertFalse($staff->isSuperAdmin());

        $this->actingAs($staff)->get(route('voyages.edit', $mien))->assertOk();
        $this->actingAs($staff)->get(route('voyages.edit', $pasMien))->assertNotFound();
        $this->actingAs($staff)->get(route('reservation.admin.show', Reservation::sole()))->assertNotFound();

        // cannot schedule a trip with another company's bus
        $this->actingAs($staff)->post(route('voyages.store'), [
            'date_depart' => now()->addDays(9)->toDateString(), 'date_arrivee' => now()->addDays(9)->toDateString(),
            'heure_depart' => '08:00', 'heure_arrivee' => '11:00', 'ville_depart_id' => $this->casa->id, 'ville_arrivee_id' => $this->rak->id,
            'autocar_id' => $pasMien->autocar_id, 'type_voyage_id' => $mien->type_voyage_id, 'prix' => 100,
        ])->assertSessionHasErrors('autocar_id');

        // the public site is never filtered
        $this->get(route('client.reservations.show', $pasMien))->assertOk();
    }

    public function test_a_company_account_without_role_is_not_super_admin(): void
    {
        $staff = User::factory()->create(['isadmin' => 1, 'societe_id' => Societe::factory()->create()->id]);

        $this->actingAs($staff)->get(route('admin.coordonnees.edit'))->assertForbidden();
    }

    // ---- scanner ----

    public function test_the_scanner_opens_a_ticket_by_number(): void
    {
        $this->book($this->voyage(3), [3]);
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->get(route('reservation.admin.scanner'))->assertOk()->assertSee('html5-qrcode');
        $this->actingAs($admin)->get(route('reservation.admin.scanner', ['billet' => Reservation::sole()->id]))
            ->assertRedirectContains('/billet/' . Reservation::sole()->id . '/verifier');
    }

    // ---- agency payment ----

    public function test_agency_payment_shows_the_code_and_cancels_when_not_paid(): void
    {
        $agence = ModeReglement::create(['mode_reglement' => 'Wafacash', 'en_agence' => true]);
        $this->book($this->voyage(5), [2], [], null, $agence);
        $billet = Reservation::sole();

        $this->actingAs($this->client)->get(route('client.commande.show', $billet->commande))->assertSee(__('Votre code de paiement'))->assertSee($billet->commande);
        $admin = User::factory()->create(['isadmin' => 1]);
        $this->actingAs($admin)->get(route('reservation.admin.index', ['q' => $billet->commande]))->assertOk()->assertSee('#' . $billet->id, false);

        $this->travelTo(now()->addHours((int) config('safar.agence_delai_heures') + 1));
        $this->artisan('reservations:agence')->assertSuccessful();
        $this->assertTrue($billet->fresh()->isCancelled());
    }
}
