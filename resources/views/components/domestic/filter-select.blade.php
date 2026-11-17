@props(['label', 'name', 'id', 'options', 'all', 'selected' => '', 'nepali' => null])
<div class="col-sm-6 col-lg-3">
    <label class="form-label" for="{{ $id }}">
        {{ $label }}@if ($nepali) <span class="nepali" lang="ne">/ {{ $nepali }}</span>@endif
    </label>
    <select name="{{ $name }}" id="{{ $id }}" class="form-select select2-filter">
        <option value="">{{ $all }}</option>
        @foreach ($options as $value => $text)
            <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $text }}</option>
        @endforeach
    </select>
</div>
