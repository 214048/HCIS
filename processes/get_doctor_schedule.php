<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../includes/session_manager.php';
    require_once '../includes/database.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Not authenticated');
    }

    // Check if user is admin or the doctor in question
    if ($_SESSION['role'] !== 'admin' && $_SESSION['role'] !== 'doctor' && $_SESSION['role'] !== 'patient') {
        throw new Exception('Unauthorized access');
    }

    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    // Validate doctor_id
    if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
        throw new Exception('Valid doctor ID is required');
    }

    $doctor_id = (int)$_GET['doctor_id'];

    // If the user is a doctor, make sure they're only viewing their own schedule
    if ($_SESSION['role'] === 'doctor' && $_SESSION['user_id'] != $doctor_id) {
        throw new Exception('You can only view your own schedule');
    }

    // Query to get doctor's schedule
    $query = "SELECT * FROM doctor_schedule WHERE doctor_id = ? ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'), start_time";
    $stmt = $conn->prepare($query);
    $stmt->bind_param('i', $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $schedule = [];
    while ($row = $result->fetch_assoc()) {
        $schedule[] = $row;
    }

    echo json_encode([
        'success' => true,
        'data' => $schedule
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 