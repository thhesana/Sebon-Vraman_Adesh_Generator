{{-- Label + select row. :options is [value => text]; the selection falls back to old() input. --}}
@props([
    'name',
    'label',
    'options',
    'selected' => null,
    'id' => null,
    'placeholder' => null,
    'labelClass' => 'form-label',
    'inputClass' => 'form-select',
])

@php
    $current = (string) old($name, $selected);
    $fieldId = $id ?? $name;
@endphp

<div class="mb-3">
    <label for="{{ $fieldId }}" class="{{ $labelClass }}">{{ $label }}</label>
    <select name="{{ $name }}" id="{{ $fieldId }}" required
            {{ $attributes->class([$inputClass, 'is-invalid' => $errors->has($name)]) }}>
        @if ($placeholder !== null)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach ($options as $optionValue => $text)
            <option value="{{ $optionValue }}" @selected($current !== '' && $current === (string) $optionValue)>{{ $text }}</option>
        @endforeach
    </select>
</div>
