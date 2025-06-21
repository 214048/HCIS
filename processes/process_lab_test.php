<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a lab technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lab') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if required data is provided
if (!isset($_POST['test_id']) || !isset($_POST['results']) || !isset($_POST['result_type'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required fields']);
    exit();
}

try {
    $test_id = $_POST['test_id'];
    $results = $_POST['results'];
    $result_type = $_POST['result_type'];
    $notes = $_POST['notes'] ?? '';
    $technician_id = $_SESSION['user_id'];
    
    // Validate result type
    if (!in_array($result_type, ['normal', 'abnormal', 'critical'])) {
        throw new Exception('Invalid result type');
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    // Check if test exists and is in the correct status
    $stmt = $conn->prepare("
        SELECT id, patient_id, doctor_id, status
        FROM lab_tests 
        WHERE id = ? AND (status = 'pending' OR status = 'processing')
    ");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();
    
    if (!$test) {
        throw new Exception('Test not found or cannot be processed');
    }
    
    // Update test with results
    $stmt = $conn->prepare("
        UPDATE lab_tests 
        SET status = 'completed',
            results = ?,
            result_type = ?,
            notes = ?,
            lab_staff_id = ?,
            completed_date = NOW()
        WHERE id = ?
    ");
    $stmt->bind_param("sssii", $results, $result_type, $notes, $technician_id, $test_id);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to update test status');
    }
    
    // Create notification for doctor
    try {
        $message = "Lab test processing has started";
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, message, reference_id, created_at)
            VALUES (?, 'lab_processing', ?, ?, NOW())
        ");
        $stmt->bind_param("isi", $test['doctor_id'], $message, $test_id);
        $stmt->execute();
    } catch (Exception $e) {
        // Ignore notification errors
    }
    
    // Commit transaction
    $conn->commit();
    echo json_encode(['success' => true]);
    
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
