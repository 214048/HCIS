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
if (!isset($_POST['patient_id']) || !isset($_POST['test_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$patient_id = $_POST['patient_id'];
$test_type = $_POST['test_type'];
$urgency = $_POST['urgency'] ?? 'normal';
$notes = $_POST['notes'] ?? '';
$doctor_id = $_SESSION['user_id'];

// Validate urgency
if (!in_array($urgency, ['normal', 'urgent', 'emergency'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid urgency level']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify patient exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
    if (!$stmt) {
        throw new Exception("Failed to prepare patient check query: " . $conn->error);
    }
    $stmt->bind_param("i", $patient_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to check patient: " . $stmt->error);
    }
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Patient not found");
    }

    // Create lab test request
    $stmt = $conn->prepare("
        INSERT INTO lab_tests 
        (patient_id, doctor_id, test_type, urgency, notes, requested_date, status)
        VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP, 'pending')
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare lab test query: " . $conn->error);
    }
    $stmt->bind_param("iisss", $patient_id, $doctor_id, $test_type, $urgency, $notes);
    if (!$stmt->execute()) {
        throw new Exception("Failed to create lab test request: " . $stmt->error);
    }

    $test_id = $conn->insert_id;

    // Commit transaction
    $conn->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Lab test ordered successfully',
        'data' => ['test_id' => $test_id]
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
