<?php

namespace Tests\Feature;

use App\Mail\ReservationMail;
use App\Models\Autocar;
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

/** Counter sales: a seller sells seats to a traveller standing at the counter and takes the money at once. */
class GuichetTest extends TestCase
{
    use RefreshDatabase;

    private User $vendeur;
    private Voyage $voyage;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->vendeur = User::factory()->create(['isadmin' => 1, 'name' => 'Salma Guichet']);
        $this->vendeur->roles()->attach(Role::where('slug', 'guichetier')->firstOrFail());

        $at = now()->addHours(5);
        $this->voyage = Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->copy()->addHours(3)->toDateString(),
            'heure_depart' => $at->format('H:i:00'), 'heure_arrivee' => $at->copy()->addHours(3)->format('H:i:00'),
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    private function vendre(array $seats, array $client = [])
    {
        $arrets = $this->voyage->fresh()->arrets;

        return $this->actingAs($this->vendeur)->post(route('reservation.admin.guichet.vendre'), array_merge([
            'voyage_id' => $this->voyage->id,
            'arret_depart_id' => $arrets->first()->id,
            'arret_arrivee_id' => $arrets->last()->id,
            'seats' => $seats,
            'nom' => 'Karim Voyageur',
            'telephone' => '06 12 34 56 78',
            'email' => 'karim@example.com',
            'mode' => 'especes',
        ], $client));
    }

    public function test_the_seller_finds_the_departure_and_sells_two_paid_seats(): void
    {
        $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet', ['date' => today()->toDateString(), 'voyage' => $this->voyage->id]))
            ->assertOk()->assertSee('Rabat')->assertSee('Encaisser et émettre les billets');

        $this->vendre([3, 4])->assertRedirect();

        $billets = Reservation::orderBy('num_siege')->get();
        $this->assertCount(2, $billets);
        $this->assertTrue($billets->every(fn ($b) => $b->isPaid() && $b->vendu_par === $this->vendeur->id));
        $this->assertSame(2, \App\Models\Encaissement::where('user_id', $this->vendeur->id)->count());
        $this->assertSame('karim@example.com', $billets->first()->user->email);
        // one e-mail with both tickets, no receipt per seat
        Mail::assertSent(ReservationMail::class, 1);
        Mail::assertSent(ReservationMail::class, fn ($m) => $m->type === 'confirmee' && $m->billets->count() === 2);

        $commande = $billets->first()->commande;
        $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet.vente', $commande))
            ->assertOk()->assertSee('wa.me/212612345678', false)->assertSee('200,00 DH');
        $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet.pdf', $commande))->assertOk();
    }

    public function test_a_seat_already_sold_is_refused(): void
    {
        $this->vendre([7])->assertRedirect();
        $this->vendre([7], ['email' => 'autre@example.com'])->assertSessionHas('error');
        $this->assertSame(1, Reservation::count());
    }

    public function test_a_traveller_without_email_is_never_mailed_and_found_again_by_phone(): void
    {
        $this->vendre([1], ['email' => null])->assertRedirect();
        $this->vendre([2], ['email' => null])->assertRedirect();

        $this->assertSame(1, User::where('isadmin', 0)->count()); // same phone = same traveller
        $this->assertStringEndsWith('.invalid', Reservation::first()->user->email);
        Mail::assertNothingSent();
        // counter sales are paid: they never use the "2 unpaid bookings per month" quota
        $this->assertTrue(Reservation::first()->user->mayBookUnpaid());
    }

    public function test_only_the_name_is_needed_and_the_ticket_is_paid_at_once(): void
    {
        $this->vendre([9], ['nom' => 'Fatima Sans Tel', 'telephone' => null, 'email' => null])->assertSessionHasNoErrors()->assertRedirect();
        $this->vendre([10], ['nom' => 'Omar Sans Tel', 'telephone' => '', 'email' => null])->assertSessionHasNoErrors();

        $billets = Reservation::orderBy('num_siege')->get();
        $this->assertSame(['Fatima Sans Tel', 'Omar Sans Tel'], $billets->map->passager()->all());
        $this->assertTrue($billets->every->isPaid());
        // both on the shared counter account: no junk account per sale, nothing mailed
        $this->assertSame(1, $billets->pluck('user_id')->unique()->count());
        Mail::assertNothingSent();
    }

    public function test_the_seller_fixes_a_typo_and_prints_on_a_receipt_printer(): void
    {
        $this->vendre([12], ['nom' => 'Krim Voyagur'])->assertRedirect();
        $billet = Reservation::sole();
        $qr = $billet->verificationUrl();

        $this->actingAs($this->vendeur)->patch(route('reservation.admin.guichet.passager', $billet), ['passager_nom' => 'Karim Voyageur'])
            ->assertSessionHas('success');
        $this->assertSame('Karim Voyageur', $billet->fresh()->passager());
        $this->assertSame($qr, $billet->fresh()->verificationUrl()); // same ticket, same QR code

        $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet.imprimer', [$billet->commande, 'largeur' => 58]))
            ->assertOk()->assertSee('size: 58mm ', false)->assertSee('<div class="seat-big">12</div>', false)->assertSee('Karim Voyageur');
        $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet.vente', $billet->commande))
            ->assertOk()->assertSee('WhatsApp avec le PDF');
    }

    public function test_the_list_shows_departures_from_the_chosen_day_on_closest_first(): void
    {
        $at = now()->addDays(2)->setTime(9, 30);
        $plusTard = Voyage::create(array_merge($this->voyage->only(['ville_depart_id', 'ville_arrivee_id', 'autocar_id', 'type_voyage_id', 'prix']), [
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '09:30:00', 'heure_arrivee' => '12:30:00',
        ]));

        $departs = $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet', ['date' => today()->toDateString()]))
            ->assertOk()->viewData('departs');
        $this->assertSame([$this->voyage->id, $plusTard->id], $departs->map(fn ($d) => $d->voyage->id)->all());

        // from the day after tomorrow: only the later one
        $departs = $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet', ['date' => $at->toDateString()]))->viewData('departs');
        $this->assertSame([$plusTard->id], $departs->map(fn ($d) => $d->voyage->id)->all());
    }

    public function test_the_seller_corrects_seat_mode_and_a_ticket_too_many(): void
    {
        $this->vendre([3, 4])->assertRedirect();
        [$a, $b] = Reservation::orderBy('num_siege')->get()->all();

        // wrong seat: 3 -> 20, same ticket
        $this->actingAs($this->vendeur)->patch(route('reservation.admin.guichet.siege', $a), ['num_siege' => 20])->assertSessionHas('success');
        $this->assertSame(20, (int) $a->fresh()->num_siege);
        // a seat already sold is refused
        $this->actingAs($this->vendeur)->patch(route('reservation.admin.guichet.siege', $a), ['num_siege' => 4])->assertSessionHas('error');

        // wrong button: cash -> card
        $this->actingAs($this->vendeur)->patch(route('reservation.admin.guichet.mode', $a->commande), ['mode' => 'carte']);
        $this->assertSame(['carte'], \App\Models\Encaissement::pluck('mode')->unique()->values()->all());

        // one ticket too many: cancelled, seat free, money given back in the drawer
        $this->actingAs($this->vendeur)->delete(route('reservation.admin.guichet.retirer', $b))->assertSessionHas('success');
        $this->assertTrue($b->fresh()->isCancelled());
        $this->assertEquals(100.0, (float) \App\Models\Encaissement::where('user_id', $this->vendeur->id)->sum('montant'));
        $this->vendre([4], ['email' => 'autre@example.com'])->assertSessionHasNoErrors(); // seat 4 is free again
    }

    public function test_after_30_minutes_only_the_refund_right_can_correct(): void
    {
        $this->vendre([5, 6])->assertRedirect();
        $commande = Reservation::first()->commande;
        $this->travel(31)->minutes();

        $this->actingAs($this->vendeur)->delete(route('reservation.admin.guichet.annuler', $commande))->assertForbidden();

        $service = User::factory()->create(['isadmin' => 1]);
        $service->roles()->attach(Role::where('slug', 'service-client')->firstOrFail());
        $this->actingAs($service)->delete(route('reservation.admin.guichet.annuler', $commande))->assertRedirect(route('reservation.admin.guichet'));
        $this->assertSame(0, Reservation::count()); // both cancelled (hidden by the active scope)
        $this->assertEquals(0.0, (float) \App\Models\Encaissement::sum('montant'));
    }

    public function test_each_part_of_a_trip_with_a_stop_is_listed_and_sold_with_its_own_price(): void
    {
        // Rabat 5 h from now -> Kenitra (stop, 40 DH from Rabat) -> Tanger (100 DH)
        $kenitra = Ville::firstOrCreate(['ville' => 'Kenitra']);
        $this->voyage->syncArrets([['ville_id' => $kenitra->id, 'heure' => now()->addHours(6)->format('H:i'), 'prix' => 40]]);

        $departs = $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet', ['date' => today()->toDateString()]))
            ->assertOk()->assertSee('bus Rabat → Tanger')->viewData('departs');
        $lignes = $departs->map(fn ($d) => $d->depart->ville->ville . '-' . $d->arrivee->ville->ville . ':' . $d->prix)->sort()->values()->all();
        $this->assertSame(['Kenitra-Tanger:60', 'Rabat-Kenitra:40', 'Rabat-Tanger:100'], $lignes);

        // the counter picks Rabat -> Kenitra: 40 DH, and Kenitra -> Tanger can still sell the same seat
        $arrets = $this->voyage->fresh()->arrets;
        $this->actingAs($this->vendeur)->get(route('reservation.admin.guichet', ['voyage' => $this->voyage->id, 'ad' => $arrets[0]->id, 'aa' => $arrets[1]->id]))
            ->assertOk()->assertSee('40,00 DH');
        $this->actingAs($this->vendeur)->post(route('reservation.admin.guichet.vendre'), [
            'voyage_id' => $this->voyage->id, 'arret_depart_id' => $arrets[0]->id, 'arret_arrivee_id' => $arrets[1]->id,
            'seats' => [9], 'nom' => 'Court trajet', 'mode' => 'especes',
        ])->assertSessionHasNoErrors();
        $this->assertEquals(40, Reservation::sole()->prix);
        $this->assertNotContains(9, $this->voyage->fresh()->seatsTaken($arrets[1], $arrets[2]));
    }

    public function test_a_controller_cannot_sell(): void
    {
        $controleur = User::factory()->create(['isadmin' => 1]);
        $controleur->roles()->attach(Role::where('slug', 'controleur')->firstOrFail());

        $this->actingAs($controleur)->get(route('reservation.admin.guichet'))->assertForbidden();
        // ...but opens the passenger list of a bus from the scanner
        $this->actingAs($controleur)->get(route('voyages.passagers', $this->voyage))->assertOk()->assertSee('Scanner ce bus');
    }
}
