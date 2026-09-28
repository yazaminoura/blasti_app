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
use Tests\TestCase;

class ReservationFlowTest extends TestCase
{
    use RefreshDatabase;

    private Voyage $voyage;
    private ModeReglement $mode;

    protected function setUp(): void
    {
        parent::setUp();

        $depart = Ville::create(['ville' => 'Casablanca']);
        $arrivee = Ville::create(['ville' => 'Marrakech']);
        $societe = Societe::factory()->create();
        $autocar = Autocar::factory()->create(['societe_id' => $societe->id, 'nbr_siege' => 30]);
        $type = TypeVoyage::create(['type_voyage' => 'Confort']);
        $this->mode = ModeReglement::create(['mode_reglement' => 'Espèces']);

        $this->voyage = Voyage::create([
            'date_depart' => now()->addDays(3)->toDateString(),
            'date_arrivee' => now()->addDays(3)->toDateString(),
            'heure_depart' => '08:00:00',
            'heure_arrivee' => '12:00:00',
            'ville_depart_id' => $depart->id,
            'ville_arrivee_id' => $arrivee->id,
            'autocar_id' => $autocar->id,
            'type_voyage_id' => $type->id,
            'prix' => 150,
        ]);
    }

    private function book(User $user, int $seat)
    {
        return $this->actingAs($user)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id,
            'seats' => [$seat],
            'mode_reglement_id' => $this->mode->id,
        ]);
    }

    public function test_public_pages_render(): void
    {
        $this->get('/')->assertOk();
        $this->get(route('voyages.list'))->assertOk();
        $this->get(route('client.societes.showVoyageSociete.index', $this->voyage->autocar->societe_id))
            ->assertOk()
            ->assertSee('Casablanca');
    }

    public function test_booking_page_shows_every_seat(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('client.reservations.show', $this->voyage));

        $response->assertOk();
        // seats 1, 2, 5 and 7 used to be missing from the map
        foreach ([1, 2, 5, 7, 30] as $seat) {
            $response->assertSee('id="seat-' . $seat . '"', false);
        }
        $response->assertDontSee('id="seat-31"', false);
    }

    public function test_client_can_book_a_seat_and_see_the_ticket(): void
    {
        $user = User::factory()->create();

        $this->book($user, 5)->assertRedirect();

        $reservation = Reservation::sole();
        $this->assertSame(5, (int) $reservation->num_siege);
        $this->assertSame($user->id, $reservation->user_id);
        // bus capacity must not change when a seat is sold
        $this->assertSame(30, (int) $this->voyage->autocar->fresh()->nbr_siege);

        $this->actingAs($user)->get(route('ticket.show', $reservation->id))->assertOk();
    }

    public function test_a_seat_cannot_be_booked_twice(): void
    {
        $this->book(User::factory()->create(), 12);
        $this->book(User::factory()->create(), 12)->assertSessionHas('error');

        $this->assertSame(1, Reservation::count());
    }

    public function test_seat_outside_the_bus_is_rejected(): void
    {
        $this->book(User::factory()->create(), 31)->assertSessionHas('error');

        $this->assertSame(0, Reservation::count());
    }

    public function test_a_client_cannot_open_someone_elses_ticket(): void
    {
        $owner = User::factory()->create();
        $this->book($owner, 3);
        $reservation = Reservation::sole();

        $this->actingAs(User::factory()->create())
            ->get(route('ticket.show', $reservation->id))
            ->assertForbidden();

        $this->actingAs(User::factory()->create(['isadmin' => 1]))
            ->get(route('ticket.show', $reservation->id))
            ->assertOk();
    }

    public function test_non_admin_is_kept_out_of_admin_pages(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('voyages.index'))
            ->assertRedirect('/');
    }

    public function test_admin_pages_render(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        foreach (['admin', 'voyages.index', 'autocars.index', 'societes.index', 'villes.index',
                  'type_voyages.index', 'modeReglements.index', 'options.index', 'equipements.index',
                  'reservation.admin.index', 'admin.users.index'] as $route) {
            $this->actingAs($admin)->get(route($route))->assertOk();
        }
    }

    public function test_admin_can_update_voyage_hours(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->put(route('voyages.update', $this->voyage), [
            'date_depart' => $this->voyage->date_depart,
            'date_arrivee' => $this->voyage->date_arrivee,
            'heure_depart' => '09:30',
            'heure_arrivee' => '13:45',
            'ville_depart_id' => $this->voyage->ville_depart_id,
            'ville_arrivee_id' => $this->voyage->ville_arrivee_id,
            'autocar_id' => $this->voyage->autocar_id,
            'type_voyage_id' => $this->voyage->type_voyage_id,
            'prix' => 175,
        ])->assertSessionHasNoErrors()->assertRedirect(route('voyages.index'));

        $this->assertStringStartsWith('09:30', $this->voyage->fresh()->heure_depart);
    }

    public function test_deleting_a_ville_in_use_shows_an_error_instead_of_crashing(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)
            ->delete(route('villes.destroy', $this->voyage->ville_depart_id))
            ->assertRedirect(route('villes.index'))
            ->assertSessionHas('error');
    }
}
