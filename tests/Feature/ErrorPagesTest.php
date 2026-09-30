<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Branded error pages instead of the bare "404 Not Found" / "403 Forbidden". */
class ErrorPagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_page_shows_the_branded_404(): void
    {
        $this->get('/cette-page-nexiste-pas')->assertNotFound()
            ->assertSee(__('Cette page n\'existe pas'))->assertSee(__('Retour à l\'accueil'));
    }

    public function test_forbidden_page_explains_why(): void
    {
        $staff = User::factory()->create(['isadmin' => 1]);
        $staff->roles()->attach(Role::create(['name' => 'Vendeur', 'slug' => 'vendeur'])->id);

        $this->actingAs($staff)->get(route('admin.roles.create'))->assertForbidden()
            ->assertSee(__('Accès refusé'))->assertSee('Seul le super administrateur peut gérer les rôles.');
    }

    public function test_expired_signed_link_says_so(): void
    {
        $at = now()->addDays(3);
        $voyage = \App\Models\Voyage::create([
            'date_depart' => $at->toDateString(), 'date_arrivee' => $at->toDateString(), 'heure_depart' => '08:00:00', 'heure_arrivee' => '11:00:00',
            'ville_depart_id' => \App\Models\Ville::firstOrCreate(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => \App\Models\Ville::firstOrCreate(['ville' => 'Tanger'])->id,
            'autocar_id' => \App\Models\Autocar::factory()->create(['societe_id' => \App\Models\Societe::factory()->create()->id, 'nbr_siege' => 40])->id,
            'type_voyage_id' => \App\Models\TypeVoyage::firstOrCreate(['type_voyage' => 'Confort'])->id,
            'prix' => 100,
        ]);
        $this->actingAs(User::factory()->create())->post(route('client.reservations.store'), [
            'voyage_id' => $voyage->id, 'seats' => [1],
            'mode_reglement_id' => \App\Models\ModeReglement::create(['mode_reglement' => 'Espèces'])->id,
        ]);

        // the QR code link without its signature (or tampered with)
        $this->get('/billet/' . \App\Models\Reservation::sole()->id . '/verifier')
            ->assertForbidden()->assertSee(__('Ce lien n\'est plus valable'));
    }
}
