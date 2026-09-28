{{--
    List of a reference table with a single name column (villes, types, modes, options, équipements).
    Params: $items, $field, $route (resource prefix), $permission (service), $title, $subtitle, $icon,
            $createLabel, $columnLabel, $unit (footer label), $emptyText
--}}
@php
    $user = auth()->user();
    $canCreate = $user->hasPermission("$permission.create");
    $canUpdate = $user->hasPermission("$permission.update");
    $canDelete = $user->hasPermission("$permission.delete");
    $paginated = $items instanceof \Illuminate\Contracts\Pagination\LengthAwarePaginator;
@endphp

<x-admin.page-header :title="$title" :subtitle="$subtitle">
    @if ($canCreate)
        <a href="{{ route($route . '.create') }}" class="btn btn-primary"><i class="bi bi-plus-lg"></i> {{ $createLabel }}</a>
    @endif
</x-admin.page-header>

<x-admin.card flush style="max-width: 880px;">
    @if ($items->isEmpty())
        <x-admin.empty :icon="$icon" :title="'Aucun élément pour le moment'" :text="$emptyText">
            @if ($canCreate)
                <a href="{{ route($route . '.create') }}" class="btn btn-primary btn-sm"><i class="bi bi-plus-lg"></i> {{ $createLabel }}</a>
            @endif
        </x-admin.empty>
    @else
        <div class="sa-table-wrap">
            <table class="table sa-table align-middle">
                <thead>
                    <tr>
                        <th style="width: 80px;">#</th>
                        <th>{{ $columnLabel }}</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($items as $item)
                        <tr>
                            <td class="sa-cell-muted sa-num">{{ $paginated ? $items->firstItem() + $loop->index : $loop->iteration }}</td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="sa-tile" style="width: 32px; height: 32px; font-size: .9rem;"><i class="bi {{ $icon }}"></i></span>
                                    <span class="sa-strong">{{ $item->$field }}</span>
                                </div>
                            </td>
                            <td class="text-end">
                                @php
                                    $editUrl = $canUpdate ? route($route . '.edit', $item->id) : null;
                                    $deleteUrl = $canDelete ? route($route . '.destroy', $item->id) : null;
                                @endphp
                                <x-admin.row-actions :edit="$editUrl" :delete="$deleteUrl" :confirm="'Supprimer « ' . $item->$field . ' » ?'" />
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <x-admin.table-footer :paginator="$items" :label="$unit" />
    @endif
</x-admin.card>
