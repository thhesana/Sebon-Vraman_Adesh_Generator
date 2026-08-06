<?php
// Database Configuration
$serverName = "DESKTOP-IGPDORH"; // e.g., "localhost" or "SERVER\SQLEXPRESS"
$database = "Vraman_Adesh_Generator";
$username = "sa";
$password = "Lazy-Car92";

// Connection info array
$connectionInfo = array(
    "Database" => $database,
    "UID" => $username,
    "PWD" => $password,
    "CharacterSet" => "UTF-8"
);

// Function to fetch USD -> NPR rate from NRB API
function getUsdToNprRate() {
    // Get today's date as string
    $today = date('Y-m-d');
    
    // Get date from 7 days ago to ensure we get data
    $fromDate = date('Y-m-d', strtotime('-7 days'));
    
    $url = "https://www.nrb.org.np/api/forex/v1/rates?page=1&per_page=50&from={$fromDate}&to={$today}";
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Accept: application/json',
        'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    if ($response === false) {
        error_log("CURL Error: " . $curlError);
        return null;
    }
    
    if ($httpCode != 200) {
        error_log("HTTP Error: " . $httpCode);
        return null;
    }
    
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON Decode Error: " . json_last_error_msg());
        return null;
    }
    
    if (!isset($data['data']['payload']) || empty($data['data']['payload'])) {
        error_log("No payload data found");
        return null;
    }
    
    // Get the most recent date's rates (last item in array)
    $latestRates = end($data['data']['payload']);
    
    if (isset($latestRates['rates']) && is_array($latestRates['rates'])) {
        foreach ($latestRates['rates'] as $rate) {
            if (isset($rate['currency']['iso3']) && $rate['currency']['iso3'] === 'USD') {
                return [
                    'unit' => isset($rate['currency']['unit']) ? $rate['currency']['unit'] : 1,
                    'buy'  => floatval($rate['buy']),
                    'sell' => floatval($rate['sell']),
                    'date' => isset($latestRates['date']) ? $latestRates['date'] : $today
                ];
            }
        }
    }
    
    error_log("USD rate not found in API response");
    return null;
}

// Function to save rate to database using stored procedure
function saveRateToDatabase($serverName, $connectionInfo, $date, $buyRate) {
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    
    if ($conn === false) {
        $errors = sqlsrv_errors();
        error_log("Database connection failed: " . print_r($errors, true));
        return [
            'success' => false,
            'message' => 'Database connection failed: ' . print_r($errors, true)
        ];
    }
    
    // Ensure date is a string
    $dateString = is_string($date) ? $date : date('Y-m-d', strtotime($date));
    
    // Prepare the stored procedure call
    $sql = "{CALL sp_UpsertUSDForex(?, ?)}";
    $params = array(
        array($dateString, SQLSRV_PARAM_IN),
        array($buyRate, SQLSRV_PARAM_IN)
    );
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        error_log("Query execution failed: " . print_r($errors, true));
        sqlsrv_close($conn);
        return [
            'success' => false,
            'message' => 'Query execution failed: ' . print_r($errors, true)
        ];
    }
    
    // Fetch the result to see what action was taken
    $result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
    
    if ($result === false) {
        return [
            'success' => true,
            'action' => 'COMPLETED',
            'date' => $dateString,
            'amount' => $buyRate,
            'oldAmount' => null
        ];
    }
    
    return [
        'success' => true,
        'action' => isset($result['Action']) ? $result['Action'] : 'COMPLETED',
        'date' => isset($result['Date']) ? $result['Date'] : $dateString,
        'amount' => isset($result['Amount']) ? $result['Amount'] : $buyRate,
        'oldAmount' => isset($result['OldAmount']) ? $result['OldAmount'] : null
    ];
}

// Fetch current rate
$rate = getUsdToNprRate();
$dbMessage = '';

// Auto-save rate to database when page loads
if ($rate) {
    $dbResult = saveRateToDatabase($serverName, $connectionInfo, $rate['date'], $rate['sell']);
    
    if ($dbResult['success']) {
        $action = isset($dbResult['action']) ? $dbResult['action'] : 'COMPLETED';
        switch ($action) {
            case 'INSERTED':
                $dbMessage = "✅ New rate inserted into database for " . htmlspecialchars($dbResult['date']);
                break;
            case 'UPDATED':
                $oldAmount = isset($dbResult['oldAmount']) ? number_format($dbResult['oldAmount'], 4) : 'N/A';
                $newAmount = number_format($dbResult['amount'], 4);
                $dbMessage = "🔄 Rate updated in database (Old: " . $oldAmount . " → New: " . $newAmount . ")";
                break;
            case 'NO_CHANGE':
                $dbMessage = "ℹ️ Rate already up-to-date in database";
                break;
            default:
                $dbMessage = "✅ Rate saved to database successfully";
        }
    } else {
        $dbMessage = "❌ Database Error: " . htmlspecialchars($dbResult['message']);
    }
}

// Handle conversion
$converted = '';
$usd = '';
if (isset($_POST['usd_amount']) && is_numeric($_POST['usd_amount']) && $rate) {
    $usd = floatval($_POST['usd_amount']);
    $converted = $usd * $rate['sell'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>USD to NPR Converter</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .converter {
            background-color: #fff;
            padding: 40px;
            max-width: 450px;
            width: 100%;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 25px;
            font-size: 28px;
        }
        .db-message {
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 13px;
            text-align: center;
            font-weight: 500;
        }
        .db-message.success {
            background-color: #e8f5e9;
            color: #2e7d32;
            border: 1px solid #4caf50;
        }
        .db-message.info {
            background-color: #e3f2fd;
            color: #1565c0;
            border: 1px solid #2196f3;
        }
        .db-message.error {
            background-color: #ffebee;
            color: #c62828;
            border: 1px solid #ef5350;
        }
        .rate-info {
            background-color: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #555;
            border-left: 4px solid #667eea;
        }
        .rate-info strong {
            color: #333;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        input[type=number] {
            width: 100%;
            padding: 12px 15px;
            margin-bottom: 20px;
            border-radius: 8px;
            border: 2px solid #e0e0e0;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        input[type=number]:focus {
            outline: none;
            border-color: #667eea;
        }
        button {
            width: 100%;
            padding: 12px 20px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            font-weight: 600;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(102, 126, 234, 0.4);
        }
        button:active {
            transform: translateY(0);
        }
        .result {
            margin-top: 25px;
            padding: 20px;
            background-color: #e8f5e9;
            border-radius: 8px;
            text-align: center;
            font-size: 18px;
            color: #2e7d32;
            border: 2px solid #4caf50;
        }
        .result .amount {
            font-size: 24px;
            font-weight: bold;
            margin-top: 8px;
        }
        .error {
            background-color: #ffebee;
            color: #c62828;
            padding: 20px;
            border-radius: 8px;
            text-align: center;
            border: 2px solid #ef5350;
        }
        .debug-info {
            margin-top: 20px;
            padding: 15px;
            background-color: #fff3cd;
            border: 1px solid #ffc107;
            border-radius: 8px;
            font-size: 12px;
            color: #856404;
        }
    </style>
</head>
<body>
    <div class="converter">
        <h2>💱 USD to NPR Converter</h2>
        
        <?php if ($dbMessage): ?>
            <div class="db-message <?= strpos($dbMessage, '✅') !== false || strpos($dbMessage, '🔄') !== false ? 'success' : (strpos($dbMessage, '❌') !== false ? 'error' : 'info') ?>">
                <?= $dbMessage ?>
            </div>
        <?php endif; ?>
        
        <?php if ($rate): ?>
            <div class="rate-info">
                <strong>Today's Exchange Rate</strong> (<?= htmlspecialchars($rate['date']) ?>)<br>
                1 USD = <strong><?= number_format($rate['buy'], 2) ?> NPR</strong> (Buy) | 
                <strong><?= number_format($rate['sell'], 2) ?> NPR</strong> (Sell)
            </div>
            <form method="post">
                <label for="usd_amount">Enter Amount in USD:</label>
                <input type="number" id="usd_amount" step="0.01" name="usd_amount" 
                       placeholder="0.00" required value="<?= htmlspecialchars($usd) ?>">
                <button type="submit">Convert to NPR</button>
            </form>
            <?php if ($converted !== ''): ?>
                <div class="result">
                    <div><?= number_format($usd, 2) ?> USD =</div>
                    <div class="amount"><?= number_format($converted, 2) ?> NPR</div>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="error">
                <strong>⚠️ Unable to fetch exchange rates</strong><br>
                The NRB API is currently unavailable. Please try again later.
            </div>
            <div class="debug-info">
                <strong>Debug Info:</strong><br>
                Date range attempted: <?= date('Y-m-d', strtotime('-7 days')) ?> to <?= date('Y-m-d') ?><br>
                Check your error_log for more details.
            </div>
        <?php endif; ?>
    </div>
</body>
</html>