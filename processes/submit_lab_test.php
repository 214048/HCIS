<?php
// processes Endpoint: submit_lab_test.php
// Allows lab technicians to submit new lab test results for patients.
// Handles authentication, input validation, and database updates.
require_once '../includes/database.php'; // Database connection
require_once '../includes/session_manager.php'; // Session management utilities

checkSession('doctor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$doctor_id = $_SESSION['user_id'];
$patient_id = $_POST['patient_id'] ?? null;
$tests_json = $_POST['tests'] ?? null;

if (!$patient_id || !$tests_json) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Missing required fields']));
}

$tests = json_decode($tests_json, true);
if (!is_array($tests) || count($tests) === 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid tests data']));
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO lab_tests (doctor_id, patient_id, test_type, notes, requested_date) VALUES (?, ?, ?, ?, NOW())");
    foreach ($tests as $test) {
        if (empty($test['test_type'])) {
            throw new Exception('Test type is required');
        }
        $notes = $test['notes'] ?? '';
        $stmt->bind_param(
            "iiss",
            $doctor_id,
            $patient_id,
            $test['test_type'],
            $notes
        );
        $stmt->execute();
    }
    $conn->commit();
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    $conn->rollback();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
