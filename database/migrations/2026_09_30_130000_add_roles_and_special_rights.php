<?php

use App\Models\Permission;
use App\Models\Role;
use App\Support\Droits;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Str;

/**
 * New rights (Clients section, refund, revenue, scanner) and the ready-made roles.
 * Existing roles keep what they could do before: users rights are copied to clients,
 * "reservations.update" (it used to cover refund + scanner) gives the two new rights, dashboard gives the revenue.
 */
return new class extends Migration
{
    public function up(): void
    {
        $permission = fn (string $name) => Permission::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)])->id;

        $copies = [
            'utilisateurs.read' => ['clients.read'], 'utilisateurs.create' => ['clients.create'],
            'utilisateurs.update' => ['clients.update'], 'utilisateurs.delete' => ['clients.delete'],
            'reservations.update' => ['reservations.rembourser', 'scanner.use'],
            'dashboard.read' => ['finance.read'],
        ];
        foreach (Role::with('permissions')->get() as $role) {
            $had = $role->permissions->pluck('name');
            $new = $had->flatMap(fn ($name) => $copies[$name] ?? [])->unique()->map($permission);
            $role->permissions()->syncWithoutDetaching($new);
        }

        foreach (Droits::roles() as $slug => [$name, $rights]) {
            $role = Role::where('slug', $slug)->first();
            if ($role) {
                // already there (e.g. the demo "Compagnie" role): new name, rights only added
                $role->update(['name' => $name]);
                $role->permissions()->syncWithoutDetaching(array_map($permission, $rights));
                continue;
            }
            if (Role::where('name', $name)->exists()) {
                continue; // a role with this name was made by hand: leave it alone
            }
            Role::create(['name' => $name, 'slug' => $slug])->permissions()->sync(array_map($permission, $rights));
        }
    }

    public function down(): void
    {
        // roles and rights are data the admin may have changed since: nothing is removed
    }
};
