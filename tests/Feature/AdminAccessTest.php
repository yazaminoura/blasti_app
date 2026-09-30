<?php

namespace Tests\Feature;

use App\Http\Middleware\AdminPermission;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/** Every admin page (without URL parameter) opens for the super admin and for a staff role that has all the rights. */
class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private const SERVICES = ['dashboard', 'clients', 'utilisateurs', 'roles', 'villes', 'type voyages', 'mode reglements', 'reservations', 'voyages', 'societes', 'autocars', 'equipements', 'options', 'promotions', 'avis', 'statistiques'];

    /** @return array<string, string> route name => uri */
    private function adminPages(): array
    {
        $pages = [];
        foreach (Route::getRoutes() as $route) {
            if (! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{') || ! $route->getName()) {
                continue;
            }
            if (in_array(AdminPermission::class, $route->gatherMiddleware(), true) || in_array('admin.permission', $route->gatherMiddleware(), true)) {
                $pages[$route->getName()] = '/' . ltrim($route->uri(), '/');
            }
        }

        return $pages;
    }

    public function test_super_admin_opens_every_admin_page(): void
    {
        $admin = User::factory()->create(['isadmin' => 1]);
        $pages = $this->adminPages();
        $this->assertNotEmpty($pages);

        foreach ($pages as $name => $uri) {
            $status = $this->actingAs($admin)->get($uri)->baseResponse->getStatusCode();
            $this->assertContains($status, [200, 302], "$name ($uri) answered $status");
        }
    }

    public function test_staff_creates_updates_and_deletes_only_with_the_matching_right(): void
    {
        $role = Role::create(['name' => 'Villes', 'slug' => 'villes']);
        foreach (['villes.read', 'villes.create', 'villes.update', 'villes.delete'] as $name) {
            $role->permissions()->attach(Permission::firstOrCreate(['name' => $name], ['slug' => str_replace('.', '-', $name)]));
        }
        $staff = User::factory()->create(['isadmin' => 1]);
        $staff->roles()->attach($role);

        $this->actingAs($staff)->post(route('villes.store'), ['ville' => 'Ifrane'])->assertRedirect();
        $ville = \App\Models\Ville::where('ville', 'Ifrane')->firstOrFail();
        $this->actingAs($staff)->put(route('villes.update', $ville), ['ville' => 'Ifrane Centre'])->assertRedirect();
        $this->assertSame('Ifrane Centre', $ville->fresh()->ville);
        $this->actingAs($staff)->delete(route('villes.destroy', $ville))->assertRedirect();
        $this->assertNull($ville->fresh());

        // read only: every write is refused
        $lecture = Role::create(['name' => 'Lecture', 'slug' => 'lecture']);
        $lecture->permissions()->attach(Permission::where('name', 'villes.read')->first());
        $lecteur = User::factory()->create(['isadmin' => 1]);
        $lecteur->roles()->attach($lecture);
        $autre = \App\Models\Ville::create(['ville' => 'Azrou']);
        $this->actingAs($lecteur)->post(route('villes.store'), ['ville' => 'Midelt'])->assertForbidden();
        $this->actingAs($lecteur)->put(route('villes.update', $autre), ['ville' => 'X'])->assertForbidden();
        $this->actingAs($lecteur)->delete(route('villes.destroy', $autre))->assertForbidden();
        $this->assertSame('Azrou', $autre->fresh()->ville);
    }

    public function test_staff_with_every_right_opens_every_page_except_super_admin_ones(): void
    {
        $role = Role::create(['name' => 'Tout', 'slug' => 'tout']);
        foreach (self::SERVICES as $service) {
            foreach (['create', 'read', 'update', 'delete'] as $action) {
                $role->permissions()->attach(Permission::firstOrCreate(['name' => "$service.$action"], ['slug' => str_replace(' ', '-', "$service-$action")]));
            }
        }
        foreach (array_keys(\App\Support\Droits::SPECIAUX) as $name) {
            $role->permissions()->attach(Permission::firstOrCreate(['name' => $name], ['slug' => \Illuminate\Support\Str::slug($name)]));
        }
        $staff = User::factory()->create(['isadmin' => 1]);
        $staff->roles()->attach($role);

        foreach ($this->adminPages() as $name => $uri) {
            $superOnly = AdminPermission::permissionFor($name) === null
                || str_starts_with($name, 'admin.roles.') || str_starts_with($name, 'admin.apparence.') || str_starts_with($name, 'admin.coordonnees.');
            $status = $this->actingAs($staff)->get($uri)->baseResponse->getStatusCode();
            $superOnly
                ? $this->assertSame(403, $status, "$name ($uri) should be super admin only")
                : $this->assertContains($status, [200, 302], "$name ($uri) answered $status");
        }
    }
}
