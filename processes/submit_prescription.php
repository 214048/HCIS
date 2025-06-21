<?php
require_once '../includes/database.php';
require_once '../includes/session_manager.php';

checkSession('doctor');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['success' => false, 'error' => 'Method not allowed']));
}

$doctor_id = $_SESSION['user_id'];
$patient_id = $_POST['patient_id'] ?? null;
$notes = $_POST['notes'] ?? '';
$medications_json = $_POST['medications'] ?? null;

if (!$patient_id || !$medications_json) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Missing required fields']));
}

$medications = json_decode($medications_json, true);
if (!is_array($medications) || count($medications) === 0) {
    http_response_code(400);
    exit(json_encode(['success' => false, 'error' => 'Invalid medications data']));
}

$conn->begin_transaction();

try {
    $stmt = $conn->prepare("INSERT INTO prescriptions (doctor_id, patient_id, medicine_id, dosage, frequency, duration, notes, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
    foreach ($medications as $med) {
        if (empty($med['medicine_id']) || empty($med['dosage']) || empty($med['frequency']) || empty($med['duration'])) {
            throw new Exception('Incomplete medication data');
        }
        $stmt->bind_param(
            "iiissss",
            $doctor_id,
            $patient_id,
            $med['medicine_id'],
            $med['dosage'],
            $med['frequency'],
            $med['duration'],
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
