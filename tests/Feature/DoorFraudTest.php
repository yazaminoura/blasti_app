<?php

namespace Tests\Feature;

use App\Mail\ReservationMail;
use App\Models\Autocar;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use App\Support\Controle;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Cash at the bus door: receipt e-mail, no paid stamp on the PDF, red flags, 2 unpaid orders per month. */
class DoorFraudTest extends TestCase
{
    use RefreshDatabase;

    private ModeReglement $cash;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->cash = ModeReglement::create(['mode_reglement' => 'Espèces']);
        $this->client = User::factory()->create();
    }

    private function voyage(int $hoursAhead): Voyage
    {
        $at = now()->addHours($hoursAhead);

        return Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->copy()->addHours(3)->toDateString(),
            'heure_depart' => $at->format('H:i:00'), 'heure_arrivee' => $at->copy()->addHours(3)->format('H:i:00'),
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    private function book(Voyage $voyage, int $seat)
    {
        return $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $voyage->id, 'seats' => [$seat], 'mode_reglement_id' => $this->cash->id,
        ]);
    }

    public function test_cash_taken_by_the_staff_sends_a_receipt_with_the_collector_name(): void
    {
        $this->book($this->voyage(2), 5);
        $ticket = Reservation::sole();
        $controleur = User::factory()->create(['isadmin' => 1, 'name' => 'Ahmed Controle']);

        $ticket->encaisser($controleur);

        Mail::assertSent(ReservationMail::class, function ($mail) {
            return $mail->type === 'paiement' && str_contains($mail->render(), 'Ahmed Controle');
        });
    }

    public function test_the_pdf_ticket_has_no_paid_stamp(): void
    {
        $this->book($this->voyage(24), 5);
        $html = view('client.reservations.ticket-pdf', ['reservations' => collect([Reservation::sole()])])->render();

        $this->assertStringNotContainsString('À PAYER', $html);
        $this->assertStringNotContainsString('>PAYÉ<', $html);
        $this->assertStringContainsString('reçu par e-mail', $html);
    }

    public function test_scanned_to_pay_then_never_paid_is_a_red_flag(): void
    {
        $this->book($this->voyage(1), 5);
        $ticket = Reservation::sole();
        $controleur = User::factory()->create(['isadmin' => 1, 'name' => 'Ahmed Controle']);
        Controle::noter($ticket, $controleur); // "À encaisser"... and nothing recorded after

        $admin = User::factory()->create(['isadmin' => 1]);
        $this->travelTo($ticket->departAt()->addHours(2)); // the bus has left
        $this->actingAs($admin)->get(route('admin.alertes'))->assertOk()->assertSee('Ahmed Controle')->assertSee('#' . $ticket->id);

        // once paid, no more alert
        $ticket->encaisser($controleur);
        $this->assertSame(0, \App\Http\Controllers\Admin\AlertesController::query()->count());
    }

    public function test_only_two_unpaid_orders_per_month_then_card_only(): void
    {
        $voyage = $this->voyage(24 * 3);
        $this->book($voyage, 1)->assertRedirect();
        $this->book($voyage, 2)->assertRedirect();
        // cancelling does not give the quota back
        Reservation::where('num_siege', 2)->first()->cancel('client');

        $this->book($voyage, 3)->assertSessionHasErrors('mode_reglement_id');
        $this->assertSame(2, Reservation::withoutGlobalScope('active')->count());

        // next month: allowed again
        $this->travelTo(now()->addMonthNoOverflow()->startOfMonth()->addHour());
        $this->book($this->voyage(24 * 3), 4)->assertSessionHasNoErrors();
    }
}
