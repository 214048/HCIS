<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only doctors can request lab tests.']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['patient_id']) || !isset($_POST['test_type']) || !isset($_POST['urgency'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

// Validate urgency
$valid_urgencies = ['routine', 'urgent', 'emergency'];
if (!in_array($_POST['urgency'], $valid_urgencies)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid urgency level']);
    exit();
}

try {
    $doctor_id = $_SESSION['user_id'];
    $patient_id = $_POST['patient_id'];
    $test_type = $_POST['test_type'];
    $urgency = $_POST['urgency'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Get lab staff count
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE role = 'lab' AND status = 'active'");
    $stmt->execute();
    $lab_staff_count = $stmt->get_result()->fetch_assoc()['count'];
    
    // Validate patient exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
        exit();
    }
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Insert lab test request
        $stmt = $conn->prepare("
            INSERT INTO lab_tests 
            (patient_id, doctor_id, test_type, urgency, notes, status, requested_date) 
            VALUES (?, ?, ?, ?, ?, 'pending', NOW())
        ");
        $stmt->bind_param("iisss", $patient_id, $doctor_id, $test_type, $urgency, $notes);
        $stmt->execute();
        
        // Get the lab test ID
        $test_id = $conn->insert_id;
        
        // Create notification for lab staff
        $stmt = $conn->prepare("
            INSERT INTO notifications 
            (user_id, type, message, reference_id, created_at) 
            SELECT id, 'lab_test', 'New lab test request received', ?, NOW()
            FROM users WHERE role = 'lab'
        ");
        $stmt->bind_param("i", $test_id);
        $stmt->execute();
        
        // Commit transaction
        $conn->commit();
        
        // Calculate estimated wait time
        $wait_time = 'No lab staff available';
        if ($lab_staff_count > 0) {
            switch ($urgency) {
                case 'emergency':
                    $wait_time = '1-2 hours';
                    break;
                case 'urgent':
                    $wait_time = '24 hours';
                    break;
                default:
                    $wait_time = '2-3 days';
            }
        }
        
        echo json_encode([
            'success' => true, 
            'message' => 'Lab test requested successfully',
            'data' => [
                'test_id' => $test_id,
                'estimated_wait' => $wait_time,
                'lab_staff_available' => $lab_staff_count > 0
            ]
        ]);
    } catch (Exception $e) {
        $conn->rollback();
        throw $e;
    }

} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
