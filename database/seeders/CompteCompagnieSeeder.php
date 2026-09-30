<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\Societe;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Demo company account: a back-office login tied to the first transport company, with the "Compagnie" role
 * (its own buses, departures, tickets, boarding scanner and reviews — nothing global like cities or users).
 * Called by DemoDataSeeder, or by hand: php artisan db:seed --class=CompteCompagnieSeeder
 *
 * Account (change the password before going live): compagnie@blasti.ma / password
 */
class CompteCompagnieSeeder extends Seeder
{
    public const EMAIL = 'compagnie@blasti.ma';

    /** What a transport company may do in its own space (same names as the role grid). */
    public const PERMISSIONS = [
        'dashboard.read',
        'reservations.read', 'reservations.update',
        'voyages.read', 'voyages.create', 'voyages.update', 'voyages.delete',
        'autocars.read', 'autocars.create', 'autocars.update',
        'avis.read',
    ];

    public function run(): void
    {
        $societe = Societe::orderBy('id')->first();
        if (! $societe) {
            return;
        }

        $role = Role::firstOrCreate(['slug' => 'compagnie'], ['name' => 'Compagnie']);
        $role->permissions()->sync(collect(self::PERMISSIONS)->map(
            fn ($name) => Permission::firstOrCreate(['name' => $name], ['slug' => Str::slug($name)])->id
        ));

        $user = User::firstOrCreate(['email' => self::EMAIL], [
            'name' => $societe->nom_contact ?: $societe->raison_social,
            'password' => Hash::make('password'),
            'isadmin' => 1,
            'email_verified_at' => now(),
        ]);
        $user->forceFill(['isadmin' => 1, 'societe_id' => $user->societe_id ?? $societe->id])->save();
        $user->roles()->syncWithoutDetaching([$role->id]);
    }
}
