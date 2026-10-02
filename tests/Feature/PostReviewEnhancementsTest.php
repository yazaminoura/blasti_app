<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PostReviewEnhancementsTest extends TestCase
{
    use RefreshDatabase;

    private Voyage $voyage;
    private ModeReglement $cash;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->cash = ModeReglement::create(['mode_reglement' => 'Espèces']);
        $this->client = User::factory()->create();
        $this->voyage = $this->createVoyage('Rabat', 'Tanger', now()->addDays(4));
    }

    private function createVoyage(string $from, string $to, $at): Voyage
    {
        return Voyage::create([
            'date_depart' => $at->toDateString(),
            'date_arrivee' => $at->toDateString(),
            'heure_depart' => '08:00:00',
            'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => $from])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => $to])->id,
            'autocar_id' => Autocar::factory()->create(['nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    private function bookTicket(Voyage $voyage, int $seat, ?User $user = null): Reservation
    {
        $user ??= $this->client;
        $segment = $voyage->segmentFor();

        return Reservation::create([
            'commande' => Reservation::nouvelleCommande(),
            'num_siege' => $seat,
            'statut' => Reservation::CONFIRMEE,
            'user_id' => $user->id,
            'mode_reglement_id' => $this->cash->id,
            'date_reservation' => now(),
            'date_depart' => $segment[0]->passage_at->toDateString(),
            'date_arrivee' => $segment[1]->passage_at->toDateString(),
            'heure_depart' => $segment[0]->passage_at->format('H:i:s'),
            'heure_arrivee' => $segment[1]->passage_at->format('H:i:s'),
            'ville_depart_id' => $segment[0]->ville_id,
            'ville_arrivee_id' => $segment[1]->ville_id,
            'autocar_id' => $voyage->autocar_id,
            'type_voyage_id' => $voyage->type_voyage_id,
            'prix' => $voyage->segmentPrice(...$segment),
            'frais' => 0,
            'voyage_id' => $voyage->id,
            'arret_depart_id' => $segment[0]->id,
            'arret_arrivee_id' => $segment[1]->id,
        ]);
    }

    public function test_language_switch_strictly_rejects_foreign_or_subdomain_spoofing(): void
    {
        // Malicious referers with same prefix
        $this->withHeader('referer', 'http://localhost.attacker.com/evil')
            ->get(route('lang.switch', 'fr'))
            ->assertRedirect(route('home'));

        $this->withHeader('referer', 'https://malicious-site.com/fake')
            ->get(route('lang.switch', 'ar'))
            ->assertRedirect(route('home'));

        // Self-referencing loop
        $this->withHeader('referer', 'http://localhost/lang/ar')
            ->get(route('lang.switch', 'ar'))
            ->assertRedirect(route('home'));

        // Valid internal referer
        $this->withHeader('referer', 'http://localhost/destinations')
            ->get(route('lang.switch', 'en'))
            ->assertRedirect('http://localhost/destinations');
    }

    public function test_guichet_passenger_name_change_respects_time_window_and_permissions(): void
    {
        config(['safar.guichet_correction_minutes' => 30]);

        $vendeur = User::factory()->create(['isadmin' => 1]);
        $role = \App\Models\Role::firstOrCreate(['slug' => 'guichetier'], ['name' => 'Guichetier']);
        $perm = \App\Models\Permission::firstOrCreate(['name' => 'reservations.update'], ['slug' => 'reservations-update']);
        $role->permissions()->sync([$perm->id]);
        $vendeur->roles()->attach($role);

        $billet = $this->bookTicket($this->voyage, 12);
        $billet->update(['vendu_par' => $vendeur->id]);

        // Seller corrects typo within 30 minutes
        $this->actingAs($vendeur)->patch(route('reservation.admin.guichet.passager', $billet), ['passager_nom' => 'Hassan El'])
            ->assertSessionHas('success');
        $this->assertSame('Hassan El', $billet->fresh()->passager());

        // 35 minutes later: seller is blocked
        $this->travel(35)->minutes();
        $this->actingAs($vendeur)->patch(route('reservation.admin.guichet.passager', $billet), ['passager_nom' => 'Hassan El Alami'])
            ->assertForbidden();

        // Super admin can still correct it after the 30-min window
        $superAdmin = User::factory()->create(['isadmin' => 1]);
        $this->actingAs($superAdmin)->patch(route('reservation.admin.guichet.passager', $billet), ['passager_nom' => 'Hassan El Alami'])
            ->assertSessionHas('success');
        $this->assertSame('Hassan El Alami', $billet->fresh()->passager());
    }

    public function test_ticket_qr_verification_url_expires_deterministically_after_trip(): void
    {
        config(['safar.qr_expiration_jours' => 7]);

        $billet = $this->bookTicket($this->voyage, 15);
        $url1 = $billet->verificationUrl();
        $url2 = $billet->fresh()->verificationUrl();

        // Must be deterministic and stable
        $this->assertSame($url1, $url2);
        $this->get($url1)->assertOk();

        // Travel 8 days past arrival: link must be expired (signature rejected)
        $this->travelTo($billet->arriveeAt()->addDays(8));
        $this->get($url1)->assertForbidden();
    }

    public function test_reservations_agence_ignores_historical_past_departures(): void
    {
        $agence = ModeReglement::create(['mode_reglement' => 'Agence', 'en_agence' => true]);

        // A past trip 2 days ago that was never paid: acts as a no-show
        $pastVoyage = $this->createVoyage('Rabat', 'Tanger', now()->subDays(2));
        $pastTicket = $this->bookTicket($pastVoyage, 3);
        $pastTicket->update([
            'mode_reglement_id' => $agence->id,
            'created_at' => now()->subDays(3),
        ]);

        // An upcoming trip with expired agency reservation deadline (25 hours ago)
        $upcomingTicket = $this->bookTicket($this->voyage, 4);
        $upcomingTicket->update([
            'mode_reglement_id' => $agence->id,
            'created_at' => now()->subHours(26),
        ]);

        $this->artisan('reservations:agence')->assertSuccessful();

        // Upcoming ticket is cancelled, past departure remains intact as a historical no-show
        $this->assertTrue($upcomingTicket->fresh()->isCancelled());
        $this->assertFalse($pastTicket->fresh()->isCancelled());
    }

    public function test_ticket_change_dispatches_waitlist_alert_for_previous_voyage(): void
    {
        config(['safar.modification_heures' => 0]);

        $voyageB = $this->createVoyage('Rabat', 'Tanger', now()->addDays(5));
        $billet = $this->bookTicket($this->voyage, 10);
        $billet->markPaid('TEST-PAID');

        // Client changes departure from Voyage A to Voyage B
        $this->actingAs($this->client)->put(route('client.reservations.change.update', $billet), [
            'voyage_id' => $voyageB->id,
            'seats' => [10],
        ])->assertSessionMissing('error');

        $this->assertSame($voyageB->id, $billet->fresh()->voyage_id);
    }

    public function test_noshow_cannot_submit_review_on_scanned_bus(): void
    {
        // Trip finished yesterday
        $pastVoyage = $this->createVoyage('Rabat', 'Tanger', now()->subDay());
        $myTicket = $this->bookTicket($pastVoyage, 1);
        $otherTicket = $this->bookTicket($pastVoyage, 2, User::factory()->create());

        // Controller scanned other passenger; my ticket was a no-show
        $otherTicket->embarquer(User::factory()->create());

        $this->actingAs($this->client)->get(route('client.avis.create', $myTicket))->assertNotFound();
    }

    public function test_robots_txt_and_sitemap_xml_endpoints(): void
    {
        $robots = file_get_contents(public_path('robots.txt'));
        $this->assertStringContainsString('Disallow: /admin/', $robots);
        $this->assertStringContainsString('Disallow: /billet/', $robots);
        $this->assertStringContainsString('Sitemap: /sitemap.xml', $robots);

        $response = $this->get(route('sitemap'));
        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/xml');
        $response->assertSee(route('pages.destinations'));
        $response->assertSee(route('pages.compagnies'));
        $response->assertSee(route('pages.legal', 'conditions'));
    }
}
