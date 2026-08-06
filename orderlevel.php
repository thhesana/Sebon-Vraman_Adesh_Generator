<?php
include 'db.php';   // your SQL Server connection file
include 'HEADER.php';

if (!$conn) {
    die("Database connection failed: " . print_r(sqlsrv_errors(), true));
}

$sql = "
    SELECT 
        TadaDefinerMasterBylevel_name,
        tadaInUSD,
        createddate
    FROM TadaDefinerMasterBylevel
    ORDER BY 
        CASE 
            WHEN TadaDefinerMasterBylevel_name = 'Chairman' THEN 0
            ELSE 1
        END,
        tadaInUSD DESC
";

$stmt = sqlsrv_query($conn, $sql);

if ($stmt === false) {
    die("Error executing query: " . print_r(sqlsrv_errors(), true));
}
?>

<div class="container mt-4">
    <h3 class="text-center mb-4">TADA Definer Master By Level</h3>

    <table class="table table-bordered table-striped">
        <thead class="bg-primary text-white">
            <tr>
                <th>Level Name</th>
                <th>TADA (USD) </th>
                
            </tr>
        </thead>
        <tbody>
        <?php while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) { ?>
            <tr>
                <td><?php echo $row['TadaDefinerMasterBylevel_name']; ?></td>
                <td><?php echo $row['tadaInUSD']; ?></td>
                <td>
                    
            </tr>
        <?php } ?>
        </tbody>
    </table>
</div>

<?php include 'FOOTER.php'; ?>
