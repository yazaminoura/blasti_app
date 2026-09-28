{{--
    List of autocar <-> option / équipement associations.
    Params: $items, $relation ('option' | 'equipement'), $field, $route, $permission, $title, $subtitle, $icon, $itemLabel
--}}
@php
    $user = auth()->user();
    $canCreate = $user->hasPermission("$permission.create");
    $canUpdate = $user->hasPermission("$permission.update");
    $canDelete = $user->hasPermission("$permission.delete");
@endphp

<x-admin.page-header :title="$title" :subtitle="$subtitle">
    @if ($canCreate)
        <a href="{{ route($route . '.create') }}" class="btn btn-primary"><i class="bi bi-link-45deg"></i> Nouvelle association</a>
    @endif
</x-admin.page-header>

<x-admin.card flush style="max-width: 980px;">
    @if ($items->isEmpty())
        <x-admin.empty :icon="$icon" title="Aucune association" text="Associez un autocar à un élément pour l'afficher sur ses voyages." />
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th>Autocar</th>
                        <th>{{ $itemLabel }}</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        @php
                            $name = $item->$relation?->$field ?? '—';
                            $editUrl = $canUpdate ? route($route . '.edit', $item->id) : null;
                            $deleteUrl = $canDelete ? route($route . '.destroy', $item->id) : null;
                        @endphp
                        <tr>
                            <td class="sa-cell-muted sa-num">{{ $items->firstItem() + $loop->index }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-bus-front text-body-secondary"></i>
                                    <span class="sa-mono sa-strong">{{ $item->autocar?->matricule ?? '—' }}</span>
                                </div>
                            </td>
                            <td><span class="sa-chip brand"><i class="bi {{ $icon }}"></i> {{ $name }}</span></td>
                            <td class="text-end">
                                <x-admin.row-actions :edit="$editUrl" :delete="$deleteUrl"
                                    :confirm="'Retirer « ' . $name . ' » de l\'autocar ' . ($item->autocar?->matricule ?? '') . ' ?'" confirmButton="Oui, retirer" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$items" label="association(s)" />
    @endif
</x-admin.card>
