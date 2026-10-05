<?php

namespace Tests\Feature;

use App\Models\Autocar;
use App\Models\ModeReglement;
use App\Models\Permission;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\Societe;
use App\Models\TypeVoyage;
use App\Models\User;
use App\Models\Ville;
use App\Models\Voyage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $superAdmin;
    private Voyage $voyage;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['isadmin' => 1]);

        $autocar = Autocar::factory()->create(['societe_id' => Societe::factory()->create()->id, 'nbr_siege' => 40]);
        $this->voyage = Voyage::create([
            'date_depart' => now()->addDays(2)->toDateString(),
            'date_arrivee' => now()->addDays(2)->toDateString(),
            'heure_depart' => '08:00:00',
            'heure_arrivee' => '11:00:00',
            'ville_depart_id' => Ville::create(['ville' => 'Rabat'])->id,
            'ville_arrivee_id' => Ville::create(['ville' => 'Tanger'])->id,
            'autocar_id' => $autocar->id,
            'type_voyage_id' => TypeVoyage::create(['type_voyage' => 'Standard'])->id,
            'prix' => 120,
        ]);
    }

    private function reservationFor(User $client, int $seat = 1): Reservation
    {
        return Reservation::create([
            'num_siege' => $seat, 'user_id' => $client->id, 'date_reservation' => now(),
            'mode_reglement_id' => ModeReglement::firstOrCreate(['mode_reglement' => 'Espèces'])->id,
            'date_depart' => $this->voyage->date_depart, 'date_arrivee' => $this->voyage->date_arrivee,
            'heure_depart' => $this->voyage->heure_depart, 'heure_arrivee' => $this->voyage->heure_arrivee,
            'ville_depart_id' => $this->voyage->ville_depart_id, 'ville_arrivee_id' => $this->voyage->ville_arrivee_id,
            'autocar_id' => $this->voyage->autocar_id, 'type_voyage_id' => $this->voyage->type_voyage_id,
            'prix' => 120, 'frais' => 0, 'voyage_id' => $this->voyage->id,
        ]);
    }

    private function staffWith(array $permissions): User
    {
        $role = Role::create(['name' => 'Agent', 'slug' => 'agent']);
        foreach ($permissions as $name) {
            $role->permissions()->attach(Permission::firstOrCreate(['name' => $name], ['slug' => str_replace([' ', '.'], '-', $name)]));
        }
        $staff = User::factory()->create(['isadmin' => 1]);
        $staff->roles()->attach($role);

        return $staff;
    }

    public function test_super_admin_sees_the_full_sidebar(): void
    {
        $this->actingAs($this->superAdmin)->get(route('admin'))
            ->assertOk()
            ->assertSee('Réservations')
            ->assertSee('Utilisateurs &amp; rôles', false)
            ->assertSee('Prochains départs');
    }

    public function test_staff_only_reaches_the_sections_of_their_role(): void
    {
        $staff = $this->staffWith(['reservations.read', 'voyages.read']);

        // no dashboard permission => sent to the first allowed section
        $this->actingAs($staff)->get(route('admin'))->assertRedirect(route('reservation.admin.index'));

        $this->actingAs($staff)->get(route('reservation.admin.index'))
            ->assertOk()
            ->assertSee('Voyages')
            ->assertDontSee('Sociétés');

        $this->actingAs($staff)->get(route('voyages.index'))->assertOk();
        $this->actingAs($staff)->get(route('voyages.create'))->assertForbidden();
        $this->actingAs($staff)->get(route('societes.index'))->assertForbidden();
        $this->actingAs($staff)->get(route('admin.export.users'))->assertForbidden();
    }

    public function test_staff_cannot_manage_roles_or_other_admins(): void
    {
        $staff = $this->staffWith(['utilisateurs.read', 'utilisateurs.update', 'roles.update']);

        $this->actingAs($staff)->get(route('admin.roles.create'))->assertForbidden();
        $this->actingAs($staff)
            ->put(route('admin.users.update-password', $this->superAdmin), ['password' => 'hacked123', 'password_confirmation' => 'hacked123'])
            ->assertForbidden();
    }

    public function test_super_admin_cannot_demote_themselves(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.users.update', $this->superAdmin), [
                'name' => 'Boss', 'email' => $this->superAdmin->email, 'isadmin' => 0,
            ])
            ->assertSessionHas('error');

        $this->assertTrue((bool) $this->superAdmin->fresh()->isadmin);
    }

    public function test_reservations_can_be_filtered_by_client(): void
    {
        $alice = User::factory()->create(['name' => 'Alice Martin']);
        $bob = User::factory()->create(['name' => 'Bob Durand']);
        $this->reservationFor($alice, 1);
        $this->reservationFor($bob, 2);

        $this->actingAs($this->superAdmin)
            ->get(route('reservation.admin.index', ['q' => 'Alice']))
            ->assertOk()
            ->assertSee('Alice Martin')
            ->assertDontSee('Bob Durand');
    }

    public function test_admin_can_cancel_a_reservation_and_free_the_seat(): void
    {
        $reservation = $this->reservationFor(User::factory()->create(), 7);

        $this->actingAs($this->superAdmin)
            ->delete(route('reservation.admin.destroy', $reservation))
            ->assertRedirect(route('reservation.admin.show', $reservation))
            ->assertSessionHas('success');

        // kept in the history as cancelled, hidden from occupancy, and the seat can be sold again
        $reservation = \App\Models\Reservation::withoutGlobalScope('active')->find($reservation->id);
        $this->assertTrue($reservation->isCancelled());
        $this->assertNull($reservation->siege_actif);
        $this->assertSame(0, $this->voyage->reservations()->count());
        $this->reservationFor(User::factory()->create(), 7);
        $this->assertSame(1, $this->voyage->reservations()->count());
    }

    public function test_voyages_list_shows_occupancy_and_filters(): void
    {
        $this->reservationFor(User::factory()->create(), 1);

        $this->actingAs($this->superAdmin)
            ->get(route('voyages.index', ['statut' => 'a_venir']))
            ->assertOk()
            ->assertSee('1/40');

        $this->actingAs($this->superAdmin)
            ->get(route('voyages.index', ['statut' => 'passe']))
            ->assertOk()
            ->assertSee('Aucun voyage ne correspond');
    }

    public function test_reservation_detail_page_renders(): void
    {
        $reservation = $this->reservationFor(User::factory()->create(['name' => 'Client Test']), 3);

        $this->actingAs($this->superAdmin)
            ->get(route('reservation.admin.show', $reservation))
            ->assertOk()
            ->assertSee('Client Test')
            ->assertSee('Annuler la réservation');
    }

    public function test_super_admin_changes_the_logo_color(): void
    {
        $this->actingAs($this->superAdmin)
            ->get(route('admin.apparence.edit'))
            ->assertOk()
            ->assertSee('Bleu du logo');

        $this->actingAs($this->superAdmin)
            ->put(route('admin.apparence.update'), ['nom' => 'Safar Voyages', 'couleur' => '#c2410c'])
            ->assertRedirect(route('admin.apparence.edit'));

        $this->assertDatabaseHas('parametres', ['nom' => 'Safar Voyages', 'couleur' => '#C2410C']);

        // Applied at boot on the next request; done by hand here since the test app is already booted
        \App\Models\Parametre::appliquerALaConfig();
        $this->actingAs($this->superAdmin)->get(route('admin'))
            ->assertSee('--brand: #C2410C', false)
            ->assertSee('Safar Voyages');
    }

    public function test_the_color_also_changes_the_public_site_and_the_logo(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.apparence.update'), ['nom' => 'Blasti', 'couleur' => '#15803D'])
            ->assertRedirect();
        \App\Models\Parametre::appliquerALaConfig();

        // CSS variables of the public/client pages + the logo copy in that color
        $this->get('/')
            ->assertOk()
            ->assertSee('--brand: #15803D', false)
            ->assertSee('assets/img/brand/15803D-w/blasti-logo.png', false);
        $this->assertFileExists(public_path('assets/img/brand/15803D-w/blasti-logo.png'));
    }

    public function test_logo_color_must_be_a_hex_code(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.apparence.update'), ['nom' => 'Safar', 'couleur' => 'red'])
            ->assertSessionHasErrors('couleur');

        $this->assertDatabaseCount('parametres', 0);
    }

    public function test_staff_cannot_change_the_appearance(): void
    {
        $staff = $this->staffWith(['dashboard.read', 'voyages.read']);

        $this->actingAs($staff)->get(route('admin.apparence.edit'))->assertForbidden();
        $this->actingAs($staff)->put(route('admin.apparence.update'), ['nom' => 'X', 'couleur' => '#000000'])->assertForbidden();
        $this->actingAs($staff)->get(route('admin'))->assertDontSee(route('admin.apparence.edit'));
    }

    public function test_admin_can_switch_language_and_admin_interface_is_localized(): void
    {
        // Switch to Arabic
        $this->actingAs($this->superAdmin)
            ->get(route('lang.switch', 'ar'))
            ->assertSessionHas('locale', 'ar');

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'ar'])
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('dir="rtl"', false)
            ->assertSee('لوحة التحكم')
            ->assertSee('الحجوزات');

        // Switch to English
        $this->actingAs($this->superAdmin)
            ->get(route('lang.switch', 'en'))
            ->assertSessionHas('locale', 'en');

        $this->actingAs($this->superAdmin)
            ->withSession(['locale' => 'en'])
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('dir="ltr"', false)
            ->assertSee('Dashboard')
            ->assertSee('Reservations');
    }

    public function test_super_admin_can_upload_and_delete_custom_logo(): void
    {
        Storage::fake('public');

        $file = UploadedFile::fake()->image('custom_logo.png', 200, 60);

        $this->actingAs($this->superAdmin)
            ->put(route('admin.apparence.update'), [
                'nom' => 'Safar Express',
                'couleur' => '#0F766E',
                'logo' => $file,
            ])
            ->assertRedirect(route('admin.apparence.edit'));

        $parametre = \App\Models\Parametre::actuel();
        $this->assertNotNull($parametre->logo);
        Storage::disk('public')->assertExists($parametre->logo);

        \App\Models\Parametre::appliquerALaConfig();
        $this->assertTrue(\App\Support\BrandImages::hasCustomLogo());
        $this->assertEquals(asset('storage/' . $parametre->logo), \App\Support\BrandImages::logoUrl());

        // Admin page displays the custom logo URL
        $this->actingAs($this->superAdmin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee($parametre->logo);

        // Now remove the custom logo
        $this->actingAs($this->superAdmin)
            ->put(route('admin.apparence.update'), [
                'nom' => 'Safar Express',
                'couleur' => '#0F766E',
                'supprimer_logo' => 1,
            ])
            ->assertRedirect(route('admin.apparence.edit'));

        $parametre->refresh();
        $this->assertNull($parametre->logo);
        \App\Models\Parametre::appliquerALaConfig();
        $this->assertFalse(\App\Support\BrandImages::hasCustomLogo());
    }

    public function test_when_nom_is_changed_without_custom_logo_dynamic_brand_is_displayed(): void
    {
        $this->actingAs($this->superAdmin)
            ->put(route('admin.apparence.update'), [
                'nom' => 'TransAtlas',
                'couleur' => '#0B4FC4',
            ])
            ->assertRedirect(route('admin.apparence.edit'));

        \App\Models\Parametre::appliquerALaConfig();
        $this->assertFalse(\App\Support\BrandImages::isDefaultBrand());

        $this->actingAs($this->superAdmin)
            ->get(route('admin'))
            ->assertOk()
            ->assertSee('TransAtlas')
            ->assertSee('brand-logo-text');
    }
}
