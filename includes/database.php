<?php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'hcis_db');

try {
    // Enable error reporting for mysqli
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    // Create connection
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

    // Set charset to utf8mb4
    $conn->set_charset('utf8mb4');

    // Check connection
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Test the connection with a simple query
    $test = $conn->query('SELECT 1');
    if (!$test) {
        throw new Exception("Database test query failed");
    }

} catch (Exception $e) {
    // Log the error
    error_log('Database connection error: ' . $e->getMessage());
    error_log('Error details: ' . print_r($e->getTrace(), true));

    // If this is an processes request, return JSON error
    if (strpos($_SERVER['REQUEST_URI'], '/processes/') !== false) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false,
            'message' => 'Database connection failed. Please try again later.'
        ]);
        exit;
    }

    // For regular pages, show error message
    die("Database connection failed. Please try again later.");
}
?>
