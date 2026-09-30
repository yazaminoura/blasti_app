<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\Societe;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** "Utilisateurs & rôles" lists back-office accounts only, split by type; clients stay on the Clients page. */
class UsersListTest extends TestCase
{
    use RefreshDatabase;

    public function test_list_shows_team_accounts_by_type_and_never_clients(): void
    {
        $admin = User::factory()->create(['isadmin' => 1, 'name' => 'Chef Admin']);
        $staff = User::factory()->create(['isadmin' => 1, 'name' => 'Agent Guichet']);
        $staff->roles()->attach(Role::create(['name' => 'Guichet', 'slug' => 'guichet'])->id);
        $compagnie = User::factory()->create(['isadmin' => 1, 'name' => 'Compte CTM']);
        $compagnie->forceFill(['societe_id' => Societe::factory()->create(['raison_social' => 'CTM'])->id])->save();
        User::factory()->create(['isadmin' => 0, 'name' => 'Voyageur Client']);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(['Chef Admin', 'Agent Guichet', 'Compte CTM', 'CTM'])
            ->assertDontSee('Voyageur Client')
            ->assertSee('data-sa-row-link="edit"', false);

        // (the signed-in admin's name is also in the top bar, so compare the listed accounts)
        $listed = fn (string $type) => $this->actingAs($admin)->get(route('admin.users.index', ['type' => $type]))
            ->viewData('users')->pluck('name')->all();
        $this->assertSame(['Chef Admin'], $listed('super'));
        $this->assertSame(['Agent Guichet'], $listed('equipe'));
        $this->assertSame(['Compte CTM'], $listed('compagnie'));

        $this->actingAs($admin)->get(route('admin.clients.index'))
            ->assertSee('Voyageur Client')->assertDontSee('Agent Guichet');
    }

    public function test_a_client_has_its_own_page_without_any_admin_field(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $client = User::factory()->create(['isadmin' => 0, 'name' => 'Voyageur Client']);

        // the old admin form now sends a client to the client page
        $this->actingAs($admin)->get(route('admin.users.edit', $client))->assertRedirect(route('admin.clients.show', $client));

        $this->actingAs($admin)->get(route('admin.clients.show', $client))
            ->assertOk()
            ->assertSee('Voyageur Client')
            ->assertDontSee("Peut accéder à l'administration")
            ->assertDontSee('name="role"', false)
            ->assertDontSee('name="societe_id"', false);

        // saving the client never touches admin access
        $this->actingAs($admin)->put(route('admin.clients.update', $client), [
            'name' => 'Nouveau Nom', 'email' => $client->email, 'telephone' => '0600000000', 'isadmin' => 1,
        ])->assertRedirect();
        $client->refresh();
        $this->assertSame(['Nouveau Nom', '0600000000', 0], [$client->name, $client->telephone, (int) $client->isadmin]);

        // a team account is never shown as a client
        $this->actingAs($admin)->get(route('admin.clients.show', $admin))->assertRedirect(route('admin.users.edit', $admin));
    }
}
