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
use Tests\TestCase;

/** Buses with intermediate stops (seats resold per segment) and the refund policy. */
class StopsAndRefundsTest extends TestCase
{
    use RefreshDatabase;

    private Voyage $voyage;
    private ModeReglement $cash;
    private array $ville = [];

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        config(['safar.annulation.paliers' => [2 => 100, 1 => 90, 0 => 50]]);

        foreach (['Fès', 'Imouzzer', 'Meknès', 'Rabat'] as $nom) {
            $this->ville[$nom] = Ville::create(['ville' => $nom])->id;
        }
        $this->cash = ModeReglement::create(['mode_reglement' => 'Espèces']);

        // Fès 08:00 → Imouzzer 08:40 (25 DH) → Meknès 10:10 (50 DH) → Rabat 12:30 (120 DH), in 5 days
        $this->voyage = $this->makeVoyage(now()->addDays(5));
    }

    private function makeVoyage(\Carbon\Carbon $day): Voyage
    {
        $voyage = Voyage::create([
            'date_depart' => $day->toDateString(), 'heure_depart' => '08:00:00',
            'date_arrivee' => $day->toDateString(), 'heure_arrivee' => '12:30:00',
            'ville_depart_id' => $this->ville['Fès'], 'ville_arrivee_id' => $this->ville['Rabat'],
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 120,
        ]);
        $voyage->syncArrets([
            ['ville_id' => $this->ville['Imouzzer'], 'heure' => '08:40', 'prix' => 25],
            ['ville_id' => $this->ville['Meknès'], 'heure' => '10:10', 'prix' => 50],
        ]);

        return $voyage->fresh();
    }

    private function stop(string $ville, ?Voyage $voyage = null)
    {
        return ($voyage ?? $this->voyage)->arrets()->where('ville_id', $this->ville[$ville])->first();
    }

    private function book(string $from, string $to, int $seat, ?User $user = null)
    {
        return $this->actingAs($user ?? User::factory()->create())->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [$seat], 'mode_reglement_id' => $this->cash->id,
            'arret_depart_id' => $this->stop($from)->id, 'arret_arrivee_id' => $this->stop($to)->id,
        ]);
    }

    // ---- Stops and segments ----

    public function test_a_voyage_keeps_its_stops_in_order(): void
    {
        $this->assertSame(['Fès', 'Imouzzer', 'Meknès', 'Rabat'], $this->voyage->arrets->map(fn ($a) => $a->ville->ville)->all());
        $this->assertSame([0, 1, 2, 3], $this->voyage->arrets->pluck('ordre')->all());
    }

    public function test_a_seat_left_at_a_stop_is_sold_again_for_the_rest_of_the_trip(): void
    {
        $this->book('Fès', 'Imouzzer', 5)->assertRedirect();

        // Imouzzer → Rabat: seat 5 is free again
        $this->book('Imouzzer', 'Rabat', 5)->assertSessionHasNoErrors()->assertSessionMissing('error');
        $this->assertSame(2, Reservation::where('num_siege', 5)->count());

        // but Fès → Meknès overlaps both tickets
        $this->book('Fès', 'Meknès', 5)->assertSessionHas('error');
        $this->assertSame(2, Reservation::where('num_siege', 5)->count());
    }

    public function test_overlapping_segments_share_nothing(): void
    {
        $this->book('Imouzzer', 'Meknès', 7);

        $this->book('Fès', 'Rabat', 7)->assertSessionHas('error');
        $this->book('Meknès', 'Rabat', 7)->assertSessionMissing('error');
        $this->assertSame(2, Reservation::where('num_siege', 7)->count());
    }

    public function test_the_ticket_holds_the_segment_price_and_times(): void
    {
        $this->book('Imouzzer', 'Meknès', 3);
        $reservation = Reservation::sole();

        $this->assertEquals(25, $reservation->prix);
        $this->assertSame($this->ville['Imouzzer'], (int) $reservation->ville_depart_id);
        $this->assertSame($this->ville['Meknès'], (int) $reservation->ville_arrivee_id);
        $this->assertSame('08:40', substr($reservation->heure_depart, 0, 5));
        $this->assertSame('10:10', substr($reservation->heure_arrivee, 0, 5));
    }

    public function test_search_finds_intermediate_stops_in_the_right_direction_only(): void
    {
        $ids = fn ($from, $to) => Voyage::serving($this->ville[$from], $this->ville[$to])->pluck('id')->all();

        $this->assertSame([$this->voyage->id], $ids('Imouzzer', 'Meknès'));
        $this->assertSame([$this->voyage->id], $ids('Fès', 'Meknès'));
        $this->assertSame([], $ids('Meknès', 'Imouzzer'));

        $this->get(route('voyages.client.index', ['ville_depart' => $this->ville['Imouzzer'], 'ville_arrivee' => $this->ville['Meknès']]))
            ->assertOk()->assertSee('25,00');
    }

    public function test_search_on_a_day_without_bus_shows_the_next_departures(): void
    {
        $response = $this->get(route('voyages.client.index', [
            'ville_depart' => $this->ville['Fès'], 'ville_arrivee' => $this->ville['Rabat'], 'date_depart' => now()->addDay()->toDateString(),
        ]));

        $response->assertOk()->assertViewHas('autresDates', true);
    }

    public function test_the_booking_page_shows_the_route_and_the_segment_seats(): void
    {
        $this->book('Fès', 'Imouzzer', 9);

        $this->actingAs(User::factory()->create())
            ->get(route('client.reservations.show', ['voyage' => $this->voyage->id, 'de' => $this->stop('Imouzzer')->id, 'a' => $this->stop('Rabat')->id]))
            ->assertOk()
            ->assertViewHas('reservedSeats', [])
            ->assertViewHas('prix', 95.0);
    }

    public function test_the_bus_can_still_be_boarded_further_on_after_leaving_the_first_stop(): void
    {
        $this->travelTo($this->stop('Fès')->passage_at->copy()->addMinutes(10));

        $this->assertSame([$this->voyage->id], Voyage::serving($this->ville['Meknès'], $this->ville['Rabat'])->pluck('id')->all());
        $this->assertSame([], Voyage::serving($this->ville['Fès'], $this->ville['Rabat'])->pluck('id')->all());
        $this->book('Meknès', 'Rabat', 2)->assertSessionMissing('error');
        $this->book('Fès', 'Rabat', 3)->assertSessionHas('error');
    }

    // ---- Admin ----

    public function test_admin_cannot_remove_a_stop_that_has_tickets(): void
    {
        $this->book('Fès', 'Imouzzer', 4);

        $this->expectException(\DomainException::class);
        $this->voyage->syncArrets([['ville_id' => $this->ville['Meknès'], 'heure' => '10:10', 'prix' => 50]]);
    }

    public function test_admin_moving_a_stop_updates_the_tickets_sold_on_it(): void
    {
        $this->book('Imouzzer', 'Meknès', 6);
        $admin = User::factory()->create(['isadmin' => 1]);

        $data = $this->voyage->only(['ville_depart_id', 'ville_arrivee_id', 'autocar_id', 'type_voyage_id', 'prix', 'date_depart', 'date_arrivee']) + [
            'heure_depart' => '08:00', 'heure_arrivee' => '12:30',
            'arrets' => [
                ['ville_id' => $this->ville['Imouzzer'], 'heure' => '08:50', 'prix' => 25],
                ['ville_id' => $this->ville['Meknès'], 'heure' => '10:20', 'prix' => 50],
            ],
        ];

        $this->actingAs($admin)->put(route('voyages.update', $this->voyage->id), $data)->assertSessionHasNoErrors();

        $ticket = Reservation::sole();
        $this->assertSame('08:50', substr($ticket->heure_depart, 0, 5));
        $this->assertSame('10:20', substr($ticket->heure_arrivee, 0, 5));
        $this->assertEquals(25, $ticket->prix); // the price already paid never changes
    }

    public function test_stop_prices_must_rise_along_the_route(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        $data = $this->voyage->only(['ville_depart_id', 'ville_arrivee_id', 'autocar_id', 'type_voyage_id', 'prix', 'date_depart', 'date_arrivee']) + [
            'heure_depart' => '08:00', 'heure_arrivee' => '12:30',
            'arrets' => [
                ['ville_id' => $this->ville['Imouzzer'], 'heure' => '08:40', 'prix' => 60],
                ['ville_id' => $this->ville['Meknès'], 'heure' => '10:10', 'prix' => 50],
            ],
        ];

        $this->actingAs($admin)->put(route('voyages.update', $this->voyage->id), $data)->assertSessionHasErrors('arrets.1.prix');
    }

    // ---- Refund policy ----

    /** Paid ticket (120 DH) cancelled by the client at $when (a closure getting the departure time). */
    private function paidTicketCancelledAt(\Closure $when): Reservation
    {
        $this->book('Fès', 'Rabat', 1);
        $reservation = Reservation::sole();
        $reservation->markPaid('TEST');
        $this->travelTo($when($reservation->departAt()->copy()));

        $this->actingAs($reservation->user)->post(route('client.reservations.cancel', $reservation));

        return $reservation->fresh();
    }

    public function test_refund_is_full_two_days_or_more_before(): void
    {
        $r = $this->paidTicketCancelledAt(fn ($d) => $d->subDays(2)->setTime(23, 50));
        $this->assertTrue($r->isCancelled());
        $this->assertEquals(120, $r->montant_rembourse);
        $this->assertEquals(0, $r->frais_annulation);
    }

    public function test_refund_the_day_before_keeps_10_percent(): void
    {
        // late in the evening before a morning bus: still "the day before" (calendar days, not hours)
        $this->assertEquals(108, $this->paidTicketCancelledAt(fn ($d) => $d->subDay()->setTime(23, 50))->montant_rembourse);
        $this->assertEquals(90, Reservation::refundPercent(1));
    }

    public function test_refund_the_departure_day_keeps_half(): void
    {
        $r = $this->paidTicketCancelledAt(fn ($d) => $d->subMinutes(30));
        $this->assertEquals(60, $r->montant_rembourse);
        $this->assertEquals(60, $r->frais_annulation);
        $this->assertTrue($r->needsRefund());
    }

    public function test_no_cancellation_after_departure(): void
    {
        $this->book('Fès', 'Rabat', 1);
        $reservation = Reservation::sole();
        $this->travelTo($reservation->departAt()->addMinute());

        $this->actingAs($reservation->user)->post(route('client.reservations.cancel', $reservation))->assertSessionHas('error');
        $this->assertFalse($reservation->fresh()->isCancelled());
    }

    public function test_an_unpaid_ticket_is_cancelled_for_free(): void
    {
        $this->book('Fès', 'Rabat', 1);
        $reservation = Reservation::sole();
        $this->travelTo($reservation->departAt()->subHours(2));

        $this->actingAs($reservation->user)->post(route('client.reservations.cancel', $reservation));

        $reservation->refresh();
        $this->assertTrue($reservation->isCancelled());
        $this->assertNull($reservation->montant_rembourse);
        $this->assertFalse($reservation->needsRefund());
    }

    public function test_a_company_cancellation_is_refunded_in_full(): void
    {
        $this->book('Fès', 'Rabat', 1);
        $reservation = Reservation::sole();
        $reservation->markPaid('TEST');
        $this->travelTo($reservation->departAt()->subHours(2));

        $reservation->cancel('admin');

        $this->assertEquals(120, $reservation->fresh()->montant_rembourse);
    }
}
