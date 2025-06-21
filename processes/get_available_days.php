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

    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    // Required parameters
    if (!isset($_GET['doctor_id']) || !is_numeric($_GET['doctor_id'])) {
        throw new Exception('Valid doctor ID is required');
    }

    $doctor_id = (int)$_GET['doctor_id'];
    
    // Query doctor's schedule
    $schedule_query = "SELECT day_of_week FROM doctor_schedule 
                      WHERE doctor_id = ? 
                      GROUP BY day_of_week
                      ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
    $schedule_stmt = $conn->prepare($schedule_query);
    $schedule_stmt->bind_param('i', $doctor_id);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    
    // If doctor doesn't have any schedule
    if ($schedule_result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Doctor has no availability set up'
        ]);
        exit;
    }
    
    // Get all working days for this doctor
    $working_days = [];
    while ($row = $schedule_result->fetch_assoc()) {
        $working_days[] = $row['day_of_week'];
    }
    
    // Generate the next 30 days of available dates
    $available_dates = [];
    $date = new DateTime();
    $end_date = clone $date;
    $end_date->modify('+30 days');
    
    while ($date <= $end_date) {
        $day_of_week = $date->format('l'); // Monday, Tuesday, etc.
        
        // If doctor works on this day
        if (in_array($day_of_week, $working_days)) {
            $date_string = $date->format('Y-m-d');
            
            $available_dates[] = [
                'date' => $date_string,
                'formatted_date' => $date->format('D, M j, Y'), // e.g., Mon, May 4, 2023
                'day_of_week' => $day_of_week
            ];
        }
        
        $date->modify('+1 day');
    }
    
    echo json_encode([
        'success' => true,
        'data' => $available_dates,
        'working_days' => $working_days
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 