<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

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

    // Validate required fields
    $required_fields = ['test_id', 'result', 'details'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception(ucfirst($field) . ' is required');
        }
    }

    // Handle PDF upload
    if (!isset($_FILES['result_pdf']) || $_FILES['result_pdf']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Result PDF is required and must be uploaded successfully.');
    }
    $pdf = $_FILES['result_pdf'];
    if ($pdf['type'] !== 'application/pdf') {
        throw new Exception('Only PDF files are allowed.');
    }
    $target_dir = '../uploads/results/';
    if (!file_exists($target_dir)) {
        mkdir($target_dir, 0777, true);
    }
    $unique_name = uniqid('result_', true) . '.pdf';
    $target_file = $target_dir . $unique_name;
    if (!move_uploaded_file($pdf['tmp_name'], $target_file)) {
        throw new Exception('Failed to upload PDF file.');
    }
    $relative_pdf_path = 'uploads/results/' . $unique_name;

    $test_id = $_POST['test_id'];
    $result = $_POST['result'];
    $details = $_POST['details'];
    $recommendations = isset($_POST['recommendations']) ? $_POST['recommendations'] : '';

    // Start transaction
    $conn->begin_transaction();

    // Get test details
    $stmt = $conn->prepare("
        SELECT * FROM lab_tests 
        WHERE id = ? AND status = 'processing'
    ");
    if ($stmt === false) {
        throw new Exception("Prepare failed (SELECT): " . $conn->error);
    }
    $stmt->bind_param("i", $test_id);
    $stmt->execute();
    $test = $stmt->get_result()->fetch_assoc();

    if (!$test) {
        throw new Exception('Test not found or not in processing state');
    }

    // Update test status and results
    // Fixed: Using 'results' column instead of 'result', and storing JSON with test data
    $results_json = json_encode([
        'result_type' => $result,
        'details' => $details,
        'recommendations' => $recommendations
    ]);
    
    $stmt = $conn->prepare("
        UPDATE lab_tests 
        SET 
            status = 'completed',
            results = ?,
            completed_date = NOW(),
            result_pdf = ?
        WHERE id = ?
    ");
    if ($stmt === false) {
        throw new Exception("Prepare failed (UPDATE): " . $conn->error);
    }
    $stmt->bind_param("ssi", $results_json, $relative_pdf_path, $test_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to update test results');
    }

    // If result is abnormal or critical, notify the doctor
    if ($result === 'abnormal' || $result === 'critical') {
        // Check if notifications table exists
        $tables = $conn->query("SHOW TABLES LIKE 'notifications'");
        if ($tables->num_rows > 0) {
            $stmt = $conn->prepare("
                INSERT INTO notifications (user_id, type, message, reference_id)
                VALUES (?, ?, ?, ?)
            ");
            if ($stmt === false) {
                throw new Exception("Prepare failed (INSERT notification): " . $conn->error);
            }
            $message = "Lab Test Result - " . ucfirst($result) . ": Results for patient #{$test['patient_id']} are {$result}. Please review.";
            $type = 'lab_result';
            $stmt->bind_param("issi", $test['doctor_id'], $type, $message, $test_id);
            $stmt->execute();
        }
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'Test results submitted successfully']);

} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
