{{-- Flash messages: success / warning / error, plus validation errors. --}}
<div class="flash-messages">
    @foreach (['success' => 'success', 'warning' => 'warning', 'error' => 'danger'] as $key => $type)
        @if (session($key))
            <div class="alert alert-{{ $type }}" role="alert">{!! nl2br(e(session($key))) !!}</div>
        @endif
    @endforeach

    @if ($errors->any())
        <div class="alert alert-danger" role="alert">
            <ul class="mb-0 ps-3">
                @foreach ($errors->all() as $message)
                    <li>{{ $message }}</li>
                @endforeach
            </ul>
        </div>
    @endif
</div>
