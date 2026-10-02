<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\ModeReglement;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use App\Support\WhatsApp;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChauffeurAndExtrasTest extends TestCase
{
    use RefreshDatabase;

    private function createVoyageWithBooking(string $passengerName = 'Secret Client', string $passengerPhone = '0612345678'): array
    {
        $societe = Societe::create([
            'raison_social' => 'Transport Alsa',
            'adresse' => 'Rabat',
            'ville' => 'Rabat',
            'tel' => '0537000000',
            'nom_contact' => 'Directeur',
            'email' => 'contact@alsa.test',
            'ice' => '001122334455667',
        ]);
        $autocar = Autocar::create(['matricule' => '12345-A-1', 'nbr_siege' => 50, 'societe_id' => $societe->id]);
        $type = TypeVoyage::create(['type_voyage' => 'Confort']);
        $v1 = Ville::create(['ville' => 'Casablanca']);
        $v2 = Ville::create(['ville' => 'Marrakech']);

        $voyage = Voyage::create([
            'date_depart' => today()->addDays(2)->toDateString(),
            'date_arrivee' => today()->addDays(2)->toDateString(),
            'heure_depart' => '09:00:00',
            'heure_arrivee' => '12:30:00',
            'ville_depart_id' => $v1->id,
            'ville_arrivee_id' => $v2->id,
            'autocar_id' => $autocar->id,
            'type_voyage_id' => $type->id,
            'prix' => 100.00,
        ]);

        $client = User::factory()->create([
            'isadmin' => 0,
            'name' => $passengerName,
            'email' => 'private_passenger@example.com',
            'telephone' => $passengerPhone,
        ]);

        $segment = $voyage->segmentFor();

        $mode = ModeReglement::firstOrCreate(
            ['mode_reglement' => 'Carte bancaire'],
            ['en_ligne' => 1]
        );

        $billet = Reservation::create([
            'commande' => Reservation::nouvelleCommande(),
            'voyage_id' => $voyage->id,
            'user_id' => $client->id,
            'mode_reglement_id' => $mode->id,
            'ville_depart_id' => $segment[0]->ville_id,
            'ville_arrivee_id' => $segment[1]->ville_id,
            'arret_depart_id' => $segment[0]->id,
            'arret_arrivee_id' => $segment[1]->id,
            'autocar_id' => $voyage->autocar_id,
            'type_voyage_id' => $voyage->type_voyage_id,
            'num_siege' => 15,
            'date_reservation' => now(),
            'date_depart' => $segment[0]->passage_at->toDateString(),
            'date_arrivee' => $segment[1]->passage_at->toDateString(),
            'heure_depart' => $segment[0]->passage_at->format('H:i:s'),
            'heure_arrivee' => $segment[1]->passage_at->format('H:i:s'),
            'prix' => 100.00,
            'frais' => 0,
            'remise' => 0,
            'total' => 100.00,
            'statut' => Reservation::CONFIRMEE,
            'paye_le' => now(),
        ]);

        return [$voyage, $billet, $client];
    }

    public function test_chauffeur_role_lands_on_chauffeur_index_and_is_forbidden_from_admin_clients(): void
    {
        $chauffeurUser = User::factory()->create(['isadmin' => 1]);
        $role = Role::where('slug', 'chauffeur')->firstOrFail();
        $chauffeurUser->roles()->attach($role);

        // Accessing /admin automatically lands on chauffeur.index
        $this->actingAs($chauffeurUser)->get(route('admin'))
            ->assertRedirect(route('chauffeur.index'));

        // Accessing chauffeur dashboard succeeds
        $this->actingAs($chauffeurUser)->get(route('chauffeur.index'))
            ->assertOk()
            ->assertSee('Espace Chauffeur');

        // Cannot view admin clients or sensitive user lists
        $this->actingAs($chauffeurUser)->get(route('admin.clients.index'))
            ->assertForbidden();
        $this->actingAs($chauffeurUser)->get(route('admin.users.index'))
            ->assertForbidden();
    }

    public function test_chauffeur_show_view_protects_client_privacy(): void
    {
        [$voyage, $billet, $client] = $this->createVoyageWithBooking('TopSecretPassager', '0699887766');

        $chauffeurUser = User::factory()->create(['isadmin' => 1]);
        $role = Role::where('slug', 'chauffeur')->firstOrFail();
        $chauffeurUser->roles()->attach($role);

        $response = $this->actingAs($chauffeurUser)->get(route('chauffeur.show', $voyage));

        $response->assertOk();

        // Operational info IS shown
        $response->assertSee('Casablanca');
        $response->assertSee('Marrakech');
        $response->assertSee('1 / 50');
        $response->assertSee('Itinéraire et flux de passagers');

        // STRICT PRIVACY: Client name, phone number, and email MUST NEVER be shown to chauffeur
        $response->assertDontSee('TopSecretPassager');
        $response->assertDontSee('0699887766');
        $response->assertDontSee('private_passenger@example.com');
    }

    public function test_reporting_trip_delay(): void
    {
        [$voyage] = $this->createVoyageWithBooking();

        $chauffeurUser = User::factory()->create(['isadmin' => 1]);
        $role = Role::where('slug', 'chauffeur')->firstOrFail();
        $chauffeurUser->roles()->attach($role);

        $response = $this->actingAs($chauffeurUser)->post(route('chauffeur.retard', $voyage), [
            'retard_minutes' => 30,
            'motif_retard' => 'Trafic dense sortie autoroute',
        ]);

        $response->assertRedirect();
        $fresh = $voyage->fresh();
        $this->assertTrue($fresh->estEnRetard());
        $this->assertSame(30, $fresh->retard_minutes);
        $this->assertSame('Trafic dense sortie autoroute', $fresh->motif_retard);
    }

    public function test_whatsapp_mobile_validation_and_fallback_for_landlines(): void
    {
        [$voyage, $billet] = $this->createVoyageWithBooking();

        // Moroccan mobile numbers
        $this->assertTrue(WhatsApp::estMobileValide('0612345678'));
        $this->assertTrue(WhatsApp::estMobileValide('0712345678'));
        $this->assertTrue(WhatsApp::estMobileValide('+212612345678'));
        $this->assertTrue(WhatsApp::estMobileValide('00212712345678'));

        // Moroccan landlines (05... or 2125...) cannot use WhatsApp
        $this->assertFalse(WhatsApp::estMobileValide('0522123456'));
        $this->assertFalse(WhatsApp::estMobileValide('0537123456'));
        $this->assertFalse(WhatsApp::estMobileValide('+212522123456'));
        $this->assertFalse(WhatsApp::estMobileValide(null));

        // Fallback test
        $checkFixe = WhatsApp::verifierDisponibilite($billet, '0522123456');
        $this->assertFalse($checkFixe['possible']);
        $this->assertSame('email', $checkFixe['canal']);
        $this->assertSame('numero_non_mobile_ou_fixe', $checkFixe['motif']);

        // Mobile check
        $checkMobile = WhatsApp::verifierDisponibilite($billet, '0612345678');
        $this->assertTrue($checkMobile['possible']);
        $this->assertSame('whatsapp', $checkMobile['canal']);
        $this->assertStringContainsString('wa.me/212612345678', $checkMobile['lien']);
    }
}
