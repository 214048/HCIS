<?php
require_once '../includes/session_manager.php';
require_once '../includes/database.php';

// Check if user is logged in and is a patient
checkSession('patient');

// Get POST data
$patient_id = $_POST['patient_id'] ?? null;
$doctor_id = $_POST['doctor_id'] ?? null;
$appointment_date = $_POST['appointment_date'] ?? null;
$slot_time = $_POST['slot_time'] ?? null;
$type = $_POST['type'] ?? 'consultation';
$reason = $_POST['reason'] ?? '';
$notes = $_POST['notes'] ?? '';

// Validate required fields
if (!$patient_id || !$doctor_id || !$appointment_date || !$slot_time) {
    header('Location: ../patient/dashboard.php?error=missing_fields');
    exit();
}

try {
    // Check if doctor exists and is actually a doctor
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'doctor'");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Invalid doctor selected");
    }

    // Get day of week for the selected date
    $day_of_week = date('l', strtotime($appointment_date)); // Returns 'Monday', 'Tuesday', etc.

    // Check if doctor works on this day and time
    $schedule_query = "SELECT * FROM doctor_schedule 
                      WHERE doctor_id = ? AND day_of_week = ? 
                      AND ? BETWEEN start_time AND end_time";
    $schedule_stmt = $conn->prepare($schedule_query);
    $schedule_stmt->bind_param('iss', $doctor_id, $day_of_week, $slot_time);
    $schedule_stmt->execute();
    
    if ($schedule_stmt->get_result()->num_rows === 0) {
        throw new Exception("Doctor is not available at this time on {$day_of_week}");
    }

    // Check if the time slot is already booked
    $check_query = "SELECT id FROM appointments 
                   WHERE doctor_id = ? AND DATE(appointment_date) = ? 
                   AND slot_time = ? AND status IN ('pending', 'confirmed')";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('iss', $doctor_id, $appointment_date, $slot_time);
    $check_stmt->execute();
    
    if ($check_stmt->get_result()->num_rows > 0) {
        throw new Exception("This time slot is already booked");
    }

    // Create full datetime for appointment
    $appointment_datetime = $appointment_date . ' ' . $slot_time;

    // Insert appointment
    $stmt = $conn->prepare("
        INSERT INTO appointments (doctor_id, patient_id, appointment_date, slot_time, type, reason, notes, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending')
    ");
    $stmt->bind_param("iisssss", $doctor_id, $patient_id, $appointment_datetime, $slot_time, $type, $reason, $notes);
    
    if ($stmt->execute()) {
        header('Location: ../patient/dashboard.php?success=appointment_booked');
    } else {
        throw new Exception("Failed to create appointment");
    }

} catch (Exception $e) {
    header('Location: ../patient/dashboard.php?error=' . urlencode($e->getMessage()));
}

exit();
