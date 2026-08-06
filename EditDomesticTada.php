<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
include 'HEADER.php';

$batch_id = $_GET['batch_id'] ?? null;

if (!$batch_id) {
    echo "<script>alert('No batch ID provided!'); window.location.href='DomesticTadaView.php';</script>";
    exit;
}

// ─── Helper: Get active fiscal year ID ────────────────────────────────────────
if (!function_exists('getDomesticFiscalYearId')) {
    function getDomesticFiscalYearId($conn) {
        $today = date('Y-m-d');
        $sql   = "SELECT fiscal_year_master_id
                  FROM fiscal_year_master
                  WHERE ? BETWEEN fy_startdate AND fy_enddate
                  AND fy_status = 'ACTIVE'";
        $stmt  = sqlsrv_query($conn, $sql, [$today]);
        if ($stmt === false) return null;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return $row['fiscal_year_master_id'] ?? null;
    }
}

// ─── Helper: Next Chalani scoped to fiscal year ───────────────────────────────
if (!function_exists('getDomesticNextChalani')) {
    function getDomesticNextChalani($conn, $fiscal_year_id) {
        if (empty($fiscal_year_id)) {
            $sql  = "SELECT ISNULL(MAX(domestic_Chalani_id), 0) + 1 AS NextChalani FROM DomesticTada";
            $stmt = sqlsrv_query($conn, $sql);
        } else {
            $sql  = "SELECT ISNULL(MAX(domestic_Chalani_id), 0) + 1 AS NextChalani
                     FROM DomesticTada
                     WHERE fiscal_year_master_id = ?";
            $stmt = sqlsrv_query($conn, $sql, [$fiscal_year_id]);
        }
        if ($stmt === false) return 1;
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        sqlsrv_free_stmt($stmt);
        return intval($row['NextChalani']);
    }
}

// ─── Fetch batch header (now includes tadaverifier_id) ────────────────────────
$batchSql  = "
SELECT TOP 1
    dt.domestic_Batch_id,
    dt.domestic_Chalani_id,
    dt.domestic_form_date,
    dt.District_id,
    dt.domestic_travel_objective,
    dt.domestic_travelDateStart,
    dt.domestic_travelDateEnd,
    dt.TadaTypeMaster_id,
    dt.fiscal_year_master_id,
    dt.tadaverifier_id
FROM DomesticTada dt
WHERE dt.domestic_Batch_id = ?
";
$batchStmt = sqlsrv_query($conn, $batchSql, [$batch_id]);
$batchData = sqlsrv_fetch_array($batchStmt, SQLSRV_FETCH_ASSOC);

if (!$batchData) {
    echo "<script>alert('Batch not found!'); window.location.href='DomesticTadaView.php';</script>";
    exit;
}

// ─── Fetch employees already in this batch ─────────────────────────────────────
$empSql  = "
SELECT
    dt.domestic_tada_id,
    dt.EmpPersonalCode,
    dt.domestic_tada,
    dt.domestic_totalday,
    dt.domestic_isTwentyPercentExtra,
    e.EmpName,
    e.LevelName,
    ISNULL(dtd.DomesticTadaDefinerMasterBylevel_name, e.LevelName) AS DomesticTadaDefinerMasterBylevel_name,
    COALESCE(NULLIF(dtd.tadaInNepali, 0), 2400) AS tadaInNepali
FROM DomesticTada dt
LEFT JOIN Employee_Information e   ON dt.EmpPersonalCode = e.EmpPersonalCode
LEFT JOIN DomesticTadaDefinerMasterBylevel dtd
       ON LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(dtd.DomesticTadaDefinerMasterBylevel_name))
WHERE dt.domestic_Batch_id = ?
ORDER BY dt.domestic_tada_id
";
$empStmt        = sqlsrv_query($conn, $empSql, [$batch_id]);
$batchEmployees = [];
while ($emp = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) {
    $batchEmployees[] = $emp;
}

// ─── Reference data for dropdowns ─────────────────────────────────────────────
$districtSql  = "SELECT District_id, District_name FROM DistrictMaster ORDER BY District_name";
$districtStmt = sqlsrv_query($conn, $districtSql);

$tadaTypeSql  = "SELECT TadaTypeMaster_id, type FROM TraveltypeMaster ORDER BY type";
$tadaTypeStmt = sqlsrv_query($conn, $tadaTypeSql);

// ============= NEW: Fetch TADA Verifiers =============
$tadaVerifierSql  = "SELECT tadaverifier_id, tadaverifierPost FROM tadaverifier ORDER BY tadaverifierPost";
$tadaVerifierStmt = sqlsrv_query($conn, $tadaVerifierSql);
if ($tadaVerifierStmt === false) die("Error fetching TADA verifiers: " . print_r(sqlsrv_errors(), true));
// ============= END =============

$allEmpSql  = "
SELECT
    e.EmpPersonalCode,
    e.EmpName,
    e.LevelName,
    COALESCE(NULLIF(dtd.tadaInNepali, 0), 2400) AS tadaInNepali
FROM Employee_Information e
LEFT JOIN DomesticTadaDefinerMasterBylevel dtd
       ON LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(dtd.DomesticTadaDefinerMasterBylevel_name))
ORDER BY e.EmpName
";
$allEmpStmt   = sqlsrv_query($conn, $allEmpSql);
$allEmployees = [];
while ($emp = sqlsrv_fetch_array($allEmpStmt, SQLSRV_FETCH_ASSOC)) {
    $allEmployees[] = $emp;
}

// ─── Handle form submission ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $form_date        = $_POST['form_date'];
    $district_id      = intval($_POST['district_id']);
    $travel_objective = trim($_POST['travel_objective']);
    $travelDateStart  = $_POST['travelDateStart'];
    $travelDateEnd    = $_POST['travelDateEnd'];
    $tada_type_id     = intval($_POST['tada_type_id']);
    // ============= NEW: Capture tadaverifier_id from POST =============
    $tadaverifier_id  = intval($_POST['tadaverifier_id']);
    // ============= END =============
    $employee_data    = $_POST['employee_data'] ?? '';
    $created_by       = $_SESSION['user_id'] ?? 1;

    if (empty($employee_data)) {
        echo "<script>alert('⚠️ Please add at least one employee!');</script>";
    // ============= NEW: Validate tadaverifier_id =============
    } elseif (empty($tadaverifier_id) || $tadaverifier_id <= 0) {
        echo "<script>alert('⚠️ Please select a TADA Verifier!');</script>";
    // ============= END =============
    } else {
        $emp_entries = explode(',', $employee_data);

        $startDT    = new DateTime($travelDateStart);
        $endDT      = new DateTime($travelDateEnd);
        $startDT->setTime(0, 0, 0);
        $endDT->setTime(0, 0, 0);
        $total_days = $startDT->diff($endDT)->days + 1;

        if ($total_days <= 0) {
            echo "<script>alert('❌ End date must be the same as or after start date.');</script>";
        } else {
            $form_date_formatted  = date('Y-m-d', strtotime($form_date));
            $start_date_formatted = date('Y-m-d', strtotime($travelDateStart));
            $end_date_formatted   = date('Y-m-d', strtotime($travelDateEnd));

            $fiscal_year_id = getDomesticFiscalYearId($conn);
            if ($fiscal_year_id === null) {
                $fiscal_year_id = $batchData['fiscal_year_master_id'] ?? null;
            }
            if ($fiscal_year_id === null) {
                echo "<script>alert('⚠️ No active fiscal year found. Cannot save.');</script>";
            } else {

                // ── Step 1: Pre-validate all submitted employees ─────────────
                $submitPayloads      = [];
                $preValidationErrors = [];

                foreach ($emp_entries as $entry) {
                    $entry = trim($entry);
                    if (empty($entry)) continue;

                    $parts = explode(':', $entry);
                    if (count($parts) !== 2) {
                        $preValidationErrors[] = "Invalid format: {$entry}";
                        continue;
                    }

                    $emp_code            = trim($parts[0]);
                    $is_twenty_pct_extra = intval($parts[1]);

                    $empDetailSql  = "
                        SELECT e.EmpPersonalCode,
                               e.LevelName,
                               COALESCE(NULLIF(dtd.tadaInNepali, 0), 2400) AS tadaInNepali
                        FROM   Employee_Information e
                        LEFT JOIN DomesticTadaDefinerMasterBylevel dtd
                               ON LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(dtd.DomesticTadaDefinerMasterBylevel_name))
                        WHERE  e.EmpPersonalCode = ?
                    ";
                    $empDetailStmt = sqlsrv_query($conn, $empDetailSql, [$emp_code]);

                    if ($empDetailStmt === false) {
                        $preValidationErrors[] = "Employee {$emp_code}: query failed";
                        continue;
                    }
                    $empDetail = sqlsrv_fetch_array($empDetailStmt, SQLSRV_FETCH_ASSOC);
                    sqlsrv_free_stmt($empDetailStmt);

                    if (!$empDetail) {
                        $preValidationErrors[] = "Employee {$emp_code}: not found";
                        continue;
                    }

                    $nepali_per_day = floatval($empDetail['tadaInNepali']);
                    $base_tada      = $total_days * $nepali_per_day;
                    $total_tada     = ($is_twenty_pct_extra == 1) ? $base_tada * 1.20 : $base_tada;

                    $submitPayloads[$emp_code] = [
                        'emp_code'            => $emp_code,
                        'is_twenty_pct_extra' => $is_twenty_pct_extra,
                        'total_tada'          => $total_tada,
                        'total_days'          => $total_days,
                    ];
                }

                if (empty($submitPayloads)) {
                    $errorMsg = "❌ No valid employees to save.";
                    if (!empty($preValidationErrors)) {
                        $errorMsg .= "\\n\\n" . implode("\\n", $preValidationErrors);
                    }
                    echo "<script>alert('{$errorMsg}');</script>";
                } else {

                    // ── Step 2: Fetch existing employees in this batch ───────
                    $existingMap  = [];
                    $existingSql  = "SELECT domestic_tada_id, EmpPersonalCode FROM DomesticTada WHERE domestic_Batch_id = ?";
                    $existingStmt = sqlsrv_query($conn, $existingSql, [$batch_id]);
                    if ($existingStmt !== false) {
                        while ($row = sqlsrv_fetch_array($existingStmt, SQLSRV_FETCH_ASSOC)) {
                            $existingMap[$row['EmpPersonalCode']] = $row['domestic_tada_id'];
                        }
                        sqlsrv_free_stmt($existingStmt);
                    }

                    $toUpdate = [];
                    $toInsert = [];
                    $toDelete = [];

                    foreach ($submitPayloads as $empCode => $payload) {
                        if (isset($existingMap[$empCode])) {
                            $toUpdate[$empCode] = $existingMap[$empCode];
                        } else {
                            $toInsert[] = $empCode;
                        }
                    }
                    foreach ($existingMap as $empCode => $tadaId) {
                        if (!isset($submitPayloads[$empCode])) {
                            $toDelete[] = $tadaId;
                        }
                    }

                    // ── Step 3: Begin transaction ────────────────────────────
                    sqlsrv_begin_transaction($conn);
                    $txSuccess     = true;
                    $opErrors      = [];
                    $insertedCount = 0;
                    $updatedCount  = 0;
                    $deletedCount  = 0;

                    // ── 3a: UPDATE existing employees ────────────────────────
                    // ============= UPDATED: UPDATE now includes tadaverifier_id =============
                    foreach ($toUpdate as $empCode => $tadaId) {
                        $payload = $submitPayloads[$empCode];

                        $updateSql = "
                            UPDATE DomesticTada SET
                                domestic_form_date            = ?,
                                District_id                   = ?,
                                domestic_travel_objective     = ?,
                                domestic_travelDateStart      = ?,
                                domestic_travelDateEnd        = ?,
                                TadaTypeMaster_id             = ?,
                                domestic_tada                 = ?,
                                domestic_isTwentyPercentExtra = ?,
                                fiscal_year_master_id         = ?,
                                tadaverifier_id               = ?
                            WHERE domestic_tada_id = ?
                              AND domestic_Batch_id = ?
                        ";
                        $updateParams = [
                            $form_date_formatted,
                            $district_id,
                            $travel_objective,
                            $start_date_formatted,
                            $end_date_formatted,
                            $tada_type_id,
                            $payload['total_tada'],
                            $payload['is_twenty_pct_extra'],
                            $fiscal_year_id,
                            $tadaverifier_id,
                            $tadaId,
                            $batch_id,
                        ];
                        // ============= END =============

                        $updateStmt = sqlsrv_query($conn, $updateSql, $updateParams);
                        if ($updateStmt === false) {
                            $txSuccess  = false;
                            $sqlErr     = sqlsrv_errors();
                            $opErrors[] = "UPDATE failed for emp {$empCode}: " . ($sqlErr[0]['message'] ?? 'Unknown error');
                            break;
                        }
                        sqlsrv_free_stmt($updateStmt);
                        $updatedCount++;
                    }

                    // ── 3b: INSERT new employees ─────────────────────────────
                    // ============= UPDATED: INSERT now includes tadaverifier_id =============
                    if ($txSuccess) {
                        foreach ($toInsert as $empCode) {
                            $payload    = $submitPayloads[$empCode];
                            $chalani_id = getDomesticNextChalani($conn, $fiscal_year_id);

                            $insertSql = "
                                INSERT INTO DomesticTada
                                    (domestic_Batch_id, domestic_Chalani_id, domestic_form_date,
                                     EmpPersonalCode, District_id, domestic_travel_objective,
                                     domestic_travelDateStart, domestic_travelDateEnd,
                                     domestic_tada, domestic_totalday,
                                     domestic_isTwentyPercentExtra, TadaTypeMaster_id,
                                     domestic_createdBy, domestic_createddate,
                                     fiscal_year_master_id, tadaverifier_id)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE(), ?, ?)
                            ";
                            $insertParams = [
                                $batch_id,
                                $chalani_id,
                                $form_date_formatted,
                                $empCode,
                                $district_id,
                                $travel_objective,
                                $start_date_formatted,
                                $end_date_formatted,
                                $payload['total_tada'],
                                $payload['total_days'],
                                $payload['is_twenty_pct_extra'],
                                $tada_type_id,
                                $created_by,
                                $fiscal_year_id,
                                $tadaverifier_id,
                            ];
                            // ============= END =============

                            $insertStmt = sqlsrv_query($conn, $insertSql, $insertParams);
                            if ($insertStmt === false) {
                                $txSuccess  = false;
                                $sqlErr     = sqlsrv_errors();
                                $opErrors[] = "INSERT failed for emp {$empCode}: " . ($sqlErr[0]['message'] ?? 'Unknown error');
                                break;
                            }
                            sqlsrv_free_stmt($insertStmt);
                            $insertedCount++;
                        }
                    }

                    // ── 3c: DELETE employees removed by the user ─────────────
                    if ($txSuccess && !empty($toDelete)) {
                        foreach ($toDelete as $tadaId) {
                            $deleteSql  = "DELETE FROM DomesticTada WHERE domestic_tada_id = ? AND domestic_Batch_id = ?";
                            $deleteStmt = sqlsrv_query($conn, $deleteSql, [$tadaId, $batch_id]);
                            if ($deleteStmt === false) {
                                $txSuccess  = false;
                                $sqlErr     = sqlsrv_errors();
                                $opErrors[] = "DELETE failed for tada_id {$tadaId}: " . ($sqlErr[0]['message'] ?? 'Unknown error');
                                break;
                            }
                            sqlsrv_free_stmt($deleteStmt);
                            $deletedCount++;
                        }
                    }

                    // ── Step 4: Commit or rollback ───────────────────────────
                    if ($txSuccess) {
                        sqlsrv_commit($conn);
                        $message  = "✅ Batch {$batch_id} updated successfully!\\n";
                        $message .= "Updated: {$updatedCount} | Added: {$insertedCount} | Removed: {$deletedCount}";
                        if (!empty($preValidationErrors)) {
                            $message .= "\\n\\nWarnings:\\n" . implode("\\n", $preValidationErrors);
                        }
                        echo "<script>alert('{$message}'); window.location.href='DomesticTadaView.php';</script>";
                    } else {
                        sqlsrv_rollback($conn);
                        $errorMsg  = "❌ Update failed — NO records were changed.\\n";
                        $errorMsg .= implode("\\n", array_merge($preValidationErrors, $opErrors));
                        echo "<script>alert('{$errorMsg}');</script>";
                    }
                }
            }
        }
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Edit Domestic TADA Batch</title>
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
        h2 { color: #1E3A8A; margin-bottom: 25px; text-align: center; font-size: 28px; }
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
        .form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; }
        .form-group { margin-bottom: 20px; }
        .form-group.full-width { grid-column: 1 / -1; }
        label { display: block; margin-bottom: 8px; color: #1F2937; font-weight: 600; font-size: 14px; }
        label .required { color: #EF4444; }
        input[type="text"], input[type="date"], input[type="number"], select, textarea {
            width: 100%; padding: 10px 12px; border: 2px solid #e0e0e0;
            border-radius: 6px; font-size: 14px;
            transition: border-color 0.3s, box-shadow 0.3s; box-sizing: border-box;
        }
        input:focus, select:focus, textarea:focus {
            outline: none; border-color: #667eea;
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
        .btn-primary:disabled { background: #9CA3AF; cursor: not-allowed; opacity: 0.6; }
        .btn-secondary { background-color: #6B7280; color: white; }
        .btn-secondary:hover { background-color: #4B5563; }
        .form-actions { margin-top: 30px; display: flex; gap: 12px; }
        .form-actions .btn-secondary { flex: 0 0 120px; }
        .form-actions .btn-primary { flex: 1; }
    </style>
</head>
<body>
<div class="form-container">
    <h2>✏️ Edit Domestic TADA Batch</h2>

    <div class="info-badge">
        📦 Batch ID: <strong><?php echo htmlspecialchars($batch_id); ?></strong>
    </div>

    <form method="POST" id="tadaForm">

        <!-- Row 1: Form Date + TADA Type -->
        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required
                       value="<?php echo $batchData['domestic_form_date']->format('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>TADA Type <span class="required">*</span></label>
                <select name="tada_type_id" id="tadaTypeSelect" class="select2-single" required>
                    <option value="">-- Select TADA Type --</option>
                    <?php while ($tadaType = sqlsrv_fetch_array($tadaTypeStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $tadaType['TadaTypeMaster_id']; ?>"
                                <?php echo ($tadaType['TadaTypeMaster_id'] == $batchData['TadaTypeMaster_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($tadaType['type']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>

        <!-- Row 2: District + spacer -->
        <div class="form-row">
            <div class="form-group">
                <label>District <span class="required">*</span></label>
                <select name="district_id" id="districtSelect" class="select2-single" required>
                    <option value="">-- Select District --</option>
                    <?php while ($district = sqlsrv_fetch_array($districtStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo $district['District_id']; ?>"
                                <?php echo ($district['District_id'] == $batchData['District_id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($district['District_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group"><!-- alignment spacer --></div>
        </div>

        <!-- ============= NEW: Row 3 — TADA Verifier (full width) ============= -->
        <div class="form-row">
            <div class="form-group full-width">
                <label>TADA Verifier <span class="required">*</span></label>
                <select name="tadaverifier_id" id="tadaVerifierSelect" class="select2-single" required>
                    <option value="">-- Select TADA Verifier --</option>
                    <?php while ($verifier = sqlsrv_fetch_array($tadaVerifierStmt, SQLSRV_FETCH_ASSOC)) { ?>
                        <option value="<?php echo intval($verifier['tadaverifier_id']); ?>"
                                <?php echo ($verifier['tadaverifier_id'] == $batchData['tadaverifier_id']) ? 'selected' : ''; ?>>
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
            <textarea name="travel_objective" required
                      placeholder="Enter the purpose of domestic travel..."><?php echo htmlspecialchars($batchData['domestic_travel_objective']); ?></textarea>
        </div>

        <!-- Travel Dates -->
        <div class="form-row">
            <div class="form-group">
                <label>Travel Start Date <span class="required">*</span></label>
                <input type="date" name="travelDateStart" id="startDate" required
                       value="<?php echo $batchData['domestic_travelDateStart']->format('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>Travel End Date <span class="required">*</span></label>
                <input type="date" name="travelDateEnd" id="endDate" required
                       value="<?php echo $batchData['domestic_travelDateEnd']->format('Y-m-d'); ?>">
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
                            $levelDisplay = !empty($emp['LevelName']) ? $emp['LevelName'] : 'No Level';
                            $nprDisplay   = floatval($emp['tadaInNepali']);
                        ?>
                            <option value="<?php echo htmlspecialchars($emp['EmpPersonalCode']); ?>"
                                    data-name="<?php echo htmlspecialchars($emp['EmpName']); ?>"
                                    data-level="<?php echo htmlspecialchars($levelDisplay); ?>"
                                    data-npr="<?php echo $nprDisplay; ?>">
                                <?php echo htmlspecialchars($emp['EmpName']); ?> - <?php echo htmlspecialchars($levelDisplay); ?>
                                (NPR <?php echo number_format($nprDisplay, 2); ?>/day)
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
                                <th>NPR/Day</th>
                                <th style="width:150px;text-align:center">20% Extra</th>
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
                    onclick="window.location.href='DomesticTadaView.php'">← Cancel</button>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
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

    // Pre-populate from existing batch employees
    const existingEmployees = <?php echo json_encode($batchEmployees); ?>;
    existingEmployees.forEach(emp => {
        addedEmployees.push({
            code:               emp.EmpPersonalCode,
            name:               emp.EmpName,
            level:              emp.DomesticTadaDefinerMasterBylevel_name || emp.LevelName || 'No Level',
            npr:                emp.tadaInNepali || 2400,
            twentyPercentExtra: emp.domestic_isTwentyPercentExtra == 1
        });
    });

    // ── Select2 init — includes tadaVerifierSelect ────────────────────────────
    $('#districtSelect').select2({ placeholder: "Select a district",              allowClear: true, width: '100%' });
    $('#tadaTypeSelect').select2({ placeholder: "Select TADA type",               allowClear: true, width: '100%' });
    $('#tadaVerifierSelect').select2({ placeholder: "Select TADA Verifier",       allowClear: true, width: '100%' });
    $('#employeeDropdown').select2({ placeholder: "Search and select employee...", allowClear: true, width: '100%' });

    // ── Render employee table ─────────────────────────────────────────────────
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
                        <td>NPR ${parseFloat(emp.npr).toFixed(2)}</td>
                        <td style="text-align:center">
                            <input type="checkbox" class="twenty-percent-check"
                                   data-index="${index}" ${emp.twentyPercentExtra ? 'checked' : ''}
                                   style="cursor:pointer;width:18px;height:18px;accent-color:#667eea">
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
        $('#employeeData').val(
            addedEmployees.map(e => `${e.code}:${e.twentyPercentExtra ? 1 : 0}`).join(',')
        );
    }

    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }

    // ── Add employee ──────────────────────────────────────────────────────────
    $('#btnAddEmployee').on('click', function () {
        const opt     = $('#employeeDropdown option:selected');
        const empCode = opt.val();
        if (!empCode) { alert('⚠️ Please select an employee first!'); return; }
        if (addedEmployees.some(e => e.code === empCode)) { alert('⚠️ This employee is already added!'); return; }

        addedEmployees.push({
            code: empCode, name: opt.data('name'), level: opt.data('level'),
            npr: opt.data('npr'), twentyPercentExtra: false
        });
        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });

    // ── 20% extra toggle ──────────────────────────────────────────────────────
    $(document).on('change', '.twenty-percent-check', function () {
        addedEmployees[$(this).data('index')].twentyPercentExtra = $(this).is(':checked');
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