<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USD to NPR Converter</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .converter { background-color: #fff; padding: 40px; max-width: 450px; width: 100%; border-radius: 15px; box-shadow: 0 10px 40px rgba(0,0,0,.2); }
        h2 { text-align: center; color: #333; margin-bottom: 25px; font-size: 28px; }
        .db-message { padding: 12px 15px; border-radius: 8px; margin-bottom: 20px; font-size: 13px; text-align: center; font-weight: 500; }
        .db-message.success { background-color: #e8f5e9; color: #2e7d32; border: 1px solid #4caf50; }
        .db-message.info { background-color: #e3f2fd; color: #1565c0; border: 1px solid #2196f3; }
        .db-message.error { background-color: #ffebee; color: #c62828; border: 1px solid #ef5350; }
        .rate-info { background-color: #f8f9fa; padding: 15px; border-radius: 8px; margin-bottom: 25px; font-size: 14px; color: #555; border-left: 4px solid #667eea; }
        .rate-info strong { color: #333; }
        label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        input[type=number] { width: 100%; padding: 12px 15px; margin-bottom: 20px; border-radius: 8px; border: 2px solid #e0e0e0; font-size: 16px; }
        input[type=number]:focus { outline: none; border-color: #667eea; }
        button { width: 100%; padding: 12px 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; border: none; border-radius: 8px; cursor: pointer; font-size: 16px; font-weight: 600; }
        .result { margin-top: 25px; padding: 20px; background-color: #e8f5e9; border-radius: 8px; text-align: center; font-size: 18px; color: #2e7d32; border: 2px solid #4caf50; }
        .result .amount { font-size: 24px; font-weight: bold; margin-top: 8px; }
        .error { background-color: #ffebee; color: #c62828; padding: 20px; border-radius: 8px; text-align: center; border: 2px solid #ef5350; }
        .debug-info { margin-top: 20px; padding: 15px; background-color: #fff3cd; border: 1px solid #ffc107; border-radius: 8px; font-size: 12px; color: #856404; }
    </style>
</head>
<body>
    <div class="converter">
        <h2>💱 USD to NPR Converter</h2>

        @if ($dbMessage)
            @php
                $cls = (str_contains($dbMessage, '✅') || str_contains($dbMessage, '🔄')) ? 'success' : (str_contains($dbMessage, '❌') ? 'error' : 'info');
            @endphp
            <div class="db-message {{ $cls }}">{{ $dbMessage }}</div>
        @endif

        @if ($rate)
            <div class="rate-info">
                <strong>Today's Exchange Rate</strong> ({{ $rate['date'] }})<br>
                1 USD = <strong>{{ number_format($rate['buy'], 2) }} NPR</strong> (Buy) |
                <strong>{{ number_format($rate['sell'], 2) }} NPR</strong> (Sell)
            </div>
            <form method="post" action="{{ url('/usdforexudater.php') }}">
                @csrf
                <label for="usd_amount">Enter Amount in USD:</label>
                <input type="number" id="usd_amount" step="0.01" name="usd_amount" placeholder="0.00" required value="{{ $usd }}">
                <button type="submit">Convert to NPR</button>
            </form>
            @if ($converted !== '')
                <div class="result">
                    <div>{{ number_format($usd, 2) }} USD =</div>
                    <div class="amount">{{ number_format($converted, 2) }} NPR</div>
                </div>
            @endif
        @else
            <div class="error">
                <strong>⚠️ Unable to fetch exchange rates</strong><br>
                The NRB API is currently unavailable. Please try again later.
            </div>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Date range attempted: {{ date('Y-m-d', strtotime('-7 days')) }} to {{ date('Y-m-d') }}<br>
                Last error: {{ $lastError ?? 'unknown (see storage/logs/laravel.log)' }}
            </div>
        @endif
    </div>
</body>
</html>
