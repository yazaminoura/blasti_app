@props(['name', 'label', 'current' => null, 'hint' => 'PNG, JPG ou WEBP.', 'col' => 'col-12', 'contain' => false, 'icon' => 'bi-image'])

{{-- Image input with a live preview of the current / newly chosen file --}}
@php $previewId = 'preview_' . $name; @endphp
<div class="{{ $col }}">
    <label for="f_{{ $name }}" class="form-label">{{ $label }}</label>
    <div class="sa-upload">
        <div class="sa-upload-preview {{ $contain ? 'contain' : '' }}" id="{{ $previewId }}">
            @if ($current)
                <img src="{{ \App\Models\Ville::resolveImageUrl($current) }}" alt="">
            @else
                <i class="bi {{ $icon }}"></i>
            @endif
        </div>
        <div class="flex-grow-1">
            <input type="file" name="{{ $name }}" id="f_{{ $name }}" accept="image/*" data-sa-preview="{{ $previewId }}"
                   class="form-control @error($name) is-invalid @enderror">
            @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
            <div class="form-text">{{ $current ? 'Laissez vide pour garder l\'image actuelle. ' : '' }}{{ $hint }}</div>
        </div>
    </div>
</div>
