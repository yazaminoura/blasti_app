{{-- Users table shared by "Utilisateurs & rôles" and "Clients". $showReservations adds the bookings column. --}}
@php
    $me = auth()->user();
    // client accounts are managed with the Clients right, team accounts with the Utilisateurs right
    $can = fn ($user, $action) => $me->hasPermission(($user->isadmin ? 'utilisateurs.' : 'clients.') . $action);
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
                    <th>{{ $showReservations ? 'Inscrit le' : 'Dernière connexion' }}</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $user)
                    @php
                        // Staff cannot edit other admin accounts (see UserController::ensureCanManage)
                        $manageable = ! $user->isadmin || $me->isSuperAdmin() || $user->is($me);
                        $editUrl = match (true) {
                            ! $user->isadmin => route('admin.clients.show', $user->id), // client page (read or edit)
                            $can($user, 'update') && $manageable => route('admin.users.edit', $user->id),
                            default => null,
                        };
                        // admin accounts are never deleted (UserController::destroy refuses): no useless button
                        $deleteUrl = $can($user, 'delete') && ! $user->isadmin ? route('admin.users.destroy', $user->id) : null;
                    @endphp
                    <tr>
                        <td>
                            <div class="sa-person">
                                <span class="sa-avatar">{{ mb_substr($user->name, 0, 2) }}</span>
                                <div>
                                    <div class="sa-strong">{{ $user->name }} @if ($user->is($me))<span class="sa-chip muted ms-1">Vous</span>@endif
                                        @if ($user->desactive_le)<span class="sa-chip danger ms-1">Désactivé</span>@endif</div>
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
                                @if ($user->societe_id)
                                    <span class="sa-chip info"><i class="bi bi-buildings"></i> {{ $user->societe?->raison_social ?? 'Compagnie' }}</span>
                                @elseif ($user->roles->isEmpty() && $user->isadmin)
                                    <span class="sa-chip brand"><i class="bi bi-shield-check"></i> Super admin</span>
                                @endif
                                @foreach ($user->roles as $role)
                                    <span class="sa-chip brand"><i class="bi bi-person-badge"></i> {{ $role->name }}</span>
                                @endforeach
                            </td>
                        @endif
                        @if ($showReservations)
                            <td class="sa-num sa-cell-muted">{{ $user->created_at?->format('d/m/Y') }}</td>
                        @else
                            <td class="sa-num sa-cell-muted">
                                {{ $user->derniere_connexion_le ? \Carbon\Carbon::parse($user->derniere_connexion_le)->format('d/m/Y H:i') : 'Jamais' }}
                                @if ($user->derniere_connexion_appareil)<div class="sa-sub">{{ $user->derniere_connexion_appareil }}</div>@endif
                            </td>
                        @endif
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
