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
use App\Support\Cmi;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/** Guests, sign up + email verification, several seats per order, QR code of the tickets. */
class BookingAccessTest extends TestCase
{
    use RefreshDatabase;

    private Voyage $voyage;
    private ModeReglement $cash;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->cash = ModeReglement::create(['mode_reglement' => 'Espèces']);
        $at = now()->addDays(3);
        $this->voyage = Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(),
            'heure_depart' => '08:00:00', 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 150,
        ]);
    }

    private function book(User $user, array $seats, ?ModeReglement $mode = null)
    {
        return $this->actingAs($user)->post(route('client.reservations.store'), [
            'voyage_id' => $this->voyage->id, 'seats' => $seats, 'mode_reglement_id' => ($mode ?? $this->cash)->id,
        ]);
    }

    // ---- Guests ----

    public function test_a_guest_sees_the_seat_map_and_the_sign_in_popup(): void
    {
        $this->get(route('client.reservations.show', $this->voyage))
            ->assertOk()
            ->assertSee('seat-1', false)
            ->assertSee('blasti-auth-modal', false)
            ->assertSee('data-auto-seconds="40"', false);
    }

    public function test_a_guest_cannot_book(): void
    {
        $this->post(route('client.reservations.store'), ['voyage_id' => $this->voyage->id, 'seats' => [3], 'mode_reglement_id' => $this->cash->id])
            ->assertRedirect(route('login'));
        $this->assertSame(0, Reservation::count());
    }

    public function test_the_popup_is_not_shown_to_signed_in_clients(): void
    {
        $this->actingAs(User::factory()->create())->get(route('home'))->assertOk()->assertDontSee('blasti-auth-modal', false);
    }

    public function test_signing_in_from_the_popup_goes_back_to_the_seat_map(): void
    {
        $user = User::factory()->create();
        $page = route('client.reservations.show', $this->voyage);

        $this->post(route('login'), ['email' => $user->email, 'password' => '11223344', 'redirect_to' => $page])
            ->assertRedirect($page);
        $this->assertAuthenticatedAs($user);
    }

    public function test_the_popup_never_redirects_to_another_site(): void
    {
        $user = User::factory()->create();

        $this->post(route('login'), ['email' => $user->email, 'password' => '11223344', 'redirect_to' => 'https://evil.example/phish'])
            ->assertRedirect(route('home'));
    }

    // ---- Sign up + verification ----

    public function test_sign_up_sends_a_verification_link_and_comes_back_to_the_page(): void
    {
        Notification::fake();
        $page = route('client.reservations.show', $this->voyage);

        $this->post(route('register'), [
            'name' => 'Sara', 'email' => 'sara@example.com', 'password' => 'password123', 'password_confirmation' => 'password123', 'redirect_to' => $page,
        ])->assertRedirect($page);

        $user = User::where('email', 'sara@example.com')->sole();
        $this->assertFalse($user->hasVerifiedEmail());
        $this->assertFalse((bool) $user->isadmin, 'a new sign up is always a plain client');
        Notification::assertSentTo($user, VerifyEmail::class);
    }

    public function test_an_unverified_client_cannot_book_until_the_link_is_clicked(): void
    {
        $user = User::factory()->unverified()->create();

        $this->book($user, [4])->assertRedirect(route('verification.notice'));
        $this->assertSame(0, Reservation::count());

        $this->actingAs($user)->get(route('client.reservations.show', $this->voyage))
            ->assertOk()->assertSee(__('Confirmez votre adresse e-mail pour réserver.'));
    }

    public function test_the_verification_link_brings_the_client_back_to_the_seat_map(): void
    {
        $user = User::factory()->unverified()->create();
        $page = route('client.reservations.show', $this->voyage);
        $link = \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);

        $this->actingAs($user)->withSession(['url.intended' => $page])->get($link)->assertRedirect($page);
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    // ---- Several seats ----

    public function test_several_seats_make_one_order_and_one_email_with_every_ticket(): void
    {
        $client = User::factory()->create();

        $this->book($client, [7, 8, 9])->assertSessionHas('success');

        $billets = Reservation::orderBy('num_siege')->get();
        $this->assertSame([7, 8, 9], $billets->pluck('num_siege')->all());
        $this->assertCount(1, $billets->pluck('commande')->unique());
        $this->assertNotNull($billets->first()->commande);
        // the seats are really taken
        $this->assertEqualsCanonicalizing([7, 8, 9], $this->voyage->fresh()->seatsTaken(...$this->voyage->fresh()->segmentFor()));

        Mail::assertSent(ReservationMail::class, 1);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->billets->count() === 3 && count($mail->attachments()) === 1);
    }

    public function test_one_taken_seat_cancels_the_whole_order(): void
    {
        $this->book(User::factory()->create(), [8]);

        $this->book(User::factory()->create(), [7, 8])->assertSessionHas('error');
        $this->assertSame(1, Reservation::count());
    }

    public function test_the_seat_limit_is_enforced(): void
    {
        config(['safar.max_sieges' => 2]);

        $this->book(User::factory()->create(), [1, 2, 3])->assertSessionHasErrors('seats');
        $this->assertSame(0, Reservation::count());
    }

    public function test_a_card_payment_covers_the_whole_order(): void
    {
        config(['services.cmi.client_id' => '600000000', 'services.cmi.store_key' => 'TEST_STORE_KEY']);
        $card = ModeReglement::create(['mode_reglement' => 'Carte bancaire', 'en_ligne' => true]);

        $this->book(User::factory()->create(), [1, 2], $card);
        $first = Reservation::orderBy('id')->first();
        $this->assertSame('300.00', Cmi::amount($first));

        $data = ['oid' => Cmi::orderId($first), 'amount' => '300.00', 'ProcReturnCode' => '00', 'Response' => 'Approved', 'TransId' => 'T9'];
        $data['HASH'] = Cmi::hash($data);
        $this->post(route('payment.cmi.callback'), $data)->assertSee('ACTION=POSTAUTH');

        $this->assertSame(2, Reservation::whereNotNull('paye_le')->where('statut', Reservation::CONFIRMEE)->count());
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->billets->count() === 2);
    }

    // ---- QR code ----

    public function test_the_ticket_qr_code_opens_a_signed_check_page(): void
    {
        $this->book(User::factory()->create(), [5]);
        $billet = Reservation::sole();

        // booked "pay at boarding": valid seat, but the controller sees it is not paid
        $this->get($billet->verificationUrl())->assertOk()->assertSee(__('Billet NON PAYÉ'));
        // a guessed URL without the signature is refused
        $this->get(route('ticket.verify', $billet))->assertForbidden();

        $billet->cancel('client');
        $this->get($billet->verificationUrl())->assertOk()->assertSee(__('Billet annulé : non valable'));
    }

    public function test_the_pdf_of_an_order_has_one_page_per_seat(): void
    {
        $this->book(User::factory()->create(), [10, 11]);

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('client.reservations.ticket-pdf', ['reservations' => Reservation::all()])->output();

        $this->assertStringStartsWith('%PDF', $pdf);
        $this->assertSame(2, preg_match_all('#/Type\s*/Page[^s]#', $pdf));
    }
}
