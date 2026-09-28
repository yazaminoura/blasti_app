<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\ModeReglement;
use App\Models\Option;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Rules added after the 2026-09-28 audit (bookings, voyage edits, capacity, exports, access). */
class BusinessRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Voyage $voyage;
    private ModeReglement $mode;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create(['isadmin' => 1]);
        $this->mode = ModeReglement::create(['mode_reglement' => 'Espèces']);
        $this->voyage = $this->makeVoyage(now()->addDays(3)->toDateString(), '08:00:00');
    }

    private function makeVoyage(string $date, string $hour, int $seats = 30): Voyage
    {
        $autocar = Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => $seats]);

        return Voyage::create([
            'date_depart' => $date, 'date_arrivee' => $date,
            'heure_depart' => $hour, 'heure_arrivee' => '23:59:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Fès'])->id,
            'autocar_id' => $autocar->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    private function sell(Voyage $voyage, int $seat, ?User $client = null): Reservation
    {
        return Reservation::create([
            'num_siege' => $seat, 'user_id' => ($client ?? User::factory()->create())->id, 'date_reservation' => now(),
            'mode_reglement_id' => $this->mode->id,
            'date_depart' => $voyage->date_depart, 'date_arrivee' => $voyage->date_arrivee,
            'heure_depart' => $voyage->heure_depart, 'heure_arrivee' => $voyage->heure_arrivee,
            'ville_depart_id' => $voyage->ville_depart_id, 'ville_arrivee_id' => $voyage->ville_arrivee_id,
            'autocar_id' => $voyage->autocar_id, 'type_voyage_id' => $voyage->type_voyage_id,
            'prix' => 100, 'frais' => 0, 'voyage_id' => $voyage->id,
        ]);
    }

    private function voyagePayload(array $overrides = []): array
    {
        return array_merge([
            'date_depart' => $this->voyage->date_depart, 'date_arrivee' => $this->voyage->date_arrivee,
            'heure_depart' => '08:00', 'heure_arrivee' => '12:00',
            'ville_depart_id' => $this->voyage->ville_depart_id, 'ville_arrivee_id' => $this->voyage->ville_arrivee_id,
            'autocar_id' => $this->voyage->autocar_id, 'type_voyage_id' => $this->voyage->type_voyage_id,
            'prix' => 100,
        ], $overrides);
    }

    // ---- Booking ----

    public function test_a_bus_that_left_earlier_today_cannot_be_booked(): void
    {
        $gone = $this->makeVoyage(today()->toDateString(), now()->subHour()->format('H:i:s'));

        $this->actingAs(User::factory()->create())->post(route('client.reservations.store'), [
            'voyage_id' => $gone->id, 'seats' => [1], 'mode_reglement_id' => $this->mode->id,
        ])->assertSessionHas('error');

        $this->assertSame(0, $gone->reservations()->count());
    }

    public function test_a_seat_sold_on_the_whole_trip_is_taken_everywhere(): void
    {
        $this->sell($this->voyage, 5);
        [$a, $b] = $this->voyage->fresh()->segmentFor();

        $this->assertContains(5, $this->voyage->fresh()->seatsTaken($a, $b));
    }

    public function test_departed_voyages_are_hidden_from_the_public_lists(): void
    {
        $this->makeVoyage(now()->subDays(2)->toDateString(), '08:00:00');

        $this->assertSame([$this->voyage->id], Voyage::bookable()->pluck('id')->all());
        $this->get(route('voyages.filter'))->assertOk();
    }

    public function test_guests_can_search_without_logging_in(): void
    {
        $this->get(route('voyages.client.index', ['ville_depart' => $this->voyage->ville_depart_id]))->assertOk();
    }

    // ---- Admin edits after seats are sold ----

    public function test_editing_a_voyage_updates_the_tickets_already_sold(): void
    {
        $reservation = $this->sell($this->voyage, 3);
        $newDate = now()->addDays(5)->toDateString();

        $this->actingAs($this->admin)
            ->put(route('voyages.update', $this->voyage), $this->voyagePayload(['date_depart' => $newDate, 'date_arrivee' => $newDate, 'heure_depart' => '10:00']))
            ->assertSessionHasNoErrors();

        $reservation->refresh();
        $this->assertSame($newDate, \Carbon\Carbon::parse($reservation->date_depart)->toDateString());
        $this->assertStringStartsWith('10:00', $reservation->heure_depart);
        $this->assertEquals(100, $reservation->prix, 'the price paid must not change');
    }

    public function test_a_voyage_cannot_arrive_before_it_leaves(): void
    {
        $this->actingAs($this->admin)
            ->put(route('voyages.update', $this->voyage), $this->voyagePayload(['heure_depart' => '18:00', 'heure_arrivee' => '08:00']))
            ->assertSessionHasErrors('heure_arrivee');
    }

    public function test_a_smaller_bus_cannot_replace_one_with_higher_seats_sold(): void
    {
        $this->sell($this->voyage, 25);
        $small = Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 20]);

        $this->actingAs($this->admin)
            ->put(route('voyages.update', $this->voyage), $this->voyagePayload(['autocar_id' => $small->id]))
            ->assertSessionHasErrors('autocar_id');
    }

    public function test_bus_capacity_cannot_go_below_a_sold_seat(): void
    {
        $this->sell($this->voyage, 25);
        $autocar = $this->voyage->autocar;

        $this->actingAs($this->admin)->put(route('autocars.update', $autocar), [
            'societe_id' => $autocar->societe_id, 'matricule' => $autocar->matricule, 'nbr_siege' => 20,
        ])->assertSessionHasErrors('nbr_siege');

        $this->assertSame(30, (int) $autocar->fresh()->nbr_siege);
    }

    public function test_the_same_option_cannot_be_linked_twice_to_a_bus(): void
    {
        $option = Option::create(['option' => 'Wi-Fi']);
        $data = ['autocar_id' => $this->voyage->autocar_id, 'option_id' => $option->id];

        $this->actingAs($this->admin)->post(route('autocaroptions.store'), $data)->assertSessionHasNoErrors();
        $this->actingAs($this->admin)->post(route('autocaroptions.store'), $data)->assertSessionHasErrors('option_id');
    }

    // ---- Exports and access ----

    public function test_export_neutralizes_excel_formulas(): void
    {
        $this->sell($this->voyage, 1, User::factory()->create(['name' => '=HYPERLINK("http://evil","x")']));

        $csv = $this->actingAs($this->admin)->get(route('admin.export.reservations'))->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $csv);
    }

    public function test_staff_without_the_reservations_permission_cannot_open_a_ticket(): void
    {
        $reservation = $this->sell($this->voyage, 2);
        $staff = User::factory()->create(['isadmin' => 1]);
        $role = Role::create(['name' => 'Flotte', 'slug' => 'flotte']);
        $role->permissions()->sync([Permission::firstOrCreate(['name' => 'autocars.read'], ['slug' => 'autocars-read'])->id]);
        $staff->roles()->sync([$role->id]);

        $this->actingAs($staff)->get(route('ticket.show', $reservation))->assertForbidden();
        $this->actingAs($this->admin)->get(route('ticket.show', $reservation))->assertOk();
    }

    public function test_a_client_with_bookings_gets_a_message_instead_of_a_crash_when_deleting_the_account(): void
    {
        $client = User::factory()->create();
        $this->sell($this->voyage, 4, $client);

        $this->actingAs($client)->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertSessionHasErrorsIn('userDeletion', 'password');

        $this->assertNotNull($client->fresh());
    }

    public function test_language_switch_never_redirects_to_another_site(): void
    {
        $this->withHeader('referer', 'https://evil.example/phish')
            ->get(route('lang.switch', 'fr'))
            ->assertRedirect(route('home'));
    }
}
