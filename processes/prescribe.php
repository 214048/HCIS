<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a doctor
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Check if required parameters are present
if (!isset($_POST['patient_id']) || !isset($_POST['medicines'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$patient_id = $_POST['patient_id'];
$medicines = json_decode($_POST['medicines'], true);
$notes = $_POST['notes'] ?? '';
$doctor_id = $_SESSION['user_id'];

if (!is_array($medicines) || empty($medicines)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid medicines data']);
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Verify patient exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
    if (!$stmt) {
        throw new Exception("Failed to prepare patient check query: " . $conn->error);
    }
    $stmt->bind_param("i", $patient_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to check patient: " . $stmt->error);
    }
    if ($stmt->get_result()->num_rows === 0) {
        throw new Exception("Patient not found");
    }

    // Create prescription
    $stmt = $conn->prepare("
        INSERT INTO prescriptions 
        (patient_id, doctor_id, notes, prescribed_date, status)
        VALUES (?, ?, ?, CURRENT_TIMESTAMP, 'active')
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare prescription query: " . $conn->error);
    }
    $stmt->bind_param("iis", $patient_id, $doctor_id, $notes);
    if (!$stmt->execute()) {
        throw new Exception("Failed to create prescription: " . $stmt->error);
    }

    $prescription_id = $conn->insert_id;

    // Add prescription items
    $stmt = $conn->prepare("
        INSERT INTO prescription_items 
        (prescription_id, medicine_id, dosage, frequency, duration, instructions)
        VALUES (?, ?, ?, ?, ?, ?)
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare prescription items query: " . $conn->error);
    }

    foreach ($medicines as $medicine) {
        // Check if medicine is in stock
        $check_stock = $conn->prepare("SELECT quantity FROM medicines WHERE id = ? AND quantity > 0");
        if (!$check_stock) {
            throw new Exception("Failed to check medicine stock: " . $conn->error);
        }
        $check_stock->bind_param("i", $medicine['medicine_id']);
        if (!$check_stock->execute()) {
            throw new Exception("Failed to execute stock check: " . $check_stock->error);
        }
        $stock_result = $check_stock->get_result();
        if ($stock_result->num_rows === 0) {
            throw new Exception("Medicine ID {$medicine['medicine_id']} is out of stock");
        }

        // Set default values for missing fields
        $dosage = !empty($medicine['dosage']) ? $medicine['dosage'] : 'Standard dose';
        $frequency = $medicine['frequency'];
        $duration = $medicine['duration'];
        $instructions = !empty($medicine['instructions']) ? $medicine['instructions'] : '';

        $stmt->bind_param(
            "iissss",
            $prescription_id,
            $medicine['medicine_id'],
            $dosage,
            $frequency,
            $duration,
            $instructions
        );
        if (!$stmt->execute()) {
            throw new Exception("Failed to add medicine to prescription: " . $stmt->error);
        }
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Prescription created successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
