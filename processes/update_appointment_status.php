<?php
session_start();
require_once '../includes/database.php';

// Check if user is logged in and has doctor role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['appointment_id']) || !isset($_POST['status'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    $doctor_id = $_SESSION['user_id'];
    $appointment_id = $_POST['appointment_id'];
    $status = $_POST['status'];

    // Validate status value
    if ($status !== 'completed' && $status !== 'cancelled') {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid status value']);
        exit();
    }

    // Update appointment status
    $stmt = $conn->prepare("
        UPDATE appointments
        SET status = ?
        WHERE id = ? AND doctor_id = ?
    ");
    $stmt->bind_param("sii", $status, $appointment_id, $doctor_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true, 'message' => 'Appointment status updated successfully']);
    } else {
        throw new Exception('Failed to update appointment status');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
