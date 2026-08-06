<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
include 'HEADER.php';

if ($conn === false) {
    die("<div style='padding:20px;color:red;'>Database connection failed!</div>");
}

$filterApplied  = isset($_GET['filter_applied']);
$filterFY       = isset($_GET['fy'])          ? trim($_GET['fy'])          : '';
$filterEmp      = isset($_GET['emp'])         ? trim($_GET['emp'])         : '';
$filterDistrict = isset($_GET['district'])    ? trim($_GET['district'])    : '';
$filterDesig    = isset($_GET['designation']) ? trim($_GET['designation']) : '';

// ── Dropdown data ─────────────────────────────────────────────────────────────
$fyList = [];
$fyStmt = sqlsrv_query($conn, "SELECT fiscal_year_master_id, fy FROM [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] ORDER BY fy DESC");
if ($fyStmt) { while ($r = sqlsrv_fetch_array($fyStmt, SQLSRV_FETCH_ASSOC)) $fyList[] = $r; }

$empList = [];
$empStmt = sqlsrv_query($conn, "SELECT DISTINCT e.EmpPersonalCode, e.EmpName FROM Employee_Information e INNER JOIN DomesticTada dt ON e.EmpPersonalCode = dt.EmpPersonalCode ORDER BY e.EmpName");
if ($empStmt) { while ($r = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) $empList[] = $r; }

$districtList = [];
$distStmt = sqlsrv_query($conn, "SELECT DISTINCT dm.District_id, dm.District_name, dm.District_name_nepali FROM DistrictMaster dm INNER JOIN DomesticTada dt ON dm.District_id = dt.District_id ORDER BY dm.District_name");
if ($distStmt) { while ($r = sqlsrv_fetch_array($distStmt, SQLSRV_FETCH_ASSOC)) $districtList[] = $r; }

$desigList = [];
$desigStmt = sqlsrv_query($conn, "SELECT DISTINCT e.Designation FROM Employee_Information e INNER JOIN DomesticTada dt ON e.EmpPersonalCode = dt.EmpPersonalCode WHERE e.Designation IS NOT NULL ORDER BY e.Designation");
if ($desigStmt) { while ($r = sqlsrv_fetch_array($desigStmt, SQLSRV_FETCH_ASSOC)) $desigList[] = $r; }

// ── Main query ────────────────────────────────────────────────────────────────
$records        = [];
$grandTotal     = 0;
$selectedFYName = '';

if ($filterApplied) {
    if (!empty($filterFY)) {
        foreach ($fyList as $f) {
            if ($f['fiscal_year_master_id'] == $filterFY) {
                $selectedFYName = $f['fy'];
                break;
            }
        }
    }

    $where  = "WHERE 1=1";
    $params = [];
    if (!empty($filterFY))       { $where .= " AND dt.fiscal_year_master_id = ?"; $params[] = $filterFY; }
    if (!empty($filterEmp))      { $where .= " AND dt.EmpPersonalCode = ?";       $params[] = $filterEmp; }
    if (!empty($filterDistrict)) { $where .= " AND dt.District_id = ?";           $params[] = $filterDistrict; }
    if (!empty($filterDesig))    { $where .= " AND e.Designation = ?";            $params[] = $filterDesig; }

    $sql = "
    SELECT
        dt.domestic_tada_id, dt.domestic_Batch_id, dt.domestic_Chalani_id,
        dt.domestic_form_date, dt.EmpPersonalCode,
        e.EmpName, e.EmpNameInNepali, e.Designation, e.LevelName,
        dtm.designationTypeInNepali,
        dt.District_id, dm.District_name, dm.District_name_nepali,
        dt.domestic_travel_objective,
        dt.domestic_travelDateStart, dt.domestic_travelDateEnd,
        dt.domestic_totalday, dt.domestic_tada,
        dt.domestic_isTwentyPercentExtra, dtd.tadaInNepali,
        ttm.type AS travel_type,
        fy.fy AS fiscal_year,
        u.username AS created_by
    FROM DomesticTada dt
    LEFT JOIN Employee_Information e               ON dt.EmpPersonalCode = e.EmpPersonalCode
    LEFT JOIN DistrictMaster dm                    ON dt.District_id = dm.District_id
    LEFT JOIN DomesticTadaDefinerMasterBylevel dtd ON e.LevelName = dtd.DomesticTadaDefinerMasterBylevel_name
    LEFT JOIN DesignationTypeMaster dtm            ON e.Designation = dtm.designationType
    LEFT JOIN TraveltypeMaster ttm                 ON dt.TadaTypeMaster_id = ttm.TadaTypeMaster_id
    LEFT JOIN Users u                              ON dt.domestic_createdBy = u.user_id
    LEFT JOIN [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] fy
           ON dt.fiscal_year_master_id = fy.fiscal_year_master_id
    $where
    ORDER BY dt.domestic_tada_id DESC
    ";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $records[]   = $row;
            $grandTotal += floatval($row['domestic_tada']);
        }
    }
}

function fmtDate($d) {
    if (!$d) return '';
    if ($d instanceof DateTime) return $d->format('Y-m-d');
    return '';
}
function fmtNum($n) {
    if ($n === null || $n === '') return '0';
    return (floor($n) == $n) ? number_format($n, 0) : number_format($n, 2);
}

// Build filter summary string for print header
$filterSummaryParts = [];
if (!empty($selectedFYName))  $filterSummaryParts[] = 'आ.व.: ' . $selectedFYName;
if (!empty($filterEmp)) {
    foreach ($empList as $e) {
        if ($e['EmpPersonalCode'] == $filterEmp) {
            $filterSummaryParts[] = 'कर्मचारी: ' . $e['EmpName'];
            break;
        }
    }
}
if (!empty($filterDistrict)) {
    foreach ($districtList as $d) {
        if ($d['District_id'] == $filterDistrict) {
            $filterSummaryParts[] = 'जिल्ला: ' . $d['District_name_nepali'];
            break;
        }
    }
}
if (!empty($filterDesig)) $filterSummaryParts[] = 'पद: ' . $filterDesig;
$filterSummaryStr = implode('   |   ', $filterSummaryParts);

$chalaniNums = [];
if (!empty($records)) {
    $chalaniNums = array_unique(array_filter(array_column($records, 'domestic_Chalani_id')));
    sort($chalaniNums);
}
$chalaniRange = !empty($chalaniNums)
    ? (count($chalaniNums) === 1 ? $chalaniNums[0] : $chalaniNums[0] . '–' . end($chalaniNums))
    : '—';
?>
<!DOCTYPE html>
<html lang="ne">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Domestic TADA Report <?= htmlspecialchars($selectedFYName) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kalimati&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>

<style>
/* ── Kalimati universal ── */
*, *::before, *::after {
    box-sizing: border-box; margin: 0; padding: 0;
    font-family: 'Kalimati', sans-serif !important;
}

:root {
    --teal:    #0d9488;
    --teal-lt: #ccfbf1;
    --teal-dk: #0f766e;
    --ink:     #0f172a;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --bg:      #f8fafc;
    --white:   #ffffff;
}

body { background: var(--bg); color: var(--ink); min-height: 100vh; padding-bottom: 60px; font-size: 14px; }

/* ── Top bar ── */
.topbar {
    background: var(--white);
    border-bottom: 1px solid var(--border);
    padding: 16px 32px;
    display: flex; align-items: center; justify-content: space-between;
    flex-wrap: wrap; gap: 12px;
    position: sticky; top: 0; z-index: 100;
    box-shadow: 0 1px 8px rgba(0,0,0,.05);
}
.topbar-title { font-size: 19px; font-weight: 700; display: flex; align-items: center; gap: 10px; color: var(--ink); }
.topbar-title .dot { width: 10px; height: 10px; border-radius: 50%; background: var(--teal); flex-shrink: 0; }
.topbar-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

/* ── Buttons ── */
.btn-teal, .btn-outline, .btn-excel, .btn-print {
    border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: background .2s, transform .15s;
    padding: 9px 18px; border: none;
}
.btn-teal   { background: var(--teal); color: #fff; }
.btn-teal:hover { background: var(--teal-dk); transform: translateY(-1px); color: #fff; }
.btn-outline { background: transparent; color: var(--teal); border: 1.5px solid var(--teal); padding: 8px 16px; }
.btn-outline:hover { background: var(--teal-lt); color: var(--teal-dk); }
.btn-excel  { background: #166534; color: #fff; }
.btn-excel:hover { background: #14532d; }
.btn-print  { background: #1e3a5f; color: #fff; }
.btn-print:hover { background: #152c47; }

/* ── Filter card ── */
.filter-card {
    background: var(--white); border: 1px solid var(--border); border-radius: 14px;
    padding: 22px 28px; margin: 22px 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);
}
.filter-card h6 { font-size: 12px; font-weight: 700; text-transform: uppercase; letter-spacing: 1px; color: var(--muted); margin-bottom: 16px; display: flex; align-items: center; gap: 6px; }
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(210px, 1fr)); gap: 14px; }
.filter-grid label { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 5px; display: block; }

/* Select2 */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single { height: 40px; border: 1.5px solid var(--border); border-radius: 8px; display: flex; align-items: center; background: var(--bg); }
.select2-container--default.select2-container--focus .select2-selection--single { border-color: var(--teal); box-shadow: 0 0 0 3px rgba(13,148,136,.12); }
.select2-container--default .select2-selection--single .select2-selection__rendered { line-height: 38px; color: var(--ink); font-size: 13px; padding-left: 12px; }
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
.select2-dropdown { border: 1.5px solid var(--teal); border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--teal); }

/* ── Stats bar ── */
.stats-bar { display: flex; gap: 14px; margin: 0 32px 18px; flex-wrap: wrap; }
.stat-pill { background: var(--white); border: 1px solid var(--border); border-radius: 10px; padding: 12px 20px; display: flex; flex-direction: column; gap: 3px; min-width: 150px; box-shadow: 0 1px 4px rgba(0,0,0,.04); }
.stat-pill .val { font-size: 22px; font-weight: 700; color: var(--teal-dk); line-height: 1; }
.stat-pill .lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); }

/* ── Prompt box ── */
.prompt-box { margin: 0 32px; background: var(--white); border: 1.5px dashed var(--border); border-radius: 14px; padding: 60px 20px; text-align: center; color: var(--muted); }
.prompt-box i { font-size: 48px; margin-bottom: 14px; display: block; color: #cbd5e1; }
.prompt-box p { font-size: 15px; }

/* ── Screen table ── */
.table-wrap { margin: 0 32px; background: var(--white); border: 1px solid var(--border); border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.05); }
#reportTable { width: 100%; border-collapse: collapse; font-size: 12.5px; }
#reportTable thead tr { background: var(--teal); color: #fff; }
#reportTable thead th { padding: 11px 12px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; border: none; }
#reportTable tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
#reportTable tbody tr:hover { background: #f0fdfa; }
#reportTable tbody td { padding: 11px 12px; color: var(--ink); vertical-align: middle; }
.td-emp  { font-weight: 600; }
.td-num  { font-weight: 600; color: #166534; white-space: nowrap; }
.td-days { text-align: center; }
.badge-yes { background: #dcfce7; color: #166534; padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-no  { background: #f1f5f9; color: var(--muted); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
#reportTable tfoot tr { background: #f0fdfa; border-top: 2px solid var(--teal); }
#reportTable tfoot td { padding: 12px; font-weight: 700; font-size: 13px; }
.tfoot-total { font-size: 14px; color: var(--teal-dk); }
.empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
.empty-state i { font-size: 48px; margin-bottom: 12px; }

@media (max-width: 768px) {
    .filter-card, .stats-bar, .table-wrap, .prompt-box { margin-left: 16px; margin-right: 16px; }
    .topbar { padding: 14px 16px; }
    #reportTable { font-size: 11px; }
    #reportTable thead th, #reportTable tbody td { padding: 8px; }
}

/* ══════════════════════════════════════════════════════
   PRINT STYLES
   Everything inside #printSection is what gets printed.
   All screen UI (topbar, filter, stats, screen table)
   is hidden. The print section is hidden on screen.
══════════════════════════════════════════════════════ */
#printSection { display: none; }

@media print {
    /* Hide everything screen-only */
    .topbar, .filter-card, .stats-bar, .table-wrap,
    .prompt-box, .screen-only { display: none !important; }

    /* Show only print section */
    #printSection { display: block !important; }

    body { background: #fff; padding: 0; margin: 0; font-size: 11pt; }

    /* ── Official document header ── */
    .print-letterhead {
        text-align: center;
        border-bottom: 3px double #000;
        padding-bottom: 10px;
        margin-bottom: 12px;
    }
    .print-org-name {
        font-size: 18pt;
        font-weight: 700;
        letter-spacing: 0.5px;
        line-height: 1.3;
    }
    .print-org-sub {
        font-size: 11pt;
        margin-top: 3px;
    }
    .print-doc-title {
        font-size: 14pt;
        font-weight: 700;
        text-align: center;
        margin: 10px 0 4px;
        text-decoration: underline;
        text-underline-offset: 4px;
    }
    .print-meta-bar {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        font-size: 10pt;
        margin: 6px 0 12px;
        border: 1px solid #ccc;
        border-radius: 4px;
        padding: 7px 12px;
        background: #f9f9f9;
        flex-wrap: wrap;
        gap: 6px;
    }
    .print-meta-bar .meta-item { display: flex; flex-direction: column; gap: 1px; }
    .print-meta-bar .meta-label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.8px; color: #555; font-weight: 700; }
    .print-meta-bar .meta-value { font-size: 11pt; font-weight: 700; color: #000; }

    /* ── Print table ── */
    #printTable {
        width: 100%;
        border-collapse: collapse;
        font-size: 9.5pt;
        margin-top: 4px;
    }
    #printTable thead tr { background: #1e3a5f !important; color: #fff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #printTable thead th {
        padding: 7px 8px;
        font-size: 8.5pt;
        font-weight: 700;
        text-align: center;
        border: 1px solid #1e3a5f;
        white-space: nowrap;
        color: #fff !important;
    }
    #printTable tbody tr:nth-child(even) { background: #f5f8ff !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #printTable tbody td {
        padding: 6px 8px;
        border: 1px solid #ccc;
        vertical-align: middle;
        color: #000;
    }
    #printTable tbody td.tc { text-align: center; }
    #printTable tbody td.tr { text-align: right; }
    #printTable tfoot tr { background: #e8f5e9 !important; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
    #printTable tfoot td {
        padding: 7px 8px;
        border: 1px solid #999;
        font-weight: 700;
        font-size: 10pt;
    }
    #printTable tfoot td.tr { text-align: right; }

    /* ── Signature block ── */
    .print-signature-block {
        display: flex;
        justify-content: space-between;
        margin-top: 40px;
        padding-top: 10px;
        font-size: 10pt;
    }
    .print-sig-item { text-align: center; min-width: 150px; }
    .print-sig-line { border-top: 1px solid #000; margin-bottom: 4px; padding-top: 4px; }

    /* ── Footer ── */
    .print-footer {
        margin-top: 16px;
        border-top: 1px solid #ccc;
        padding-top: 6px;
        font-size: 8pt;
        color: #555;
        display: flex;
        justify-content: space-between;
    }

    @page { size: A4 landscape; margin: 12mm 10mm; }
}
</style>
</head>
<body>

<!-- ══════════════════════════════════════════
     SCREEN UI
══════════════════════════════════════════ -->

<!-- Top bar -->
<div class="topbar screen-only">
    <div class="topbar-title">
        <span class="dot"></span>
        Domestic TADA Report
        <?php if ($filterApplied && $selectedFYName): ?>
            <span style="font-size:13px;font-weight:500;color:var(--teal);margin-left:4px;">
                — आ.व. <?= htmlspecialchars($selectedFYName) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <?php if ($filterApplied && !empty($records)): ?>
        <button class="btn-excel" id="btnExcel" onclick="exportExcel()">
            <i class="bi bi-file-earmark-excel-fill"></i> Excel
        </button>
        <button class="btn-print" onclick="triggerPrint()">
            <i class="bi bi-printer-fill"></i> Print
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filter card -->
<div class="filter-card screen-only">
    <h6><i class="bi bi-funnel-fill"></i> Filter Report</h6>
    <form method="GET" action="">
        <input type="hidden" name="filter_applied" value="1">
        <div class="filter-grid">
            <div>
                <label>Fiscal Year / आर्थिक वर्ष</label>
                <select name="fy" id="sel-fy" class="select2-filter">
                    <option value="">— All Years —</option>
                    <?php foreach ($fyList as $fy): ?>
                        <option value="<?= htmlspecialchars($fy['fiscal_year_master_id']) ?>"
                            <?= $filterFY == $fy['fiscal_year_master_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($fy['fy']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Employee / कर्मचारी</label>
                <select name="emp" id="sel-emp" class="select2-filter">
                    <option value="">— All Employees —</option>
                    <?php foreach ($empList as $emp): ?>
                        <option value="<?= htmlspecialchars($emp['EmpPersonalCode']) ?>"
                            <?= $filterEmp == $emp['EmpPersonalCode'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($emp['EmpName']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>District / जिल्ला</label>
                <select name="district" id="sel-dist" class="select2-filter">
                    <option value="">— All Districts —</option>
                    <?php foreach ($districtList as $d): ?>
                        <option value="<?= htmlspecialchars($d['District_id']) ?>"
                            <?= $filterDistrict == $d['District_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['District_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>Designation / पद</label>
                <select name="designation" id="sel-desig" class="select2-filter">
                    <option value="">— All Designations —</option>
                    <?php foreach ($desigList as $d): ?>
                        <option value="<?= htmlspecialchars($d['Designation']) ?>"
                            <?= $filterDesig == $d['Designation'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($d['Designation']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn-teal">
                <i class="bi bi-search"></i> Apply Filter
            </button>
            <?php if ($filterApplied): ?>
                <a href="?" class="btn-outline"><i class="bi bi-x-circle"></i> Clear All</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<?php if (!$filterApplied): ?>
<div class="prompt-box screen-only">
    <i class="bi bi-funnel"></i>
    <p>Please select filter options above and click <strong>Apply Filter</strong> to load the report.</p>
</div>

<?php else:
$batchCount = count(array_unique(array_column($records, 'domestic_Batch_id')));
$empCount   = count(array_unique(array_column($records, 'EmpPersonalCode')));
?>

<!-- Stats -->
<div class="stats-bar screen-only">
    <div class="stat-pill">
        <span class="val"><?= count($records) ?></span>
        <span class="lbl">Total Records</span>
    </div>
    <div class="stat-pill">
        <span class="val"><?= $batchCount ?></span>
        <span class="lbl">Batches</span>
    </div>
    <div class="stat-pill">
        <span class="val"><?= $empCount ?></span>
        <span class="lbl">Employees</span>
    </div>
    <div class="stat-pill">
        <span class="val" style="font-size:16px;"><?= htmlspecialchars($selectedFYName ?: 'All') ?></span>
        <span class="lbl">Fiscal Year</span>
    </div>
</div>

<!-- Screen table -->
<div class="table-wrap screen-only">
<div style="overflow-x:auto;">
<table id="reportTable">
    <thead>
        <tr>
            <th>SN</th>
            <th>Travel Order</th>
            <th>Chalani No</th>
            <th>Employee</th>
            <th>Designation</th>
            <th>District</th>
            <th>Travel Objective</th>
            <th>Travel Start (BS)</th>
            <th>Travel End (BS)</th>
            <th>Days</th>
            <th>Daily Rate (रु)</th>
            <th>Total TADA (रु)</th>
            <th>20% Extra</th>
            <th>Travel Mode</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($records)): ?>
        <tr><td colspan="14"><div class="empty-state"><i class="bi bi-inbox d-block"></i>No records found.</div></td></tr>
    <?php else: $sn = 1; foreach ($records as $row):
        $startAD     = fmtDate($row['domestic_travelDateStart']);
        $endAD       = fmtDate($row['domestic_travelDateEnd']);
        $orderDateAD = fmtDate($row['domestic_form_date']);
        $dailyRate   = floatval($row['tadaInNepali'] ?? 0);
        $is20        = intval($row['domestic_isTwentyPercentExtra'] ?? 0);
        $displayRate = $is20 ? $dailyRate * 1.2 : $dailyRate;
        $desigNep    = $row['designationTypeInNepali'] ?? '-';
    ?>
        <tr>
            <td class="text-muted" style="font-size:11px;"><?= $sn++ ?></td>
            <td class="td-days"><span class="bs-date" data-ad="<?= $orderDateAD ?>"><?= $orderDateAD ?></span></td>
            <td style="font-size:12px;"><?= htmlspecialchars(($row['fiscal_year'] ?? '') . '-' . ($row['domestic_Chalani_id'] ?? '')) ?></td>
            <td class="td-emp"><?= htmlspecialchars($row['EmpNameInNepali'] ?? $row['EmpPersonalCode']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($desigNep) ?></td>
            <td><?= htmlspecialchars($row['District_name_nepali'] ?? '-') ?></td>
            <td style="max-width:180px;font-size:12px;"><?= htmlspecialchars($row['domestic_travel_objective'] ?? '') ?></td>
            <td class="td-days"><span class="bs-date" data-ad="<?= $startAD ?>"><?= $startAD ?></span></td>
            <td class="td-days"><span class="bs-date" data-ad="<?= $endAD ?>"><?= $endAD ?></span></td>
            <td class="td-days"><strong><?= fmtNum($row['domestic_totalday']) ?></strong></td>
            <td class="td-num"><?= fmtNum($displayRate) ?></td>
            <td class="td-num"><?= fmtNum(round($row['domestic_tada'], 2)) ?></td>
            <td class="text-center"><span class="<?= $is20 ? 'badge-yes' : 'badge-no' ?>"><?= $is20 ? 'छ' : 'छैन' ?></span></td>
            <td style="font-size:12px;"><?= htmlspecialchars($row['travel_type'] ?? '-') ?></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <?php if (!empty($records)): ?>
    <tfoot>
        <tr>
            <td colspan="11" class="text-end" style="font-size:12px;font-weight:700;">जम्मा (Grand Total)</td>
            <td class="tfoot-total">रु. <?= number_format($grandTotal, 2) ?></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>
</div>
</div><!-- /screen table-wrap -->


<!-- ══════════════════════════════════════════
     PRINT SECTION — hidden on screen,
     rendered only by @media print
══════════════════════════════════════════ -->
<div id="printSection">

    <!-- Letterhead -->
    <div class="print-letterhead">
        
        <div class="print-org-sub">नेपाल धितोपत्र बोर्ड</div>
        <div class="print-org-sub">खुमत्लर, ललितपुर</div>
    </div>

    <!-- Document title -->
    <div class="print-doc-title">स्वदेश  भ्रमण  विवरण</div>

   
        
       
    </div>

    <!-- Print table -->
    <table id="printTable">
        <thead>
            <tr>
                <th style="width:28px;">सि.नं.</th>
                <th>आदेश मिति</th>
                <th>चलानी नं.</th>
                <th>कर्मचारीको नाम</th>
                <th>पद</th>
                <th>जिल्ला</th>
                <th>भ्रमणको उद्देश्य</th>
                <th>भ्रमण सुरु</th>
                <th>भ्रमण अन्त्य</th>
                <th style="width:36px;">दिन</th>
                <th>दर (रु.)</th>
                <th>जम्मा (रु.)</th>
                <th style="width:46px;">२०% थप</th>
                <th>यात्रा</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="14" style="text-align:center;padding:20px;">कुनै रेकर्ड भेटिएन।</td></tr>
        <?php else: $sn2 = 1; foreach ($records as $row):
            $startAD2     = fmtDate($row['domestic_travelDateStart']);
            $endAD2       = fmtDate($row['domestic_travelDateEnd']);
            $orderDateAD2 = fmtDate($row['domestic_form_date']);
            $dailyRate2   = floatval($row['tadaInNepali'] ?? 0);
            $is202        = intval($row['domestic_isTwentyPercentExtra'] ?? 0);
            $displayRate2 = $is202 ? $dailyRate2 * 1.2 : $dailyRate2;
            $desigNep2    = $row['designationTypeInNepali'] ?? '-';
        ?>
            <tr>
                <td class="tc"><?= $sn2++ ?></td>
                <td class="tc"><span class="bs-date-print" data-ad="<?= $orderDateAD2 ?>"><?= $orderDateAD2 ?></span></td>
                <td class="tc"><?= htmlspecialchars(($row['fiscal_year'] ?? '') . '-' . ($row['domestic_Chalani_id'] ?? '')) ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($row['EmpNameInNepali'] ?? $row['EmpPersonalCode']) ?></td>
                <td><?= htmlspecialchars($desigNep2) ?></td>
                <td><?= htmlspecialchars($row['District_name_nepali'] ?? '-') ?></td>
                <td style="max-width:150px;"><?= htmlspecialchars($row['domestic_travel_objective'] ?? '') ?></td>
                <td class="tc"><span class="bs-date-print" data-ad="<?= $startAD2 ?>"><?= $startAD2 ?></span></td>
                <td class="tc"><span class="bs-date-print" data-ad="<?= $endAD2 ?>"><?= $endAD2 ?></span></td>
                <td class="tc"><strong><?= fmtNum($row['domestic_totalday']) ?></strong></td>
                <td class="tr"><?= fmtNum($displayRate2) ?></td>
                <td class="tr"><?= fmtNum(round($row['domestic_tada'], 2)) ?></td>
                <td class="tc"><?= $is202 ? 'छ' : 'छैन' ?></td>
                <td><?= htmlspecialchars($row['travel_type'] ?? '-') ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        <?php if (!empty($records)): ?>
        <tfoot>
            <tr>
                <td colspan="11" class="tr" style="font-size:10pt;">जम्मा (Grand Total)</td>
                <td class="tr">रु. <?= number_format($grandTotal, 2) ?></td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
        <?php endif; ?>
    </table>

    

    <!-- Print footer -->
    <div class="print-footer">
        <span>यो विवरण कम्प्युटरबाट उत्पन्न गरिएको हो।</span>
        <span id="printFooterDate"></span>
    </div>


<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('.select2-filter').select2({ placeholder: 'Search...', allowClear: true, width: '100%' });
});

/* ── BS date conversion for screen table ── */
window.addEventListener('load', function () {
    setTimeout(function () {
        /* Screen dates */
        document.querySelectorAll('.bs-date').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
                el.setAttribute('data-bs-val', bs);
            } catch(e) {}
        });

        /* Print table dates */
        document.querySelectorAll('.bs-date-print').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
            } catch(e) {}
        });

        /* Print header today BS date */
        try {
            var todayAD = new Date().toISOString().slice(0,10);
            var todayBS = NepaliFunctions.AD2BS(todayAD, "YYYY-MM-DD", "YYYY/MM/DD");
            var el1 = document.getElementById('printTodayBS');
            var el2 = document.getElementById('printFooterDate');
            if (el1) el1.textContent = todayBS;
            if (el2) el2.textContent = 'मुद्रण मिति: ' + todayBS;
        } catch(e) {
            var fallback = new Date().toLocaleDateString();
            var el1 = document.getElementById('printTodayBS');
            var el2 = document.getElementById('printFooterDate');
            if (el1) el1.textContent = fallback;
            if (el2) el2.textContent = 'Printed: ' + fallback;
        }
    }, 450);
});

/* ── Print trigger ── */
function triggerPrint() {
    window.print();
}

/* ── Excel export ── */
function exportExcel() {
    var table = document.getElementById('reportTable');
    var rows  = [];

    rows.push(['घरेलु भ्रमण भत्ता (Domestic TADA) विवरण']);
    rows.push(['आर्थिक वर्ष:', '<?= addslashes($selectedFYName) ?>', '', 'चलानी नं.:', '<?= addslashes($chalaniRange) ?>']);
    rows.push(['मुद्रण मिति:', new Date().toLocaleDateString()]);
    rows.push([]);

    var headers = [];
    table.querySelectorAll('thead th').forEach(function(th){ headers.push(th.innerText.trim()); });
    rows.push(headers);

    table.querySelectorAll('tbody tr').forEach(function(tr) {
        if (tr.querySelector('.empty-state')) return;
        var row = [];
        tr.querySelectorAll('td').forEach(function(td) {
            var bsEl = td.querySelector('.bs-date');
            row.push(bsEl ? (bsEl.getAttribute('data-bs-val') || bsEl.innerText.trim()) : td.innerText.trim());
        });
        rows.push(row);
    });

    var tfoot = table.querySelector('tfoot tr');
    if (tfoot) {
        var fRow = new Array(headers.length).fill('');
        fRow[10] = 'जम्मा (Grand Total)';
        var fc   = tfoot.querySelectorAll('td');
        fRow[11] = fc[1] ? fc[1].innerText.trim() : '';
        rows.push(fRow);
    }

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [4,14,14,22,18,16,30,13,13,5,14,16,9,14].map(function(w){ return {wch:w}; });
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:13} }];

    XLSX.utils.book_append_sheet(wb, ws, 'Domestic TADA');
    XLSX.writeFile(wb, 'DomesticTADA_<?= addslashes($selectedFYName) ?>_' + new Date().toISOString().slice(0,10) + '.xlsx');
}
</script>
</body>
</html>