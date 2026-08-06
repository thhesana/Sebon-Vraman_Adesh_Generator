<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection FIRST
require_once 'db.php';

// Then include header
include 'HEADER.php';

// Handle AJAX requests for cities BEFORE any HTML output
if (isset($_GET['action']) && $_GET['action'] == 'getCities' && isset($_GET['country_id'])) {
    if (ob_get_length()) ob_clean();
    $country_id = intval($_GET['country_id']);
    
    $citySql = "SELECT City_id, City_name FROM CityMaster WHERE Country_id = ? ORDER BY City_name";
    $params = array($country_id);
    $cityStmt = sqlsrv_query($conn, $citySql, $params);
    
    $cities = [];
    if ($cityStmt !== false) {
        while ($city = sqlsrv_fetch_array($cityStmt, SQLSRV_FETCH_ASSOC)) {
            $cities[] = array(
                'City_id' => $city['City_id'],
                'City_name' => $city['City_name']
            );
        }
        sqlsrv_free_stmt($cityStmt);
    } else {
        $errors = sqlsrv_errors();
        error_log("SQL Error: " . print_r($errors, true));
    }
    
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cities);
    exit();
}

// Verify connection is still valid
if ($conn === false) {
    die("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
        <h3>❌ Database Connection Lost!</h3>
        <p>Please refresh the page or contact administrator.</p>
        </div>");
}

// Function to get current fiscal year ID
if (!function_exists('getCurrentFiscalYearId')) {
    function getCurrentFiscalYearId($conn) {
        $sql = "SELECT fiscal_year_master_id 
                FROM fiscal_year_master 
                WHERE CAST(GETDATE() AS DATE) BETWEEN CAST(fy_startdate AS DATE) AND CAST(fy_enddate AS DATE)
                AND fy_status = 'ACTIVE'";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            error_log("Fiscal Year Query Error: " . print_r(sqlsrv_errors(), true));
            return null;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return $row['fiscal_year_master_id'] ?? null;
    }
}

// Fetch Countries
$countrySql = "SELECT Country_id, Country_name FROM CountryMaster ORDER BY Country_name";
$countryStmt = sqlsrv_query($conn, $countrySql);
if ($countryStmt === false) {
    die("Error fetching countries: " . print_r(sqlsrv_errors(), true));
}

// Fetch Employees with TADA Level
$empSql = "
SELECT 
    e.EmpPersonalCode,
    e.EmpName,
    e.LevelName,
    t.TadaDefinerMasterBylevel_id,
    t.tadaInUSD
FROM Employee_Information e
LEFT JOIN TadaDefinerMasterBylevel t ON e.LevelName = t.TadaDefinerMasterBylevel_name
ORDER BY e.EmpName
";
$empStmt = sqlsrv_query($conn, $empSql);
if ($empStmt === false) {
    die("Error fetching employees: " . print_r(sqlsrv_errors(), true));
}

$employees = [];
while ($emp = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) {
    $employees[] = $emp;
}

// ============= NEW: Fetch TADA Verifiers =============
$tadaVerifierSql  = "SELECT tadaverifier_id, tadaverifierPost FROM tadaverifier ORDER BY tadaverifierPost";
$tadaVerifierStmt = sqlsrv_query($conn, $tadaVerifierSql);
if ($tadaVerifierStmt === false) die("Error fetching TADA verifiers: " . print_r(sqlsrv_errors(), true));
// ============= END =============

// Function to generate next Batch ID
if (!function_exists('getNextBatchId')) {
    function getNextBatchId($conn) {
        $sql = "SELECT dbo.fn_GenerateBatchId() AS Batch_id";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) {
            $sql2 = "SELECT MAX(Batch_id) AS MaxBatch FROM International_tada";
            $stmt2 = sqlsrv_query($conn, $sql2);
            if ($stmt2 === false) return 'BATCH001';
            $row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
            $maxBatch = $row['MaxBatch'];
            if (empty($maxBatch)) return 'BATCH001';
            $numericPart = intval(substr($maxBatch, 5));
            return 'BATCH' . str_pad($numericPart + 1, 3, '0', STR_PAD_LEFT);
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return $row['Batch_id'];
    }
}

// Function to get next Chalani number per fiscal year
if (!function_exists('getNextChalaniNumber')) {
    function getNextChalaniNumber($conn, $fiscal_year_id) {
        if (empty($fiscal_year_id)) return 1;
        $sql = "SELECT ISNULL(MAX(Chalani_id), 0) + 1 AS NextChalani 
                FROM International_tada
                WHERE fiscal_year_master_id = ?";
        $params = array($fiscal_year_id);
        $stmt = sqlsrv_query($conn, $sql, $params);
        if ($stmt === false) {
            error_log("Error fetching next Chalani: " . print_r(sqlsrv_errors(), true));
            return 1;
        }
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        return intval($row['NextChalani']);
    }
}

// Run Python script in background
function runPythonScriptInBackground($batchId) {
    $pythonExe = 'C:\\Users\\Lenovo\\AppData\\Local\\Programs\\Python\\Python313\\python.exe';
    $script = 'C:\\xampp\\htdocs\\Vraman_Adesh_Generator\\international_mail_notifier.py';
    $cmd = "start /B \"\" \"$pythonExe\" \"$script\" $batchId";
    pclose(popen($cmd, 'r'));
    error_log("Python script executed for International Batch: $batchId");
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_date        = $_POST['form_date'];
    $country_id       = intval($_POST['country_id']);
    $city_id          = intval($_POST['city_id']);
    $travel_objective = trim($_POST['travel_objective']);
    $travelDateStart  = $_POST['travelDateStart'];
    $travelDateEnd    = $_POST['travelDateEnd'];
    $created_by       = $_SESSION['user_id'] ?? 1;
    // ============= NEW: Capture tadaverifier_id from POST =============
    $tadaverifier_id  = intval($_POST['tadaverifier_id']);
    // ============= END =============

    // Get current fiscal year
    $fiscal_year_id = getCurrentFiscalYearId($conn);
    if ($fiscal_year_id === null) {
        echo "<script>alert('⚠️ Error: No active fiscal year found!');</script>";
        exit;
    }

    $employee_data = $_POST['employee_data'] ?? '';
    if (empty($employee_data)) {
        echo "<script>alert('⚠️ Please add at least one employee!');</script>";
        exit;
    }

    // ============= NEW: Validate tadaverifier_id =============
    if (empty($tadaverifier_id) || $tadaverifier_id <= 0) {
        echo "<script>alert('⚠️ Please select a TADA Verifier!');</script>";
        exit;
    }
    // ============= END =============

    $emp_entries = explode(',', $employee_data);

    $start = new DateTime($travelDateStart);
    $end   = new DateTime($travelDateEnd);
    $interval   = $start->diff($end);
    $total_days = $interval->days + 1 - 0.5;

    $batch_id             = getNextBatchId($conn);
    $form_date_formatted  = date('Y-m-d', strtotime($form_date));
    $start_date_formatted = date('Y-m-d', strtotime($travelDateStart));
    $end_date_formatted   = date('Y-m-d', strtotime($travelDateEnd));

    $success       = true;
    $insertedCount = 0;
    $errors        = [];
    $firstChalani  = null;
    $lastChalani   = null;

    foreach ($emp_entries as $entry) {
        if (empty(trim($entry))) continue;
        $parts = explode(':', $entry);
        if (count($parts) !== 2) {
            $errors[] = "Invalid employee data format: {$entry}";
            continue;
        }

        $emp_code        = trim($parts[0]);
        $dress_allowance = intval($parts[1]);

        $chalani_id = getNextChalaniNumber($conn, $fiscal_year_id);
        if ($firstChalani === null) $firstChalani = $chalani_id;
        $lastChalani = $chalani_id;

        $empDetailSql = "
            SELECT e.EmpPersonalCode, e.LevelName, t.TadaDefinerMasterBylevel_id, t.tadaInUSD
            FROM Employee_Information e
            LEFT JOIN TadaDefinerMasterBylevel t ON e.LevelName = t.TadaDefinerMasterBylevel_name
            WHERE e.EmpPersonalCode = ?
        ";
        $empDetailStmt = sqlsrv_query($conn, $empDetailSql, array($emp_code));
        if ($empDetailStmt === false) {
            $sqlErrors = sqlsrv_errors();
            $errors[] = "Employee {$emp_code}: Query failed - " . print_r($sqlErrors, true);
            continue;
        }

        $empDetail = sqlsrv_fetch_array($empDetailStmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($empDetailStmt);
        if (!$empDetail || empty($empDetail['TadaDefinerMasterBylevel_id'])) {
            $errors[] = "Employee {$emp_code}: TADA info missing";
            continue;
        }

        $tada_level_id = intval($empDetail['TadaDefinerMasterBylevel_id']);
        $usd_per_day   = floatval($empDetail['tadaInUSD']);
        $total_usd     = $total_days * $usd_per_day;

        // ============= UPDATED: INSERT now includes tadaverifier_id =============
        $insertSql = "INSERT INTO International_tada 
            (Batch_id, fiscal_year_master_id, Chalani_id, form_date, EmpPersonalCode, 
             Country_id, City_id, travel_objective, travelDateStart, travelDateEnd, 
             TadaDefinerMasterBylevel_id, totalUSdrecevid, DressAllowance,
             createdBy, createddate, tadaverifier_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?)";

        $params = array(
            $batch_id,
            $fiscal_year_id,
            $chalani_id,
            $form_date_formatted,
            $emp_code,
            $country_id,
            $city_id,
            $travel_objective,
            $start_date_formatted,
            $end_date_formatted,
            $tada_level_id,
            $total_usd,
            $dress_allowance,
            $created_by,
            $tadaverifier_id
        );
        // ============= END =============

        $insertStmt = sqlsrv_query($conn, $insertSql, $params);
        if ($insertStmt === false) {
            $sqlErrors = sqlsrv_errors();
            $errors[] = "Employee {$emp_code}: Insert failed - " . print_r($sqlErrors, true);
            $success = false;
            continue;
        }
        sqlsrv_free_stmt($insertStmt);
        $insertedCount++;
    }

    if ($insertedCount > 0) {
        runPythonScriptInBackground($batch_id);
        $chalaniRange = ($firstChalani == $lastChalani) ? "Chalani #: {$firstChalani}" : "Chalani #: {$firstChalani} - {$lastChalani}";
        $message = "✅ Batch {$batch_id} created successfully!\n{$chalaniRange}\nEmployees Added: {$insertedCount}\nFiscal Year ID: {$fiscal_year_id}";
        if (!empty($errors)) $message .= "\n\nWarnings:\n" . implode("\n", $errors);
        echo "<script>alert('{$message}'); window.location.href='InternationalVraman.php';</script>";
    } else {
        $errorMsg = "❌ Error: Could not insert any records.";
        if (!empty($errors)) $errorMsg .= "\n\n" . implode("\n", $errors);
        echo "<script>alert('{$errorMsg}');</script>";
    }
}

// Get next Chalani and Batch (for UI/display purposes)
$currentFiscalYear = getCurrentFiscalYearId($conn);
$nextChalani       = getNextChalaniNumber($conn, $currentFiscalYear);
$nextBatch         = getNextBatchId($conn);
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add International TADA Batch</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
            min-height: 100vh;
        }
        .form-container {
            max-width: 1100px;
            margin: 0 auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        h2 {
            color: #1E3A8A;
            margin-bottom: 25px;
            text-align: center;
            font-size: 28px;
        }
        .info-badges {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 25px;
        }
        .info-badge {
            background: linear-gradient(135deg, #E0E7FF 0%, #C7D2FE 100%);
            color: #3730A3;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            border: 2px solid #A5B4FC;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        label {
            display: block;
            margin-bottom: 8px;
            color: #1F2937;
            font-weight: 600;
            font-size: 14px;
        }
        label .required { color: #EF4444; }
        input[type="text"],
        input[type="date"],
        input[type="number"],
        select,
        textarea {
            width: 100%;
            padding: 10px 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s;
            box-sizing: border-box;
        }
        input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }
        textarea { resize: vertical; min-height: 90px; font-family: inherit; }
        .select2-container--default .select2-selection--single {
            border: 2px solid #e0e0e0; border-radius: 6px; height: 44px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px; padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow { height: 40px; }
        .select2-container--default.select2-container--focus .select2-selection--single { border-color: #667eea; }
        .select2-container { width: 100% !important; }

        .employee-section {
            background: #F9FAFB; padding: 20px; border-radius: 8px;
            margin: 20px 0; border: 2px dashed #D1D5DB;
        }
        .employee-selector { display: flex; gap: 10px; margin-bottom: 15px; align-items: stretch; }
        .employee-selector select { flex: 1; }
        .btn-add-employee {
            background: #10B981; color: white; padding: 10px 24px;
            border: none; border-radius: 6px; cursor: pointer;
            font-weight: 600; white-space: nowrap; transition: background 0.3s, transform 0.1s;
        }
        .btn-add-employee:hover { background: #059669; transform: translateY(-1px); }
        .btn-add-employee:active { transform: translateY(0); }

        .employee-table-container {
            overflow-x: auto; margin-top: 15px; border-radius: 8px; border: 1px solid #E5E7EB;
        }
        .employee-table { width: 100%; border-collapse: collapse; background: white; }
        .employee-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white;
        }
        .employee-table th {
            padding: 14px 12px; text-align: left; font-weight: 600;
            font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px;
        }
        .employee-table td { padding: 14px 12px; border-bottom: 1px solid #E5E7EB; font-size: 14px; }
        .employee-table tbody tr:hover { background: #F3F4F6; }
        .employee-table tbody tr:last-child td { border-bottom: none; }
        .btn-remove {
            background: #EF4444; color: white; border: none;
            padding: 6px 14px; border-radius: 5px; cursor: pointer;
            font-size: 12px; font-weight: 600; transition: background 0.3s;
        }
        .btn-remove:hover { background: #DC2626; }
        .empty-state { text-align: center; padding: 40px 20px; color: #6B7280; }
        .empty-state-icon { font-size: 48px; margin-bottom: 10px; }

        .btn { padding: 12px 24px; border: none; border-radius: 6px; cursor: pointer; font-size: 15px; font-weight: 600; transition: all 0.3s; }
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; width: 100%; padding: 14px; font-size: 16px;
        }
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4); }
        .btn-primary:disabled { background: #9CA3AF; cursor: not-allowed; transform: none; opacity: 0.6; }
        .btn-secondary { background-color: #6B7280; color: white; }
        .btn-secondary:hover { background-color: #4B5563; }
        .loading { display: inline-block; margin-left: 10px; color: #667eea; animation: pulse 1.5s ease-in-out infinite; }
        @keyframes pulse { 0%, 100% { opacity: 1; } 50% { opacity: 0.5; } }
        .form-actions { margin-top: 30px; display: flex; gap: 12px; }
        .form-actions .btn-secondary { flex: 0 0 120px; }
        .form-actions .btn-primary { flex: 1; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>➕ Add International TADA Batch</h2>

    <div class="info-badges">
        <div class="info-badge">
            📋 Next Batch ID: <strong><?php echo htmlspecialchars($nextBatch); ?></strong>
        </div>
        <div class="info-badge">
            🔢 Next Chalani #: <strong><?php echo htmlspecialchars($nextChalani); ?></strong>
        </div>
    </div>

    <form method="POST" id="tadaForm">

        <!-- Row 1: Form Date -->
        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group"><!-- alignment spacer --></div>
        </div>

        <!-- Row 2: Country + City -->
        <div class="form-row">
            <div class="form-group">
                <label>Country <span class="required">*</span></label>
                <select name="country_id" id="countrySelect" class="select2-single" required>
                    <option value="">-- Select Country --</option>
                    <?php while ($country = sqlsrv_fetch_array($countryStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $country['Country_id']; ?>">
                            <?php echo htmlspecialchars($country['Country_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>City <span class="required">*</span>
                    <span class="loading" id="cityLoading" style="display:none;">⏳ Loading...</span>
                </label>
                <select name="city_id" id="citySelect" class="select2-single" required disabled>
                    <option value="">-- Select Country First --</option>
                </select>
            </div>
        </div>

        <!-- ============= NEW: Row 3 — TADA Verifier (full width) ============= -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>TADA Verifier <span class="required">*</span></label>
                <select name="tadaverifier_id" id="tadaVerifierSelect" class="select2-single" required>
                    <option value="">-- Select TADA Verifier --</option>
                    <?php while ($verifier = sqlsrv_fetch_array($tadaVerifierStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo intval($verifier['tadaverifier_id']); ?>">
                            <?php echo htmlspecialchars($verifier['tadaverifierPost']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
        <!-- ============= END: TADA Verifier ============= -->

        <!-- Travel Objective -->
        <div class="form-group full-width">
            <label>Travel Objective <span class="required">*</span></label>
            <textarea name="travel_objective" required placeholder="Enter the purpose of international travel..."></textarea>
        </div>

        <!-- Travel Dates -->
        <div class="form-row">
            <div class="form-group">
                <label>Travel Start Date <span class="required">*</span></label>
                <input type="date" name="travelDateStart" id="startDate" required>
            </div>
            <div class="form-group">
                <label>Travel End Date <span class="required">*</span></label>
                <input type="date" name="travelDateEnd" id="endDate" required>
            </div>
        </div>

        <!-- Employee Selection Section -->
        <div class="form-group full-width">
            <label>Add Employees <span class="required">*</span></label>
            <div class="employee-section">
                <div class="employee-selector">
                    <select id="employeeDropdown" class="select2-single">
                        <option value="">-- Search and Select Employee --</option>
                        <?php foreach ($employees as $emp) {
                            $levelDisplay = !empty($emp['LevelName']) ? $emp['LevelName'] : 'No TADA Level';
                            $usdDisplay   = !empty($emp['tadaInUSD']) ? $emp['tadaInUSD'] : 0;
                        ?>
                            <option value="<?php echo htmlspecialchars($emp['EmpPersonalCode']); ?>"
                                    data-name="<?php echo htmlspecialchars($emp['EmpName']); ?>"
                                    data-level="<?php echo htmlspecialchars($levelDisplay); ?>"
                                    data-usd="<?php echo $usdDisplay; ?>"
                                    data-tada-id="<?php echo $emp['TadaDefinerMasterBylevel_id'] ?? ''; ?>">
                                <?php echo htmlspecialchars($emp['EmpName']); ?> - <?php echo htmlspecialchars($levelDisplay); ?>
                            </option>
                        <?php } ?>
                    </select>
                    <button type="button" class="btn-add-employee" id="btnAddEmployee">
                        ➕ Add Employee
                    </button>
                </div>

                <div class="employee-table-container">
                    <table class="employee-table" id="employeeTable">
                        <thead>
                            <tr>
                                <th style="width: 40px;">#</th>
                                <th>Employee Name</th>
                                <th>Personal Code</th>
                                <th>TADA Level</th>
                                <th>USD/Day</th>
                                <th style="width: 150px; text-align: center;">Dress Allowance</th>
                                <th style="width: 100px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <tr class="empty-state">
                                <td colspan="7">
                                    <div class="empty-state-icon">👥</div>
                                    <div>No employees added yet. Select from dropdown above.</div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="form-actions">
            <button type="button" class="btn btn-secondary" onclick="window.location.href='InternationalVraman.php'">
                ← Cancel
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                💾 Create Batch (<?php echo htmlspecialchars($nextBatch); ?> - Chalani #<?php echo htmlspecialchars($nextChalani); ?>)
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    let addedEmployees = [];

    // ── Select2 init — includes tadaVerifierSelect ────────────────────────────
    $('#countrySelect').select2({ placeholder: "Select a country",          allowClear: true, width: '100%' });
    $('#citySelect').select2({ placeholder: "Select country first",          allowClear: true, width: '100%' });
    $('#tadaVerifierSelect').select2({ placeholder: "Select TADA Verifier", allowClear: true, width: '100%' });
    $('#employeeDropdown').select2({ placeholder: "Search and select employee by name...", allowClear: true, width: '100%' });

    // ── Country → City AJAX ───────────────────────────────────────────────────
    $('#countrySelect').on('change', function () {
        const countryId  = $(this).val();
        const citySelect = $('#citySelect');
        const cityLoading = $('#cityLoading');

        if (countryId) {
            cityLoading.show();
            citySelect.prop('disabled', true);
            citySelect.empty().append('<option value="">Loading cities...</option>');

            $.ajax({
                url: 'get_cities.php',
                type: 'GET',
                data: { country_id: countryId },
                dataType: 'json',
                cache: false,
                success: function (response) {
                    citySelect.empty();
                    citySelect.append('<option value="">-- Select City --</option>');

                    if (response.success && response.data && response.data.length > 0) {
                        $.each(response.data, function (index, city) {
                            citySelect.append($('<option></option>').val(city.City_id).text(city.City_name));
                        });
                        citySelect.prop('disabled', false);
                    } else {
                        citySelect.append('<option value="">No cities available</option>');
                    }

                    citySelect.select2('destroy').select2({ placeholder: "Select a city", allowClear: true, width: '100%' });
                    cityLoading.hide();
                },
                error: function (xhr, status, error) {
                    console.error('AJAX Error:', error);
                    alert('⚠️ Error loading cities. Please try again.');
                    citySelect.empty().append('<option value="">-- Error Loading Cities --</option>');
                    citySelect.prop('disabled', false);
                    cityLoading.hide();
                }
            });
        } else {
            citySelect.empty().append('<option value="">-- Select Country First --</option>');
            citySelect.prop('disabled', true);
            citySelect.select2('destroy').select2({ placeholder: "Select country first", allowClear: true, width: '100%' });
        }
    });

    // ── Add Employee ──────────────────────────────────────────────────────────
    $('#btnAddEmployee').on('click', function () {
        const selectedOption = $('#employeeDropdown option:selected');
        const empCode = selectedOption.val();

        if (!empCode) { alert('⚠️ Please select an employee first!'); return; }
        if (addedEmployees.some(emp => emp.code === empCode)) { alert('⚠️ This employee is already added!'); return; }

        addedEmployees.push({
            code:          empCode,
            name:          selectedOption.data('name'),
            level:         selectedOption.data('level'),
            usd:           selectedOption.data('usd'),
            tadaId:        selectedOption.data('tada-id'),
            dressAllowance: false
        });

        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    // ── Render Employee Table ─────────────────────────────────────────────────
    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();

        if (addedEmployees.length === 0) {
            tbody.html(`
                <tr class="empty-state">
                    <td colspan="7">
                        <div class="empty-state-icon">👥</div>
                        <div>No employees added yet. Select from dropdown above.</div>
                    </td>
                </tr>`);
        } else {
            addedEmployees.forEach((emp, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${emp.name}</strong></td>
                        <td>${emp.code}</td>
                        <td>${emp.level}</td>
                        <td>${parseFloat(emp.usd).toFixed(2)}</td>
                        <td style="text-align: center;">
                            <input type="checkbox" class="dress-allowance-check"
                                   data-index="${index}"
                                   ${emp.dressAllowance ? 'checked' : ''}
                                   style="cursor: pointer; width: 18px; height: 18px;">
                        </td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-remove" data-index="${index}">
                                🗑️ Remove
                            </button>
                        </td>
                    </tr>`);
            });
        }
        updateHiddenInput();
    }

    // ── Dress Allowance toggle ────────────────────────────────────────────────
    $(document).on('change', '.dress-allowance-check', function () {
        addedEmployees[$(this).data('index')].dressAllowance = $(this).is(':checked');
        updateHiddenInput();
    });

    // ── Remove Employee ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove', function () {
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice($(this).data('index'), 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });

    function updateHiddenInput() {
        $('#employeeData').val(addedEmployees.map(emp => `${emp.code}:${emp.dressAllowance ? 1 : 0}`).join(','));
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    // ── Date validation ───────────────────────────────────────────────────────
    $('#startDate, #endDate').on('change', function () {
        const s = $('#startDate').val(), e = $('#endDate').val();
        if (s && e && new Date(e) < new Date(s)) {
            alert('⚠️ End date cannot be before start date!');
            $('#endDate').val('');
        }
    });

    // ── Form submit guard ─────────────────────────────────────────────────────
    $('#tadaForm').on('submit', function (e) {
        if (addedEmployees.length === 0) {
            e.preventDefault();
            alert('⚠️ Please add at least one employee before submitting!');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html('⏳ Creating Batch...');
    });
});
</script>
</body>
</html>