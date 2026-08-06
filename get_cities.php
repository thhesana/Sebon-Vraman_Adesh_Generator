<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include DB connection
require_once 'db.php';

// Set header for JSON response
header('Content-Type: application/json; charset=utf-8');

// Log the request
error_log("get_cities.php called with country_id: " . ($_GET['country_id'] ?? 'NULL'));

// Get country_id from query parameter
$country_id = isset($_GET['country_id']) ? intval($_GET['country_id']) : null;

// Validate country_id
if (!$country_id || $country_id <= 0) {
    error_log("Invalid country_id: " . $country_id);
    echo json_encode([
        'success' => false,
        'data' => [],
        'message' => 'Invalid country ID'
    ]);
    exit;
}

try {
    // Query to fetch cities for the selected country
    $sql = "SELECT City_id, City_name FROM CityMaster WHERE Country_id = ? ORDER BY City_name";
    $params = array($country_id);
    
    error_log("Executing SQL: $sql with Country_id = $country_id");
    
    $stmt = sqlsrv_query($conn, $sql, $params);
    
    // Check for query errors
    if ($stmt === false) {
        $errors = sqlsrv_errors();
        error_log("SQL Error: " . print_r($errors, true));
        
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'data' => [],
            'error' => 'Database query failed',
            'message' => 'Could not fetch cities',
            'sql_error' => $errors[0]['message'] ?? 'Unknown error',
            'country_id' => $country_id
        ]);
        exit;
    }
    
    // Fetch all cities
    $cities = [];
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        $cities[] = [
            'City_id' => $row['City_id'],
            'City_name' => $row['City_name']
        ];
    }
    
    error_log("Found " . count($cities) . " cities for Country_id = $country_id");
    
    // Return cities as JSON with success flag and data array
    echo json_encode([
        'success' => true,
        'data' => $cities,
        'count' => count($cities)
    ]);
    
    // Free statement
    sqlsrv_free_stmt($stmt);
    
} catch (Exception $e) {
    error_log("Exception in get_cities.php: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'data' => [],
        'error' => 'Server error',
        'message' => $e->getMessage(),
        'country_id' => $country_id
    ]);
}
?>