<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
ob_start(); include 'HEADER.php'; $headerHtml = ob_get_clean();
echo '<div class="screen-only">' . $headerHtml . '</div>';

if ($conn === false) {
    die("<div style='padding:20px;color:red;'>Database connection failed!</div>");
}

$filterApplied  = isset($_GET['filter_applied']);
$filterFY       = isset($_GET['fy'])          ? trim($_GET['fy'])          : '';
$filterEmp      = isset($_GET['emp'])         ? trim($_GET['emp'])         : '';
$filterCountry  = isset($_GET['country'])     ? trim($_GET['country'])     : '';
$filterCity     = isset($_GET['city'])        ? trim($_GET['city'])        : '';
$filterDesig    = isset($_GET['designation']) ? trim($_GET['designation']) : '';
$filterDress    = isset($_GET['dress'])       ? trim($_GET['dress'])       : '';
$filterExtra33  = isset($_GET['extra33'])     ? trim($_GET['extra33'])     : '';
$filterBatch    = isset($_GET['batch'])       ? trim($_GET['batch'])       : '';
$filterDateFrom = isset($_GET['date_from'])   ? trim($_GET['date_from'])   : '';
$filterDateTo   = isset($_GET['date_to'])     ? trim($_GET['date_to'])     : '';

// ── Dropdown data ─────────────────────────────────────────────────────────────
$fyList = [];
$fyStmt = sqlsrv_query($conn, "SELECT fiscal_year_master_id, fy FROM [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] ORDER BY fy DESC");
if ($fyStmt) { while ($r = sqlsrv_fetch_array($fyStmt, SQLSRV_FETCH_ASSOC)) $fyList[] = $r; }

$empList = [];
$empStmt = sqlsrv_query($conn, "SELECT DISTINCT e.EmpPersonalCode, e.EmpName FROM Employee_Information e INNER JOIN [Vraman_Adesh_Generator].[dbo].[International_tada] it ON e.EmpPersonalCode = it.EmpPersonalCode ORDER BY e.EmpName");
if ($empStmt) { while ($r = sqlsrv_fetch_array($empStmt, SQLSRV_FETCH_ASSOC)) $empList[] = $r; }

$countryList = [];
$cntStmt = sqlsrv_query($conn, "SELECT DISTINCT c.Country_id, c.Country_name FROM CountryMaster c INNER JOIN [Vraman_Adesh_Generator].[dbo].[International_tada] it ON c.Country_id = it.Country_id ORDER BY c.Country_name");
if ($cntStmt) { while ($r = sqlsrv_fetch_array($cntStmt, SQLSRV_FETCH_ASSOC)) $countryList[] = $r; }

$cityList = [];
$cityStmt = sqlsrv_query($conn, "SELECT DISTINCT ci.City_id, ci.City_name FROM CityMaster ci INNER JOIN [Vraman_Adesh_Generator].[dbo].[International_tada] it ON ci.City_id = it.City_id ORDER BY ci.City_name");
if ($cityStmt) { while ($r = sqlsrv_fetch_array($cityStmt, SQLSRV_FETCH_ASSOC)) $cityList[] = $r; }

$desigList = [];
$desigStmt = sqlsrv_query($conn, "SELECT DISTINCT e.Designation FROM Employee_Information e INNER JOIN [Vraman_Adesh_Generator].[dbo].[International_tada] it ON e.EmpPersonalCode = it.EmpPersonalCode WHERE e.Designation IS NOT NULL ORDER BY e.Designation");
if ($desigStmt) { while ($r = sqlsrv_fetch_array($desigStmt, SQLSRV_FETCH_ASSOC)) $desigList[] = $r; }

$batchList = [];
$batchStmt = sqlsrv_query($conn, "SELECT DISTINCT Batch_id FROM [Vraman_Adesh_Generator].[dbo].[International_tada] ORDER BY Batch_id DESC");
if ($batchStmt) { while ($r = sqlsrv_fetch_array($batchStmt, SQLSRV_FETCH_ASSOC)) $batchList[] = $r; }

// ── Main query ─────────────────────────────────────────────────────────────────
$records        = [];
$grandTotalUSD  = 0;
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

    if (!empty($filterFY))       { $where .= " AND it.fiscal_year_master_id = ?"; $params[] = $filterFY; }
    if (!empty($filterEmp))      { $where .= " AND it.EmpPersonalCode = ?";        $params[] = $filterEmp; }
    if (!empty($filterCountry))  { $where .= " AND it.Country_id = ?";             $params[] = $filterCountry; }
    if (!empty($filterCity))     { $where .= " AND it.City_id = ?";                $params[] = $filterCity; }
    if (!empty($filterDesig))    { $where .= " AND e.Designation = ?";             $params[] = $filterDesig; }
    if (!empty($filterBatch))    { $where .= " AND it.Batch_id = ?";               $params[] = $filterBatch; }
    if ($filterDress !== '')     { $where .= " AND it.DressAllowance = ?";         $params[] = intval($filterDress); }
    if (!empty($filterExtra33) && $filterExtra33 !== '') {
        if ($filterExtra33 == '1') { $where .= " AND c.extra33percent_country = 1"; }
        else                       { $where .= " AND (c.extra33percent_country = 0 OR c.extra33percent_country IS NULL)"; }
    }
    if (!empty($filterDateFrom)) { $where .= " AND it.travelDateStart >= ?"; $params[] = $filterDateFrom; }
    if (!empty($filterDateTo))   { $where .= " AND it.travelDateEnd <= ?";   $params[] = $filterDateTo; }

    $sql = "
    SELECT
        it.International_tada_id,
        it.Batch_id,
        it.Chalani_id,
        it.form_date,
        it.EmpPersonalCode,
        e.EmpName,
        e.EmpNameInNepali,
        e.Designation,
        e.LevelName,
        dtm.designationTypeInNepali,
        c.Country_id,
        c.Country_name,
        c.extra33percent_country,
        ci.City_name,
        it.travel_objective,
        it.travelDateStart,
        it.travelDateEnd,
        it.totalday,
        it.totalUSdrecevid,
        it.DressAllowance,
        t.TadaDefinerMasterBylevel_name AS TADA_Level,
        t.tadaInUSD,
        CASE
            WHEN c.extra33percent_country = 1
                THEN (t.tadaInUSD + (t.tadaInUSD * 0.33)) * it.totalday
            ELSE t.tadaInUSD * it.totalday
        END AS tadaInUSD_Calc,
        fy.fy AS fiscal_year,
        u.username AS created_by
    FROM [Vraman_Adesh_Generator].[dbo].[International_tada] it
    LEFT JOIN Employee_Information e               ON it.EmpPersonalCode = e.EmpPersonalCode
    LEFT JOIN CountryMaster c                      ON it.Country_id = c.Country_id
    LEFT JOIN CityMaster ci                        ON it.City_id = ci.City_id
    LEFT JOIN TadaDefinerMasterBylevel t           ON it.TadaDefinerMasterBylevel_id = t.TadaDefinerMasterBylevel_id
    LEFT JOIN DesignationTypeMaster dtm            ON e.Designation = dtm.designationType
    LEFT JOIN Users u                              ON it.createdBy = u.user_id
    LEFT JOIN [Vraman_Adesh_Generator].[dbo].[fiscal_year_master] fy
           ON it.fiscal_year_master_id = fy.fiscal_year_master_id
    $where
    ORDER BY it.International_tada_id DESC
    ";

    $stmt = sqlsrv_query($conn, $sql, $params);
    if ($stmt) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            $records[]      = $row;
            $grandTotalUSD += floatval($row['totalUSdrecevid'] ?? $row['tadaInUSD_Calc'] ?? 0);
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
if (!empty($selectedFYName)) $filterSummaryParts[] = 'आ.व.: ' . $selectedFYName;
if (!empty($filterEmp)) {
    foreach ($empList as $e) {
        if ($e['EmpPersonalCode'] == $filterEmp) {
            $filterSummaryParts[] = 'कर्मचारी: ' . $e['EmpName'];
            break;
        }
    }
}
if (!empty($filterCountry)) {
    foreach ($countryList as $c) {
        if ($c['Country_id'] == $filterCountry) {
            $filterSummaryParts[] = 'देश: ' . $c['Country_name'];
            break;
        }
    }
}
if (!empty($filterDesig)) $filterSummaryParts[] = 'पद: ' . $filterDesig;
$filterSummaryStr = implode('   |   ', $filterSummaryParts);

$chalaniNums = [];
if (!empty($records)) {
    $chalaniNums = array_unique(array_filter(array_column($records, 'Chalani_id')));
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
<title>International TADA Report <?= htmlspecialchars($selectedFYName) ?></title>

<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Kalimati&display=swap" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/xlsx@0.18.5/dist/xlsx.full.min.js"></script>
<script src="https://nepalidatepicker.sajanmaharjan.com.np/v5/nepali.datepicker/js/nepali.datepicker.v5.0.6.min.js"></script>

<style>
*, *::before, *::after {
    box-sizing: border-box; margin: 0; padding: 0;
    font-family: 'Kalimati', sans-serif !important;
}

:root {
    --navy:    #1e3a5f;
    --navy-lt: #dbeafe;
    --navy-dk: #152c47;
    --gold:    #b45309;
    --gold-lt: #fef3c7;
    --ink:     #0f172a;
    --muted:   #64748b;
    --border:  #e2e8f0;
    --bg:      #f8fafc;
    --white:   #ffffff;
    --green:   #166534;
    --green-lt:#dcfce7;
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
.topbar-title {
    font-size: 19px; font-weight: 700;
    display: flex; align-items: center; gap: 10px; color: var(--ink);
}
.topbar-title .dot {
    width: 10px; height: 10px; border-radius: 50%;
    background: var(--navy); flex-shrink: 0;
}
.topbar-actions { display: flex; gap: 8px; flex-wrap: wrap; align-items: center; }

/* ── Buttons ── */
.btn-navy, .btn-outline, .btn-excel, .btn-print {
    border-radius: 8px; font-size: 13px; font-weight: 600; cursor: pointer;
    display: inline-flex; align-items: center; gap: 6px;
    text-decoration: none; transition: background .2s, transform .15s;
    padding: 9px 18px; border: none;
}
.btn-navy   { background: var(--navy); color: #fff; }
.btn-navy:hover { background: var(--navy-dk); transform: translateY(-1px); color: #fff; }
.btn-outline { background: transparent; color: var(--navy); border: 1.5px solid var(--navy); padding: 8px 16px; }
.btn-outline:hover { background: var(--navy-lt); color: var(--navy-dk); }
.btn-excel  { background: #166534; color: #fff; }
.btn-excel:hover { background: #14532d; }
.btn-print  { background: var(--navy); color: #fff; }
.btn-print:hover { background: var(--navy-dk); }

/* ── Filter card ── */
.filter-card {
    background: var(--white); border: 1px solid var(--border); border-radius: 14px;
    padding: 22px 28px; margin: 22px 32px; box-shadow: 0 2px 12px rgba(0,0,0,.04);
}
.filter-card h6 {
    font-size: 12px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--muted); margin-bottom: 16px;
    display: flex; align-items: center; gap: 6px;
}
.filter-section-title {
    font-size: 11px; font-weight: 700; text-transform: uppercase;
    letter-spacing: 1px; color: var(--navy); margin: 16px 0 10px;
    border-bottom: 1px solid var(--border); padding-bottom: 6px;
}
.filter-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; }
.filter-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-top: 14px; }
.filter-grid label { font-size: 12px; font-weight: 600; color: var(--muted); margin-bottom: 5px; display: block; }

/* native selects / inputs matching Select2 size */
.filter-native {
    width: 100%; height: 40px; border: 1.5px solid var(--border);
    border-radius: 8px; background: var(--bg); padding: 0 12px;
    font-size: 13px; color: var(--ink); outline: none;
    font-family: 'Kalimati', sans-serif !important;
}
.filter-native:focus { border-color: var(--navy); box-shadow: 0 0 0 3px rgba(30,58,95,.12); }

/* Select2 */
.select2-container { width: 100% !important; }
.select2-container--default .select2-selection--single {
    height: 40px; border: 1.5px solid var(--border); border-radius: 8px;
    display: flex; align-items: center; background: var(--bg);
}
.select2-container--default.select2-container--focus .select2-selection--single {
    border-color: var(--navy); box-shadow: 0 0 0 3px rgba(30,58,95,.12);
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 38px; color: var(--ink); font-size: 13px; padding-left: 12px;
}
.select2-container--default .select2-selection--single .select2-selection__arrow { height: 38px; }
.select2-dropdown { border: 1.5px solid var(--navy); border-radius: 8px; box-shadow: 0 8px 24px rgba(0,0,0,.12); }
.select2-container--default .select2-results__option--highlighted[aria-selected] { background: var(--navy); }

/* ── Stats bar ── */
.stats-bar { display: flex; gap: 14px; margin: 0 32px 18px; flex-wrap: wrap; }
.stat-pill {
    background: var(--white); border: 1px solid var(--border); border-radius: 10px;
    padding: 12px 20px; display: flex; flex-direction: column; gap: 3px;
    min-width: 150px; box-shadow: 0 1px 4px rgba(0,0,0,.04);
}
.stat-pill .val { font-size: 22px; font-weight: 700; color: var(--navy); line-height: 1; }
.stat-pill .lbl { font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: .7px; color: var(--muted); }
.stat-pill.gold .val { color: var(--gold); }

/* ── Prompt box ── */
.prompt-box {
    margin: 0 32px; background: var(--white); border: 1.5px dashed var(--border);
    border-radius: 14px; padding: 60px 20px; text-align: center; color: var(--muted);
}
.prompt-box i { font-size: 48px; margin-bottom: 14px; display: block; color: #cbd5e1; }
.prompt-box p { font-size: 15px; }

/* ── Screen table ── */
.table-wrap {
    margin: 0 32px; background: var(--white); border: 1px solid var(--border);
    border-radius: 14px; overflow: hidden; box-shadow: 0 2px 12px rgba(0,0,0,.05);
}
#reportTable { width: 100%; border-collapse: collapse; font-size: 12.5px; }
#reportTable thead tr { background: var(--navy); color: #fff; }
#reportTable thead th {
    padding: 11px 12px; font-size: 11px; font-weight: 700;
    text-transform: uppercase; letter-spacing: .4px; white-space: nowrap; border: none;
}
#reportTable tbody tr { border-bottom: 1px solid #f1f5f9; transition: background .15s; }
#reportTable tbody tr:hover { background: #eff6ff; }
#reportTable tbody td { padding: 11px 12px; color: var(--ink); vertical-align: middle; }
.td-emp  { font-weight: 600; }
.td-usd  { font-weight: 700; color: var(--green); white-space: nowrap; }
.td-days { text-align: center; }
.badge-yes  { background: var(--green-lt); color: var(--green); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-no   { background: #f1f5f9; color: var(--muted); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
.badge-extra { background: #fef3c7; color: var(--gold); padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
#reportTable tfoot tr { background: #eff6ff; border-top: 2px solid var(--navy); }
#reportTable tfoot td { padding: 12px; font-weight: 700; font-size: 13px; }
.tfoot-total { font-size: 14px; color: var(--navy); }
.empty-state { text-align: center; padding: 60px 20px; color: var(--muted); }
.empty-state i { font-size: 48px; margin-bottom: 12px; }

@media (max-width: 768px) {
    .filter-card, .stats-bar, .table-wrap, .prompt-box { margin-left: 16px; margin-right: 16px; }
    .topbar { padding: 14px 16px; }
    #reportTable { font-size: 11px; }
    #reportTable thead th, #reportTable tbody td { padding: 8px; }
}

/* ══════════════════════════
   PRINT STYLES
══════════════════════════ */
#printSection { display: none; }

@media print {
    .topbar, .filter-card, .stats-bar, .table-wrap,
    .prompt-box, .screen-only,
    header, nav, .navbar, .sidebar, #header, #nav,
    [class*="header"], [class*="navbar"], [class*="nav-"],
    [id*="header"], [id*="navbar"] { display: none !important; }

    #printSection { display: block !important; }

    body { background: #fff; padding: 0; margin: 0; font-size: 11pt; }

    .print-letterhead {
        text-align: center; border-bottom: 3px double #000;
        padding-bottom: 10px; margin-bottom: 12px;
    }
    .print-org-name { font-size: 18pt; font-weight: 700; letter-spacing: 0.5px; line-height: 1.3; }
    .print-org-sub  { font-size: 11pt; margin-top: 3px; }
    .print-doc-title {
        font-size: 14pt; font-weight: 700; text-align: center;
        margin: 10px 0 4px; text-decoration: underline; text-underline-offset: 4px;
    }
    .print-meta-bar {
        display: flex; justify-content: space-between; align-items: flex-start;
        font-size: 10pt; margin: 6px 0 12px; border: 1px solid #ccc;
        border-radius: 4px; padding: 7px 12px; background: #f9f9f9;
        flex-wrap: wrap; gap: 6px;
    }
    .print-meta-bar .meta-item { display: flex; flex-direction: column; gap: 1px; }
    .print-meta-bar .meta-label { font-size: 8pt; text-transform: uppercase; letter-spacing: 0.8px; color: #555; font-weight: 700; }
    .print-meta-bar .meta-value { font-size: 11pt; font-weight: 700; color: #000; }

    #printTable {
        width: 100%; border-collapse: collapse; font-size: 9.5pt; margin-top: 4px;
    }
    #printTable thead tr {
        background: #1e3a5f !important; color: #fff !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    #printTable thead th {
        padding: 7px 8px; font-size: 8.5pt; font-weight: 700;
        text-align: center; border: 1px solid #1e3a5f; white-space: nowrap; color: #fff !important;
    }
    #printTable tbody tr:nth-child(even) {
        background: #f0f4ff !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    #printTable tbody td { padding: 6px 8px; border: 1px solid #ccc; vertical-align: middle; color: #000; }
    #printTable tbody td.tc { text-align: center; }
    #printTable tbody td.tr { text-align: right; }
    #printTable tfoot tr {
        background: #e8f0fe !important;
        -webkit-print-color-adjust: exact; print-color-adjust: exact;
    }
    #printTable tfoot td { padding: 7px 8px; border: 1px solid #999; font-weight: 700; font-size: 10pt; }
    #printTable tfoot td.tr { text-align: right; }

    .print-grand-total {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        gap: 40px;
        border-top: 2px solid #1e3a5f;
        border-bottom: 2px solid #1e3a5f;
        padding: 7px 12px;
        margin-top: 0;
        font-size: 11pt;
        font-weight: 700;
        background: #e8f0fe !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
        page-break-inside: avoid;
        break-inside: avoid;
    }

    .print-signature-block {
        display: flex; justify-content: space-between;
        margin-top: 40px; padding-top: 10px; font-size: 10pt;
    }
    .print-sig-item { text-align: center; min-width: 150px; }
    .print-sig-line { border-top: 1px solid #000; margin-bottom: 4px; padding-top: 4px; }

    .print-footer {
        margin-top: 16px; border-top: 1px solid #ccc; padding-top: 6px;
        font-size: 8pt; color: #555; display: flex; justify-content: space-between;
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
        International TADA Report
        <?php if ($filterApplied && $selectedFYName): ?>
            <span style="font-size:13px;font-weight:500;color:var(--navy);margin-left:4px;">
                — आ.व. <?= htmlspecialchars($selectedFYName) ?>
            </span>
        <?php endif; ?>
    </div>
    <div class="topbar-actions">
        <?php if ($filterApplied && !empty($records)): ?>
        <button class="btn-excel" onclick="exportExcel()">
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

        <div class="filter-section-title"><i class="bi bi-person-lines-fill"></i> Employee &amp; Fiscal Year</div>
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

        <div class="filter-section-title"><i class="bi bi-globe2"></i> Destination</div>
        <div class="filter-grid">
            <div>
                <label>Country / देश</label>
                <select name="country" id="sel-country" class="select2-filter">
                    <option value="">— All Countries —</option>
                    <?php foreach ($countryList as $c): ?>
                        <option value="<?= htmlspecialchars($c['Country_id']) ?>"
                            <?= $filterCountry == $c['Country_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['Country_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label>City / शहर</label>
                <select name="city" id="sel-city" class="select2-filter">
                    <option value="">— All Cities —</option>
                    <?php foreach ($cityList as $c): ?>
                        <option value="<?= htmlspecialchars($c['City_id']) ?>"
                            <?= $filterCity == $c['City_id'] ? 'selected' : '' ?>>
                            <?= htmlspecialchars($c['City_name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="filter-section-title"><i class="bi bi-sliders"></i> Allowance &amp; Date</div>
        <div class="filter-grid">
            <div>
                <label>Dress Allowance</label>
                <select name="dress" id="sel-dress" class="select2-filter">
                    <option value="">— All —</option>
                    <option value="1" <?= $filterDress === '1' ? 'selected' : '' ?>>Yes (छ)</option>
                    <option value="0" <?= $filterDress === '0' ? 'selected' : '' ?>>No (छैन)</option>
                </select>
            </div>
            <div>
                <label>Extra 33% Country</label>
                <select name="extra33" id="sel-extra" class="select2-filter">
                    <option value="">— All —</option>
                    <option value="1" <?= $filterExtra33 === '1' ? 'selected' : '' ?>>Yes — Extra 33%</option>
                    <option value="0" <?= $filterExtra33 === '0' ? 'selected' : '' ?>>No — Standard</option>
                </select>
            </div>
            <div>
                <label>Travel From (AD)</label>
                <input type="date" name="date_from" class="filter-native"
                    value="<?= htmlspecialchars($filterDateFrom) ?>">
            </div>
            <div>
                <label>Travel To (AD)</label>
                <input type="date" name="date_to" class="filter-native"
                    value="<?= htmlspecialchars($filterDateTo) ?>">
            </div>
        </div>

        <div class="mt-3 d-flex gap-2 flex-wrap">
            <button type="submit" class="btn-navy">
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
    <i class="bi bi-airplane"></i>
    <p>Please select filter options above and click <strong>Apply Filter</strong> to load the report.</p>
</div>

<?php else:
$batchCount = count(array_unique(array_column($records, 'Batch_id')));
$empCount   = count(array_unique(array_column($records, 'EmpPersonalCode')));
$countryCount = count(array_unique(array_filter(array_column($records, 'Country_name'))));
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
        <span class="val"><?= $countryCount ?></span>
        <span class="lbl">Countries</span>
    </div>
    <div class="stat-pill gold">
        <span class="val" style="font-size:16px;">$ <?= number_format($grandTotalUSD, 2) ?></span>
        <span class="lbl">Grand Total USD</span>
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
            <th>Batch ID</th>
            <th>Chalani No</th>
            <th>Employee</th>
            <th>Designation</th>
            <th>Country</th>
            <th>City</th>
            <th>Travel Objective</th>
            <th>Travel Start (BS)</th>
            <th>Travel End (BS)</th>
            <th>Days</th>
            <th>USD Rate</th>
            <th>Total USD ($)</th>
            <th>Dress Allow.</th>
            <th>33% Extra</th>
        </tr>
    </thead>
    <tbody>
    <?php if (empty($records)): ?>
        <tr><td colspan="15"><div class="empty-state"><i class="bi bi-inbox d-block"></i>No records found.</div></td></tr>
    <?php else: $sn = 1; foreach ($records as $row):
        $startAD     = fmtDate($row['travelDateStart']);
        $endAD       = fmtDate($row['travelDateEnd']);
        $orderDateAD = fmtDate($row['form_date']);
        $tadaRate    = floatval($row['tadaInUSD'] ?? 0);
        $is33        = intval($row['extra33percent_country'] ?? 0);
        $displayRate = $is33 ? $tadaRate * 1.33 : $tadaRate;
        $totalUSD    = floatval($row['totalUSdrecevid'] ?? $row['tadaInUSD_Calc'] ?? 0);
        $desigNep    = $row['designationTypeInNepali'] ?? '-';
        $isDress     = intval($row['DressAllowance'] ?? 0);
    ?>
        <tr>
            <td class="text-muted" style="font-size:11px;"><?= $sn++ ?></td>
            <td style="font-weight:700;color:var(--navy);"><?= htmlspecialchars($row['Batch_id']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars(($row['fiscal_year'] ?? '') . '-' . ($row['Chalani_id'] ?? '')) ?></td>
            <td class="td-emp"><?= htmlspecialchars($row['EmpNameInNepali'] ?? $row['EmpName'] ?? $row['EmpPersonalCode']) ?></td>
            <td style="font-size:12px;"><?= htmlspecialchars($desigNep) ?></td>
            <td><i class="bi bi-globe2" style="color:var(--navy);margin-right:4px;"></i><?= htmlspecialchars($row['Country_name'] ?? '-') ?></td>
            <td><?= htmlspecialchars($row['City_name'] ?? '-') ?></td>
            <td style="max-width:180px;font-size:12px;"><?= htmlspecialchars($row['travel_objective'] ?? '') ?></td>
            <td class="td-days"><span class="bs-date" data-ad="<?= $startAD ?>"><?= $startAD ?></span></td>
            <td class="td-days"><span class="bs-date" data-ad="<?= $endAD ?>"><?= $endAD ?></span></td>
            <td class="td-days"><strong><?= fmtNum($row['totalday']) ?></strong></td>
            <td class="td-usd"><?= fmtNum($displayRate) ?></td>
            <td class="td-usd">$ <?= fmtNum($totalUSD) ?></td>
            <td class="text-center"><span class="<?= $isDress ? 'badge-yes' : 'badge-no' ?>"><?= $isDress ? 'छ' : 'छैन' ?></span></td>
            <td class="text-center"><span class="<?= $is33 ? 'badge-extra' : 'badge-no' ?>"><?= $is33 ? '+33%' : 'Standard' ?></span></td>
        </tr>
    <?php endforeach; endif; ?>
    </tbody>
    <?php if (!empty($records)): ?>
    <tfoot>
        <tr>
            <td colspan="12" class="text-end" style="font-size:12px;font-weight:700;">जम्मा (Grand Total)</td>
            <td class="tfoot-total">$ <?= number_format($grandTotalUSD, 2) ?></td>
            <td colspan="2"></td>
        </tr>
    </tfoot>
    <?php endif; ?>
</table>
</div>
</div><!-- /table-wrap -->


<!-- ══════════════════════════════════════════
     PRINT SECTION
══════════════════════════════════════════ -->
<div id="printSection">

    <!-- Letterhead -->
    <div class="print-letterhead">
        <div class="print-org-sub">नेपाल धितोपत्र बोर्ड</div>
        <div class="print-org-sub">खुमत्लर, ललितपुर</div>
    </div>

    <!-- Document title -->
    <div class="print-doc-title">विदेश भ्रमण भत्ता (International TADA) विवरण</div>

    <!-- Print table -->
    <table id="printTable">
        <thead>
            <tr>
                <th style="width:28px;">सि.नं.</th>
                <th>चलानी नं.</th>
                <th>कर्मचारीको नाम</th>
                <th>पद</th>
                <th>देश</th>
                <th>भ्रमणको उद्देश्य</th>
                <th>भ्रमण सुरु</th>
                <th>भ्रमण अन्त्य</th>
                <th style="width:34px;">दिन</th>
                <th>दर ($)</th>
                <th>जम्मा ($)</th>
                <th style="width:44px;">पोशाक</th>
                <th style="width:50px;">३३% थप</th>
            </tr>
        </thead>
        <tbody>
        <?php if (empty($records)): ?>
            <tr><td colspan="13" style="text-align:center;padding:20px;">कुनै रेकर्ड भेटिएन।</td></tr>
        <?php else: $sn2 = 1; foreach ($records as $row):
            $startAD2     = fmtDate($row['travelDateStart']);
            $endAD2       = fmtDate($row['travelDateEnd']);
            $tadaRate2    = floatval($row['tadaInUSD'] ?? 0);
            $is332        = intval($row['extra33percent_country'] ?? 0);
            $displayRate2 = $is332 ? $tadaRate2 * 1.33 : $tadaRate2;
            $totalUSD2    = floatval($row['totalUSdrecevid'] ?? $row['tadaInUSD_Calc'] ?? 0);
            $desigNep2    = $row['designationTypeInNepali'] ?? '-';
            $isDress2     = intval($row['DressAllowance'] ?? 0);
        ?>
            <tr>
                <td class="tc"><?= $sn2++ ?></td>
                <td class="tc"><?= htmlspecialchars(($row['fiscal_year'] ?? '') . '-' . ($row['Chalani_id'] ?? '')) ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($row['EmpNameInNepali'] ?? $row['EmpName'] ?? $row['EmpPersonalCode']) ?></td>
                <td><?= htmlspecialchars($desigNep2) ?></td>
                <td><?= htmlspecialchars($row['Country_name'] ?? '-') ?></td>
                <td style="max-width:130px;"><?= htmlspecialchars($row['travel_objective'] ?? '') ?></td>
                <td class="tc"><span class="bs-date-print" data-ad="<?= $startAD2 ?>"><?= $startAD2 ?></span></td>
                <td class="tc"><span class="bs-date-print" data-ad="<?= $endAD2 ?>"><?= $endAD2 ?></span></td>
                <td class="tc"><strong><?= fmtNum($row['totalday']) ?></strong></td>
                <td class="tr"><?= fmtNum($displayRate2) ?></td>
                <td class="tr"><?= fmtNum($totalUSD2) ?></td>
                <td class="tc"><?= $isDress2 ? 'छ' : 'छैन' ?></td>
                <td class="tc"><?= $is332 ? '३३% छ' : 'छैन' ?></td>
            </tr>
        <?php endforeach; endif; ?>
        </tbody>
        </tbody>
    </table>

    <!-- Grand total — outside table so it never repeats on multi-page print -->
    <?php if (!empty($records)): ?>
    <div class="print-grand-total">
        <span>जम्मा (Grand Total)</span>
        <span>$ <?= number_format($grandTotalUSD, 2) ?></span>
    </div>
    <?php endif; ?>

   



</div><!-- /printSection -->

<?php endif; ?>

<!-- Scripts -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.0/dist/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<script>
$(document).ready(function () {
    $('.select2-filter').select2({ placeholder: 'Search...', allowClear: true, width: '100%' });
});

/* ── BS date conversion ── */
window.addEventListener('load', function () {
    setTimeout(function () {
        document.querySelectorAll('.bs-date').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
                el.setAttribute('data-bs-val', bs);
            } catch(e) {}
        });
        document.querySelectorAll('.bs-date-print').forEach(function (el) {
            var ad = el.getAttribute('data-ad');
            if (!ad) return;
            try {
                var bs = NepaliFunctions.AD2BS(ad, "YYYY-MM-DD", "YYYY/MM/DD");
                el.textContent = bs;
            } catch(e) {}
        });
        try {
            var todayAD = new Date().toISOString().slice(0,10);
            var todayBS = NepaliFunctions.AD2BS(todayAD, "YYYY-MM-DD", "YYYY/MM/DD");
            var el2 = document.getElementById('printFooterDate');
            if (el2) el2.textContent = 'मुद्रण मिति: ' + todayBS;
        } catch(e) {
            var el2 = document.getElementById('printFooterDate');
            if (el2) el2.textContent = 'Printed: ' + new Date().toLocaleDateString();
        }
    }, 450);
});

/* ── Print ── */
function triggerPrint() { window.print(); }

/* ── Excel export ── */
function exportExcel() {
    var table = document.getElementById('reportTable');
    var rows  = [];

    rows.push(['विदेश भ्रमण भत्ता (International TADA) विवरण']);
    rows.push(['आर्थिक वर्ष:', '<?= addslashes($selectedFYName) ?>', '', 'Grand Total USD:', '$ <?= number_format($grandTotalUSD, 2) ?>']);
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
        fRow[11] = 'जम्मा (Grand Total)';
        var fc   = tfoot.querySelectorAll('td');
        fRow[12] = fc[1] ? fc[1].innerText.trim() : '';
        rows.push(fRow);
    }

    var wb = XLSX.utils.book_new();
    var ws = XLSX.utils.aoa_to_sheet(rows);
    ws['!cols'] = [4,10,14,22,18,16,14,28,13,13,5,12,14,9,10].map(function(w){ return {wch:w}; });
    ws['!merges'] = [{ s:{r:0,c:0}, e:{r:0,c:14} }];

    XLSX.utils.book_append_sheet(wb, ws, 'International TADA');
    XLSX.writeFile(wb, 'IntlTADA_<?= addslashes($selectedFYName) ?>_' + new Date().toISOString().slice(0,10) + '.xlsx');
}
</script>
</body>
</html>