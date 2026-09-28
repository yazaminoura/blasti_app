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
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/** Booking statuses, cancellation, payment (cash + CMI card), e-mails and reminders. */
class PaymentFlowTest extends TestCase
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
        $this->voyage = $this->voyageIn(days: 3);
    }

    private function voyageIn(int $days = 0, int $hours = 0): Voyage
    {
        $at = now()->addDays($days)->addHours($hours);

        return Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->copy()->addHours(3)->toDateString(),
            'heure_depart' => $at->format('H:i:00'), 'heure_arrivee' => $at->copy()->addHours(3)->format('H:i:00'),
            'ville_depart_id' => Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 150,
        ]);
    }

    private function book(ModeReglement $mode, int $seat = 5, ?Voyage $voyage = null)
    {
        return $this->actingAs($this->client)->post(route('client.reservations.store'), [
            'voyage_id' => ($voyage ?? $this->voyage)->id, 'seats' => [$seat], 'mode_reglement_id' => $mode->id,
        ]);
    }

    private function enableCmi(): void
    {
        config(['services.cmi.client_id' => '600000000', 'services.cmi.store_key' => 'TEST_STORE_KEY']);
    }

    // ---- Cash ----

    public function test_cash_booking_is_confirmed_unpaid_and_emailed_with_the_ticket(): void
    {
        $this->book($this->cash)->assertSessionHas('success');

        $reservation = Reservation::sole();
        $this->assertSame(Reservation::CONFIRMEE, $reservation->statut);
        $this->assertFalse($reservation->isPaid());
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'confirmee'
            && $mail->hasTo($this->client->email)
            && count($mail->attachments()) === 1);
    }

    public function test_confirmation_email_really_builds_with_the_pdf_ticket(): void
    {
        $this->book($this->cash);
        $reservation = Reservation::sole();

        // real sending (in-memory transport), not the fake: renders the view and builds the PDF
        config(['mail.default' => 'array']);
        app()->forgetInstance('mail.manager');
        Mail::clearResolvedInstances();
        Mail::to($this->client->email)->send(new ReservationMail($reservation, 'confirmee'));

        $messages = app('mail.manager')->mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages->first()->getOriginalMessage();
        $this->assertStringContainsString('est confirmé', $email->getSubject());
        $this->assertSame('billet-' . $reservation->id . '.pdf', $email->getAttachments()[0]->getFilename());
        $this->assertStringStartsWith('%PDF', $email->getAttachments()[0]->getBody());
    }

    public function test_card_payment_is_not_offered_without_cmi_keys(): void
    {
        $this->book($this->card)->assertSessionHasErrors('mode_reglement_id');
        $this->assertSame(0, Reservation::count());
    }

    // ---- Cancellation ----

    public function test_client_cancels_and_the_seat_can_be_booked_again(): void
    {
        $this->book($this->cash, 9);
        $reservation = Reservation::sole();

        $this->actingAs($this->client)->post(route('client.reservations.cancel', $reservation))->assertSessionHas('success');

        $reservation = Reservation::withoutGlobalScope('active')->find($reservation->id);
        $this->assertTrue($reservation->isCancelled());
        $this->assertSame('client', $reservation->annulee_par);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'annulee');

        // same seat, another client
        $this->client = User::factory()->create();
        $this->book($this->cash, 9)->assertSessionHas('success');
        $this->assertSame(1, $this->voyage->reservations()->count());
    }

    public function test_no_online_cancellation_once_the_bus_has_left(): void
    {
        $soon = $this->voyageIn(hours: 5);
        $this->book($this->cash, 1, $soon);
        $reservation = Reservation::sole();

        $this->travel(6)->hours();
        $this->actingAs($this->client)->post(route('client.reservations.cancel', $reservation))->assertSessionHas('error');
        $this->assertFalse($reservation->fresh()->isCancelled());
    }

    public function test_a_client_cannot_cancel_someone_elses_ticket(): void
    {
        $this->book($this->cash);
        $reservation = Reservation::sole();

        $this->actingAs(User::factory()->create())->post(route('client.reservations.cancel', $reservation))->assertForbidden();
    }

    // ---- Admin: payment and refund ----

    public function test_admin_marks_paid_then_cancels_and_refunds(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $this->book($this->cash);
        $reservation = Reservation::sole();

        $this->actingAs($admin)->patch(route('reservation.admin.payer', $reservation))->assertSessionHas('success');
        $this->assertTrue($reservation->fresh()->isPaid());

        $this->actingAs($admin)->delete(route('reservation.admin.destroy', $reservation));
        $reservation = Reservation::withoutGlobalScope('active')->find($reservation->id);
        $this->assertTrue($reservation->needsRefund());

        $this->actingAs($admin)->get(route('reservation.admin.index', ['statut' => 'a_rembourser']))
            ->assertOk()->assertSee('#' . $reservation->id);
        $this->actingAs($admin)->patch(route('reservation.admin.rembourser', $reservation))->assertSessionHas('success');
        $this->assertFalse($reservation->fresh()->needsRefund());
    }

    // ---- CMI card payment ----

    public function test_card_booking_holds_the_seat_and_sends_a_signed_form_to_cmi(): void
    {
        $this->enableCmi();

        $reservation = null;
        $this->book($this->card)->assertRedirect();
        $reservation = Reservation::sole();
        $this->assertSame(Reservation::EN_ATTENTE, $reservation->statut);
        Mail::assertNothingSent();

        $this->actingAs($this->client)->get(route('payment.cmi.start', $reservation))
            ->assertOk()
            ->assertSee('testpayment.cmi.co.ma', false)
            ->assertSee('name="HASH"', false)
            ->assertSee('value="150.00"', false);
    }

    public function test_signed_cmi_callback_confirms_and_pays(): void
    {
        $this->enableCmi();
        $this->book($this->card);
        $reservation = Reservation::sole();

        $data = ['oid' => Cmi::orderId($reservation), 'amount' => '150.00', 'ProcReturnCode' => '00', 'Response' => 'Approved', 'TransId' => 'T123'];
        $data['HASH'] = Cmi::hash($data);

        $this->post(route('payment.cmi.callback'), $data)->assertSee('ACTION=POSTAUTH');

        $reservation->refresh();
        $this->assertTrue($reservation->isPaid());
        $this->assertSame(Reservation::CONFIRMEE, $reservation->statut);
        $this->assertSame('T123', $reservation->paiement_ref);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'confirmee');
    }

    public function test_forged_cmi_callback_is_refused(): void
    {
        $this->enableCmi();
        $this->book($this->card);
        $reservation = Reservation::sole();

        $data = ['oid' => Cmi::orderId($reservation), 'amount' => '150.00', 'ProcReturnCode' => '00', 'Response' => 'Approved', 'HASH' => 'forged'];

        $this->post(route('payment.cmi.callback'), $data)->assertSee('FAILURE');
        $this->assertFalse($reservation->fresh()->isPaid());
    }

    public function test_abandoned_card_payment_releases_the_seat(): void
    {
        $this->enableCmi();
        $this->book($this->card, 12);
        $reservation = Reservation::sole();

        $this->travel(Reservation::DELAI_PAIEMENT_MINUTES + 1)->minutes();
        $this->artisan('reservations:expirer')->assertSuccessful();

        $this->assertSame(0, $this->voyage->reservations()->count());
        $this->assertSame('systeme', Reservation::withoutGlobalScope('active')->find($reservation->id)->annulee_par);
    }

    // ---- Reminder ----

    public function test_reminder_is_sent_once_the_day_before(): void
    {
        $tomorrow = $this->voyageIn(days: 1);
        $this->book($this->cash, 3, $tomorrow);
        Mail::fake(); // forget the confirmation e-mail

        $this->artisan('reservations:rappels')->assertSuccessful();
        $this->artisan('reservations:rappels')->assertSuccessful();

        Mail::assertSent(ReservationMail::class, 1);
        Mail::assertSent(ReservationMail::class, fn ($mail) => $mail->type === 'rappel');
    }
}
