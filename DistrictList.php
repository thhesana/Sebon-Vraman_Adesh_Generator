<?php
include 'db.php';
include 'header.php';

/* =========================
   Pagination Settings
========================= */
$limit = 5;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

/* =========================
   Search Filter
========================= */
$search = isset($_GET['search']) ? trim($_GET['search']) : "";

/* =========================
   Count Total Rows
========================= */
$count_sql = "
    SELECT COUNT(*) AS total
    FROM DistrictMaster
    WHERE District_name LIKE ? OR District_name_nepali LIKE ?
";
$params = ["%$search%", "%$search%"];
$count_stmt = sqlsrv_query($conn, $count_sql, $params);
$count_row = sqlsrv_fetch_array($count_stmt, SQLSRV_FETCH_ASSOC);

$total_rows = $count_row['total'];
$total_pages = ceil($total_rows / $limit);

/* =========================
   Fetch Paginated Data
========================= */
$sql = "
    SELECT District_id, District_name, District_name_nepali
    FROM DistrictMaster
    WHERE District_name LIKE ? OR District_name_nepali LIKE ?
    ORDER BY District_id desc
    OFFSET ? ROWS FETCH NEXT ? ROWS ONLY
";
$params = ["%$search%", "%$search%", $offset, $limit];
$result = sqlsrv_query($conn, $sql, $params);

if ($result === false) {
    die("SQL Error: " . print_r(sqlsrv_errors(), true));
}
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>District List</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f7fa;
        }

        .container {
            width: 75%;
            margin: 30px auto;
            background: #ffffff;
            padding: 20px;
            border-radius: 6px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.1);
        }

        .header-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }

        .header-row h2 {
            margin: 0;
            color: #004080;
        }

        .add-btn {
            background-color: #004080;
            color: #ffffff;
            padding: 8px 14px;
            text-decoration: none;
            border-radius: 4px;
            font-size: 14px;
        }

        .add-btn:hover {
            background-color: #003060;
        }

        .search-bar {
            margin-bottom: 10px;
            display: flex;
            justify-content: flex-start;
            gap: 6px;
        }

        #searchBox {
            width: 250px;
            padding: 8px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #004080;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #f2f2f2;
        }

        .pagination {
            margin-top: 15px;
            text-align: center;
        }

        .pagination a,
        .pagination span {
            padding: 6px 12px;
            margin: 2px;
            text-decoration: none;
            border: 1px solid #004080;
            color: #004080;
            border-radius: 3px;
        }

        .pagination .active {
            background-color: #004080;
            color: white;
        }
    </style>
</head>

<body>

<div class="container">

    <!-- Header -->
    <div class="header-row">
        <h2>District List</h2>
        <a href="add_district.php" class="add-btn">+ Add New District</a>
    </div>

    <!-- Search -->
    <form method="GET" class="search-bar">
        <input type="text" name="search" id="searchBox"
               placeholder="Search district..."
               value="<?php echo htmlspecialchars($search); ?>">
        <input type="submit" value="Search">
    </form>

    <!-- Table -->
    <table>
        <tr>
            <th>SN</th>
            <th>District Name (English)</th>
            <th>District Name (Nepali)</th>
        </tr>

        <?php
        $sn = $offset + 1;
        while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_ASSOC)):
        ?>
            <tr>
                <td><?= $sn++; ?></td>
                <td><?= htmlspecialchars($row['District_name']); ?></td>
                <td><?= htmlspecialchars($row['District_name_nepali']); ?></td>
            </tr>
        <?php endwhile; ?>

        <?php if ($total_rows == 0): ?>
            <tr>
                <td colspan="3" style="text-align:center;">No records found.</td>
            </tr>
        <?php endif; ?>
    </table>

    <!-- Pagination -->
    <div class="pagination">
        <?php if ($page > 1): ?>
            <a href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Prev</a>
        <?php endif; ?>

        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
            <?php if ($i == $page): ?>
                <span class="active"><?= $i ?></span>
            <?php else: ?>
                <a href="?page=<?= $i ?>&search=<?= urlencode($search) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php endfor; ?>

        <?php if ($page < $total_pages): ?>
            <a href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
        <?php endif; ?>
    </div>

</div>

</body>
</html>

<?php sqlsrv_close($conn); ?>
