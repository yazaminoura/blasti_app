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

/** Fixes from the 2026-10-01 review: proxies, card holds, return discount, boarded cancel, payments, promos, company scope. */
class ReviewFixesTest extends TestCase
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
        $this->voyage = $this->voyage('Rabat', 'Tanger', now()->addDays(4));
    }

    private function voyage(string $from, string $to, $at, ?Societe $societe = null): Voyage
    {
        return Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '08:00:00', 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => $from])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => $to])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => ($societe ?? Societe::factory()->create())->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    private function card(): ModeReglement
    {
        config(['services.cmi.client_id' => '600000000', 'services.cmi.store_key' => 'TEST_STORE_KEY']);

        return ModeReglement::create(['mode_reglement' => 'Carte bancaire', 'en_ligne' => true]);
    }

    private function book(Voyage $voyage, array $seats, ModeReglement $mode, array $extra = [], ?User $user = null)
    {
        return $this->actingAs($user ?? $this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $voyage->id, 'seats' => $seats, 'mode_reglement_id' => $mode->id,
        ] + $extra);
    }

    public function test_one_account_holds_only_one_card_checkout_at_a_time(): void
    {
        $card = $this->card();
        $this->book($this->voyage, [1, 2, 3, 4, 5, 6], $card)->assertRedirect(route('payment.cmi.start', Reservation::orderBy('id')->first()));
        $commande = Reservation::first()->commande;

        // a second unpaid card order is refused: back to the first one
        $this->book($this->voyage, [7, 8, 9, 10, 11, 12], $card)
            ->assertRedirect(route('client.commande.show', $commande))->assertSessionHas('error');
        $this->assertSame(6, Reservation::count());

        // the client can give it up: seats free again, then book anew
        $this->actingAs($this->client)->get(route('client.commande.show', $commande))->assertOk()->assertSee(route('client.commande.abandon', $commande), false);
        $this->actingAs($this->client)->post(route('client.commande.abandon', $commande))->assertRedirect();
        $this->assertSame([], $this->voyage->fresh()->seatsTaken(...$this->voyage->fresh()->segmentFor()));
        $this->book($this->voyage, [7], $card)->assertRedirect(route('payment.cmi.start', Reservation::where('num_siege', 7)->first()));

        // nobody else can give up my payment
        $this->actingAs(User::factory()->create())->post(route('client.commande.abandon', Reservation::where('num_siege', 7)->first()->commande))->assertForbidden();
    }

    public function test_the_return_discount_is_used_once_and_lost_when_the_outbound_is_cancelled(): void
    {
        config(['safar.remise_retour_pourcent' => 10, 'safar.max_non_payes' => 0, 'safar.non_payes_par_mois' => 0]);
        $this->book($this->voyage, [5], $this->cash);
        $aller = Reservation::sole()->commande;

        // not a real return (Tanger → Fès is not back to Rabat): no discount
        $ailleurs = $this->voyage('Tanger', 'Fès', now()->addDays(6));
        $this->book($ailleurs, [1], $this->cash, ['retour_de' => $aller]);
        $this->assertEqualsWithDelta(0.0, (float) Reservation::where('voyage_id', $ailleurs->id)->sole()->remise, 0.001);
        $this->assertNull(Reservation::where('voyage_id', $ailleurs->id)->sole()->retour_de);

        // real return, 3 seats for a 1-seat outbound: discount on 1 seat only (10 % of 100)
        $retour = $this->voyage('Tanger', 'Rabat', now()->addDays(6));
        $this->book($retour, [1, 2, 3], $this->cash, ['retour_de' => $aller]);
        $this->assertEqualsWithDelta(10.0, (float) Reservation::where('voyage_id', $retour->id)->sum('remise'), 0.001);

        // the same outbound cannot discount a second return
        $encore = $this->voyage('Tanger', 'Rabat', now()->addDays(7));
        $this->book($encore, [1], $this->cash, ['retour_de' => $aller]);
        $this->assertEqualsWithDelta(0.0, (float) Reservation::where('voyage_id', $encore->id)->sole()->remise, 0.001);

        // outbound cancelled for free: the unpaid return tickets lose the discount
        $this->actingAs($this->client)->post(route('client.reservations.cancel', Reservation::where('commande', $aller)->sole()));
        $this->assertEqualsWithDelta(0.0, (float) Reservation::where('voyage_id', $retour->id)->sum('remise'), 0.001);
    }

    public function test_a_boarded_traveller_cannot_cancel(): void
    {
        $this->book($this->voyage, [5], $this->cash);
        $billet = Reservation::sole();
        $billet->encaisser(User::factory()->create(), 'especes', false);
        $billet->embarquer(User::factory()->create());

        $this->actingAs($this->client)->post(route('client.reservations.cancel', $billet))->assertSessionHas('error');
        $this->assertFalse($billet->fresh()->isCancelled());
    }

    private function cmiCallback(Reservation $billet, string $amount, string $trans = 'T1')
    {
        $data = ['oid' => \App\Support\Cmi::orderId($billet), 'amount' => $amount, 'ProcReturnCode' => '00', 'Response' => 'Approved', 'TransId' => $trans];
        $data['HASH'] = \App\Support\Cmi::hash($data);

        return $this->post(route('payment.cmi.callback'), $data);
    }

    public function test_a_payment_after_the_hold_expired_brings_the_ticket_back_if_the_seat_is_free(): void
    {
        $this->book($this->voyage, [5], $this->card());
        $billet = Reservation::sole();
        $this->travel(Reservation::DELAI_PAIEMENT_MINUTES + 1)->minutes();
        $this->assertSame(1, Reservation::expirePendingPayments());

        $this->cmiCallback($billet, '100.00')->assertSee('ACTION=POSTAUTH');

        $billet = $billet->fresh();
        $this->assertSame(Reservation::CONFIRMEE, $billet->statut);
        $this->assertTrue($billet->isPaid());
        $this->assertSame(5, (int) $billet->siege_actif);
        // resent callback: accepted again, nothing changes
        $this->cmiCallback($billet, '100.00')->assertSee('ACTION=POSTAUTH');
    }

    public function test_a_payment_after_the_seat_was_resold_is_kept_for_refund(): void
    {
        $this->book($this->voyage, [5], $this->card());
        $billet = Reservation::sole();
        $this->travel(Reservation::DELAI_PAIEMENT_MINUTES + 1)->minutes();
        Reservation::expirePendingPayments();
        $this->book($this->voyage, [5], $this->cash, [], User::factory()->create());

        $this->cmiCallback($billet, '100.00')->assertSee('ACTION=POSTAUTH');

        $billet = Reservation::withoutGlobalScope('active')->find($billet->id);
        $this->assertTrue($billet->isCancelled());
        $this->assertTrue($billet->needsRefund());
        $this->assertSame(1, Reservation::where('num_siege', 5)->count()); // never two live tickets on one seat
    }

    public function test_seats_cancelled_before_paying_are_not_charged(): void
    {
        $this->book($this->voyage, [5, 6, 7], $this->card());
        $billets = Reservation::orderBy('num_siege')->get();
        $this->actingAs($this->client)->post(route('client.reservations.cancel', $billets[2]));

        $this->assertSame('200.00', \App\Support\Cmi::amount($billets[0]));
        // the old full-order amount is refused
        $this->cmiCallback($billets[0], '300.00')->assertSee('FAILURE');
        $this->cmiCallback($billets[0], '200.00')->assertSee('ACTION=POSTAUTH');
        $this->assertSame(2, Reservation::whereNotNull('paye_le')->count());
        $this->assertFalse(Reservation::withoutGlobalScope('active')->find($billets[2]->id)->isPaid());
    }

    public function test_a_double_click_on_collect_takes_the_money_once(): void
    {
        $this->book($this->voyage, [5], $this->cash);
        // two requests loaded the same unpaid ticket before either saved
        $first = Reservation::sole();
        $second = Reservation::sole();
        $staff = User::factory()->create();

        $this->assertEqualsWithDelta(100.0, $first->encaisser($staff), 0.001);
        $this->assertEqualsWithDelta(0.0, $second->encaisser($staff), 0.001);
        $this->assertSame(1, \App\Models\Encaissement::count());
    }

    public function test_promo_codes_count_live_orders_per_client_and_in_total(): void
    {
        config(['safar.max_non_payes' => 0, 'safar.non_payes_par_mois' => 0]);
        $promo = \App\Models\Promotion::create(['code' => 'ETE', 'type' => 'pourcentage', 'valeur' => 10, 'max_utilisations' => 2, 'max_par_client' => 1, 'actif' => true]);
        $other = $this->voyage('Rabat', 'Tanger', now()->addDays(5));

        $this->book($this->voyage, [1], $this->cash, ['code_promo' => 'ETE'])->assertSessionMissing('error');
        // same client, second order: refused
        $this->book($other, [1], $this->cash, ['code_promo' => 'ETE'])->assertSessionHas('error');

        // another client takes the last use, a third one is refused
        $this->book($other, [2], $this->cash, ['code_promo' => 'ETE'], User::factory()->create())->assertSessionMissing('error');
        $this->book($other, [3], $this->cash, ['code_promo' => 'ETE'], User::factory()->create())->assertSessionHas('error');
        $this->assertSame(2, $promo->fresh()->utilisations);

        // a cancelled order gives its use back
        $this->actingAs($this->client)->post(route('client.reservations.cancel', Reservation::where('voyage_id', $this->voyage->id)->sole()));
        $this->assertSame(1, $promo->fresh()->utilisations);
        $this->book($other, [3], $this->cash, ['code_promo' => 'ETE'], User::factory()->create())->assertSessionMissing('error');
    }

    public function test_a_company_account_cannot_open_another_company_ticket(): void
    {
        $ctm = Societe::factory()->create();
        $mien = $this->voyage('Rabat', 'Tanger', now()->addDays(5), $ctm);
        $this->book($this->voyage, [1], $this->cash);
        $this->book($mien, [1], $this->cash, [], User::factory()->create());
        [$autre, $notre] = [Reservation::where('voyage_id', $this->voyage->id)->sole(), Reservation::where('voyage_id', $mien->id)->sole()];

        $role = \App\Models\Role::create(['name' => 'Compagnie test', 'slug' => 'compagnie-test']);
        $role->permissions()->sync([\App\Models\Permission::firstOrCreate(['name' => 'reservations.read'], ['slug' => 'reservations-read'])->id]);
        $staff = User::factory()->create(['isadmin' => 1, 'societe_id' => $ctm->id]);
        $staff->roles()->attach($role->id);

        $this->actingAs($staff)->get(route('ticket.show', $notre->id))->assertOk();
        $this->actingAs($staff)->get(route('ticket.show', $autre->id))->assertForbidden();
        $this->actingAs($staff)->get(route('ticket.download', $autre->id))->assertForbidden();
    }

    public function test_scheduled_emails_go_once_per_order_and_the_link_confirms_every_seat(): void
    {
        $voyage = $this->voyage('Rabat', 'Tanger', now()->addDay());
        $this->book($voyage, [1, 2, 3], $this->cash);
        $commande = Reservation::first()->commande;
        Mail::fake();

        $this->artisan('reservations:rappels')->assertSuccessful();
        Mail::assertSent(\App\Mail\ReservationMail::class, 1);
        Mail::assertSent(\App\Mail\ReservationMail::class, fn ($m) => $m->type === 'rappel' && $m->billets->count() === 3);
        $this->assertSame(3, Reservation::where('commande', $commande)->whereNotNull('rappel_envoye_le')->count());

        // "pay or confirm" (bus in 4 days): one e-mail, its link confirms the 3 seats
        $this->book($this->voyage, [1, 2, 3], $this->cash, [], $autre = User::factory()->create());
        $commande = Reservation::where('user_id', $autre->id)->first()->commande;
        $this->travel((int) config('safar.confirmation.demande_heures') + 1)->hours();
        $this->artisan('reservations:presence')->assertSuccessful();
        Mail::assertSent(\App\Mail\ReservationMail::class, fn ($m) => $m->type === 'presence' && $m->billets->count() === 3);
        $this->get(Reservation::where('commande', $commande)->first()->presenceUrl())->assertOk();
        $this->assertSame(3, Reservation::where('commande', $commande)->whereNotNull('presence_confirmee_le')->count());
    }

    public function test_no_review_request_for_a_no_show_on_a_scanned_bus(): void
    {
        $this->book($this->voyage, [1], $this->cash);
        $this->book($this->voyage, [2], $this->cash, [], $voyageur = User::factory()->create());
        Reservation::where('user_id', $voyageur->id)->sole()->embarquer(User::factory()->create());
        Mail::fake();

        $this->travelTo(now()->addDays(5));
        $this->artisan('reservations:avis')->assertSuccessful();

        Mail::assertSent(\App\Mail\ReservationMail::class, 1);
        Mail::assertSent(\App\Mail\ReservationMail::class, fn ($m) => $m->type === 'avis' && $m->hasTo($voyageur->email));
    }

    public function test_a_price_changed_after_the_payment_page_is_not_charged_silently(): void
    {
        // the payment page showed 100 DH; a "5 days or less: +20 DH" rule now applies
        config(['safar.majorations' => [['jours' => 5, 'type' => 'montant', 'valeur' => 20]]]);

        $this->book($this->voyage, [1], $this->cash, ['prix_affiche' => 100])->assertSessionHas('error');
        $this->assertSame(0, Reservation::count());

        $this->book($this->voyage, [1], $this->cash, ['prix_affiche' => 120])->assertSessionMissing('error');
        $this->assertEqualsWithDelta(120.0, (float) Reservation::sole()->prix, 0.001);
    }

    public function test_changing_seat_on_the_same_bus_keeps_the_price(): void
    {
        config(['safar.modification_heures' => 0]);
        $this->book($this->voyage, [1], $this->cash);
        $billet = Reservation::sole();
        $billet->encaisser(User::factory()->create(), 'especes', false);

        // prices went up since (closer to departure), the client only moves to seat 9
        config(['safar.majorations' => [['jours' => 5, 'type' => 'montant', 'valeur' => 20]]]);
        $this->actingAs($this->client)->put(route('client.reservations.change.update', $billet), ['voyage_id' => $this->voyage->id, 'seats' => [9]])
            ->assertSessionMissing('error');

        $billet->refresh();
        $this->assertSame(9, (int) $billet->num_siege);
        $this->assertEqualsWithDelta(100.0, (float) $billet->prix, 0.001);
        $this->assertEqualsWithDelta(0.0, $billet->resteAPayer(), 0.001);
    }

    public function test_a_faked_forwarded_ip_does_not_reset_the_login_limit(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 6) as $i) {
            $response = $this->withHeader('X-Forwarded-For', "203.0.113.$i")
                ->post('/login', ['email' => $user->email, 'password' => 'wrong-password']);
        }

        // 6th try from a "new IP" is still blocked: the header is not trusted
        $response->assertSessionHasErrors('email');
        $this->assertTrue(\Illuminate\Support\Facades\RateLimiter::tooManyAttempts(
            \Illuminate\Support\Str::transliterate(\Illuminate\Support\Str::lower($user->email).'|127.0.0.1'), 5
        ));
    }
}
