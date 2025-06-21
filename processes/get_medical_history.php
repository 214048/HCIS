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
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    
    // For patients, use their own ID
    if ($role === 'patient') {
        $patient_id = $user_id;
    } 
    // For doctors, get patient ID from query params
    else if ($role === 'doctor' && isset($_GET['patient_id'])) {
        $patient_id = $_GET['patient_id'];
    } else {
        throw new Exception('Invalid role or missing patient ID');
    }
    
    // Get medical history for the patient
    $stmt = $conn->prepare("
        SELECT mh.*,
               d.name as doctor_name,
               d.specialization as doctor_specialization
        FROM medical_history mh
        JOIN users d ON mh.doctor_id = d.id
        WHERE mh.patient_id = ?
        ORDER BY mh.diagnosis_date DESC
    ");
    
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $medical_history = [];
    
    while ($row = $result->fetch_assoc()) {
        // Format dates
        $row['diagnosis_date'] = date('Y-m-d', strtotime($row['diagnosis_date']));
        if ($row['resolution_date']) {
            $row['resolution_date'] = date('Y-m-d', strtotime($row['resolution_date']));
        }
        
        // Add status based on resolution date and active flag
        if ($row['active'] == 0) {
            $row['status'] = 'resolved';
        } else if ($row['resolution_date']) {
            $row['status'] = 'resolving';
        } else {
            $row['status'] = 'active';
        }
        
        $medical_history[] = $row;
    }
    
    echo json_encode([
        'success' => true,
        'data' => $medical_history
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
