<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Get pending prescriptions
    $stmt = $conn->prepare("
        SELECT p.*, 
               u_patient.name as patient_name,
               u_doctor.name as doctor_name,
               GROUP_CONCAT(CONCAT(m.name, ' (', m.strength, ')') SEPARATOR ', ') as medicines,
               m.quantity as current_stock
        FROM prescriptions p
        JOIN users u_patient ON p.patient_id = u_patient.id
        JOIN users u_doctor ON p.doctor_id = u_doctor.id
        LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
        LEFT JOIN medicines m ON pi.medicine_id = m.id
        WHERE p.status = 'active'
        GROUP BY p.id, u_patient.name, u_doctor.name
        ORDER BY p.created_at DESC
    ");
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo json_encode([
        'success' => true,
        'prescriptions' => $prescriptions
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 