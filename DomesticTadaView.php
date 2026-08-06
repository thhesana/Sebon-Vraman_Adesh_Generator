<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once 'db.php';
include 'HEADER.php';

if ($conn === false) {
    die("<div style='background: #fee; padding: 20px; border-radius: 5px; color: #c00;'>
        <h3>❌ Database Connection Lost!</h3>
        <p>Please refresh the page or contact administrator.</p>
        </div>");
}

// Get search parameter
$searchName = isset($_GET['search']) ? trim($_GET['search']) : '';

// Pagination settings - BATCH-BASED pagination
$batchesPerPage = 5; // Show 5 complete batches per page
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;

// Build WHERE clause for search
$whereClause = "";
$params = [];
if (!empty($searchName)) {
    $whereClause = "WHERE e.EmpName LIKE ?";
    $params[] = '%' . $searchName . '%';
}

// Step 1: Get distinct batches with their earliest created date (for ordering)
$batchSql = "
SELECT DISTINCT 
    dt.domestic_Batch_id,
    MIN(dt.domestic_createddate) as batch_created_date
FROM DomesticTada dt
LEFT JOIN Employee_Information e ON dt.EmpPersonalCode = e.EmpPersonalCode
$whereClause
GROUP BY dt.domestic_Batch_id
ORDER BY batch_created_date DESC, dt.domestic_Batch_id DESC
";

$batchStmt = sqlsrv_query($conn, $batchSql, $params);
if ($batchStmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$allBatches = [];
while ($batchRow = sqlsrv_fetch_array($batchStmt, SQLSRV_FETCH_ASSOC)) {
    $allBatches[] = $batchRow['domestic_Batch_id'];
}

$totalBatches = count($allBatches);
$totalPages = ceil($totalBatches / $batchesPerPage);

// Step 2: Get batches for current page
$startIndex = ($currentPage - 1) * $batchesPerPage;
$currentPageBatches = array_slice($allBatches, $startIndex, $batchesPerPage);

// Step 3: If we have batches to display, get all records for those batches
$records = [];
$totalRecordsOnPage = 0;

if (!empty($currentPageBatches)) {
    // Create placeholders for IN clause
    $placeholders = implode(',', array_fill(0, count($currentPageBatches), '?'));
    
    $recordsSql = "
    SELECT 
        dt.domestic_tada_id,
        dt.domestic_Batch_id,
        dt.domestic_Chalani_id,
        dt.domestic_form_date,
        dt.EmpPersonalCode,
        e.EmpName,
        e.Designation,
        e.LevelName,
        dt.District_id,
        dm.District_name,
        CASE 
            WHEN dt.domestic_isTwentyPercentExtra = 1 THEN 'YES'
            ELSE 'NO'
        END AS isTwentyPercentExtra,
        dt.domestic_travel_objective,
        dt.domestic_travelDateStart,
        dt.domestic_travelDateEnd,
        dt.domestic_totalday,
        dt.domestic_tada,
        dtd.DomesticTadaDefinerMasterBylevel_name,
        dtd.tadaInNepali,
        dt.domestic_createddate,
        u.username as created_by_name
    FROM DomesticTada dt
    LEFT JOIN Employee_Information e ON dt.EmpPersonalCode = e.EmpPersonalCode
    LEFT JOIN DistrictMaster dm ON dt.District_id = dm.District_id
    LEFT JOIN DomesticTadaDefinerMasterBylevel dtd ON e.LevelName = dtd.DomesticTadaDefinerMasterBylevel_name
    LEFT JOIN Users u ON dt.domestic_createdBy = u.user_id
    WHERE dt.domestic_Batch_id IN ($placeholders)
    ORDER BY dt.domestic_Batch_id DESC, dt.domestic_createddate DESC, dt.domestic_tada_id ASC
    ";
    
    $stmt = sqlsrv_query($conn, $recordsSql, $currentPageBatches);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $records[] = $row;
        $totalRecordsOnPage++;
    }
}

// Function to format date
function formatDate($dateObj) {
    if (!$dateObj) return '-';
    return strtoupper($dateObj->format('d-M-Y'));
}

// Function to format number
function formatNumber($number) {
    if ($number == floor($number)) {
        return number_format($number, 0);
    } else {
        return number_format($number, 2);
    }
}

// Calculate total records
$totalRecordsSql = "SELECT COUNT(*) as total FROM DomesticTada dt
                    LEFT JOIN Employee_Information e ON dt.EmpPersonalCode = e.EmpPersonalCode
                    $whereClause";
$totalRecordsStmt = sqlsrv_query($conn, $totalRecordsSql, $params);
$totalRecords = 0;
if ($totalRecordsRow = sqlsrv_fetch_array($totalRecordsStmt, SQLSRV_FETCH_ASSOC)) {
    $totalRecords = $totalRecordsRow['total'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>DOMESTIC TRAVEL RECORDS</title>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Inter', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            padding: 30px 0;
        }

        .main-container {
            background: #ffffff;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            animation: fadeIn 0.5s ease-in;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            flex-wrap: wrap;
            gap: 20px;
        }

        .page-title {
            color: #2d3748;
            font-size: 32px;
            font-weight: 700;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .btn-gradient {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        .btn-gradient:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            color: white;
        }

        .search-container {
            background: linear-gradient(135deg, #f0f4ff 0%, #e9f0ff 100%);
            padding: 20px;
            border-radius: 15px;
            margin-bottom: 25px;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.1);
        }

        .search-form {
            display: flex;
            gap: 15px;
            align-items: center;
            flex-wrap: wrap;
        }

        .search-input-wrapper {
            flex: 1;
            min-width: 250px;
            position: relative;
        }

        .search-input {
            width: 100%;
            padding: 12px 45px 12px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s ease;
            background: white;
        }

        .search-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
        }

        .search-icon {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            color: #a0aec0;
            pointer-events: none;
        }

        .btn-search {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
            white-space: nowrap;
        }

        .btn-search:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .btn-clear {
            background: #e2e8f0;
            color: #4a5568;
            border: none;
            padding: 12px 24px;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            white-space: nowrap;
        }

        .btn-clear:hover {
            background: #cbd5e0;
            transform: translateY(-2px);
        }

        .search-results-info {
            margin-top: 15px;
            padding: 12px 20px;
            background: white;
            border-radius: 10px;
            color: #2d3748;
            font-weight: 500;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .table-container {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
            margin-bottom: 25px;
        }

        .table-responsive {
            border-radius: 15px;
            overflow-x: auto;
        }

        .custom-table {
            margin: 0;
            width: 100%;
            border-collapse: collapse;
        }

        .custom-table thead {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .custom-table thead th {
            padding: 18px 16px;
            text-align: left;
            font-weight: 600;
            text-transform: uppercase;
            font-size: 13px;
            letter-spacing: 0.5px;
            white-space: nowrap;
            border: none;
            position: sticky;
            top: 0;
            z-index: 10;
        }

        .custom-table tbody tr {
            border-bottom: 1px solid #e2e8f0;
            transition: all 0.3s ease;
        }

        .custom-table tbody tr:hover {
            background: linear-gradient(90deg, #f7fafc 0%, #edf2f7 100%);
            transform: scale(1.001);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
        }

        .custom-table tbody tr.batch-group {
            border-top: 3px solid #667eea;
            background: linear-gradient(90deg, #f0f4ff 0%, #e9f0ff 100%);
            font-weight: 500;
        }

        .custom-table tbody tr.batch-group:hover {
            background: linear-gradient(90deg, #e6edff 0%, #dde7ff 100%);
        }

        .custom-table tbody td {
            padding: 16px;
            color: #2d3748;
            vertical-align: middle;
            font-size: 14px;
            border: none;
        }

        .custom-table tbody tr:nth-child(even):not(.batch-group) {
            background: #fafafa;
        }

        .batch-id-column {
            font-weight: 700;
            color: #667eea;
            font-size: 15px;
        }

        .employee-column {
            font-weight: 500;
            color: #2d3748;
        }

        .district-column {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .tada-column {
            font-weight: 700;
            color: #2f855a;
            font-size: 16px;
        }

        .badge-yes {
            background: linear-gradient(135deg, #48bb78 0%, #38a169 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-no {
            background: #e2e8f0;
            color: #718096;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .action-buttons {
            display: flex;
            flex-direction: column;
            gap: 8px;
            align-items: stretch;
        }

        .btn-action {
            padding: 6px 14px;
            border-radius: 8px;
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            transition: all 0.3s ease;
            border: none;
            white-space: nowrap;
            text-align: center;
        }

        .btn-edit {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }

        .btn-edit:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(245, 87, 108, 0.4);
            color: white;
        }

        .btn-print {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }

        .btn-print:hover {
            transform: scale(1.05);
            box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
            color: white;
        }

        .table-responsive::-webkit-scrollbar {
            height: 10px;
        }

        .table-responsive::-webkit-scrollbar-track {
            background: #f1f1f1;
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 10px;
        }

        .table-responsive::-webkit-scrollbar-thumb:hover {
            background: linear-gradient(135deg, #764ba2 0%, #667eea 100%);
        }

        .pagination-container {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 0;
            flex-wrap: wrap;
            gap: 15px;
        }

        .pagination-info {
            color: #4a5568;
            font-size: 14px;
            font-weight: 500;
        }

        .pagination {
            display: flex;
            gap: 5px;
            list-style: none;
            margin: 0;
            padding: 0;
        }

        .pagination li a {
            padding: 8px 14px;
            background: #f7fafc;
            color: #667eea;
            border: 2px solid #e2e8f0;
            border-radius: 8px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13px;
            transition: all 0.3s ease;
            display: inline-block;
        }

        .pagination li a:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
            transform: translateY(-2px);
        }

        .pagination li.active a {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-color: #667eea;
        }

        .pagination li.disabled a {
            background: #e2e8f0;
            color: #a0aec0;
            cursor: not-allowed;
            pointer-events: none;
        }

        @media (max-width: 768px) {
            .main-container {
                padding: 20px;
                border-radius: 15px;
            }

            .page-title {
                font-size: 24px;
            }

            .search-form {
                flex-direction: column;
            }

            .search-input-wrapper {
                width: 100%;
            }

            .btn-search,
            .btn-clear {
                width: 100%;
            }

            .custom-table thead th,
            .custom-table tbody td {
                padding: 12px 10px;
                font-size: 12px;
            }

            .btn-action {
                padding: 5px 10px;
                font-size: 11px;
            }

            .pagination-container {
                flex-direction: column;
                gap: 15px;
            }

            .pagination {
                flex-wrap: wrap;
                justify-content: center;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid px-lg-5">
    <div class="main-container">
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-house-fill"></i>
                DOMESTIC TRAVEL RECORDS
            </h1>
            <a href="AddDomesticTada.php" class="btn-gradient">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Batch
            </a>
        </div>

        <!-- Search Filter -->
        <div class="search-container">
            <form method="GET" action="" class="search-form">
                <div class="search-input-wrapper">
                    <input 
                        type="text" 
                        name="search" 
                        class="search-input" 
                        placeholder="Search by Employee Name..."
                        value="<?php echo htmlspecialchars($searchName); ?>"
                    >
                    <i class="bi bi-search search-icon"></i>
                </div>
                <button type="submit" class="btn-search">
                    <i class="bi bi-search"></i>
                    Search
                </button>
                <?php if (!empty($searchName)): ?>
                    <a href="?" class="btn-clear">
                        <i class="bi bi-x-circle"></i>
                        Clear
                    </a>
                <?php endif; ?>
            </form>
            
            <?php if (!empty($searchName)): ?>
                <div class="search-results-info">
                    <i class="bi bi-info-circle-fill" style="color: #667eea;"></i>
                    Showing results for: <strong>"<?php echo htmlspecialchars($searchName); ?>"</strong>
                    (<?php echo $totalRecords; ?> records in <?php echo $totalBatches; ?> batches)
                </div>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Employee</th>
                            <th>District</th>
                            <th>Travel Objective</th>
                            <th>Travel Date</th>
                            <th>Days</th>
                            <th>Total TADA</th>
                            <th>20% Extra</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (empty($records)) {
                            echo '<tr id="noResults"><td colspan="9" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e0;"></i>
                                    <p class="mt-3 text-muted">No records found</p>
                                  </td></tr>';
                        } else {
                            $currentBatch = null;
                            $editedBatches = [];
                            
                            foreach ($records as $row) { 
                                $batchClass = '';
                                $isFirstInBatch = false;
                                
                                if ($currentBatch !== $row['domestic_Batch_id']) {
                                    $currentBatch = $row['domestic_Batch_id'];
                                    $batchClass = 'batch-group';
                                    $isFirstInBatch = true;
                                }
                        ?>
                            <tr class="<?php echo $batchClass; ?>">
                                <td class="batch-id-column"><?php echo htmlspecialchars($row['domestic_Batch_id']); ?></td>
                                <td class="employee-column"><?php echo htmlspecialchars($row['EmpName'] ?? $row['EmpPersonalCode']); ?></td>
                                <td>
                                    <span class="district-column">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <?php echo htmlspecialchars($row['District_name']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($row['domestic_travel_objective']); ?></td>
                                <td><?php echo formatDate($row['domestic_travelDateStart']); ?> - <?php echo formatDate($row['domestic_travelDateEnd']); ?></td>
                                <td class="text-center"><strong><?php echo formatNumber($row['domestic_totalday']); ?></strong></td>
                                <td class="tada-column">NPR <?php echo formatNumber($row['domestic_tada']); ?></td>
                                <td class="text-center">
                                    <span class="<?php echo $row['isTwentyPercentExtra'] == 'YES' ? 'badge-yes' : 'badge-no'; ?>">
                                        <?php echo $row['isTwentyPercentExtra']; ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <?php if ($isFirstInBatch): ?>
                                            <a href="EditDomesticTada.php?batch_id=<?php echo urlencode($row['domestic_Batch_id']); ?>" class="btn-action btn-edit">
                                                <i class="bi bi-pencil-fill"></i> Edit
                                            </a>
                                            <a href="PrintDomesticTada.php?batch_id=<?php echo urlencode($row['domestic_Batch_id']); ?>" class="btn-action btn-print" target="_blank">
                                                <i class="bi bi-printer-fill"></i> Print
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php 
                            }
                        } 
                        ?>
                    </tbody>
                </table>
            </div>
        </div>

        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?php echo count($currentPageBatches); ?> batches 
                (<?php echo $totalRecordsOnPage; ?> records) 
                | Total: <?php echo $totalBatches; ?> batches (<?php echo $totalRecords; ?> records)
            </div>
            
            <ul class="pagination">
                <li class="<?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                    <a href="?page=<?php echo $currentPage - 1; ?><?php echo !empty($searchName) ? '&search=' . urlencode($searchName) : ''; ?>">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>
                </li>

                <?php
                $range = 2;
                $startPage = max(1, $currentPage - $range);
                $endPage = min($totalPages, $currentPage + $range);

                if ($startPage > 1) {
                    echo '<li><a href="?page=1' . (!empty($searchName) ? '&search=' . urlencode($searchName) : '') . '">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="disabled"><a>...</a></li>';
                    }
                }

                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a href="?page=<?php echo $i; ?><?php echo !empty($searchName) ? '&search=' . urlencode($searchName) : ''; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor;

                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="disabled"><a>...</a></li>';
                    }
                    echo '<li><a href="?page=' . $totalPages . (!empty($searchName) ? '&search=' . urlencode($searchName) : '') . '">' . $totalPages . '</a></li>';
                }
                ?>

                <li class="<?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                    <a href="?page=<?php echo $currentPage + 1; ?><?php echo !empty($searchName) ? '&search=' . urlencode($searchName) : ''; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>