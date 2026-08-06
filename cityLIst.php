<?php
include 'db.php';
include 'HEADER.php';

// Pagination settings
$limit = 5;  
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Search parameter
$search = isset($_GET['search']) ? trim($_GET['search']) : '';

// Build WHERE clause for search
$whereClause = "";
$params = [];
if (!empty($search)) {
    $whereClause = "WHERE c.City_name LIKE ? OR co.Country_name LIKE ?";
    $searchParam = '%' . $search . '%';
    $params = [$searchParam, $searchParam];
}

// Count total records
$countSql = "SELECT COUNT(*) AS total FROM CityMaster c 
             LEFT JOIN CountryMaster co ON c.Country_id = co.Country_id 
             $whereClause";
$countStmt = sqlsrv_query($conn, $countSql, $params);
$countRow = sqlsrv_fetch_array($countStmt, SQLSRV_FETCH_ASSOC);
$totalRows = $countRow['total'];
$totalPages = ceil($totalRows / $limit);

// Fetch paginated data
$sql = "
SELECT 
    c.City_id,
    c.City_name,
    c.Country_id,
    co.Country_name
FROM CityMaster AS c
LEFT JOIN CountryMaster AS co ON c.Country_id = co.Country_id
$whereClause
ORDER BY c.Country_id ASC, c.City_id ASC
OFFSET $offset ROWS FETCH NEXT $limit ROWS ONLY;
";
$stmt = sqlsrv_query($conn, $sql, $params);
?>
<h2 class="text-center mt-4">City List</h2>
<div class="container mt-3">
    <a href="city_add.php" class="btn btn-success mb-3 float-end">Add New City</a>
    
    <!-- SEARCH FORM -->
    <form method="GET" action="" class="mb-3">
        <div class="input-group">
            <input type="text" name="search" class="form-control" 
                   placeholder="Search city, country..." 
                   value="<?= htmlspecialchars($search) ?>">
            <button class="btn btn-primary" type="submit">Search</button>
            <?php if (!empty($search)): ?>
                <a href="?" class="btn btn-secondary">Clear</a>
            <?php endif; ?>
        </div>
    </form>

    <?php if (!empty($search)): ?>
        <p class="text-muted">Showing results for: <strong><?= htmlspecialchars($search) ?></strong> (<?= $totalRows ?> found)</p>
    <?php endif; ?>

    <table class="table table-bordered table-striped" id="cityTable">
        <thead class="bg-primary text-white">
            <tr>
                <th>S.N.</th>
                <th style="display:none;">City ID</th>
                <th>City Name</th>
                <th>Country</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            if ($totalRows > 0) {
                $sn = $offset + 1;
                while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
                    <tr>
                        <td><?= $sn++; ?></td>
                        <td style="display:none;"><?= $row['City_id']; ?></td>
                        <td><?= htmlspecialchars($row['City_name']); ?></td>
                        <td><?= htmlspecialchars($row['Country_name']); ?></td>
                        <td>
                            <a href="city_edit.php?id=<?= $row['City_id']; ?>" class="btn btn-primary btn-sm">Edit</a>
                        </td>
                    </tr>
                <?php }
            } else { ?>
                <tr>
                    <td colspan="4" class="text-center">No cities found</td>
                </tr>
            <?php } ?>
        </tbody>
    </table>

    <!-- PAGINATION LINKS -->
    <?php if ($totalPages > 1): ?>
    <nav aria-label="Page navigation">
        <ul class="pagination justify-content-center">
            <!-- Previous Button -->
            <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Previous</a>
            </li>
            
            <!-- Page Numbers -->
            <?php 
            // Show max 10 page numbers
            $startPage = max(1, $page - 5);
            $endPage = min($totalPages, $page + 4);
            
            if ($startPage > 1): ?>
                <li class="page-item">
                    <a class="page-link" href="?page=1<?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">1</a>
                </li>
                <?php if ($startPage > 2): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif;
            endif;
            
            for ($i = $startPage; $i <= $endPage; $i++): ?>
                <li class="page-item <?= ($i == $page) ? 'active' : '' ?>">
                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $i ?></a>
                </li>
            <?php endfor;
            
            if ($endPage < $totalPages): 
                if ($endPage < $totalPages - 1): ?>
                    <li class="page-item disabled"><span class="page-link">...</span></li>
                <?php endif; ?>
                <li class="page-item">
                    <a class="page-link" href="?page=<?= $totalPages ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>"><?= $totalPages ?></a>
                </li>
            <?php endif; ?>
            
            <!-- Next Button -->
            <li class="page-item <?= ($page >= $totalPages) ? 'disabled' : '' ?>">
                <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?>">Next</a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>
</div>

<?php sqlsrv_close($conn); ?>