@props(['edit' => null, 'delete' => null, 'show' => null, 'confirm' => 'Supprimer cet élément ?', 'deleteLabel' => 'Supprimer', 'deleteIcon' => 'bi-trash3', 'confirmButton' => 'Oui, supprimer'])

{{-- Icon buttons at the end of a table row. Pass null to hide an action (e.g. missing permission). --}}
<div class="sa-row-actions">
    {{ $slot }}
    @if ($show)
        <a href="{{ $show }}" class="sa-icon-btn" title="Voir"><i class="bi bi-eye"></i></a>
    @endif
    @if ($edit)
        <a href="{{ $edit }}" class="sa-icon-btn" title="Modifier"><i class="bi bi-pencil"></i></a>
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
