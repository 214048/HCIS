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

    if (!isset($_GET['date']) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['date'])) {
        throw new Exception('Valid date is required (YYYY-MM-DD)');
    }

    $doctor_id = (int)$_GET['doctor_id'];
    $date = $_GET['date'];
    
    // Get the day of the week for the requested date
    $day_of_week = date('l', strtotime($date)); // Returns 'Monday', 'Tuesday', etc.
    
    // Query doctor's schedule for that day
    $schedule_query = "SELECT * FROM doctor_schedule 
                      WHERE doctor_id = ? AND day_of_week = ? 
                      ORDER BY start_time";
    $schedule_stmt = $conn->prepare($schedule_query);
    $schedule_stmt->bind_param('is', $doctor_id, $day_of_week);
    $schedule_stmt->execute();
    $schedule_result = $schedule_stmt->get_result();
    
    // If doctor doesn't work on this day
    if ($schedule_result->num_rows === 0) {
        echo json_encode([
            'success' => true,
            'data' => [],
            'message' => 'Doctor is not available on this day'
        ]);
        exit;
    }
    
    // Get all schedules for this day
    $schedules = [];
    while ($row = $schedule_result->fetch_assoc()) {
        $schedules[] = [
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }
    
    // Get all booked appointments for this doctor on this date
    $appointment_query = "SELECT slot_time FROM appointments 
                         WHERE doctor_id = ? AND DATE(appointment_date) = ? 
                         AND status IN ('pending', 'confirmed')";
    $appointment_stmt = $conn->prepare($appointment_query);
    $appointment_stmt->bind_param('is', $doctor_id, $date);
    $appointment_stmt->execute();
    $appointment_result = $appointment_stmt->get_result();
    
    // Create a list of booked times
    $booked_slots = [];
    while ($row = $appointment_result->fetch_assoc()) {
        $booked_slots[] = $row['slot_time'];
    }
    
    // Generate available time slots (30-minute intervals)
    $available_slots = [];
    foreach ($schedules as $schedule) {
        $current_time = strtotime($schedule['start_time']);
        $end_time = strtotime($schedule['end_time']);
        
        while ($current_time < $end_time) {
            $slot_time = date('H:i:s', $current_time);
            
            // Skip if slot is already booked
            if (!in_array($slot_time, $booked_slots)) {
                $available_slots[] = [
                    'time' => $slot_time,
                    'formatted_time' => date('h:i A', $current_time)
                ];
            }
            
            // Move to next slot (30 minutes)
            $current_time += 30 * 60;
        }
    }
    
    // Sort slots by time
    usort($available_slots, function($a, $b) {
        return strcmp($a['time'], $b['time']);
    });
    
    echo json_encode([
        'success' => true,
        'data' => $available_slots
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 