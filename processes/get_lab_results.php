<?php
session_start();
error_log("SESSION: " . print_r($_SESSION, true));
header('Content-Type: application/json');
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Invalid request method');
    }

    $user_id = $_SESSION['user_id'];
    $test_id = $_GET['test_id'] ?? null;

    error_log("Processing lab result request for test_id: $test_id, user_id: $user_id, role: {$_SESSION['role']}");
    
    if (!$test_id) {
        throw new Exception('Test ID is required');
    }
    
    if (!is_numeric($test_id)) {
        throw new Exception('Invalid Test ID format');
    }

    // Get lab test results - modified to match actual database structure
    $stmt = $conn->prepare("
        SELECT l.*, 
               u_doc.name as doctor_name,
               u_pat.name as patient_name
        FROM lab_tests l
        JOIN users u_doc ON l.doctor_id = u_doc.id
        JOIN users u_pat ON l.patient_id = u_pat.id
        WHERE l.id = ? AND (
            l.patient_id = ? OR 
            l.doctor_id = ? OR 
            ? = 'admin'
        )
    ");
    
    if (!$stmt) {
        error_log("MySQL Error in prepare: " . $conn->error);
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("iiis", $test_id, $user_id, $user_id, $_SESSION['role']);
    
    if (!$stmt->execute()) {
        error_log("MySQL Error in execute: " . $stmt->error);
        throw new Exception("Query execution failed: " . $stmt->error);
    }
    
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        throw new Exception('Lab test not found or you do not have permission to view it');
    }

    $test = $result->fetch_assoc();
    error_log("Retrieved test record: " . json_encode($test));
    
    // Parse the JSON results if available
    $result_data = null;
    $result_type = '';
    $details = '';
    $recommendations = '';
    
    if ($test['status'] === 'completed' && !empty($test['results'])) {
        error_log("Parsing JSON results: " . $test['results']);
        try {
            $result_data = json_decode($test['results'], true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                error_log("JSON decode error: " . json_last_error_msg());
                $result_data = null;
            }
            
            $result_type = isset($result_data['result_type']) ? $result_data['result_type'] : '';
            $details = isset($result_data['details']) ? $result_data['details'] : '';
            $recommendations = isset($result_data['recommendations']) ? $result_data['recommendations'] : '';
            
            error_log("Parsed result_type: $result_type");
        } catch (Exception $e) {
            error_log("Error parsing JSON: " . $e->getMessage());
        }
    } else {
        error_log("Test status is not completed or results is empty: status=" . $test['status'] . ", results=" . (empty($test['results']) ? "empty" : "not empty"));
    }
    
    // Format the results in HTML with result type, details, and recommendations
    $html = '
        <div class="lab-results">
            <div class="mb-3">
                <strong>Patient:</strong> ' . htmlspecialchars($test['patient_name']) . '
            </div>
            <div class="mb-3">
                <strong>Test Type:</strong> ' . htmlspecialchars($test['test_type']) . '
            </div>
            <div class="mb-3">
                <strong>Requested By:</strong> ' . htmlspecialchars($test['doctor_name']) . '
            </div>
            <div class="mb-3">
                <strong>Requested Date:</strong> ' . date('M d, Y', strtotime($test['requested_date'])) . '
            </div>
            ' . (isset($test['completed_date']) && $test['completed_date'] ? '
            <div class="mb-3">
                <strong>Completion Date:</strong> ' . date('M d, Y', strtotime($test['completed_date'])) . '
            </div>' : '') . '
            <div class="mb-3">
                <strong>Status:</strong> 
                <span class="badge status-' . $test['status'] . '">' . ucfirst($test['status']) . '</span>
                ' . ($result_type ? '
                <span class="badge ms-2 status-' . $result_type . '">' . ucfirst($result_type) . '</span>' : '') . '
            </div>
            ' . ($details ? '
            <div class="mb-3">
                <strong>Result Details:</strong>
                <div class="result-details mt-2 p-3 border rounded">
                    ' . nl2br(htmlspecialchars($details)) . '
                </div>
            </div>' : '') . '
            ' . ($recommendations ? '
            <div class="mb-3">
                <strong>Recommendations:</strong>
                <div class="recommendations mt-2 p-3 border rounded bg-light">
                    ' . nl2br(htmlspecialchars($recommendations)) . '
                </div>
            </div>' : '') . '
            ' . ($test['notes'] ? '
            <div class="mb-3">
                <strong>Additional Notes:</strong>
                <div class="notes-content mt-2 p-3 border rounded">
                    ' . nl2br(htmlspecialchars($test['notes'])) . '
                </div>
            </div>' : '') . '
        </div>
    ';

    echo json_encode([
        'success' => true,
        'html' => $html,
        'test_data' => $test
    ]);

} catch (Exception $e) {
    if (isset($conn) && $conn->connect_error === null) {
        error_log("Lab results processes error: " . $e->getMessage());
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
