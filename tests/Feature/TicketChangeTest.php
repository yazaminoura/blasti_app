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

/** Moving a ticket to another departure, payment status on the QR check page. */
class TicketChangeTest extends TestCase
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

    private function voyage(int $days, float $prix = 150, string $hour = '08:00:00'): Voyage
    {
        $at = now()->addDays($days);

        return Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => $hour, 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => $prix,
        ]);
    }

    private function book(Voyage $voyage, int $seat = 5): Reservation
    {
        $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $voyage->id, 'seats' => [$seat], 'mode_reglement_id' => $this->cash->id,
        ]);

        return Reservation::latest('id')->firstOrFail();
    }

    public function test_the_client_moves_a_ticket_to_another_day(): void
    {
        $ticket = $this->book($this->voyage(5));
        $other = $this->voyage(8);

        $this->actingAs($this->client)->get(route('client.reservations.change', $ticket))
            ->assertOk()->assertSee(__('Choisissez le nouveau départ'));

        $this->actingAs($this->client)->put(route('client.reservations.change.update', $ticket), [
            'voyage_id' => $other->id, 'seats' => [12],
        ])->assertRedirect(route('ticket.show', $ticket->id));

        $ticket->refresh();
        $this->assertSame($other->id, $ticket->voyage_id);
        $this->assertSame(12, (int) $ticket->num_siege);
        $this->assertSame(now()->addDays(8)->toDateString(), \Carbon\Carbon::parse($ticket->date_depart)->toDateString());
        $this->assertNotNull($ticket->modifiee_le);
        // same number (and QR), the old seat is free again, the new one taken
        $this->assertSame(1, Reservation::count());
        $this->assertContains(12, $other->fresh()->seatsTaken(...$other->fresh()->segmentFor()));
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'modifiee' && count($mail->attachments()) === 1);
    }

    public function test_no_change_less_than_48_hours_before_departure(): void
    {
        $ticket = $this->book($this->voyage(1));
        $other = $this->voyage(6);

        $this->actingAs($this->client)->get(route('client.reservations.change', $ticket))->assertRedirect(route('ticket.show', $ticket->id));
        $this->actingAs($this->client)->put(route('client.reservations.change.update', $ticket), ['voyage_id' => $other->id, 'seats' => [3]])
            ->assertSessionHas('error');
        $this->assertNotSame($other->id, $ticket->fresh()->voyage_id);
    }

    public function test_someone_else_cannot_change_my_ticket(): void
    {
        $ticket = $this->book($this->voyage(5));

        $this->actingAs(User::factory()->create())->get(route('client.reservations.change', $ticket))->assertForbidden();
    }

    public function test_a_taken_seat_cannot_be_chosen(): void
    {
        $other = $this->voyage(8);
        $this->book($other, 12);
        $ticket = $this->book($this->voyage(5));

        $this->actingAs($this->client)->put(route('client.reservations.change.update', $ticket), ['voyage_id' => $other->id, 'seats' => [12]])
            ->assertSessionHas('error');
        $this->assertNotSame($other->id, $ticket->fresh()->voyage_id);
    }

    public function test_a_paid_ticket_moved_to_a_dearer_departure_owes_the_difference(): void
    {
        $ticket = $this->book($this->voyage(5, 150));
        $ticket->markPaid('guichet');
        $dearer = $this->voyage(9, 200);

        $this->actingAs($this->client)->put(route('client.reservations.change.update', $ticket), ['voyage_id' => $dearer->id, 'seats' => [7]]);

        $ticket->refresh();
        $this->assertSame(50.0, $ticket->resteAPayer());
        $this->get($ticket->verificationUrl())->assertSee(__('Billet NON PAYÉ'))->assertSee('50,00');

        // the controller collects the supplement: fully paid again
        $ticket->markPaid('guichet');
        $this->assertSame(0.0, $ticket->fresh()->resteAPayer());
    }

    public function test_a_cheaper_departure_is_not_refunded(): void
    {
        $ticket = $this->book($this->voyage(5, 150));
        $ticket->markPaid('guichet');
        $cheaper = $this->voyage(9, 120);

        $this->actingAs($this->client)->put(route('client.reservations.change.update', $ticket), ['voyage_id' => $cheaper->id, 'seats' => [7]]);

        $ticket->refresh();
        $this->assertSame(0.0, $ticket->resteAPayer());
        $this->assertSame(150.0, $ticket->montantPaye());
    }

    public function test_the_qr_page_says_when_a_ticket_is_not_paid(): void
    {
        $ticket = $this->book($this->voyage(5));

        $this->get($ticket->verificationUrl())->assertOk()->assertSee(__('Billet NON PAYÉ'))->assertSee('150,00');

        $ticket->markPaid('guichet');
        $this->get($ticket->verificationUrl())->assertSee(__('Billet valable · payé'));
    }
}
