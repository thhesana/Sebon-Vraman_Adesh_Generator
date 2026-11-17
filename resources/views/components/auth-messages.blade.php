{{-- Success flash and validation errors for the standalone auth pages (theme alerts). --}}
@props(['tag' => 'div', 'success' => false])

@if ($success && session('success'))
    <div class="alert alert-success" role="status">{{ session('success') }}</div>
@endif
@if ($errors->any())
    <div class="alert alert-danger" role="alert">
        @foreach ($errors->all() as $message)
            <{{ $tag === 'p' ? 'div' : $tag }} class="{{ $loop->last ? '' : 'mb-1' }}">{{ $message }}</{{ $tag === 'p' ? 'div' : $tag }}>
        @endforeach
    </div>
@endif
