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
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Traveller name per seat, recurring trips, sharing the tickets on WhatsApp. */
class PassengersScheduleShareTest extends TestCase
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
        $this->client = User::factory()->create(['name' => 'Sara Alaoui']);
        $at = now()->addDays(3);
        $this->voyage = Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '08:00:00', 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Casablanca'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Marrakech'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 120,
        ]);
    }

    public function test_each_seat_gets_its_traveller_name(): void
    {
        $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [5, 6], 'mode_reglement_id' => $this->cash->id,
            'passagers' => [5 => 'Sara Alaoui', 6 => 'Yassine Alaoui'],
        ]);

        $this->assertSame('Yassine Alaoui', Reservation::where('num_siege', 6)->value('passager_nom'));
        $billet = Reservation::where('num_siege', 6)->first();
        $this->get($billet->verificationUrl())->assertSee('Yassine Alaoui');
        $this->actingAs($this->client)->get(route('ticket.show', $billet->id))->assertSee('Yassine Alaoui')->assertSee('Sara Alaoui');
    }

    public function test_an_empty_name_falls_back_to_the_account_holder(): void
    {
        $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [7], 'mode_reglement_id' => $this->cash->id,
        ]);

        $this->assertSame('Sara Alaoui', Reservation::sole()->passager());
    }

    public function test_a_voyage_is_scheduled_on_chosen_weekdays_with_its_stops(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $du = now()->addDays(10)->startOfWeek(); // a Monday
        $au = $du->copy()->addDays(13);           // two weeks

        $this->actingAs($admin)->post(route('voyages.programmer.store', $this->voyage), [
            'du' => $du->toDateString(), 'au' => $au->toDateString(), 'jours' => [1, 3, 5], // Mon, Wed, Fri
        ])->assertSessionHas('success');

        $copies = Voyage::where('id', '!=', $this->voyage->id)->get();
        $this->assertCount(6, $copies);
        foreach ($copies as $copie) {
            $this->assertContains($copie->departAt()->dayOfWeekIso, [1, 3, 5]);
            $this->assertSame('08:00', $copie->departAt()->format('H:i'));
            $this->assertSame((int) $this->voyage->autocar_id, (int) $copie->autocar_id);
            $this->assertCount(2, $copie->arrets); // first and last stop
        }
    }

    public function test_the_same_bus_is_never_scheduled_twice_at_the_same_time(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $jour = now()->addDays(12)->toDateString();
        $this->actingAs($admin)->post(route('voyages.programmer.store', $this->voyage), ['du' => $jour, 'au' => $jour, 'jours' => range(1, 7)]);
        $this->actingAs($admin)->post(route('voyages.programmer.store', $this->voyage), ['du' => $jour, 'au' => $jour, 'jours' => range(1, 7)])
            ->assertSessionHas('error');

        $this->assertSame(2, Voyage::count());
    }

    public function test_the_whatsapp_link_carries_the_trip_and_a_working_pdf_link(): void
    {
        $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [9], 'mode_reglement_id' => $this->cash->id,
        ]);
        $billet = Reservation::sole();

        $message = rawurldecode(substr(WhatsApp::lien($billet), strlen('https://wa.me/?text=')));
        $this->assertStringContainsString('Casablanca → Marrakech', $message);
        $this->assertStringContainsString('Sara Alaoui', $message);

        // the shared PDF link opens without logging in, a guessed one does not
        $this->get(WhatsApp::lienPdf($billet))->assertOk()->assertHeader('content-type', 'application/pdf');
        $this->get(route('ticket.pdf.partage', $billet))->assertForbidden();
    }
}
