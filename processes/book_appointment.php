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

    // Get input data
    $input = $_POST;

    // Required fields
    $required_fields = ['doctor_id', 'appointment_date', 'slot_time', 'reason'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || empty(trim($input[$field]))) {
            throw new Exception("Missing required field: {$field}");
        }
    }

    // Validate and sanitize input
    $doctor_id = filter_var($input['doctor_id'], FILTER_VALIDATE_INT);
    $appointment_date = filter_var($input['appointment_date'], FILTER_SANITIZE_STRING);
    $slot_time = filter_var($input['slot_time'], FILTER_SANITIZE_STRING);
    $reason = htmlspecialchars($input['reason'], ENT_QUOTES, 'UTF-8');
    $patient_id = $_SESSION['role'] === 'admin' && isset($input['patient_id']) ? 
                 filter_var($input['patient_id'], FILTER_VALIDATE_INT) : 
                 $_SESSION['user_id'];
    $type = isset($input['type']) ? htmlspecialchars($input['type'], ENT_QUOTES, 'UTF-8') : 'consultation';
    $notes = isset($input['notes']) ? htmlspecialchars($input['notes'], ENT_QUOTES, 'UTF-8') : '';

    // Validate doctor_id
    if (!$doctor_id) {
        throw new Exception('Invalid doctor ID');
    }

    // Validate appointment date and time format
    if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $appointment_date)) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD');
    }

    if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $slot_time)) {
        throw new Exception('Invalid time format. Use HH:MM:SS');
    }

    // Check if appointment is in the future
    if (strtotime($appointment_date . ' ' . $slot_time) <= time()) {
        throw new Exception('Appointment must be in the future');
    }

    // Start transaction
    $conn->begin_transaction();

    try {
        // Verify doctor exists
        $doctor_stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor'");
        $doctor_stmt->bind_param('i', $doctor_id);
        $doctor_stmt->execute();
        if ($doctor_stmt->get_result()->num_rows === 0) {
            throw new Exception('Doctor not found');
        }

        // Get the day of week for the appointment date
        $day_of_week = date('l', strtotime($appointment_date));

        // Check if doctor works on this day and time
        $schedule_query = "SELECT * FROM doctor_schedule 
                          WHERE doctor_id = ? AND day_of_week = ? 
                          AND ? BETWEEN start_time AND end_time";
        $schedule_stmt = $conn->prepare($schedule_query);
        $schedule_stmt->bind_param('iss', $doctor_id, $day_of_week, $slot_time);
        $schedule_stmt->execute();
        
        if ($schedule_stmt->get_result()->num_rows === 0) {
            throw new Exception('Doctor is not available at this time');
        }

        // Check if slot is already booked
        $check_query = "SELECT id FROM appointments 
                       WHERE doctor_id = ? AND DATE(appointment_date) = ? 
                       AND slot_time = ? AND status IN ('pending', 'confirmed')";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('iss', $doctor_id, $appointment_date, $slot_time);
        $check_stmt->execute();
        
        if ($check_stmt->get_result()->num_rows > 0) {
            throw new Exception('This time slot is already booked');
        }

        // Insert appointment
        $insert_query = "INSERT INTO appointments (
                            doctor_id, patient_id, appointment_date, slot_time,
                            type, reason, notes, status
                        ) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param(
            'iisssss',
            $doctor_id,
            $patient_id,
            $appointment_date,
            $slot_time,
            $type,
            $reason,
            $notes
        );
        
        if (!$insert_stmt->execute()) {
            throw new Exception('Failed to book appointment');
        }

        // Commit transaction
        $conn->commit();

        echo json_encode([
            'success' => true,
            'message' => 'Appointment booked successfully',
            'appointment_id' => $conn->insert_id
        ]);

    } catch (Exception $e) {
        // Rollback on error
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
