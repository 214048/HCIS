<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $user_id = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'] ?? null;

    if (!$appointment_id) {
        throw new Exception('Appointment ID is required');
    }

    // Start transaction
    $conn->begin_transaction();

    // Check if the appointment exists and belongs to the user
    $stmt = $conn->prepare("
        SELECT status FROM appointments 
        WHERE id = ? AND (
            patient_id = ? OR 
            (doctor_id = ? AND ? = 'doctor') OR
            ? = 'admin'
        )
    ");
    $stmt->bind_param("iiiss", $appointment_id, $user_id, $user_id, $_SESSION['role'], $_SESSION['role']);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Appointment not found or you do not have permission to cancel it');
    }

    $appointment = $result->fetch_assoc();
    if ($appointment['status'] === 'cancelled') {
        throw new Exception('Appointment is already cancelled');
    }

    // Cancel the appointment
    $stmt = $conn->prepare("UPDATE appointments SET status = 'cancelled' WHERE id = ?");
    $stmt->bind_param("i", $appointment_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to cancel appointment');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Appointment cancelled successfully']);

} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
