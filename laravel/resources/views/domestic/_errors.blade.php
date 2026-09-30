@if ($errors->any())
    <div style="background:#fee;color:#c00;padding:12px 16px;border-radius:6px;margin-bottom:20px;">
        <ul style="margin:0;padding-left:18px;">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
