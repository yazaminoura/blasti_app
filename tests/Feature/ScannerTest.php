<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\Encaissement;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Scan;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Database\Seeders\CompteCompagnieSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Bus door: the scanner page checks each ticket, logs every scan, collects the money and lets travellers in. */
class ScannerTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['isadmin' => 1]);
    }

    private function voyage(?Societe $societe = null, string $quand = '+1 hour'): Voyage
    {
        $depart = now()->modify($quand);
        $autocar = Autocar::factory()->create(['societe_id' => ($societe ?? Societe::factory()->create())->id, 'nbr_siege' => 40]);

        return Voyage::create([
            'date_depart' => $depart->toDateString(), 'date_arrivee' => $depart->toDateString(),
            'heure_depart' => $depart->format('H:i:00'), 'heure_arrivee' => $depart->copy()->addHours(3)->format('H:i:00'),
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => $autocar->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Standard'])->id,
            'prix' => 120,
        ]);
    }

    private function billet(Voyage $voyage, bool $paye = true, int $siege = 1): Reservation
    {
        $r = Reservation::create([
            'num_siege' => $siege, 'user_id' => User::factory()->create()->id, 'date_reservation' => now(),
            'mode_reglement_id' => ModeReglement::firstOrCreate(['mode_reglement' => 'Espèces'])->id,
            'date_depart' => $voyage->date_depart, 'date_arrivee' => $voyage->date_arrivee,
            'heure_depart' => $voyage->heure_depart, 'heure_arrivee' => $voyage->heure_arrivee,
            'ville_depart_id' => $voyage->ville_depart_id, 'ville_arrivee_id' => $voyage->ville_arrivee_id,
            'autocar_id' => $voyage->autocar_id, 'type_voyage_id' => $voyage->type_voyage_id,
            'prix' => 120, 'frais' => 0, 'voyage_id' => $voyage->id,
        ]);
        if ($paye) {
            $r->markPaid('test');
        }

        return $r->fresh();
    }

    private function scan(string $code, ?Voyage $bus = null, ?User $as = null)
    {
        return $this->actingAs($as ?? $this->admin)->postJson(route('reservation.admin.scan'), ['code' => $code, 'voyage' => $bus?->id]);
    }

    public function test_a_paid_ticket_is_valid_and_the_scan_is_recorded(): void
    {
        $billet = $this->billet($this->voyage());

        $this->scan($billet->verificationUrl())->assertOk()->assertJsonPath('verdict.code', 'valable')->assertJsonPath('verdict.ton', 'ok');

        $this->assertNotNull($billet->fresh()->scanne_le);
        $this->assertSame('valable', Scan::sole()->resultat);
        $this->assertNull($billet->fresh()->embarque_le, 'scanning alone does not board');
    }

    public function test_a_typed_ticket_number_works_too_but_a_tampered_link_does_not(): void
    {
        $billet = $this->billet($this->voyage());

        $this->scan((string) $billet->id)->assertJsonPath('verdict.code', 'valable');
        $this->scan(str_replace('signature=', 'signature=x', $billet->verificationUrl()))->assertJsonPath('verdict.code', 'illisible');
        $this->scan('https://example.com/billet/1/verifier')->assertJsonPath('verdict.code', 'illisible');
        $this->scan('999999')->assertJsonPath('verdict.code', 'introuvable');
    }

    public function test_unpaid_ticket_is_collected_in_cash_and_boarded_in_one_click(): void
    {
        $billet = $this->billet($this->voyage(), paye: false);

        $this->scan($billet->verificationUrl())->assertJsonPath('verdict.ton', 'payer');

        // boarding without collecting is refused
        $this->actingAs($this->admin)->patchJson(route('reservation.admin.embarquer', $billet))->assertStatus(422);

        $this->actingAs($this->admin)->patchJson(route('reservation.admin.embarquer', $billet), ['mode' => 'especes'])
            ->assertOk()->assertJsonPath('stats.caisse.especes', 120);

        $billet->refresh();
        $this->assertTrue($billet->isPaid());
        $this->assertNotNull($billet->embarque_le);
        $this->assertSame($this->admin->id, $billet->embarque_par);
        $e = Encaissement::sole();
        $this->assertSame(['especes', '120.00', $this->admin->id], [$e->mode, (string) $e->montant, $e->user_id]);
    }

    public function test_card_payment_from_the_ticket_page_goes_into_the_cash_drawer(): void
    {
        $billet = $this->billet($this->voyage(), paye: false);

        $this->actingAs($this->admin)->patch(route('reservation.admin.payer', $billet), ['mode' => 'carte'])->assertRedirect();

        $this->assertSame('carte', Encaissement::sole()->mode);
        $this->assertSame(0.0, $billet->fresh()->resteAPayer());
    }

    public function test_the_same_ticket_cannot_board_twice(): void
    {
        $billet = $this->billet($this->voyage());
        $this->actingAs($this->admin)->patchJson(route('reservation.admin.embarquer', $billet))->assertOk();

        $this->scan($billet->verificationUrl())->assertJsonPath('verdict.code', 'deja_monte')->assertJsonPath('verdict.ton', 'stop');
        $this->actingAs($this->admin)->patchJson(route('reservation.admin.embarquer', $billet))->assertStatus(422);
    }

    public function test_wrong_bus_and_wrong_day_are_refused(): void
    {
        $bus = $this->voyage();
        $autre = $this->billet($this->voyage());
        $demain = $this->billet($this->voyage(null, '+2 days'));
        $hier = $this->billet($this->voyage(null, '-1 day'));

        $this->scan($autre->verificationUrl(), $bus)->assertJsonPath('verdict.code', 'mauvais_bus');
        $this->scan($demain->verificationUrl())->assertJsonPath('verdict.code', 'trop_tot');
        $this->scan($hier->verificationUrl())->assertJsonPath('verdict.code', 'parti');
        $this->scan($autre->verificationUrl(), $bus)->assertJsonPath('stats.bus.billets', 0);
    }

    public function test_cancelled_ticket_is_refused(): void
    {
        $billet = $this->billet($this->voyage());
        $billet->cancel('admin');

        $this->scan($billet->verificationUrl())->assertJsonPath('verdict.code', 'annule');
    }

    public function test_company_account_only_checks_its_own_tickets(): void
    {
        $maSociete = Societe::factory()->create();
        (new CompteCompagnieSeeder())->run();
        $compte = User::where('email', CompteCompagnieSeeder::EMAIL)->firstOrFail();
        $this->assertSame($maSociete->id, $compte->societe_id);

        $mien = $this->billet($this->voyage($maSociete));
        $autre = $this->billet($this->voyage());

        $this->scan($mien->verificationUrl(), null, $compte)->assertJsonPath('verdict.code', 'valable');
        $this->scan($autre->verificationUrl(), null, $compte)->assertJsonPath('verdict.code', 'introuvable');
        $this->actingAs($compte)->patchJson(route('reservation.admin.embarquer', $autre))->assertNotFound();
    }

    public function test_company_account_opens_its_space_but_not_global_settings(): void
    {
        Societe::factory()->create();
        (new CompteCompagnieSeeder())->run();
        $compte = User::where('email', CompteCompagnieSeeder::EMAIL)->firstOrFail();

        foreach (['reservation.admin.scanner', 'voyages.index', 'autocars.index', 'reservation.admin.index', 'avis.index'] as $route) {
            $this->actingAs($compte)->get(route($route))->assertOk();
        }
        foreach (['villes.index', 'admin.users.index', 'promotions.index', 'admin.apparence.edit', 'societes.index'] as $route) {
            $this->actingAs($compte)->get(route($route))->assertForbidden();
        }
    }

    public function test_scanner_page_shows_the_bus_counters(): void
    {
        $bus = $this->voyage();
        $this->billet($bus, siege: 1);
        $this->billet($bus, paye: false, siege: 2);

        $this->actingAs($this->admin)->get(route('reservation.admin.scanner', ['voyage' => $bus->id]))
            ->assertOk()->assertSee('0 / 2')->assertSee('120,00 DH');
    }
}
