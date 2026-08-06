<?php
// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection
require_once 'db.php';
include 'HEADER.php';

// Check DB connection
if ($conn === false) {
    die("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
        <h3>❌ Database Connection Lost!</h3>
        <p>Please refresh the page or contact administrator.</p>
        </div>");
}

// Fetch Districts
$districtSql = "SELECT District_id, District_name FROM DistrictMaster ORDER BY District_name";
$districtStmt = sqlsrv_query($conn, $districtSql);
if ($districtStmt === false) die("Error fetching districts: " . print_r(sqlsrv_errors(), true));

// Fetch TADA Type Master (from TraveltypeMaster table)
$tadaTypeSql = "SELECT TadaTypeMaster_id, type FROM TraveltypeMaster ORDER BY type";
$tadaTypeStmt = sqlsrv_query($conn, $tadaTypeSql);
if ($tadaTypeStmt === false) die("Error fetching TADA types: " . print_r(sqlsrv_errors(), true));

// Fetch Employees with TADA level
$empSql = "
SELECT 
    e.EmpPersonalCode,
    e.EmpName,
    e.LevelName,
    e.LevelName_id,
    t.DomesticTadaDefinerMasterBylevel_id,
    t.DomesticTadaDefinerMasterBylevel_name,
    t.tadaInNepali
FROM Employee_Information e
LEFT JOIN DomesticTadaDefinerMasterBylevel t 
    ON LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(t.DomesticTadaDefinerMasterBylevel_name))
    OR e.LevelName_id = t.DomesticTadaDefinerMasterBylevel_id
ORDER BY e.EmpName
";
$empStmt = sqlsrv_query($conn, $empSql);
if ($empStmt === false) die("Error fetching employees: " . print_r(sqlsrv_errors(), true));

$employees = [];
while ($emp = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) {
    $employees[] = $emp;
}

// Function: Next Batch ID
function getNextDomesticBatchId($conn) {
    $sql = "SELECT MAX(domestic_Batch_id) AS MaxBatch FROM DomesticTada";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 'DBATCH001';
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    $maxBatch = $row['MaxBatch'];
    if (empty($maxBatch)) return 'DBATCH001';
    $numericPart = intval(substr($maxBatch, 6));
    return 'DBATCH' . str_pad($numericPart + 1, 3, '0', STR_PAD_LEFT);
}

// Function: Next Chalani #
function getNextDomesticChalaniNumber($conn) {
    $sql = "SELECT ISNULL(MAX(domestic_Chalani_id), 0) + 1 AS NextChalani FROM DomesticTada";
    $stmt = sqlsrv_query($conn, $sql);
    if ($stmt === false) return 1;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    return $row['NextChalani'];
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $form_date = $_POST['form_date'];
    $district_id = intval($_POST['district_id']);
    $travel_objective = trim($_POST['travel_objective']);
    $travelDateStart = $_POST['travelDateStart'];
    $travelDateEnd = $_POST['travelDateEnd'];
    $is_twenty_percent_extra = isset($_POST['is_twenty_percent_extra']) ? 1 : 0;
    $tada_type_id = intval($_POST['tada_type_id']);
    $created_by = $_SESSION['user_id'] ?? 1;
    $employee_data = $_POST['employee_data'] ?? '';

    if (empty($employee_data)) {
        echo "<script>alert('⚠️ Please add at least one employee!');</script>";
    } elseif (empty($tada_type_id) || $tada_type_id <= 0) {
        echo "<script>alert('⚠️ Please select a TADA Type!');</script>";
    } else {
        $emp_entries = explode(',', $employee_data);

        // Total days including start & end date (ignore hours)
        $start = new DateTime($travelDateStart);
        $end = new DateTime($travelDateEnd);
        $start->setTime(0,0,0);
        $end->setTime(0,0,0);
        $total_days = $start->diff($end)->days + 1;

        if ($total_days <= 0) {
            die("<script>alert('❌ End date must be same or after start date.');</script>");
        }

        $batch_id = getNextDomesticBatchId($conn);
        $form_date_formatted = date('Y-m-d', strtotime($form_date));
        $start_date_formatted = date('Y-m-d', strtotime($travelDateStart));
        $end_date_formatted = date('Y-m-d', strtotime($travelDateEnd));

        $success = true;
        $insertedCount = 0;
        $errors = [];
        $firstChalani = null;
        $lastChalani = null;

        foreach ($emp_entries as $entry) {
            if (empty(trim($entry))) continue;
            $emp_code = trim($entry);

            $chalani_id = getNextDomesticChalaniNumber($conn);
            if ($firstChalani === null) $firstChalani = $chalani_id;
            $lastChalani = $chalani_id;

            // Get employee details
            $empDetailSql = "
            SELECT 
                e.EmpPersonalCode,
                e.LevelName,
                e.LevelName_id,
                t.DomesticTadaDefinerMasterBylevel_id,
                t.DomesticTadaDefinerMasterBylevel_name,
                t.tadaInNepali
            FROM Employee_Information e
            LEFT JOIN DomesticTadaDefinerMasterBylevel t 
                ON LTRIM(RTRIM(e.LevelName)) = LTRIM(RTRIM(t.DomesticTadaDefinerMasterBylevel_name))
                OR e.LevelName_id = t.DomesticTadaDefinerMasterBylevel_id
            WHERE e.EmpPersonalCode = ?
            ";
            $empDetailStmt = sqlsrv_query($conn, $empDetailSql, [$emp_code]);
            if ($empDetailStmt === false) { $errors[] = "Employee {$emp_code}: Query failed"; continue; }
            $empDetail = sqlsrv_fetch_array($empDetailStmt, SQLSRV_FETCH_ASSOC);
            sqlsrv_free_stmt($empDetailStmt);

            if (!$empDetail || empty($empDetail['tadaInNepali'])) {
                $errors[] = "Employee {$emp_code}: No TADA rate found";
                continue;
            }

            $nepali_per_day = floatval($empDetail['tadaInNepali']);
            $total_tada = $total_days * $nepali_per_day;

            // Apply 20% extra if checked
            if ($is_twenty_percent_extra) {
                $total_tada = $total_tada * 1.20;
            }

            // Insert into DB with new columns (domestic_totalday is auto-computed)
            $insertSql = "INSERT INTO DomesticTada 
                (domestic_Batch_id, domestic_Chalani_id, domestic_form_date, EmpPersonalCode, 
                 District_id, domestic_isTwentyPercentExtra, TadaTypeMaster_id, 
                 domestic_travel_objective, domestic_travelDateStart, domestic_travelDateEnd, 
                 domestic_tada, domestic_createdBy, domestic_createddate)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, GETDATE())";

            $params = [
                $batch_id, $chalani_id, $form_date_formatted, $emp_code, 
                $district_id, $is_twenty_percent_extra, $tada_type_id,
                $travel_objective, $start_date_formatted, $end_date_formatted,
                $total_tada, $created_by
            ];

            $insertStmt = sqlsrv_query($conn, $insertSql, $params);
            if ($insertStmt === false) {
                $sqlErrors = sqlsrv_errors();
                $errorMsg = "Employee {$emp_code}: " . $sqlErrors[0]['message'];
                $errors[] = $errorMsg;
                error_log("SQL Insert Error: " . print_r($sqlErrors, true));
                continue;
            } else {
                sqlsrv_free_stmt($insertStmt);
                $insertedCount++;
            }
        }

        if ($insertedCount > 0) {
            $chalaniRange = ($firstChalani == $lastChalani) ? "Chalani #: {$firstChalani}" : "Chalani #: {$firstChalani} - {$lastChalani}";
            $message = "✅ Batch {$batch_id} created successfully!\\n{$chalaniRange}\\nEmployees Added: {$insertedCount}";
            if (!empty($errors)) $message .= "\\n\\nWarnings:\\n" . implode("\\n", $errors);
            echo "<script>alert('{$message}'); window.location.href='DomesticTadaView.php';</script>";
        } else {
            $errorMsg = "❌ Error: Could not insert any records.\\n\\n";
            $errorMsg .= "Total Days: {$total_days}\\n";
            $errorMsg .= "Batch ID: {$batch_id}\\n";
            $errorMsg .= "TADA Type ID: {$tada_type_id}\\n";
            $errorMsg .= "20% Extra: " . ($is_twenty_percent_extra ? 'Yes' : 'No') . "\\n\\n";
            if (!empty($errors)) {
                $errorMsg .= "Errors:\\n" . implode("\\n", array_slice($errors, 0, 3));
                if (count($errors) > 3) $errorMsg .= "\\n... and " . (count($errors) - 3) . " more errors";
            }
            echo "<script>alert('{$errorMsg}');</script>";
        }
    }
}

$nextChalani = getNextDomesticChalaniNumber($conn);
$nextBatch = getNextDomesticBatchId($conn);
?>



<!DOCTYPE html>
<html>
<head>
    <title>Add Domestic TADA Batch</title>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
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
            color: #065F46;
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
            background: linear-gradient(135deg, #D1FAE5 0%, #A7F3D0 100%);
            color: #065F46;
            padding: 12px 15px;
            border-radius: 8px;
            font-weight: 600;
            text-align: center;
            border: 2px solid #6EE7B7;
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
        label .required {
            color: #EF4444;
        }
        input[type="text"],
        input[type="date"],
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
            border-color: #10B981;
            box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1);
        }
        textarea {
            resize: vertical;
            min-height: 90px;
            font-family: inherit;
        }
        .select2-container--default .select2-selection--single {
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            height: 44px;
        }
        .select2-container--default .select2-selection--single .select2-selection__rendered {
            line-height: 40px;
            padding-left: 12px;
        }
        .select2-container--default .select2-selection--single .select2-selection__arrow {
            height: 40px;
        }
        .select2-container--default.select2-container--focus .select2-selection--single {
            border-color: #10B981;
        }
        .select2-container {
            width: 100% !important;
        }
        
        .checkbox-container {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 12px;
            background: #F3F4F6;
            border-radius: 6px;
            border: 2px solid #E5E7EB;
            cursor: pointer;
            transition: all 0.3s;
        }
        .checkbox-container:hover {
            background: #E5E7EB;
            border-color: #10B981;
        }
        .checkbox-container input[type="checkbox"] {
            width: 20px;
            height: 20px;
            cursor: pointer;
            accent-color: #10B981;
        }
        .checkbox-container label {
            margin: 0;
            cursor: pointer;
            flex: 1;
        }
        
        .employee-section {
            background: #F9FAFB;
            padding: 20px;
            border-radius: 8px;
            margin: 20px 0;
            border: 2px dashed #D1D5DB;
        }
        .employee-selector {
            display: flex;
            gap: 10px;
            margin-bottom: 15px;
            align-items: stretch;
        }
        .employee-selector select {
            flex: 1;
        }
        .btn-add-employee {
            background: #10B981;
            color: white;
            padding: 10px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            white-space: nowrap;
            transition: background 0.3s, transform 0.1s;
        }
        .btn-add-employee:hover {
            background: #059669;
            transform: translateY(-1px);
        }
        
        .employee-table-container {
            overflow-x: auto;
            margin-top: 15px;
            border-radius: 8px;
            border: 1px solid #E5E7EB;
        }
        .employee-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
        }
        .employee-table thead {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
        }
        .employee-table th {
            padding: 14px 12px;
            text-align: left;
            font-weight: 600;
            font-size: 13px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .employee-table td {
            padding: 14px 12px;
            border-bottom: 1px solid #E5E7EB;
            font-size: 14px;
        }
        .employee-table tbody tr:hover {
            background: #F3F4F6;
        }
        .btn-remove {
            background: #EF4444;
            color: white;
            border: none;
            padding: 6px 14px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
            font-weight: 600;
            transition: background 0.3s;
        }
        .btn-remove:hover {
            background: #DC2626;
        }
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #6B7280;
        }
        .empty-state-icon {
            font-size: 48px;
            margin-bottom: 10px;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 15px;
            font-weight: 600;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #10B981 0%, #059669 100%);
            color: white;
            width: 100%;
            padding: 14px;
            font-size: 16px;
        }
        .btn-primary:hover:not(:disabled) {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(16, 185, 129, 0.4);
        }
        .btn-primary:disabled {
            background: #9CA3AF;
            cursor: not-allowed;
            opacity: 0.6;
        }
        .btn-secondary {
            background-color: #6B7280;
            color: white;
        }
        .btn-secondary:hover {
            background-color: #4B5563;
        }
        .form-actions {
            margin-top: 30px;
            display: flex;
            gap: 12px;
        }
        .form-actions .btn-secondary {
            flex: 0 0 120px;
        }
        .form-actions .btn-primary {
            flex: 1;
        }
    </style>
</head>
<body>
<div class="form-container">
    <h2>➕ Add Domestic TADA Batch</h2>
    
    <div class="info-badges">
        <div class="info-badge">
            📋 Next Batch ID: <strong><?php echo htmlspecialchars($nextBatch); ?></strong>
        </div>
        <div class="info-badge">
            🔢 Next Chalani #: <strong><?php echo htmlspecialchars($nextChalani); ?></strong>
        </div>
    </div>
    
    <form method="POST" id="tadaForm">
        <div class="form-row">
            <div class="form-group">
                <label>Form Date <span class="required">*</span></label>
                <input type="date" name="form_date" required value="<?php echo date('Y-m-d'); ?>">
            </div>
            <div class="form-group">
                <label>District <span class="required">*</span></label>
                <select name="district_id" id="districtSelect" class="select2-single" required>
                    <option value="">-- Select District --</option>
                    <?php 
                    while ($district = sqlsrv_fetch_array($districtStmt, SQLSRV_FETCH_ASSOC)) { 
                    ?>
                        <option value="<?php echo $district['District_id']; ?>">
                            <?php echo htmlspecialchars($district['District_name']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
        </div>
        
        <div class="form-row">
            <div class="form-group">
                <label>TADA Type <span class="required">*</span></label>
                <select name="tada_type_id" id="tadaTypeSelect" class="select2-single" required>
                    <option value="">-- Select TADA Type --</option>
                    <?php 
                    while ($tadaType = sqlsrv_fetch_array($tadaTypeStmt, SQLSRV_FETCH_ASSOC)) { 
                    ?>
                        <option value="<?php echo $tadaType['TadaTypeMaster_id']; ?>">
                            <?php echo htmlspecialchars($tadaType['type']); ?>
                        </option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-group">
                <label>&nbsp;</label>
                <div class="checkbox-container">
                    <input type="checkbox" name="is_twenty_percent_extra" id="twentyPercentExtra">
                    <label for="twentyPercentExtra">Add 20% Extra TADA</label>
                </div>
            </div>
        </div>
        
        <div class="form-group full-width">
            <label>Travel Objective <span class="required">*</span></label>
            <textarea name="travel_objective" required placeholder="Enter the purpose of domestic travel..."></textarea>
        </div>
        
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
        
        <div class="form-group full-width">
            <label>Add Employees <span class="required">*</span></label>
            <div class="employee-section">
                <div class="employee-selector">
                    <select id="employeeDropdown" class="select2-single">
                        <option value="">-- Search and Select Employee --</option>
                        <?php foreach ($employees as $emp) { 
                            $levelDisplay = !empty($emp['LevelName']) ? $emp['LevelName'] : 'No Level';
                            $nepaliDisplay = !empty($emp['tadaInNepali']) ? $emp['tadaInNepali'] : 0;
                            $hasValidTada = ($nepaliDisplay > 0);
                            $displayStyle = !$hasValidTada ? 'color: #DC2626; font-style: italic;' : '';
                        ?>
                            <option value="<?php echo htmlspecialchars($emp['EmpPersonalCode']); ?>" 
                                    data-name="<?php echo htmlspecialchars($emp['EmpName']); ?>"
                                    data-level="<?php echo htmlspecialchars($levelDisplay); ?>"
                                    data-nepali="<?php echo $nepaliDisplay; ?>"
                                    data-tada-id="<?php echo $emp['DomesticTadaDefinerMasterBylevel_id'] ?? ''; ?>"
                                    style="<?php echo $displayStyle; ?>"
                                    <?php echo !$hasValidTada ? 'disabled' : ''; ?>>
                                <?php echo htmlspecialchars($emp['EmpName']); ?> - <?php echo htmlspecialchars($levelDisplay); ?> 
                                (NPR <?php echo number_format($nepaliDisplay, 2); ?>/day)
                                <?php if (!$hasValidTada) echo ' - NO TADA RATE'; ?>
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
                                <th>NPR/Day</th>
                                <th style="width: 100px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody id="employeeTableBody">
                            <tr class="empty-state">
                                <td colspan="6">
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
            <button type="button" class="btn btn-secondary" onclick="window.location.href='DomesticTadaView.php'">
                ← Cancel
            </button>
            <button type="submit" class="btn btn-primary" id="submitBtn" disabled>
                💾 Create Batch
            </button>
        </div>
    </form>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function() {
    let addedEmployees = [];
    
    $('#districtSelect, #employeeDropdown, #tadaTypeSelect').select2({
        placeholder: "Select...",
        allowClear: true,
        width: '100%'
    });
    
    $('#btnAddEmployee').on('click', function() {
        const selectedOption = $('#employeeDropdown option:selected');
        const empCode = selectedOption.val();
        
        if (!empCode) {
            alert('⚠️ Please select an employee first!');
            return;
        }
        
        if (addedEmployees.some(emp => emp.code === empCode)) {
            alert('⚠️ This employee is already added!');
            return;
        }
        
        const empData = {
            code: empCode,
            name: selectedOption.data('name'),
            level: selectedOption.data('level'),
            nepali: selectedOption.data('nepali'),
            tadaId: selectedOption.data('tada-id')
        };
        
        addedEmployees.push(empData);
        renderEmployeeTable();
        $('#employeeDropdown').val('').trigger('change');
        updateSubmitButton();
    });
    
    function renderEmployeeTable() {
        const tbody = $('#employeeTableBody');
        tbody.empty();
        
        if (addedEmployees.length === 0) {
            tbody.html(`
                <tr class="empty-state">
                    <td colspan="6">
                        <div class="empty-state-icon">👥</div>
                        <div>No employees added yet. Select from dropdown above.</div>
                    </td>
                </tr>
            `);
        } else {
            addedEmployees.forEach((emp, index) => {
                const row = `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${emp.name}</strong></td>
                        <td>${emp.code}</td>
                        <td>${emp.level}</td>
                        <td>NPR ${parseFloat(emp.nepali).toFixed(2)}</td>
                        <td style="text-align: center;">
                            <button type="button" class="btn-remove" data-index="${index}">
                                🗑️ Remove
                            </button>
                        </td>
                    </tr>
                `;
                tbody.append(row);
            });
        }
        updateHiddenInput();
    }
    
    $(document).on('click', '.btn-remove', function() {
        const index = $(this).data('index');
        if (confirm('Are you sure you want to remove this employee?')) {
            addedEmployees.splice(index, 1);
            renderEmployeeTable();
            updateSubmitButton();
        }
    });
    
    function updateHiddenInput() {
        const data = addedEmployees.map(emp => emp.code).join(',');
        $('#employeeData').val(data);
    }
    
    function updateSubmitButton() {
        $('#submitBtn').prop('disabled', addedEmployees.length === 0);
    }
    
    $('#startDate, #endDate').on('change', function() {
        const startDate = $('#startDate').val();
        const endDate = $('#endDate').val();
        
        if (startDate && endDate) {
            const start = new Date(startDate);
            const end = new Date(endDate);
            
            if (end < start) {
                alert('⚠️ End date cannot be before start date!');
                $('#endDate').val('');
            }
        }
    });
    
    $('#tadaForm').on('submit', function(e) {
        if (addedEmployees.length === 0) {
            e.preventDefault();
            alert('⚠️ Please add at least one employee!');
            return false;
        }
        $('#submitBtn').prop('disabled', true).html('⏳ Creating Batch...');
    });
});
</script>
</body>
</html>