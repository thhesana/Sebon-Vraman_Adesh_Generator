<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USD to NPR Converter</title>
    <link rel="icon" href="{{ asset('sebon_logo.png') }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <link href="{{ asset('css/usd/converter.css') }}" rel="stylesheet">
</head>
<body>
    <main class="converter-page">
        <div class="card converter-card">
            <div class="card-header">USD to NPR Converter</div>
            <div class="card-body">
                @if ($dbMessage)
                    @php
                        $cls = (str_contains($dbMessage, "\u{2705}") || str_contains($dbMessage, "\u{1F504}")) ? 'success' : (str_contains($dbMessage, "\u{274C}") ? 'danger' : 'info');
                    @endphp
                    <div class="alert alert-{{ $cls }}" role="status">{{ $dbMessage }}</div>
                @endif

                @if ($rate)
                    <div class="alert alert-info">
                        <strong>Today&#39;s Exchange Rate</strong> ({{ $rate['date'] }})<br>
                        1 USD = <strong>{{ number_format($rate['buy'], 2) }} NPR</strong> (Buy) |
                        <strong>{{ number_format($rate['sell'], 2) }} NPR</strong> (Sell)
                    </div>

                    <form method="post" action="{{ route('usd.converter') }}">
                        @csrf
                        <div class="mb-3">
                            <label for="usd_amount" class="form-label">Enter Amount in USD</label>
                            <input type="number" id="usd_amount" step="0.01" name="usd_amount" class="form-control" placeholder="0.00" required value="{{ $usd }}">
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Convert to NPR</button>
                    </form>

                    @if ($converted !== '')
                        <div class="converter-result" role="status">
                            <div class="text-muted">{{ number_format($usd, 2) }} USD =</div>
                            <div class="stat-value">{{ number_format($converted, 2) }} NPR</div>
                        </div>
                    @endif
                @else
                    <div class="alert alert-danger" role="alert">
                        <strong>Unable to fetch exchange rates</strong><br>
                        The NRB API is currently unavailable. Please try again later.
                    </div>
                    <div class="alert alert-warning">
                        <strong>Debug Info</strong><br>
                        Date range attempted: {{ date('Y-m-d', strtotime('-7 days')) }} to {{ date('Y-m-d') }}<br>
                        Last error: {{ $lastError ?? 'unknown (see storage/logs/laravel.log)' }}
                    </div>
                @endif
            </div>
        </div>
    </main>
</body>
</html>
