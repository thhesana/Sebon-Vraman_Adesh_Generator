<?php
// Include DB connection FIRST - this will start the session
include 'db.php';
include 'header.php';

// Pagination settings
$recordsPerPage = 7;
$currentPage = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$offset = ($currentPage - 1) * $recordsPerPage;

// Get total count for pagination
$countSql = "SELECT COUNT(*) as total FROM International_tada";
$countStmt = sqlsrv_query($conn, $countSql);
$totalRecords = 0;
if ($countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC)) {
    $totalRecords = $countRow['total'];
}
$totalPages = ceil($totalRecords / $recordsPerPage);

// SQL query with calculated TADA and pagination
$sql = "
SELECT 
    i.International_tada_id,
    i.Batch_id,
    i.Chalani_id,
    i.form_date,
    i.EmpPersonalCode,
    emp.EmpName AS EmployeeName,
    c.Country_name AS Country,
    ci.City_name AS City,
    i.travel_objective,
    i.travelDateStart,
    i.travelDateEnd,
    t.TadaDefinerMasterBylevel_name AS TADA_Level,
    t.tadaInUSD,
    CAST(DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5 AS DECIMAL(5,2)) AS totalday,
    
    CASE 
        WHEN c.extra33percent_country = 1 
            THEN (t.tadaInUSD + (t.tadaInUSD * 0.33)) * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
        ELSE t.tadaInUSD * (DATEDIFF(DAY, i.travelDateStart, i.travelDateEnd) + 1 - 0.5)
    END AS tadaInUSD_Final,
    
    CASE WHEN i.DressAllowance = 1 THEN 'Yes' ELSE 'No' END AS DressAllowance,
    
    CASE 
        WHEN c.extra33percent_country = 1 THEN 'Receives Extra 33%'
        ELSE 'No Extra'
    END AS Extra33Percent,
    
    i.createdBy,
    u.username AS CreatedByName,
    i.createddate
FROM International_tada i
LEFT JOIN CountryMaster c ON i.Country_id = c.Country_id
LEFT JOIN CityMaster ci ON i.City_id = ci.City_id
LEFT JOIN TadaDefinerMasterBylevel t ON i.TadaDefinerMasterBylevel_id = t.TadaDefinerMasterBylevel_id
LEFT JOIN Employee_Information emp ON i.EmpPersonalCode = emp.EmpPersonalCode
LEFT JOIN Users u ON i.createdBy = u.user_id
ORDER BY i.createddate DESC, i.Batch_id DESC
OFFSET $offset ROWS
FETCH NEXT $recordsPerPage ROWS ONLY
";

$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

$records = [];
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    $records[] = $row;
}

// Function to format date
function formatDate($dateObj) {
    if (!$dateObj) return '-';
    return strtoupper($dateObj->format('d-M-Y'));
}

// Function to format number (show 2 decimals only if needed)
function formatNumber($number) {
    if ($number == floor($number)) {
        return number_format($number, 0);
    } else {
        return number_format($number, 2);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>INTERNATIONAL TRAVEL RECORDS</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <!-- Google Fonts -->
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

        .country-column {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .usd-column {
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

        .badge-extra {
            background: linear-gradient(135deg, #f6ad55 0%, #dd6b20 100%);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            display: inline-block;
        }

        .badge-no-extra {
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

        .created-by {
            color: #4a5568;
            font-size: 13px;
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

        /* Pagination Styles */
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

            .custom-table thead th,
            .custom-table tbody td {
                padding: 12px 10px;
                font-size: 12px;
            }

            .btn-action {
                padding: 5px 10px;
                font-size: 11px;
            }
        }
    </style>
</head>
<body>

<div class="container-fluid px-lg-5">
    <div class="main-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">
                <i class="bi bi-airplane-fill"></i>
                INTERNATIONAL TRAVEL RECORDS
            </h1>
            <a href="add_international_tada.php" class="btn-gradient">
                <i class="bi bi-plus-circle-fill"></i>
                Add New Batch
            </a>
        </div>

        <!-- Table Container -->
        <div class="table-container">
            <div class="table-responsive">
                <table class="custom-table" id="tadaTable">
                    <thead>
                        <tr>
                            <th>Batch ID</th>
                            <th>Employee</th>
                            <th>Country</th>
							<th>Travel Objective</th>
                            <th>Travel Date</th>
                            
                            <th>Days</th>
                            <th>Total USD</th>
                            <th>Dress Allow.</th>
                            <th>Extra 33%</th>
                            
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php 
                        if (empty($records)) {
                            echo '<tr id="noResults"><td colspan="11" class="text-center py-5">
                                    <i class="bi bi-inbox" style="font-size: 48px; color: #cbd5e0;"></i>
                                    <p class="mt-3 text-muted">No records found</p>
                                  </td></tr>';
                        } else {
                            $currentBatch = null;
                            $editedBatches = [];
                            
                            foreach ($records as $row) { 
                                $batchClass = '';
                                if ($currentBatch !== $row['Batch_id']) {
                                    $currentBatch = $row['Batch_id'];
                                    $batchClass = 'batch-group';
                                }
                        ?>
                            <tr class="<?php echo $batchClass; ?>">
                                <td class="batch-id-column"><?php echo $row['Batch_id']; ?></td>
                                <td class="employee-column"><?php echo htmlspecialchars($row['EmployeeName'] ?? $row['EmpPersonalCode']); ?></td>
                                <td>
                                    <span class="country-column">
                                        <i class="bi bi-geo-alt-fill"></i>
                                        <?php echo htmlspecialchars($row['Country']); ?>
                                    </span>
                                </td>
								 <td><?php echo htmlspecialchars($row['travel_objective']); ?></td>
                                <td><?php echo formatDate($row['travelDateStart']); ?>-<?php echo formatDate($row['travelDateEnd']); ?></td>
                                
                                <td class="text-center"><strong><?php echo formatNumber($row['totalday']); ?></strong></td>
                                <td class="usd-column">$<?php echo formatNumber($row['tadaInUSD_Final']); ?></td>
                                <td class="text-center">
                                    <span class="<?php echo $row['DressAllowance'] == 'Yes' ? 'badge-yes' : 'badge-no'; ?>">
                                        <?php echo $row['DressAllowance']; ?>
                                    </span>
                                </td>
                                <td class="text-center">
                                    <span class="<?php echo strpos($row['Extra33Percent'], 'Receives') !== false ? 'badge-extra' : 'badge-no-extra'; ?>">
                                        <?php echo $row['Extra33Percent']; ?>
                                    </span>
                                </td>
                                
                                <td>
                                    <div class="action-buttons">
                                        <?php 
                                        if ($row['Batch_id'] && !in_array($row['Batch_id'], $editedBatches)) {
                                            $editedBatches[] = $row['Batch_id'];
                                        ?>
                                            <a href="edit_international_tada.php?batch_id=<?php echo $row['Batch_id']; ?>" class="btn-action btn-edit">
                                                <i class="bi bi-pencil-fill"></i> Edit
                                            </a>
                                            <a href="print_international_tada.php?batch_id=<?php echo $row['Batch_id']; ?>" class="btn-action btn-print" target="_blank">
                                                <i class="bi bi-printer-fill"></i> Print
                                            </a>
                                        <?php } ?>
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

        <!-- Pagination -->
        <?php if ($totalPages > 1): ?>
        <div class="pagination-container">
            <div class="pagination-info">
                Showing <?php echo (($currentPage - 1) * $recordsPerPage) + 1; ?> 
                to <?php echo min($currentPage * $recordsPerPage, $totalRecords); ?> 
                of <?php echo $totalRecords; ?> records
            </div>
            
            <ul class="pagination">
                <!-- Previous Button -->
                <li class="<?php echo $currentPage == 1 ? 'disabled' : ''; ?>">
                    <a href="?page=<?php echo $currentPage - 1; ?>">
                        <i class="bi bi-chevron-left"></i> Prev
                    </a>
                </li>

                <?php
                // Calculate page range to show
                $range = 2;
                $startPage = max(1, $currentPage - $range);
                $endPage = min($totalPages, $currentPage + $range);

                // First page
                if ($startPage > 1) {
                    echo '<li><a href="?page=1">1</a></li>';
                    if ($startPage > 2) {
                        echo '<li class="disabled"><a>...</a></li>';
                    }
                }

                // Page numbers
                for ($i = $startPage; $i <= $endPage; $i++): ?>
                    <li class="<?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                <?php endfor;

                // Last page
                if ($endPage < $totalPages) {
                    if ($endPage < $totalPages - 1) {
                        echo '<li class="disabled"><a>...</a></li>';
                    }
                    echo '<li><a href="?page=' . $totalPages . '">' . $totalPages . '</a></li>';
                }
                ?>

                <!-- Next Button -->
                <li class="<?php echo $currentPage == $totalPages ? 'disabled' : ''; ?>">
                    <a href="?page=<?php echo $currentPage + 1; ?>">
                        Next <i class="bi bi-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Bootstrap 5 JS Bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</body>
</html>