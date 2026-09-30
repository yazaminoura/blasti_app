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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Presence confirmation of unpaid tickets, cap on unpaid seats, no-shows, controller at the bus door. */
class UnpaidTicketRulesTest extends TestCase
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

    private function book(Voyage $voyage, array $seats, ?User $user = null)
    {
        return $this->actingAs($user ?? $this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $voyage->id, 'seats' => $seats, 'mode_reglement_id' => $this->cash->id,
        ]);
    }

    public function test_unpaid_ticket_gets_an_email_after_24h_then_is_cancelled_24h_later_without_answer(): void
    {
        $this->book($this->voyage(24 * 5), [4]);
        $ticket = Reservation::sole();
        $booked = $ticket->created_at->copy();

        // 23 h after booking: nothing yet
        $this->travelTo($booked->copy()->addHours(23));
        $this->artisan('reservations:presence')->assertSuccessful();
        Mail::assertNotSent(ReservationMail::class, fn ($mail) => $mail->type === 'presence');

        // 24 h after booking: the "pay or confirm" e-mail
        $this->travelTo($booked->copy()->addHours(24)->addMinute());
        $this->artisan('reservations:presence')->assertSuccessful();
        $this->assertNotNull($ticket->fresh()->confirmation_demandee_le);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'presence');

        // 24 h after the e-mail, no payment, no answer: cancelled, seat free again
        $this->travelTo($booked->copy()->addHours(48)->addMinutes(2));
        $this->artisan('reservations:presence')->assertSuccessful();
        $this->assertTrue($ticket->fresh()->isCancelled());
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'sans_confirmation');
    }

    public function test_confirmed_but_unpaid_is_cancelled_36h_after_the_email(): void
    {
        $this->book($this->voyage(24 * 5), [4]);
        $ticket = Reservation::sole();
        $booked = $ticket->created_at->copy();

        $this->travelTo($booked->copy()->addHours(24)->addMinute());
        $this->artisan('reservations:presence');
        $this->get($ticket->fresh()->presenceUrl())->assertOk()->assertSee(__('Merci, votre siège est gardé !'));

        // 30 h after the e-mail: confirmed, still kept
        $this->travelTo($booked->copy()->addHours(24 + 30));
        $this->artisan('reservations:presence');
        $this->assertFalse($ticket->fresh()->isCancelled());

        // 36 h after the e-mail, still not paid: cancelled
        $this->travelTo($booked->copy()->addHours(24 + 36)->addMinutes(2));
        $this->artisan('reservations:presence');
        $this->assertTrue($ticket->fresh()->isCancelled());
    }

    public function test_a_paid_ticket_is_never_asked_nor_cancelled(): void
    {
        $this->book($this->voyage(24 * 5), [4]);
        $ticket = Reservation::sole();
        $ticket->markPaid('guichet');
        $booked = $ticket->created_at->copy();

        $this->travelTo($booked->copy()->addHours(25));
        $this->artisan('reservations:presence');
        $this->travelTo($booked->copy()->addHours(70));
        $this->artisan('reservations:presence');

        $this->assertFalse($ticket->fresh()->isCancelled());
        Mail::assertNotSent(ReservationMail::class, fn ($mail) => $mail->type === 'presence');
    }

    public function test_a_bus_leaving_before_the_deadline_is_paid_at_the_door_never_cancelled(): void
    {
        $this->book($this->voyage(30), [4]);
        $ticket = Reservation::sole();
        $booked = $ticket->created_at->copy();

        // e-mail at 24 h, but the 24 h deadline is after departure (30 h): not cancelled before the bus leaves
        $this->travelTo($booked->copy()->addHours(24)->addMinute());
        $this->artisan('reservations:presence');
        $this->assertNull($ticket->fresh()->cancellationDue());
        $this->travelTo($booked->copy()->addHours(29));
        $this->artisan('reservations:presence');
        $this->assertFalse($ticket->fresh()->isCancelled());
    }

    public function test_unpaid_seats_are_capped(): void
    {
        config(['safar.max_non_payes' => 4]);
        $this->book($this->voyage(24 * 5), [1, 2, 3]);

        $this->book($this->voyage(24 * 6), [1, 2])->assertSessionHas('error');
        $this->assertSame(3, Reservation::count());
    }

    public function test_no_shows_lose_pay_at_boarding(): void
    {
        config(['safar.absences_max' => 2]);
        // two past buses where the controller scanned other travellers but never this client
        foreach ([1, 2] as $i) {
            $voyage = $this->voyage(24 * 5);
            $this->book($voyage, [1]);
            $this->book($voyage, [2], $other = User::factory()->create());
            Reservation::where('user_id', $other->id)->update(['embarque_le' => now()]);
            $voyage->update(['date_depart' => now()->subDays($i)->toDateString()]);
            Reservation::where('voyage_id', $voyage->id)->update(['date_depart' => now()->subDays($i)->toDateString()]);
        }
        $this->assertSame(2, $this->client->absences());
        $this->assertFalse($this->client->mayPayAtBoarding());

        $this->book($this->voyage(24 * 7), [5])->assertSessionHasErrors('mode_reglement_id');
    }

    public function test_the_controller_collects_the_money_then_lets_the_traveller_in(): void
    {
        $this->book($this->voyage(24 * 3), [6]);
        $ticket = Reservation::sole();
        $controller = User::factory()->create(['isadmin' => 1]); // super admin

        // boarding day, at the bus door
        $this->travelTo($ticket->departAt()->subHour());

        // not paid yet: boarding refused
        $this->actingAs($controller)->patch(route('reservation.admin.embarquer', $ticket))->assertSessionHas('error');
        $this->actingAs($controller)->get($ticket->verificationUrl())->assertSee(__('Encaisser :montant DH en espèces', ['montant' => '100,00']));

        $this->actingAs($controller)->patch(route('reservation.admin.payer', $ticket));
        $this->actingAs($controller)->patch(route('reservation.admin.embarquer', $ticket))->assertSessionHas('success');
        $this->assertTrue($ticket->fresh()->isBoarded());

        $this->actingAs($controller)->get(route('voyages.passagers', $ticket->voyage_id))->assertOk()->assertSee('Embarqué');
    }

    public function test_a_client_cannot_board_himself(): void
    {
        $this->book($this->voyage(24 * 3), [6]);

        // the back-office guard sends non-staff away: nothing changes
        $this->actingAs($this->client)->patch(route('reservation.admin.embarquer', Reservation::sole()));
        $this->assertFalse(Reservation::sole()->isBoarded());
        $this->actingAs($this->client)->get(Reservation::sole()->verificationUrl())->assertDontSee(__('Faire monter le voyageur'));
    }
}
