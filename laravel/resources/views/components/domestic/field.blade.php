{{-- Form column: label + control. $for must be the id of the control; $nepali is an optional Devanagari label part. --}}
@props(['label', 'for' => null, 'nepali' => null, 'required' => false, 'full' => false, 'cols' => 6])
<div {{ $attributes->class([$full ? 'col-12' : 'col-md-'.$cols]) }}>
    <label class="form-label" @if ($for) for="{{ $for }}" @endif>
        {{ $label }}@if ($nepali) <span class="nepali" lang="ne">({{ $nepali }})</span>@endif
        @if ($required)<span class="text-danger" aria-hidden="true">*</span>@endif
    </label>
    {{ $slot }}
</div>
