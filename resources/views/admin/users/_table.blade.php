{{-- Users table shared by "Utilisateurs & rôles" and "Clients". $showReservations adds the bookings column. --}}
@php
    $me = auth()->user();
    $canUpdate = $me->hasPermission('utilisateurs.update');
    $canDelete = $me->hasPermission('utilisateurs.delete');
    $showReservations = $showReservations ?? false;
@endphp

@if ($users->isEmpty())
    <x-admin.empty icon="bi-people" :title="request('q') ? 'Aucun résultat pour « ' . request('q') . ' »' : 'Aucun compte pour le moment'" />
@else
    <div class="sa-table-wrap">
        <table class="table sa-table align-middle">
            <thead>
                <tr>
                    <th>Utilisateur</th>
                    <th>Email</th>
                    <th>Statut</th>
                    @if ($showReservations)
                        <th>Réservations</th>
                    @else
                        <th>Accès</th>
                    @endif
                    <th>Inscrit le</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        // Staff cannot edit other admin accounts (see UserController::ensureCanManage)
                        $manageable = ! $user->isadmin || $me->isSuperAdmin() || $user->is($me);
                        $editUrl = $canUpdate && $manageable ? route('admin.users.edit', $user->id) : null;
                        $deleteUrl = $canDelete && $manageable && ! $user->is($me) ? route('admin.users.destroy', $user->id) : null;
                    @endphp
                    <tr>
                        <td>
                            <div class="sa-person">
                                <span class="sa-avatar">{{ mb_substr($user->name, 0, 2) }}</span>
                                <div>
                                    <div class="sa-strong">{{ $user->name }}</div>
                                    <div class="sa-sub">{{ $user->telephone ?: 'N° ' . $user->id }}</div>
                                </div>
                            </div>
                        </td>
                        <td><a href="mailto:{{ $user->email }}">{{ $user->email }}</a></td>
                        <td>
                            @if ($user->email_verified_at)
                                <span class="sa-chip success dot">Vérifié</span>
                            @else
                                <span class="sa-chip warning dot">En attente</span>
                            @endif
                        </td>
                        @if ($showReservations)
                            <td>
                                <a href="{{ route('reservation.admin.index', ['q' => $user->email]) }}" class="sa-chip {{ $user->reservations_count ? 'brand' : 'muted' }}">
                                    <i class="bi bi-ticket-perforated"></i> {{ $user->reservations_count }}
                                </a>
                            </td>
                        @else
                            <td>
                                @forelse ($user->roles as $role)
                                    <span class="sa-chip brand">{{ $role->name }}</span>
                                @empty
                                    @if ($user->isadmin)
                                        <span class="sa-chip brand"><i class="bi bi-shield-check"></i> Super admin</span>
                                    @else
                                        <span class="sa-chip muted">Client</span>
                                    @endif
                                @endforelse
                            </td>
                        @endif
                        <td class="sa-num sa-cell-muted">{{ $user->created_at?->format('d/m/Y') }}</td>
                        <td class="text-end">
                            <x-admin.row-actions :edit="$editUrl" :delete="$deleteUrl" :confirm="'Supprimer le compte de ' . $user->name . ' ?'" />
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    <x-admin.table-footer :paginator="$users" label="compte(s)" />
@endif
