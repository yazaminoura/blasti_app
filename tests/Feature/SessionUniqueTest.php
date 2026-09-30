<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Support\SessionUnique;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** One open session per team / company account: the latest login wins. Clients and the super admin are not limited. */
class SessionUniqueTest extends TestCase
{
    use RefreshDatabase;

    private function staff(): User
    {
        $user = User::factory()->create(['isadmin' => 1, 'name' => 'Salma Guichet']);
        $user->roles()->attach(Role::where('slug', 'guichetier')->firstOrFail());

        return $user;
    }

    /** Logs in with the form and returns the token of that "browser". */
    private function login(User $user): ?string
    {
        $this->post('/login', ['email' => $user->email, 'password' => '11223344'])->assertRedirect();
        $jeton = session(SessionUnique::CLE);
        $this->flushSession();
        auth()->forgetGuards();

        return $jeton;
    }

    public function test_a_second_login_logs_the_first_browser_out(): void
    {
        $staff = $this->staff();
        $premier = $this->login($staff);
        $second = $this->login($staff);
        $this->assertNotSame($premier, $second);

        // the first browser, at its next click: logged out with the explanation
        $this->actingAs($staff->fresh())->withSession([SessionUnique::CLE => $premier])
            ->get(route('admin'))->assertRedirect(route('login'))->assertSessionHas('error');
        $this->assertGuest();

        // the new one keeps working
        $this->flushSession();
        $this->actingAs($staff->fresh())->withSession([SessionUnique::CLE => $second])->get(route('admin'))->assertOk();

        $staff->refresh();
        $this->assertNotNull($staff->derniere_connexion_le);
        $this->assertStringContainsString('·', $staff->derniere_connexion_appareil);
    }

    public function test_super_admin_and_clients_are_not_limited(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $premier = $this->login($admin);
        $this->login($admin);
        $this->actingAs($admin->fresh())->withSession([SessionUnique::CLE => $premier])->get(route('admin'))->assertOk();

        $client = User::factory()->create();
        $this->login($client);
        $this->login($client);
        $this->actingAs($client->fresh())->get(route('client.profile.reservations.index'))->assertOk();
    }

    public function test_the_super_admin_logs_a_team_account_out_everywhere(): void
    {
        $staff = $this->staff();
        $jeton = $this->login($staff);
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->get(route('admin.users.edit', $staff))->assertOk()->assertSee('Déconnecter partout')->assertSee('Salma Guichet');
        $this->actingAs($admin)->post(route('admin.users.deconnecter', $staff))->assertSessionHas('success');
        $this->flushSession();

        $this->actingAs($staff->fresh())->withSession([SessionUnique::CLE => $jeton])->get(route('admin'))->assertRedirect(route('login'));

        // a staff member cannot do it
        $autre = $this->staff();
        $this->flushSession();
        $this->actingAs($autre)->post(route('admin.users.deconnecter', $staff))->assertForbidden();
    }
}
