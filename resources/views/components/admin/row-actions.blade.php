@props(['edit' => null, 'delete' => null, 'show' => null, 'confirm' => 'Supprimer cet élément ?', 'deleteLabel' => 'Supprimer', 'deleteIcon' => 'bi-trash3', 'confirmButton' => 'Oui, supprimer'])

{{-- Icon buttons at the end of a table row. Pass null to hide an action (e.g. missing permission).
     data-sa-row-link: clicking anywhere on the row opens the edit form (or the detail page), see safar-admin.js. --}}
<div class="sa-row-actions">
    {{ $slot }}
    @if ($show)
        <a href="{{ $show }}" class="sa-icon-btn" title="Voir" data-sa-row-link="show"><i class="bi bi-eye"></i></a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" class="sa-icon-btn" title="Modifier" data-sa-row-link="edit"><i class="bi bi-pencil"></i></a>
    @endif
    @if ($delete)
        <form action="{{ $delete }}" method="POST" onsubmit="confirmDelete(event, this)"
              data-confirm="{{ $confirm }}" data-confirm-button="{{ $confirmButton }}">
            @csrf
            @method('DELETE')
            <button type="submit" class="sa-icon-btn danger" title="{{ $deleteLabel }}"><i class="bi {{ $deleteIcon }}"></i></button>
        </form>
    @endif
</div>
