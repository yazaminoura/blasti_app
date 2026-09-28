@props([
    'name',
    'label',
    'type' => 'text',
    'value' => null,
    'required' => false,
    'hint' => null,
    'placeholder' => null,
    'col' => 'col-12',
    'options' => [],      // select: [value => label]
    'empty' => null,      // select: text of the empty first option
    'suffix' => null,     // text shown after the input (DH, sièges...)
    'icon' => null,       // icon shown before the input
])

@php
    $id = $attributes->get('id', 'f_' . $name);
    $current = old($name, $value);
    $invalid = $errors->has($name);
    $control = ($type === 'select' ? 'form-select' : 'form-control') . ($invalid ? ' is-invalid' : '');
@endphp

<div class="{{ $col }}">
    <label for="{{ $id }}" class="form-label">{{ $label }}@if ($required)<span class="sa-req">*</span>@endif</label>

    @if ($suffix || $icon)<div class="input-group {{ $invalid ? 'has-validation' : '' }}">@endif
        @if ($icon)<span class="input-group-text"><i class="bi {{ $icon }}"></i></span>@endif

        @if ($type === 'select')
            <select name="{{ $name }}" id="{{ $id }}" {{ $attributes->except('id')->merge(['class' => $control]) }} @required($required)>
                @if ($empty !== null)
                    <option value="">{{ $empty }}</option>
                @endif
                @foreach ($options as $optionValue => $optionLabel)
                    <option value="{{ $optionValue }}" @selected((string) $current === (string) $optionValue)>{{ $optionLabel }}</option>
                @endforeach
            </select>
        @elseif ($type === 'textarea')
            <textarea name="{{ $name }}" id="{{ $id }}" rows="3" placeholder="{{ $placeholder }}"
                {{ $attributes->except('id')->merge(['class' => $control]) }} @required($required)>{{ $current }}</textarea>
        @else
            <input type="{{ $type }}" name="{{ $name }}" id="{{ $id }}" value="{{ $type === 'password' ? '' : $current }}" placeholder="{{ $placeholder }}"
                {{ $attributes->except('id')->merge(['class' => $control]) }} @required($required)>
        @endif

        @if ($suffix)<span class="input-group-text">{{ $suffix }}</span>@endif
        @error($name)<div class="invalid-feedback">{{ $message }}</div>@enderror
    @if ($suffix || $icon)</div>@endif

    @if ($hint)<div class="form-text">{{ $hint }}</div>@endif
</div>
