@props(['colspan', 'message'])

<tr><td colspan="{{ $colspan }}" {{ $attributes->class(['empty-state']) }}>{{ $message }}</td></tr>
