{{-- Page title block. Usage: <x-page-header title="Country List" subtitle="..."> <a class="btn btn-primary">Add</a> </x-page-header> --}}
@props(['title', 'subtitle' => null])

<div {{ $attributes->class(['page-header']) }}>
    <div>
        <h1>{{ $title }}</h1>
        @if ($subtitle)
            <p class="page-subtitle">{{ $subtitle }}</p>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="page-actions">{{ $slot }}</div>
    @endif
</div>
