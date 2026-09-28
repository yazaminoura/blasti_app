<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the users.
     */
    public function index(Request $request)
    {
        $users = User::with('roles')
            ->when($request->filled('q'), fn ($query) => $this->search($query, $request->q))
            ->latest()
            ->paginate(10)
            ->withQueryString();
        $roles = \App\Models\Role::withCount('users')->get();
        return view('admin.users.index', compact('users', 'roles'));
    }

    /**
     * Display a listing of the clients.
     */
    public function clients(Request $request)
    {
        // Les clients sont les utilisateurs sans rôle administratif et isadmin = 0
        $users = User::where('isadmin', 0)
            ->where(function($query) {
                $query->whereDoesntHave('roles')
                      ->orWhereHas('roles', function($q) {
                          $q->where('name', 'client');
                      });
            })
            ->when($request->filled('q'), fn ($query) => $this->search($query, $request->q))
            ->with('roles')
            ->withCount('reservations')
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.users.clients', compact('users'));
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = \App\Models\Role::all();
        return view('admin.users.create', compact('roles'));
    }

    /**
     * Store a newly created user in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'nullable|exists:roles,id',
            'isadmin' => 'nullable|boolean',
        ]);

        // Only a super admin may create back-office accounts or give roles
        $canManageAccess = auth()->user()->isSuperAdmin();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'isadmin' => $canManageAccess && $request->boolean('isadmin') ? 1 : 0,
        ]);

        if ($canManageAccess && $request->role) {
            $user->roles()->attach($request->role);
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        $this->ensureCanManage($user);

        $roles = \App\Models\Role::all();
        $canManageAccess = auth()->user()->isSuperAdmin();

        return view('admin.users.edit', compact('user', 'roles', 'canManageAccess'));
    }

    /**
     * Update the specified user in storage.
     */
    public function update(Request $request, User $user)
    {
        $this->ensureCanManage($user);

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'nullable|exists:roles,id',
            'isadmin' => 'nullable|boolean',
        ]);

        // Access rights (admin flag + role) can only be changed by a super admin; checked before saving anything
        $canManageAccess = auth()->user()->isSuperAdmin();
        $isAdmin = $request->boolean('isadmin');
        $roleIds = $request->filled('role') ? [$request->role] : [];

        if ($canManageAccess && $user->is(auth()->user()) && (! $isAdmin || $roleIds)) {
            return back()->withInput()->with('error', 'Vous ne pouvez pas retirer vos propres droits de super administrateur.');
        }

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        if ($canManageAccess) {
            $user->update(['isadmin' => $isAdmin ? 1 : 0]);
            $user->roles()->sync($roleIds);
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /**
     * Reset the user's password.
     */
    public function updatePassword(Request $request, User $user)
    {
        $this->ensureCanManage($user);

        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
        ]);

        return back()->with('success', 'Mot de passe réinitialisé avec succès.');
    }

    /**
     * Remove the specified user from storage.
     */
    public function destroy(User $user)
    {
        if ($user->isadmin) {
            return back()->with('error', 'Vous ne pouvez pas supprimer un administrateur.');
        }

        // Optional: Check if user has active reservations before deleting
        if ($user->reservations()->exists()) {
            return back()->with('error', 'Cet utilisateur a des réservations actives et ne peut pas être supprimé.');
        }

        $user->delete();

        return back()->with('success', 'Utilisateur supprimé avec succès.');
    }

    /** Search box of the user lists: name, email or phone. */
    private function search($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('telephone', 'like', "%{$term}%");
        });
    }

    /**
     * Staff members (admins with a limited role) may manage clients, but not other admin accounts:
     * otherwise they could reset the super admin's password or promote themselves.
     */
    private function ensureCanManage(User $user): void
    {
        $current = auth()->user();

        abort_if(
            $user->isadmin && ! $current->isSuperAdmin() && ! $user->is($current),
            403,
            'Seul le super administrateur peut modifier un compte administrateur.'
        );
    }
}
