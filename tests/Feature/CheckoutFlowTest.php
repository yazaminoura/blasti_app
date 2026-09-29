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

/** Seat map → payment page → booking → order page with every ticket (+ the local test card payment). */
class CheckoutFlowTest extends TestCase
{
    use RefreshDatabase;

    private Voyage $voyage;
    private ModeReglement $cash;
    private ModeReglement $card;
    private User $client;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->cash = ModeReglement::create(['mode_reglement' => 'Espèces']);
        $this->card = ModeReglement::create(['mode_reglement' => 'Carte bancaire', 'en_ligne' => true]);
        $this->client = User::factory()->create();
        $at = now()->addDays(4);
        $this->voyage = Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '08:00:00', 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
    }

    private function toPaymentPage(array $seats)
    {
        return $this->actingAs($this->client)->post(route('client.reservations.checkout'), ['voyage_id' => $this->voyage->id, 'seats' => $seats]);
    }

    public function test_the_seat_map_leads_to_a_payment_page_with_the_order(): void
    {
        $this->toPaymentPage([3, 4, 5])->assertRedirect(route('client.reservations.payment'));

        $this->actingAs($this->client)->get(route('client.reservations.payment'))
            ->assertOk()
            ->assertSee(__('Comment voulez-vous payer ?'))
            ->assertSee('300,00')
            ->assertSee(__('Payer à l\'embarquement'));
        // nothing booked before the client confirms
        $this->assertSame(0, Reservation::count());
    }

    public function test_paying_at_boarding_books_every_seat_and_shows_them_all(): void
    {
        $this->toPaymentPage([3, 4, 5]);
        $response = $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [3, 4, 5], 'mode_reglement_id' => $this->cash->id,
        ]);

        $commande = Reservation::first()->commande;
        $response->assertRedirect(route('client.commande.show', $commande));
        $this->assertSame(3, Reservation::where('commande', $commande)->count());

        $page = $this->actingAs($this->client)->get(route('client.commande.show', $commande))->assertOk();
        foreach (Reservation::all() as $billet) {
            $page->assertSee(route('ticket.show', $billet->id), false);
        }
        $page->assertSee('300,00');
        $this->actingAs($this->client)->get(route('client.commande.download', $commande))->assertOk()->assertHeader('content-type', 'application/pdf');
    }

    public function test_a_taken_seat_sends_the_client_back_to_the_seat_map(): void
    {
        // another client books seat 7 (tests share one session: done before this client fills the cart)
        $this->actingAs(User::factory()->create())->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [7], 'mode_reglement_id' => $this->cash->id,
        ]);
        $this->toPaymentPage([7]);

        $this->actingAs($this->client)->get(route('client.reservations.payment'))
            ->assertRedirect()->assertSessionHas('error');
    }

    public function test_someone_else_cannot_open_my_order(): void
    {
        $this->actingAs($this->client)->post(route('client.reservations.store'), ['voyage_id' => $this->voyage->id, 'seats' => [1], 'mode_reglement_id' => $this->cash->id]);

        $this->actingAs(User::factory()->create())->get(route('client.commande.show', Reservation::first()->commande))->assertForbidden();
    }

    public function test_local_test_card_payment_pays_the_whole_order(): void
    {
        $this->app['env'] = 'local'; // no CMI keys + local PC = test payment page
        // outside the "testing" env Laravel checks CSRF tokens again: not what this test is about
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [8, 9], 'mode_reglement_id' => $this->card->id,
        ])->assertRedirect(route('payment.cmi.start', Reservation::orderBy('id')->first()));
        $first = Reservation::orderBy('id')->first();
        $this->assertSame(Reservation::EN_ATTENTE, $first->statut);

        $this->actingAs($this->client)->get(route('payment.cmi.start', $first))->assertOk()->assertSee(__('Mode test'))->assertSee('200,00');

        $this->actingAs($this->client)->post(route('payment.test', $first), ['resultat' => 'ok'])
            ->assertRedirect(route('client.commande.show', $first->commande));
        $this->assertSame(2, Reservation::whereNotNull('paye_le')->where('statut', Reservation::CONFIRMEE)->count());
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->billets->count() === 2);
    }

    public function test_the_test_payment_page_never_exists_in_production(): void
    {
        $this->app['env'] = 'production';
        $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);

        $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => [8], 'mode_reglement_id' => $this->card->id,
        ])->assertSessionHasErrors('mode_reglement_id');
        $this->assertSame(0, Reservation::count());
    }
}
