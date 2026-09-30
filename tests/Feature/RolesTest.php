<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Ready-made roles (App\Support\Droits) and the special rights: refund, revenue, scanner, clients. */
class RolesTest extends TestCase
{
    use RefreshDatabase;

    private function compte(string $slug): User
    {
        $user = User::factory()->create(['isadmin' => 1]);
        $user->roles()->attach(Role::where('slug', $slug)->firstOrFail());

        return $user;
    }

    public function test_the_nine_roles_exist_after_migrate(): void
    {
        $this->assertSame(
            ['compagnie', 'compagnie-controleur', 'comptable', 'controleur', 'directeur', 'guichetier', 'marketing', 'planning', 'service-client'],
            Role::orderBy('slug')->pluck('slug')->all()
        );
    }

    public function test_controleur_only_gets_the_scanner(): void
    {
        $controleur = $this->compte('controleur');

        $this->actingAs($controleur)->get(route('reservation.admin.scanner'))->assertOk();
        $this->actingAs($controleur)->get(route('admin'))->assertRedirect(route('reservation.admin.scanner'));
        $this->actingAs($controleur)->get(route('reservation.admin.index'))->assertForbidden();
        $this->actingAs($controleur)->get(route('admin.clients.index'))->assertForbidden();
    }

    public function test_guichetier_sees_clients_but_not_the_team_nor_revenue_and_cannot_refund(): void
    {
        $guichetier = $this->compte('guichetier');

        $this->actingAs($guichetier)->get(route('admin.clients.index'))->assertOk();
        $this->actingAs($guichetier)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($guichetier)->get(route('reservation.admin.scanner'))->assertForbidden();
        $this->actingAs($guichetier)->get(route('admin'))->assertOk()->assertDontSee("Chiffre d'affaires");
        $this->assertFalse($guichetier->hasPermission('reservations.rembourser'));

        // a client account can be edited with the Clients right... but not a team account
        $client = User::factory()->create(['isadmin' => 0]);
        $this->actingAs($this->compte('service-client'))->get(route('admin.clients.show', $client))->assertOk();
        $this->actingAs($this->compte('service-client'))->get(route('admin.users.edit', $guichetier))->assertForbidden();
    }

    public function test_comptable_sees_revenue_and_statistics_but_changes_nothing(): void
    {
        $comptable = $this->compte('comptable');

        $this->actingAs($comptable)->get(route('admin'))->assertOk()->assertSee("Chiffre d'affaires");
        $this->actingAs($comptable)->get(route('admin.statistiques'))->assertOk();
        $this->actingAs($comptable)->get(route('voyages.create'))->assertForbidden();
    }
}
