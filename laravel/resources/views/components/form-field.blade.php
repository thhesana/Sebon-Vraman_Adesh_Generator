{{-- Label + input row. The value falls back to old() input when a name is given.
     Extra attributes (lang, autocomplete, ...) are forwarded to the input; pass class="nepali" style via inputClass. --}}
@props([
    'label',
    'name' => null,
    'type' => 'text',
    'value' => null,
    'id' => null,
    'required' => true,
    'readonly' => false,
    'placeholder' => null,
    'labelClass' => 'form-label',
    'inputClass' => 'form-control',
    'help' => null,
])

@php
    $fieldId = $id ?? $name;
    $invalid = $name && $errors->has($name);
@endphp

<div class="mb-3">
    <label @if ($fieldId) for="{{ $fieldId }}" @endif class="{{ $labelClass }}">{{ $label }}</label>
    <input type="{{ $type }}"
           @if ($name) name="{{ $name }}" @endif
           @if ($fieldId) id="{{ $fieldId }}" @endif
           value="{{ $name ? old($name, $value) : $value }}"
           @if ($placeholder) placeholder="{{ $placeholder }}" @endif
           @required($required)
           @readonly($readonly)
           {{ $attributes->class([$inputClass, 'is-invalid' => $invalid]) }}>
    @if ($help)
        <div class="form-text">{{ $help }}</div>
    @endif
</div>
