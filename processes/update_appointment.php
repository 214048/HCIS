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

// Check if required parameters are present
if (!isset($_POST['appointment_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$appointment_id = $_POST['appointment_id'];
$status = $_POST['status'];
$notes = $_POST['notes'] ?? '';
$doctor_id = $_SESSION['user_id'];

// Validate status
if (!in_array($status, ['completed', 'cancelled'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid status']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if appointment exists and belongs to the doctor
    $stmt = $conn->prepare("
        SELECT a.id, a.patient_id, a.status 
        FROM appointments a
        WHERE a.id = ? AND a.doctor_id = ? AND a.status = 'pending'
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare appointment check query: " . $conn->error);
    }
    $stmt->bind_param("ii", $appointment_id, $doctor_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to check appointment: " . $stmt->error);
    }
    $appointment = $stmt->get_result()->fetch_assoc();
    
    if (!$appointment) {
        throw new Exception('Appointment not found or already processed');
    }
    
    // Update appointment status
    $stmt = $conn->prepare("
        UPDATE appointments 
        SET status = ?,
            notes = ?,
            completed_at = CURRENT_TIMESTAMP
        WHERE id = ? AND doctor_id = ?
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare update query: " . $conn->error);
    }
    $stmt->bind_param("ssii", $status, $notes, $appointment_id, $doctor_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update appointment: " . $stmt->error);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Appointment ' . $status . ' successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
