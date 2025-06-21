<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if appointment ID and action are provided
if (!isset($_POST['appointment_id']) || !isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Appointment ID and action are required']);
    exit();
}

$valid_actions = ['complete', 'cancel'];
if (!in_array($_POST['action'], $valid_actions)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid action']);
    exit();
}

try {
    $doctor_id = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get appointment details first
        $stmt = $conn->prepare("
            SELECT patient_id 
            FROM appointments 
            WHERE id = ? 
            AND doctor_id = ? 
            AND status = 'confirmed'
        ");
        $stmt->bind_param("ii", $appointment_id, $doctor_id);
        $stmt->execute();
        $appointment = $stmt->get_result()->fetch_assoc();
        
        if (!$appointment) {
            throw new Exception('Appointment not found or cannot be completed');
        }
        
        // Update appointment status
        $new_status = $_POST['action'] === 'complete' ? 'completed' : 'cancelled';
        $stmt = $conn->prepare("
            UPDATE appointments 
            SET status = ?, 
                completed_date = CASE WHEN ? = 'completed' THEN NOW() ELSE NULL END,
                cancellation_reason = CASE WHEN ? = 'cancelled' THEN 'Cancelled by doctor' ELSE NULL END
            WHERE id = ? 
            AND doctor_id = ? 
            AND status = 'confirmed'
        ");
        $stmt->bind_param("sssii", $new_status, $new_status, $new_status, $appointment_id, $doctor_id);
        $stmt->bind_param("ii", $appointment_id, $doctor_id);
        $stmt->execute();
        
        if ($stmt->affected_rows > 0) {
            // Create notification for patient
            $notification_type = $_POST['action'] === 'complete' ? 'appointment_completed' : 'appointment_cancelled';
            $notification_message = $_POST['action'] === 'complete' ? 
                'Your appointment has been marked as completed' : 
                'Your appointment has been cancelled by the doctor';
            
            $stmt = $conn->prepare("
                INSERT INTO notifications 
                (user_id, type, message, reference_id, created_at) 
                VALUES (?, ?, ?, ?, NOW())
            ");
            $stmt->bind_param("issi", $appointment['patient_id'], $notification_type, $notification_message, $appointment_id);
            $stmt->bind_param("ii", $appointment['patient_id'], $appointment_id);
            $stmt->execute();
            
            // If this was a follow-up appointment, update any related prescriptions or lab tests
            if ($appointment['type'] === 'follow_up') {
                // Update related prescriptions
                $stmt = $conn->prepare("
                    UPDATE prescriptions 
                    SET status = 'completed' 
                    WHERE patient_id = ? 
                    AND doctor_id = ? 
                    AND status = 'pending'
                ");
                $stmt->bind_param("ii", $appointment['patient_id'], $doctor_id);
                $stmt->execute();

                // Update related lab tests
                $stmt = $conn->prepare("
                    UPDATE lab_tests 
                    SET status = 'completed' 
                    WHERE patient_id = ? 
                    AND doctor_id = ? 
                    AND status = 'pending'
                ");
                $stmt->bind_param("ii", $appointment['patient_id'], $doctor_id);
                $stmt->execute();
            }

            // Commit transaction
            $conn->commit();
            
            $message = $_POST['action'] === 'complete' ? 'Appointment marked as completed' : 'Appointment cancelled successfully';
            echo json_encode(['success' => true, 'message' => $message]);
        } else {
            throw new Exception('Failed to complete appointment');
        }
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
