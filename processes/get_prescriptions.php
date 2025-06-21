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
    
    // Get prescriptions for the patient
    $stmt = $conn->prepare("
        SELECT p.*, 
               u_patient.name as patient_name,
               u_doctor.name as doctor_name,
               GROUP_CONCAT(CONCAT(m.name, ' (', m.strength, ')') SEPARATOR ', ') as medicines
        FROM prescriptions p
        JOIN users u_patient ON p.patient_id = u_patient.id
        JOIN users u_doctor ON p.doctor_id = u_doctor.id
        LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
        LEFT JOIN medicines m ON pi.medicine_id = m.id
        WHERE p.status = ?
        GROUP BY p.id, u_patient.name, u_doctor.name
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("s", $status);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    echo json_encode([
        'success' => true,
        'data' => $prescriptions
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
