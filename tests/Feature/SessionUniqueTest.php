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

    public function test_a_closed_account_cannot_log_in_and_keeps_its_history(): void
    {
        $staff = $this->staff();
        $jeton = $this->login($staff);
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->patch(route('admin.users.desactiver', $staff))->assertSessionHas('success');
        $this->assertNotNull($staff->fresh()->desactive_le);
        $this->flushSession();

        // the open session ends at the next click, and the password no longer opens the door
        $this->actingAs($staff->fresh())->withSession([SessionUnique::CLE => $jeton])->get(route('admin'))->assertRedirect(route('login'));
        $this->flushSession();
        auth()->forgetGuards();
        $this->post('/login', ['email' => $staff->email, 'password' => '11223344'])->assertSessionHasErrors('email');
        $this->assertGuest();
        $this->assertNotNull(User::find($staff->id)); // never deleted

        // opened again: login works
        $this->actingAs($admin)->patch(route('admin.users.desactiver', $staff))->assertSessionHas('success');
        $this->flushSession();
        auth()->forgetGuards();
        $this->post('/login', ['email' => $staff->email, 'password' => '11223344'])->assertSessionHasNoErrors();
    }

    public function test_the_super_admin_can_delete_a_team_account_and_its_history_stays(): void
    {
        $staff = $this->staff();
        $admin = User::factory()->create(['isadmin' => 1]);
        $scan = \App\Models\Scan::create(['user_id' => $staff->id, 'resultat' => 'valable']);

        // a staff member cannot delete another team account
        $this->actingAs($this->staff())->delete(route('admin.users.destroy', $staff))->assertForbidden();

        $this->actingAs($admin)->delete(route('admin.users.destroy', $staff))->assertRedirect(route('admin.users.index'));
        $this->assertNull(User::find($staff->id));
        $this->assertNotNull($scan->fresh());          // the scan is still there...
        $this->assertNull($scan->fresh()->user_id);    // ...without the name

        // never yourself, never the last super admin
        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();
    }

    public function test_the_last_super_admin_and_yourself_cannot_be_closed(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $autre = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->patch(route('admin.users.desactiver', $admin))->assertForbidden();
        $this->actingAs($admin)->patch(route('admin.users.desactiver', $autre))->assertSessionHas('success'); // 2 super admins: ok
        $this->actingAs($autre->fresh())->patch(route('admin.users.desactiver', $admin)); // $autre is closed now
        $this->assertNull($admin->fresh()->desactive_le);
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
