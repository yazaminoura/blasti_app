<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\Reservation;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UpcomingDeparturesCardTest extends TestCase
{
    use RefreshDatabase;

    public function test_prochains_departs_cards_render_full_modern_visual_elements(): void
    {
        $dep = Ville::create(['ville' => 'Casablanca']);
        $arr = Ville::create(['ville' => 'Marrakech', 'image' => 'villes/marrakech.jpg']);
        $soc = Societe::create([
            'raison_social' => 'Supratours Express',
            'adresse' => 'Gare Marrakech',
            'ville' => 'Marrakech',
            'tel' => '0524000000',
            'nom_contact' => 'Responsable',
            'email' => 'supratours@example.ma',
            'ice' => '123456789012345',
        ]);
        $car = Autocar::create([
            'societe_id' => $soc->id,
            'matricule' => '98765-B-26',
            'nbr_siege' => 40,
            'classe' => 'Confort Plus',
        ]);
        $type = TypeVoyage::create(['type_voyage' => 'Express']);

        $voyage = Voyage::create([
            'ville_depart_id' => $dep->id,
            'ville_arrivee_id' => $arr->id,
            'autocar_id' => $car->id,
            'type_voyage_id' => $type->id,
            'date_depart' => now()->addDays(1)->toDateString(),
            'heure_depart' => '09:30:00',
            'date_arrivee' => now()->addDays(1)->toDateString(),
            'heure_arrivee' => '12:45:00',
            'prix' => 120,
            'statut' => 'programme',
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();

        // 1. Route Headline
        $response->assertSee('Casablanca');
        $response->assertSee('Marrakech');
        $response->assertSee('bl-route-link');
        $response->assertSee('bl-arrow-separator');

        // 2. Departure Time Badge & Image Gradient Overlay
        $response->assertSee('bl-departure-badge');
        $response->assertSee('09:30');
        $response->assertSee('bl-img-gradient-overlay');

        // 3. Company & Bus Class / Type
        $response->assertSee('Supratours Express');
        $response->assertSee('Express');
        $response->assertSee('bl-class-badge');

        // 4. Formatted Duration
        $this->assertSame('3h15', $voyage->dureeFormattee());
        $response->assertSee('3h15');
        $response->assertSee('bl-duration-pill');

        // 5. Price & Booking Button
        $response->assertSee('À partir de');
        $response->assertSee('120');
        $response->assertSee('DHS');
        $response->assertSee('bl-book-btn');
        $response->assertSee('Réserver');

        // 6. Seats Badge (40 > 5 => bl-seat-available)
        $response->assertSee('bl-seat-available');
        $response->assertSee('40 places');

        // 7. Wishlist button
        $response->assertSee('wishlist-toggle');
        $response->assertSee('data-id="' . $voyage->id . '"', false);
    }

    public function test_prochains_departs_seats_warning_and_full_badges(): void
    {
        $dep = Ville::create(['ville' => 'Tanger']);
        $arr = Ville::create(['ville' => 'Tétouan']);
        $soc = Societe::create([
            'raison_social' => 'CTM',
            'adresse' => 'Tanger',
            'ville' => 'Tanger',
            'tel' => '0539000000',
            'nom_contact' => 'Responsable',
            'email' => 'ctm-tanger@example.ma',
            'ice' => '998877665544332',
        ]);
        $carLow = Autocar::create([
            'societe_id' => $soc->id,
            'matricule' => '11111-A-1',
            'nbr_siege' => 4, // 4 seats total <= 5
        ]);
        $type = TypeVoyage::create(['type_voyage' => 'Standard']);

        $voyageLow = Voyage::create([
            'ville_depart_id' => $dep->id,
            'ville_arrivee_id' => $arr->id,
            'autocar_id' => $carLow->id,
            'type_voyage_id' => $type->id,
            'date_depart' => now()->addDays(2)->toDateString(),
            'heure_depart' => '14:00:00',
            'date_arrivee' => now()->addDays(2)->toDateString(),
            'heure_arrivee' => '15:15:00',
            'prix' => 35,
            'statut' => 'programme',
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('bl-seat-low');
        $response->assertSee('4 places');
    }

    public function test_prochains_departs_seats_fully_booked(): void
    {
        $dep = Ville::create(['ville' => 'Rabat']);
        $arr = Ville::create(['ville' => 'Fès']);
        $soc = Societe::create([
            'raison_social' => 'Ghazala Transport',
            'adresse' => 'Rabat',
            'ville' => 'Rabat',
            'tel' => '0537000000',
            'nom_contact' => 'Responsable',
            'email' => 'ghazala@example.ma',
            'ice' => '888877665544332',
        ]);
        $car = Autocar::create([
            'societe_id' => $soc->id,
            'matricule' => '22222-B-1',
            'nbr_siege' => 1,
        ]);
        $type = TypeVoyage::create(['type_voyage' => 'Standard']);

        $voyage = Voyage::create([
            'ville_depart_id' => $dep->id,
            'ville_arrivee_id' => $arr->id,
            'autocar_id' => $car->id,
            'type_voyage_id' => $type->id,
            'date_depart' => now()->addDays(2)->toDateString(),
            'heure_depart' => '16:00:00',
            'date_arrivee' => now()->addDays(2)->toDateString(),
            'heure_arrivee' => '19:00:00',
            'prix' => 60,
            'statut' => 'programme',
        ]);

        $client = User::factory()->create();
        Reservation::factory()->create([
            'voyage_id' => $voyage->id,
            'user_id' => $client->id,
            'num_siege' => 1,
            'statut' => Reservation::CONFIRMEE,
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('bl-seat-full');
        $response->assertSee('Complet');
    }
}
