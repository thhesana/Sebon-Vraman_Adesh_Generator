{{-- Search box for list pages. `grouped` is kept for compatibility; both render the same input group. --}}
@props(['action', 'search' => '', 'placeholder' => 'Search...', 'grouped' => false])

<form method="GET" action="{{ $action }}" class="toolbar" role="search">
    <div class="input-group search-group">
        <input type="text" name="search" class="form-control" placeholder="{{ $placeholder }}" value="{{ $search }}" aria-label="{{ $placeholder }}">
        <button class="btn btn-primary" type="submit">Search</button>
        @if ($search !== '')
            <a href="{{ $action }}" class="btn btn-secondary">Clear</a>
        @endif
    </div>
    @if (trim((string) $slot) !== '')
        <div class="page-actions">{{ $slot }}</div>
    @endif
</form>
