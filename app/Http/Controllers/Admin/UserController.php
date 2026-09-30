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
        // Back-office accounts only: clients have their own page (admin.clients.index)
        $types = [
            'super' => fn ($q) => $q->whereNull('societe_id')->whereDoesntHave('roles'),
            'equipe' => fn ($q) => $q->whereNull('societe_id')->whereHas('roles'),
            'compagnie' => fn ($q) => $q->whereNotNull('societe_id'),
        ];
        $type = array_key_exists((string) $request->type, $types) ? $request->type : null;

        $counts = ['tous' => User::where('isadmin', 1)->count()];
        foreach ($types as $key => $scope) {
            $counts[$key] = $scope(User::where('isadmin', 1))->count();
        }

        $users = User::where('isadmin', 1)
            ->with('roles', 'societe:id,raison_social')
            ->when($type, fn ($query) => $types[$type]($query))
            ->when($request->filled('q'), fn ($query) => $this->search($query, $request->q))
            ->latest()
            ->paginate(15)
            ->withQueryString();
        $roles = \App\Models\Role::withCount(['users' => fn ($q) => $q->where('isadmin', 1)])->orderBy('name')->get();

        return view('admin.users.index', compact('users', 'roles', 'counts', 'type'));
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

    /** One client: identity, numbers and bookings. Team accounts are edited on the team page. */
    public function showClient(User $user)
    {
        if ($user->isadmin) {
            return redirect()->route('admin.users.edit', $user);
        }

        $reservations = $user->reservations()->withoutGlobalScope('active')
            ->with('villeDepart', 'villeArrivee', 'modeReglement')
            ->latest('id')
            ->paginate(10);
        $stats = [
            'billets' => $user->reservations()->count(),
            'payes' => $user->reservations()->whereNotNull('paye_le')->count(),
            'non_payes' => $user->reservations()->whereNull('paye_le')->whereDate('date_depart', '>=', today())->count(),
            'annules' => $user->reservations()->withoutGlobalScope('active')->where('statut', \App\Models\Reservation::ANNULEE)->count(),
            'absences' => $user->absences(),
        ];

        return view('admin.users.client', compact('user', 'reservations', 'stats'));
    }

    /** Identity of a client only: the admin switch, role and company never appear here. */
    public function updateClient(Request $request, User $user)
    {
        abort_if($user->isadmin, 404);

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'telephone' => 'nullable|string|max:30',
        ]);
        $user->update($data);

        return back()->with('success', 'Client mis à jour.');
    }

    /**
     * Show the form for creating a new user.
     */
    public function create()
    {
        $roles = \App\Models\Role::all();
        $societes = \App\Models\Societe::orderBy('raison_social')->get(['id', 'raison_social']);
        return view('admin.users.create', compact('roles', 'societes'));
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
            'societe_id' => 'nullable|exists:societes,id',
        ]);

        // Only a super admin may create back-office accounts or give roles
        $canManageAccess = auth()->user()->isSuperAdmin();

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => \Illuminate\Support\Facades\Hash::make($request->password),
            'isadmin' => $canManageAccess && $request->boolean('isadmin') ? 1 : 0,
        ]);
        // created by the back office: the address is trusted, no verification link needed
        $user->forceFill(['email_verified_at' => now()])->save();

        if ($canManageAccess && $request->role) {
            $user->roles()->attach($request->role);
        }
        // company space: this back-office account only sees one transport company
        if ($canManageAccess && $user->isadmin) {
            $user->forceFill(['societe_id' => $request->input('societe_id') ?: null])->save();
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur créé avec succès.');
    }

    /**
     * Show the form for editing the specified user.
     */
    public function edit(User $user)
    {
        // a client has its own page; the super admin can still open this form to give admin access (?acces=1)
        if (! $user->isadmin && ! (request()->boolean('acces') && auth()->user()->isSuperAdmin())) {
            return redirect()->route('admin.clients.show', $user);
        }
        $this->ensureCanManage($user);

        $roles = \App\Models\Role::all();
        $canManageAccess = auth()->user()->isSuperAdmin();
        $societes = \App\Models\Societe::orderBy('raison_social')->get(['id', 'raison_social']);

        return view('admin.users.edit', compact('user', 'roles', 'canManageAccess', 'societes'));
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
            'societe_id' => 'nullable|exists:societes,id',
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
            // company space (never on your own account: you would lose the super admin rights)
            if (! $user->is(auth()->user())) {
                $user->forceFill(['societe_id' => $isAdmin ? ($request->input('societe_id') ?: null) : null])->save();
            }
        }

        return redirect()->route('admin.users.index')->with('success', 'Utilisateur mis à jour avec succès.');
    }

    /** Super admin: log this team account out of every browser (lost tablet, staff member leaving...). */
    public function deconnecter(User $user)
    {
        abort_unless(auth()->user()->isSuperAdmin(), 403, 'Seul le super administrateur peut déconnecter un compte.');
        abort_if($user->is(auth()->user()), 403, 'Utilisez « Déconnexion » pour votre propre compte.');

        \App\Support\SessionUnique::deconnecterPartout($user);

        return back()->with('success', $user->name . ' est déconnecté de tous ses appareils.');
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
