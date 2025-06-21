<?php
require_once '../includes/session_manager.php';
require_once '../includes/database.php';

// Check if user is logged in and is a doctor
checkSession('doctor');

if (!isset($_GET['patient_id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Patient ID is required']);
    exit();
}

$patient_id = $_GET['patient_id'];
$doctor_id = $_SESSION['user_id'];

// Get patient details
$stmt = $conn->prepare("
    SELECT name as patient_name, date_of_birth, gender
    FROM users 
    WHERE id = ? AND role = 'patient'
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

if (!$patient) {
    http_response_code(404);
    echo json_encode(['error' => 'Patient not found']);
    exit();
}

// Get patient's medical history
$stmt = $conn->prepare("
    SELECT condition_name, status, diagnosis_date, notes
    FROM medical_history
    WHERE patient_id = ?
    ORDER BY diagnosis_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get available medicines
$stmt = $conn->prepare("
    SELECT id, name, generic_name, category, dosage_form, strength
    FROM medicines
    WHERE quantity > 0
    ORDER BY name ASC
");
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get patient's active prescriptions
$stmt = $conn->prepare("
    SELECT 
        p.id,
        p.prescribed_date,
        GROUP_CONCAT(CONCAT(m.name, ' (', m.strength, ')') SEPARATOR ', ') as medicines,
        p.status,
        p.notes
    FROM prescriptions p
    LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
    LEFT JOIN medicines m ON pi.medicine_id = m.id
    WHERE p.patient_id = ? AND p.status = 'active'
    GROUP BY p.id
    ORDER BY p.prescribed_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$active_prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

echo json_encode([
    'patient' => $patient,
    'medical_history' => $medical_history,
    'medicines' => $medicines,
    'active_prescriptions' => $active_prescriptions
]);
