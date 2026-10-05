<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\Parametre;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BrandLogoAndCityImagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_brand_logo_underline_has_no_orange_middle_bar(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        // Configure dynamic brand name (e.g. MAHATATI)
        $this->actingAs($admin)
            ->put(route('admin.apparence.update'), [
                'nom' => 'MAHATATI',
                'couleur' => '#0A1F5C',
            ])
            ->assertRedirect();

        Parametre::appliquerALaConfig();

        // 1. Check homepage navbar and footer
        $response = $this->get(route('home'));
        $response->assertOk();
        $response->assertSee('MAHATATI');
        $response->assertSee('brand-logo-line');
        $response->assertDontSee('brand-line-accent');
        $response->assertDontSee('#F2690D');

        // 2. Check admin panel
        $adminResponse = $this->actingAs($admin)->get(route('admin'));
        $adminResponse->assertOk();
        $adminResponse->assertSee('MAHATATI');
        $adminResponse->assertSee('brand-logo-line');
        $adminResponse->assertDontSee('brand-line-accent');
    }

    public function test_ville_resolve_image_url_handles_relative_and_redundant_paths(): void
    {
        // Path with 'villes/...'
        $url1 = Ville::resolveImageUrl('villes/fes.jpg');
        $this->assertStringEndsWith('/storage/villes/fes.jpg', $url1);
        $this->assertStringNotContainsString('/storage/storage/', $url1);

        // Path already containing 'storage/...'
        $url2 = Ville::resolveImageUrl('storage/villes/kenitra.jpg');
        $this->assertStringEndsWith('/storage/villes/kenitra.jpg', $url2);
        $this->assertStringNotContainsString('/storage/storage/', $url2);

        // Path with leading '/storage/...'
        $url3 = Ville::resolveImageUrl('/storage/villes/rabat.jpg');
        $this->assertStringEndsWith('/storage/villes/rabat.jpg', $url3);
        $this->assertStringNotContainsString('/storage/storage/', $url3);

        // Full URL
        $url4 = Ville::resolveImageUrl('https://example.com/photo.jpg');
        $this->assertSame('https://example.com/photo.jpg', $url4);

        // Bundled asset path
        $url5 = Ville::resolveImageUrl('assets/img/citys/custom.jpg');
        $this->assertStringEndsWith('/assets/img/citys/custom.jpg', $url5);

        // Null
        $this->assertNull(Ville::resolveImageUrl(null));
    }

    public function test_ville_photo_url_attribute_uses_resolve_image_url(): void
    {
        $v1 = Ville::create(['ville' => 'Kenitra', 'image' => 'villes/kenitra.jpg']);
        $this->assertStringEndsWith('/storage/villes/kenitra.jpg', $v1->photo_url);
        $this->assertStringNotContainsString('/storage/storage/', $v1->photo_url);

        $v2 = Ville::create(['ville' => 'Fès', 'image' => 'storage/villes/fes.jpg']);
        $this->assertStringEndsWith('/storage/villes/fes.jpg', $v2->photo_url);
        $this->assertStringNotContainsString('/storage/storage/', $v2->photo_url);

        // Bundled fallback for city without custom image
        $v3 = Ville::create(['ville' => 'Casablanca', 'image' => null]);
        $this->assertNotNull($v3->photo_url);
        $this->assertStringContainsString('assets/img/citys/', $v3->photo_url);

        // Non-bundled city without custom image returns null
        $v4 = Ville::create(['ville' => 'Midelt', 'image' => null]);
        $this->assertNull($v4->photo_url);
    }

    public function test_voyage_image_url_resolution_hierarchy(): void
    {
        $dep = Ville::create(['ville' => 'Meknès']);
        $arr = Ville::create(['ville' => 'Fès', 'image' => 'villes/fes.jpg']);
        $soc = Societe::create(['raison_social' => 'CTM', 'adresse' => 'Casablanca', 'ville' => 'Casablanca', 'tel' => '0522000000', 'nom_contact' => 'Directeur', 'email' => 'ctm@example.ma', 'ice' => '001122334455667']);
        $car = Autocar::create(['societe_id' => $soc->id, 'matricule' => '12345-A-1', 'nbr_siege' => 50, 'image' => 'autocars/bus.jpg']);
        $type = TypeVoyage::create(['type_voyage' => 'Standard']);

        // Case 1: Voyage has its own image
        $voyage = Voyage::create([
            'ville_depart_id' => $dep->id,
            'ville_arrivee_id' => $arr->id,
            'autocar_id' => $car->id,
            'type_voyage_id' => $type->id,
            'date_depart' => now()->addDays(2)->toDateString(),
            'heure_depart' => '08:00:00',
            'date_arrivee' => now()->addDays(2)->toDateString(),
            'heure_arrivee' => '10:00:00',
            'prix' => 50,
            'statut' => 'programme',
            'image' => 'voyages/trip.jpg',
        ]);
        $this->assertStringEndsWith('/storage/voyages/trip.jpg', $voyage->image_url);

        // Case 2: Voyage has no image, falls back to autocar image
        $voyage->update(['image' => null]);
        $voyage->refresh();
        $this->assertStringEndsWith('/storage/autocars/bus.jpg', $voyage->image_url);

        // Case 3: Autocar has no image, falls back to arrival city image
        $car->update(['image' => null]);
        $voyage->refresh();
        $this->assertStringEndsWith('/storage/villes/fes.jpg', $voyage->image_url);

        // Case 4: Arrival city has no image, falls back to default illustration
        $arr->update(['image' => null]);
        $voyage->refresh();
        // Since Fès has a bundled image, photo_url returns that bundled image
        $this->assertStringContainsString('assets/img/citys/', $voyage->image_url);

        // Case 5: When arrival city has no image and no bundled image
        $arrUnbundled = Ville::create(['ville' => 'Midelt', 'image' => null]);
        $voyage->update(['ville_arrivee_id' => $arrUnbundled->id]);
        $voyage->refresh();
        $this->assertStringContainsString('blasti-hero-bus-bg.png', $voyage->image_url);
    }

    public function test_homepage_prochains_departs_renders_cards_with_onerror_and_clean_urls(): void
    {
        $dep = Ville::create(['ville' => 'Meknès']);
        $arr = Ville::create(['ville' => 'Fès', 'image' => 'villes/fes.jpg']);
        $soc = Societe::create(['raison_social' => 'CTM', 'adresse' => 'Casablanca', 'ville' => 'Casablanca', 'tel' => '0522000000', 'nom_contact' => 'Directeur', 'email' => 'ctm@example.ma', 'ice' => '001122334455667']);
        $car = Autocar::create(['societe_id' => $soc->id, 'matricule' => '12345-A-1', 'nbr_siege' => 50]);
        $type = TypeVoyage::create(['type_voyage' => 'Standard']);

        Voyage::create([
            'ville_depart_id' => $dep->id,
            'ville_arrivee_id' => $arr->id,
            'autocar_id' => $car->id,
            'type_voyage_id' => $type->id,
            'date_depart' => now()->addDays(2)->toDateString(),
            'heure_depart' => '08:00:00',
            'date_arrivee' => now()->addDays(2)->toDateString(),
            'heure_arrivee' => '10:00:00',
            'prix' => 50,
            'statut' => 'programme',
        ]);

        $response = $this->get(route('home'));
        $response->assertOk();

        // Check Prochains départs section contains trip and onerror fallback
        $response->assertSee('Meknès → Fès');
        $response->assertSee('/storage/villes/fes.jpg');
        $response->assertDontSee('/storage/storage/');
        $response->assertSee('onerror="this.onerror=null; this.src=', false);
        $response->assertSee('blasti-hero-bus-bg.png');
    }
}
