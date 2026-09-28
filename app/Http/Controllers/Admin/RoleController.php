<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    /** Rows and columns of the permission grid; a permission is "<service in lowercase>.<action>". */
    private const SERVICES = ['Dashboard', 'Utilisateurs', 'Roles', 'Villes', 'Type Voyages', 'Mode Reglements', 'Reservations', 'Voyages', 'Societes', 'Autocars', 'Equipements', 'Options'];
    private const ACTIONS = ['read', 'create', 'update', 'delete'];

    public function create()
    {
        return view('admin.roles.create', ['services' => self::SERVICES, 'actions' => self::ACTIONS]);
    }

    public function store(Request $request)
    {
        $this->validateRole($request);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $this->uniqueSlug($request->name),
        ]);
        $this->syncPermissions($role, $request->input('permissions', []));

        return redirect()->route('admin.users.index')->with('success', 'Rôle créé avec succès.');
    }

    public function edit(Role $role)
    {
        return view('admin.roles.edit', [
            'role' => $role,
            'services' => self::SERVICES,
            'actions' => self::ACTIONS,
            'rolePermissions' => $role->permissions->pluck('name')->toArray(),
        ]);
    }

    public function update(Request $request, Role $role)
    {
        $this->validateRole($request, $role);

        $role->update([
            'name' => $request->name,
            'slug' => $this->uniqueSlug($request->name, $role),
        ]);
        $this->syncPermissions($role, $request->input('permissions', []));

        return redirect()->route('admin.users.index')->with('success', 'Rôle mis à jour avec succès.');
    }

    public function destroy(Role $role)
    {
        if ($role->users()->exists()) {
            return back()->with('error', 'Impossible de supprimer un rôle attribué à des utilisateurs.');
        }

        $role->delete();

        return back()->with('success', 'Rôle supprimé avec succès.');
    }

    private function validateRole(Request $request, ?Role $role = null): void
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('roles', 'name')->ignore($role)],
            'permissions' => ['array'],
            // Only the permissions of the grid (no made-up names)
            'permissions.*' => ['string', Rule::in(self::allPermissions())],
        ], [
            'name.required' => 'Le nom du rôle est obligatoire.',
            'name.unique' => 'Un rôle porte déjà ce nom.',
            'permissions.*.in' => 'Permission inconnue.',
        ]);
    }

    private static function allPermissions(): array
    {
        $names = [];
        foreach (self::SERVICES as $service) {
            foreach (self::ACTIONS as $action) {
                $names[] = strtolower($service) . '.' . $action;
            }
        }

        return $names;
    }

    private function syncPermissions(Role $role, array $names): void
    {
        $ids = collect($names)->unique()->map(fn ($name) => Permission::firstOrCreate(
            ['name' => $name],
            ['slug' => Str::slug($name)]
        )->id);

        $role->permissions()->sync($ids);
    }

    /**
     * The slug column is unique: "Agent" and "agent." (or two Arabic names, which slug to "")
     * would collide, so a number is added when needed.
     */
    private function uniqueSlug(string $name, ?Role $ignore = null): string
    {
        $base = Str::slug($name) ?: 'role';
        $slug = $base;
        $i = 2;
        while (Role::where('slug', $slug)->when($ignore, fn ($q) => $q->whereKeyNot($ignore->id))->exists()) {
            $slug = $base . '-' . $i++;
        }

        return $slug;
    }
}
