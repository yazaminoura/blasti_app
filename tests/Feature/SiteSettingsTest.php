<?php

namespace Tests\Feature;

use App\Models\Parametre;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/** Contact details edited in the admin, legal pages. */
class SiteSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_super_admin_sets_the_real_contact_details(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->get(route('admin.coordonnees.edit'))->assertOk();
        $this->actingAs($admin)->put(route('admin.coordonnees.update'), [
            'telephone' => '+212 5 22 11 22 33', 'email' => 'contact@vraie-adresse.ma', 'adresse' => '12 rue Exemple, Casablanca',
            'instagram' => 'https://instagram.com/blasti',
        ])->assertSessionHas('success');

        $this->assertSame('+212 5 22 11 22 33', Parametre::actuel()->telephone);
        Parametre::appliquerALaConfig();
        $this->assertSame('contact@vraie-adresse.ma', config('safar.contact.email'));
        $this->get(route('contact'))->assertSee('+212 5 22 11 22 33');
    }

    public function test_bad_contact_details_are_refused(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->put(route('admin.coordonnees.update'), [
            'telephone' => 'appelez-nous', 'email' => 'pas-un-email', 'adresse' => '', 'facebook' => 'facebook.com/x',
        ])->assertSessionHasErrors(['telephone', 'email', 'adresse', 'facebook']);
    }

    public function test_staff_with_a_role_cannot_change_the_contact_details(): void
    {
        $staff = User::factory()->create(['isadmin' => 1]);
        $staff->roles()->attach(Role::create(['name' => 'Contrôleur', 'slug' => 'controleur'])->id);

        $this->actingAs($staff)->get(route('admin.coordonnees.edit'))->assertForbidden();
    }

    public function test_the_test_email_warns_when_mails_are_only_logged(): void
    {
        config(['mail.default' => 'log']);
        $admin = User::factory()->create(['isadmin' => 1]);

        $this->actingAs($admin)->post(route('admin.coordonnees.test-mail'))->assertSessionHas('error');
    }

    public function test_the_legal_pages_exist_and_are_linked_in_the_footer(): void
    {
        foreach (['conditions', 'confidentialite', 'mentions-legales'] as $page) {
            $this->get(route('pages.legal', $page))->assertOk();
        }
        $this->get('/legal/autre')->assertNotFound();
        $this->get(route('home'))->assertSee(route('pages.legal', 'conditions'), false);
        // the CGV quote the site's real rules
        $this->get(route('pages.legal', 'conditions'))->assertSee((string) config('safar.modification_heures'));
    }
}
