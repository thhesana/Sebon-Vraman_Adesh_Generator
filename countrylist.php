<?php
include 'db.php';
include 'HEADER.php';

// ------------------------------
// PAGINATION SETTINGS
// ------------------------------
$limit = 5; // records per page
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

$offset = ($page - 1) * $limit;

// ------------------------------
// SEARCH FILTER
// ------------------------------
$search = isset($_GET['search']) ? $_GET['search'] : "";
$searchQuery = "";

if (!empty($search)) {
    $searchQuery = "WHERE Country_name LIKE ?";
    $params = ["%$search%"];
} else {
    $searchQuery = "";
    $params = [];
}

// ------------------------------
// COUNT TOTAL RECORDS (for pagination)
// ------------------------------
$countSql = "SELECT COUNT(*) AS total FROM CountryMaster $searchQuery";
$countStmt = sqlsrv_query($conn, $countSql, !empty($params) ? $params : []);

$countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
$totalRecords = $countRow['total'];
$totalPages = ceil($totalRecords / $limit);

// ------------------------------
// MAIN QUERY WITH LIMIT & OFFSET
// ------------------------------
$sql = "
    SELECT Country_id, Country_name, extra33percent_country
    FROM CountryMaster
    $searchQuery
    ORDER BY Country_name ASC
    OFFSET $offset ROWS
    FETCH NEXT $limit ROWS ONLY
";

$stmt = sqlsrv_query($conn, $sql, !empty($params) ? $params : []);

if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
?>

<div class="container mt-4">
    <h3 class="text-center mb-4">Country List</h3>

    <!-- SEARCH BOX -->
    <form method="get" class="mb-3 text-center">
        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>" 
               placeholder="Search Country..." class="form-control w-50 d-inline-block">
        <button type="submit" class="btn btn-primary">Search</button>
    </form>

    <table class="table table-bordered table-striped">
        <thead class="bg-primary text-white">
            <tr>
                <th>Country ID</th>
                <th>Country Name</th>
                <th>Extra 33%?</th>
                <th style="width: 150px;">Actions</th>
            </tr>
        </thead>

        <tbody>
            <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                <tr>
                    <td><?= $row['Country_id'] ?></td>
                    <td><?= htmlspecialchars($row['Country_name']) ?></td>
                    <td><?= ($row['extra33percent_country'] == 1) ? 'Yes' : 'No' ?></td>
                    <td>
                        <a href="edit_country.php?id=<?= $row['Country_id'] ?>" class="btn btn-sm btn-primary">
                            Edit
                        </a>
                    </td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- PAGINATION -->
    <nav>
        <ul class="pagination justify-content-center">

            <!-- Previous button -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?>&search=<?= urlencode($search) ?>">Previous</a>
            </li>

            <!-- Page Numbers -->
            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?>&search=<?= urlencode($search) ?>">
                        <?= $i ?>
                    </a>
                </li>
            <?php endfor; ?>

            <!-- Next button -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?>&search=<?= urlencode($search) ?>">Next</a>
            </li>

        </ul>
    </nav>
</div>
