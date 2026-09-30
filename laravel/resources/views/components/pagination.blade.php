{{-- Pagination links (Bootstrap 5 view, styled by theme.css). Renders nothing for a single page. --}}
@props(['paginator'])

@if ($paginator->hasPages())
    <div {{ $attributes->class(['d-flex', 'justify-content-center', 'mt-3']) }}>{{ $paginator->links() }}</div>
@endif
