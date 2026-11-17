{{-- $options: value => label. $placeholder is the empty first option, data-placeholder the select2 one. --}}
@props(['name', 'options', 'placeholder', 'selected' => null, 'id' => null, 'required' => false, 'select2Placeholder' => 'Select...'])
<select name="{{ $name }}" id="{{ $id ?? $name }}" class="form-select select2-single" data-placeholder="{{ $select2Placeholder }}" @required($required)>
    <option value="">{{ $placeholder }}</option>
    @foreach ($options as $value => $label)
        <option value="{{ $value }}" @selected((string) $selected === (string) $value)>{{ $label }}</option>
    @endforeach
</select>
