<?php

namespace Tests\Feature;

use App\Models\ModeReglement;
use App\Models\User;
use App\Models\Ville;
use App\Support\Cmi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/** Starting a real site: base lists, first super admin, no demo data, no fake card page. */
class ProductionSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_db_seed_without_demo_only_loads_the_base_lists(): void
    {
        config(['safar.demo' => false]);
        $this->seed();

        $this->assertTrue(Ville::where('ville', 'Casablanca')->exists());
        $this->assertTrue(ModeReglement::where('en_agence', true)->exists());
        $this->assertSame(0, User::count());
        $this->assertSame(0, \App\Models\Voyage::count());
    }

    public function test_the_demo_never_loads_in_production(): void
    {
        config(['safar.demo' => true]);
        $this->app['env'] = 'production';
        (new \Database\Seeders\DatabaseSeeder)->setContainer($this->app)->__invoke();

        $this->assertSame(0, User::count());
    }

    public function test_the_admin_command_creates_a_super_admin(): void
    {
        $this->artisan('blasti:admin', ['email' => 'Patron@Blasti.ma', '--name' => 'Patron'])
            ->expectsQuestion('Mot de passe (8 caractères minimum)', 'un-vrai-secret')
            ->expectsQuestion('Confirmez le mot de passe', 'un-vrai-secret')
            ->assertSuccessful();

        $admin = User::where('email', 'patron@blasti.ma')->sole();
        $this->assertTrue($admin->isSuperAdmin());
        $this->assertTrue(Hash::check('un-vrai-secret', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_the_admin_command_refuses_a_short_password(): void
    {
        $this->artisan('blasti:admin', ['email' => 'patron@blasti.ma', '--name' => 'Patron'])
            ->expectsQuestion('Mot de passe (8 caractères minimum)', 'court')
            ->expectsQuestion('Confirmez le mot de passe', 'court')
            ->assertFailed();

        $this->assertSame(0, User::count());
    }

    public function test_the_fake_card_page_needs_local_and_debug(): void
    {
        config(['safar.paiement_test' => true, 'app.debug' => false]);
        $this->app['env'] = 'local';
        $this->assertFalse(Cmi::testMode());

        config(['app.debug' => true]);
        $this->assertTrue(Cmi::testMode());
    }
}
