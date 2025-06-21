<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is lab staff
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lab') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $test_id = $_POST['test_id'];

    // Start transaction
    $conn->begin_transaction();

    // Update test status to processing
    $stmt = $conn->prepare("
        UPDATE lab_tests 
        SET status = 'processing'
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("i", $test_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Test not found or already being processed');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Test status updated to processing']);

} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
