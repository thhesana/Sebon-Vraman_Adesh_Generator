<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection FIRST
require_once 'db.php';

// Then include header
include 'HEADER.php';

$batch_id = $_GET['batch_id'] ?? null;

if (!$batch_id) {
    echo "<script>alert('No batch ID provided!'); window.location.href='InternationalVraman.php';</script>";
    exit;
}

// Handle AJAX requests for cities BEFORE any HTML output
if (isset($_GET['action']) && $_GET['action'] == 'getCities' && isset($_GET['country_id'])) {
    if (ob_get_length()) ob_clean();

    $country_id = intval($_GET['country_id']);
    $citySql = "SELECT City_id, City_name FROM CityMaster WHERE Country_id = ? ORDER BY City_name";
    $cityStmt = sqlsrv_query($conn, $citySql, array($country_id));

    $cities = [];
    if ($cityStmt !== false) {
        while ($city = sqlsrv_fetch_array($cityStmt, SQLSRV_FETCH_ASSOC)) {
            $cities[] = ['City_id' => $city['City_id'], 'City_name' => $city['City_name']];
        }
        sqlsrv_free_stmt($cityStmt);
    }

    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($cities);
    exit();
}

// ─── Helper: Get current fiscal year ID ───────────────────────────────────────
if (!function_exists('getCurrentFiscalYearId')) {
    function getCurrentFiscalYearId($conn) {
        $sql = "SELECT fiscal_year_master_id
                FROM fiscal_year_master
                WHERE CAST(GETDATE() AS DATE) BETWEEN CAST(fy_startdate AS DATE) AND CAST(fy_enddate AS DATE)
                AND fy_status = 'ACTIVE'";
        $stmt = sqlsrv_query($conn, $sql);
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return $row['fiscal_year_master_id'] ?? null;
    }
}

// ─── Helper: Get next Chalani per fiscal year ─────────────────────────────────
if (!function_exists('getNextChalaniNumber')) {
    function getNextChalaniNumber($conn, $fiscal_year_id) {
        if (empty($fiscal_year_id)) return 1;
        $sql = "SELECT ISNULL(MAX(Chalani_id), 0) + 1 AS NextChalani
                FROM International_tada
                WHERE fiscal_year_master_id = ?";
        $stmt = sqlsrv_query($conn, $sql, array($fiscal_year_id));
        if ($stmt === false) return 1;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return intval($row['NextChalani']);
    }
}

// ─── Fetch batch header (first record as reference) ───────────────────────────
$batchSql = "
SELECT TOP 1
    i.Batch_id, i.fiscal_year_master_id, i.Chalani_id,
    i.form_date, i.Country_id, i.City_id,
    i.travel_objective, i.travelDateStart, i.travelDateEnd,
    i.TadaDefinerMasterBylevel_id, i.DressAllowance
FROM International_tada i
WHERE i.Batch_id = ?
";
$batchStmt = sqlsrv_query($conn, $batchSql, array($batch_id));
$batchData = sqlsrv_fetch_array($batchStmt, SQLSRV_FETCH_ASSOC);

if (!$batchData) {
    echo "<script>alert('Batch not found!'); window.location.href='InternationalVraman.php';</script>";
    exit;
}

// ─── Fetch all employees in this batch ────────────────────────────────────────
$empSql = "
SELECT
    it.International_tada_id,
    it.EmpPersonalCode,
    it.totalUSdrecevid,
    it.totalday,
    it.DressAllowance,
    e.EmpName,
    t.TadaDefinerMasterBylevel_name,
    t.TadaDefinerMasterBylevel_id,
    t.tadaInUSD
FROM International_tada it
LEFT JOIN Employee_Information e   ON it.EmpPersonalCode = e.EmpPersonalCode
LEFT JOIN TadaDefinerMasterBylevel t ON it.TadaDefinerMasterBylevel_id = t.TadaDefinerMasterBylevel_id
WHERE it.Batch_id = ?
ORDER BY it.International_tada_id
";
$empStmt = sqlsrv_query($conn, $empSql, array($batch_id));
$batchEmployees = [];
while ($emp = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) {
    $batchEmployees[] = $emp;
}

// ─── Fetch Countries ──────────────────────────────────────────────────────────
$countrySql  = "SELECT Country_id, Country_name FROM CountryMaster ORDER BY Country_name";
$countryStmt = sqlsrv_query($conn, $countrySql);

// ─── Fetch All Cities for initial load ────────────────────────────────────────
$citySql  = "SELECT City_id, City_name, Country_id FROM CityMaster ORDER BY City_name";
$cityStmt = sqlsrv_query($conn, $citySql);

// ─── Fetch All Employees for dropdown ─────────────────────────────────────────
$allEmpSql = "
SELECT e.EmpPersonalCode, e.EmpName, e.LevelName,
       t.TadaDefinerMasterBylevel_id, t.tadaInUSD
FROM Employee_Information e
LEFT JOIN TadaDefinerMasterBylevel t ON e.LevelName = t.TadaDefinerMasterBylevel_name
ORDER BY e.EmpName
";
$allEmpStmt = sqlsrv_query($conn, $allEmpSql);
$allEmployees = [];
while ($emp = sqlsrv_fetch_array($allEmpStmt, SQLSRV_FETCH_ASSOC)) {
    $allEmployees[] = $emp;
}

// ─── Handle Form Submission ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_date       = $_POST['form_date'];
    $country_id      = intval($_POST['country_id']);
    $city_id         = intval($_POST['city_id']);
    $travel_objective = trim($_POST['travel_objective']);
    $travelDateStart = $_POST['travelDateStart'];
    $travelDateEnd   = $_POST['travelDateEnd'];
    $employee_data   = $_POST['employee_data'] ?? '';
    $created_by      = $_SESSION['user_id'] ?? 1;

    if (empty($employee_data)) {
        echo "<script>alert('⚠️ Please add at least one employee!');</script>";
    } else {
        $emp_entries = explode(',', $employee_data);

        // Calculate total days (same formula as add page)
        $start      = new DateTime($travelDateStart);
        $end        = new DateTime($travelDateEnd);
        $interval   = $start->diff($end);
        $total_days = $interval->days + 1 - 0.5;

        $form_date_formatted  = date('Y-m-d', strtotime($form_date));
        $start_date_formatted = date('Y-m-d', strtotime($travelDateStart));
        $end_date_formatted   = date('Y-m-d', strtotime($travelDateEnd));

        // ── Get fiscal year (required to scope Chalani numbers correctly) ──
        $fiscal_year_id = getCurrentFiscalYearId($conn);
        if ($fiscal_year_id === null) {
            // Fall back to the fiscal year stored in the original batch
            $fiscal_year_id = $batchData['fiscal_year_master_id'];
        }

        // ────────────────────────────────────────────────────────────────────
        // SAFE UPDATE STRATEGY:
        //  1. Begin a transaction.
        //  2. Pre-validate EVERY employee entry so we know all inserts will
        //     succeed BEFORE we touch the existing rows.
        //  3. Only after all validation passes, delete the old rows and insert
        //     the new ones inside the same transaction.
        //  4. Commit on full success, ROLLBACK on any failure — the original
        //     data is never lost.
        // ────────────────────────────────────────────────────────────────────

        // Step 1 – Pre-validate all entries and build the insert payload
        $insertPayloads = [];
        $preValidationErrors = [];

        foreach ($emp_entries as $entry) {
            $entry = trim($entry);
            if (empty($entry)) continue;

            $parts = explode(':', $entry);
            if (count($parts) !== 2) {
                $preValidationErrors[] = "Invalid format: {$entry}";
                continue;
            }

            $emp_code      = trim($parts[0]);
            $dress_allowance = intval($parts[1]);

            // Fetch TADA info for this employee
            $empDetailSql = "
                SELECT e.EmpPersonalCode, e.LevelName,
                       t.TadaDefinerMasterBylevel_id, t.tadaInUSD
                FROM Employee_Information e
                LEFT JOIN TadaDefinerMasterBylevel t
                       ON e.LevelName = t.TadaDefinerMasterBylevel_name
                WHERE e.EmpPersonalCode = ?
            ";
            $empDetailStmt = sqlsrv_query($conn, $empDetailSql, array($emp_code));

            if ($empDetailStmt === false) {
                $preValidationErrors[] = "Employee {$emp_code}: query failed";
                continue;
            }

            $empDetail = sqlsrv_fetch_array($empDetailStmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($empDetailStmt);

            if (!$empDetail || empty($empDetail['TadaDefinerMasterBylevel_id'])) {
                $preValidationErrors[] = "Employee {$emp_code}: no TADA level assigned — skipped";
                continue;
            }

            $tada_level_id = intval($empDetail['TadaDefinerMasterBylevel_id']);
            $usd_per_day   = floatval($empDetail['tadaInUSD']);
            $total_usd     = $total_days * $usd_per_day;

            $insertPayloads[] = [
                'emp_code'       => $emp_code,
                'dress_allowance'=> $dress_allowance,
                'tada_level_id'  => $tada_level_id,
                'total_usd'      => $total_usd,
            ];
        }

        // If ALL entries failed validation, abort with no DB changes
        if (empty($insertPayloads)) {
            $errorMsg = "❌ No valid employees to update.";
            if (!empty($preValidationErrors)) {
                $errorMsg .= "\\n\\n" . implode("\\n", $preValidationErrors);
            }
            echo "<script>alert('{$errorMsg}');</script>";
        } else {
            // Step 2 – Run the whole operation inside a transaction
            sqlsrv_begin_transaction($conn);
            $txSuccess      = true;
            $insertedCount  = 0;
            $insertErrors   = [];
            $firstChalani   = null;
            $lastChalani    = null;

            // Delete existing rows for this batch INSIDE the transaction
            $deleteSql  = "DELETE FROM International_tada WHERE Batch_id = ?";
            $deleteStmt = sqlsrv_query($conn, $deleteSql, array($batch_id));

            if ($deleteStmt === false) {
                sqlsrv_rollback($conn);
                $sqlErr = sqlsrv_errors();
                echo "<script>alert('❌ Error deleting existing records: " . addslashes(print_r($sqlErr, true)) . "');</script>";
            } else {
                sqlsrv_free_stmt($deleteStmt);

                // Insert each validated employee
                foreach ($insertPayloads as $payload) {
                    // Get next Chalani scoped to fiscal year
                    // NOTE: We re-query WITHIN the transaction so the
                    //       just-deleted rows are not counted.
                    $chalani_id = getNextChalaniNumber($conn, $fiscal_year_id);
                    if ($firstChalani === null) $firstChalani = $chalani_id;
                    $lastChalani = $chalani_id;

                    $insertSql = "
                        INSERT INTO International_tada
                            (Batch_id, fiscal_year_master_id, Chalani_id, form_date,
                             EmpPersonalCode, Country_id, City_id, travel_objective,
                             travelDateStart, travelDateEnd, TadaDefinerMasterBylevel_id,
                             totalUSdrecevid, DressAllowance, createdBy, createddate)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())
                    ";

                    $params = [
                        $batch_id,
                        $fiscal_year_id,
                        $chalani_id,
                        $form_date_formatted,
                        $payload['emp_code'],
                        $country_id,
                        $city_id,
                        $travel_objective,
                        $start_date_formatted,
                        $end_date_formatted,
                        $payload['tada_level_id'],
                        $payload['total_usd'],
                        $payload['dress_allowance'],
                        $created_by
                    ];

                    $insertStmt = sqlsrv_query($conn, $insertSql, $params);

                    if ($insertStmt === false) {
                        $txSuccess = false;
                        $insertErrors[] = "Employee {$payload['emp_code']}: insert failed - "
                                        . print_r(sqlsrv_errors(), true);
                        break; // Stop inserting; we will rollback
                    }

                    sqlsrv_free_stmt($insertStmt);
                    $insertedCount++;
                }

                if ($txSuccess && $insertedCount > 0) {
                    // ✅ Everything succeeded — commit
                    sqlsrv_commit($conn);

                    $chalaniRange = ($firstChalani == $lastChalani)
                        ? "Chalani #: {$firstChalani}"
                        : "Chalani #: {$firstChalani} - {$lastChalani}";

                    $message  = "✅ Batch {$batch_id} updated successfully!\\n";
                    $message .= "{$chalaniRange}\\n";
                    $message .= "Employees: {$insertedCount}";

                    // Surface any skipped-employee warnings
                    $allWarnings = array_merge($preValidationErrors, $insertErrors);
                    if (!empty($allWarnings)) {
                        $message .= "\\n\\nWarnings:\\n" . implode("\\n", $allWarnings);
                    }

                    echo "<script>alert('{$message}'); window.location.href='InternationalVraman.php';</script>";
                } else {
                    // ❌ A mid-insert failure — rollback, original data is safe
                    sqlsrv_rollback($conn);

                    $errorMsg  = "❌ Update failed — original records have been kept.\\n";
                    $errorMsg .= implode("\\n", array_merge($preValidationErrors, $insertErrors));
                    echo "<script>alert('{$errorMsg}');</script>";
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit International TADA Batch</title>
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
        .info-badge {
            background: linear-gradient(135deg, #FEF3C7 0%, #FDE68A 100%);
            color: #92400E;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            border: 2px solid #FCD34D;
            margin-bottom: 25px;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label {
            display: block;
            margin-bottom: 8px;
            color: #1F2937;
            font-weight: 600;
            font-size: 14px;
        }
        label .required { color: #EF4444; }
        input[type="text"], input[type="date"], input[type="number"], select, textarea {
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
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
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
        .btn-primary:hover:not(:disabled) { transform: translateY(-2px); box-shadow: 0 8px 20px rgba(102,126,234,0.4); }
        .btn-primary:disabled { background: #9CA3AF; cursor: not-allowed; opacity: 0.6; }
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
    <h2>✏️ Edit International TADA Batch</h2>

    <div class="info-badge">
        📦 Batch ID: <strong><?php echo htmlspecialchars($batch_id); ?></strong>
    </div>

    <form method="POST" id="tadaForm">
        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required
                       value="<?php echo $batchData['form_date']->format('Y-m-d'); ?>">
            </div>
            <div class="form-group"><!-- alignment spacer --></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Country <span class="required">*</span></label>
                <select name="country_id" id="countrySelect" class="select2-single" required>
                    <option value="">-- Select Country --</option>
                    <?php while ($country = sqlsrv_fetch_array($countryStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $country['Country_id']; ?>"
                                <?php echo ($country['Country_id'] == $batchData['Country_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($country['Country_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>City <span class="required">*</span>
                    <span class="loading" id="cityLoading" style="display:none;">⏳ Loading...</span>
                </label>
                <select name="city_id" id="citySelect" class="select2-single" required>
                    <option value="">-- Select City --</option>
                    <?php while ($city = sqlsrv_fetch_array($cityStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $city['City_id']; ?>"
                                data-country="<?php echo $city['Country_id']; ?>"
                                <?php echo ($city['City_id'] == $batchData['City_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($city['City_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <div class="form-group full-width">
            <label>Travel Objective <span class="required">*</span></label>
            <textarea name="travel_objective" required
                      placeholder="Enter the purpose of international travel..."><?php echo htmlspecialchars($batchData['travel_objective']); ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label>Travel Start Date <span class="required">*</span></label>
                <input type="date" name="travelDateStart" id="startDate" required
                       value="<?php echo $batchData['travelDateStart']->format('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Travel End Date <span class="required">*</span></label>
                <input type="date" name="travelDateEnd" id="endDate" required
                       value="<?php echo $batchData['travelDateEnd']->format('Y-m-d'); ?>">
            </div>
        </div>

        <!-- Employee Section -->
        <div class="form-group full-width">
            <label>Employees <span class="required">*</span></label>
            <div class="employee-section">
                <div class="employee-selector">
                    <select id="employeeDropdown" class="select2-single">
                        <option value="">-- Search and Select Employee --</option>
                        <?php foreach ($allEmployees as $emp) {
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
                                <th style="width:40px">#</th>
                                <th>Employee Name</th>
                                <th>Personal Code</th>
                                <th>TADA Level</th>
                                <th>USD/Day</th>
                                <th style="width:150px;text-align:center">Dress Allowance</th>
                                <th style="width:100px;text-align:center">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <!-- Populated by JS -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <input type="hidden" name="employee_data" id="employeeData" value="">

        <div class="form-actions">
            <button type="button" class="btn btn-secondary"
                    onclick="window.location.href='InternationalVraman.php'">← Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBtn">
                💾 Update Batch <?php echo htmlspecialchars($batch_id); ?>
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
$(document).ready(function () {
    let addedEmployees = [];

    // Pre-populate from existing batch records
    const existingEmployees = <?php echo json_encode($batchEmployees); ?>;
    existingEmployees.forEach(emp => {
        addedEmployees.push({
            code:          emp.EmpPersonalCode,
            name:          emp.EmpName,
            level:         emp.TadaDefinerMasterBylevel_name || 'No TADA Level',
            usd:           emp.tadaInUSD || 0,
            tadaId:        emp.TadaDefinerMasterBylevel_id,
            dressAllowance: emp.DressAllowance == 1
        });
    });

    // ── Select2 init ──────────────────────────────────────────────────────────
    $('#countrySelect').select2({ placeholder: "Select a country", allowClear: true, width: '100%' });
    $('#citySelect').select2({ placeholder: "Select a city", allowClear: true, width: '100%' });
    $('#employeeDropdown').select2({ placeholder: "Search and select employee by name...", allowClear: true, width: '100%' });

    // ── Country → load cities via AJAX ────────────────────────────────────────
    $('#countrySelect').on('change', function () {
        const countryId = $(this).val();
        const citySelect = $('#citySelect');
        const cityLoading = $('#cityLoading');

        if (countryId) {
            cityLoading.show();
            citySelect.prop('disabled', true).empty().append('<option value="">Loading cities...</option>');

            $.ajax({
                url: window.location.pathname + '?batch_id=<?php echo urlencode($batch_id); ?>',
                type: 'GET',
                data: { action: 'getCities', country_id: countryId },
                dataType: 'json',
                success: function (cities) {
                    citySelect.empty().append('<option value="">-- Select City --</option>');
                    if (cities && cities.length > 0) {
                        $.each(cities, function (i, city) {
                            citySelect.append($('<option>').val(city.City_id).text(city.City_name));
                        });
                        citySelect.prop('disabled', false);
                    } else {
                        citySelect.append('<option value="">No cities available</option>');
                    }
                    citySelect.select2('destroy').select2({ placeholder: "Select a city", allowClear: true, width: '100%' });
                    cityLoading.hide();
                },
                error: function () {
                    alert('⚠️ Error loading cities. Please try again.');
                    citySelect.empty().append('<option value="">-- Error Loading Cities --</option>').prop('disabled', false);
                    cityLoading.hide();
                }
            });
        } else {
            citySelect.empty().append('<option value="">-- Select Country First --</option>').prop('disabled', true);
            citySelect.select2('destroy').select2({ placeholder: "Select country first", allowClear: true, width: '100%' });
        }
    });

    // ── Render table ─────────────────────────────────────────────────────────
    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();
        if (addedEmployees.length === 0) {
            tbody.html('<tr class="empty-state"><td colspan="7"><div class="empty-state-icon">👥</div><div>No employees added yet.</div></td></tr>');
        } else {
            addedEmployees.forEach((emp, index) => {
                tbody.append(`
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${emp.name}</strong></td>
                        <td>${emp.code}</td>
                        <td>${emp.level}</td>
                        <td>${parseFloat(emp.usd).toFixed(2)}</td>
                        <td style="text-align:center">
                            <input type="checkbox" class="dress-allowance-check"
                                   data-index="${index}" ${emp.dressAllowance ? 'checked' : ''}
                                   style="cursor:pointer;width:18px;height:18px">
                        </td>
                        <td style="text-align:center">
                            <button type="button" class="btn-remove" data-index="${index}">🗑️ Remove</button>
                        </td>
                    </tr>`);
            });
        }
        updateHiddenInput();
    }

    function updateHiddenInput() {
        $('#employeeData').val(addedEmployees.map(e => `${e.code}:${e.dressAllowance ? 1 : 0}`).join(','));
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    // ── Add employee ──────────────────────────────────────────────────────────
    $('#btnAddEmployee').on('click', function () {
        const opt = $('#employeeDropdown option:selected');
        const empCode = opt.val();
        if (!empCode) { alert('⚠️ Please select an employee first!'); return; }
        if (addedEmployees.some(e => e.code === empCode)) { alert('⚠️ This employee is already added!'); return; }
        addedEmployees.push({
            code: empCode, name: opt.data('name'), level: opt.data('level'),
            usd: opt.data('usd'), tadaId: opt.data('tada-id'), dressAllowance: false
        });
        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    // ── Dress allowance toggle ────────────────────────────────────────────────
    $(document).on('change', '.dress-allowance-check', function () {
        addedEmployees[$(this).data('index')].dressAllowance = $(this).is(':checked');
        updateHiddenInput();
    });

    // ── Remove employee ───────────────────────────────────────────────────────
    $(document).on('click', '.btn-remove', function () {
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice($(this).data('index'), 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });

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
        $('#submitBtn').prop('disabled', true).html('⏳ Updating Batch...');
    });

    // Initial render
    renderEmployeeTable();
    updateSubmitButton();
});
</script>
</body>
</html>