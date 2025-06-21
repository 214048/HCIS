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
if (!isset($_POST['patient_id']) || !isset($_POST['medicine_id']) || 
    !isset($_POST['dosage']) || !isset($_POST['duration'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

$patient_id = $_POST['patient_id'];
$medicine_id = $_POST['medicine_id'];
$dosage = $_POST['dosage'];
$duration = $_POST['duration'];
$timing = $_POST['timing'] ?? '';
$instructions = $_POST['instructions'] ?? '';
$doctor_id = $_SESSION['user_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if medicine exists and has stock
    $stmt = $conn->prepare("
        SELECT current_stock, name 
        FROM medicines 
        WHERE id = ? AND current_stock > 0
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare medicine check query: " . $conn->error);
    }
    $stmt->bind_param("i", $medicine_id);
    if (!$stmt->execute()) {
        throw new Exception("Failed to check medicine: " . $stmt->error);
    }
    $medicine = $stmt->get_result()->fetch_assoc();
    
    if (!$medicine) {
        throw new Exception('Medicine not found or out of stock');
    }
    
    // Insert prescription
    $stmt = $conn->prepare("
        INSERT INTO prescriptions (
            patient_id, 
            doctor_id, 
            medicine_id, 
            dosage_per_day,
            duration_days,
            timing,
            special_instructions,
            prescribed_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare prescription insert query: " . $conn->error);
    }
    $stmt->bind_param("iiiisss", 
        $patient_id, 
        $doctor_id, 
        $medicine_id, 
        $dosage,
        $duration,
        $timing,
        $instructions
    );
    if (!$stmt->execute()) {
        throw new Exception("Failed to create prescription: " . $stmt->error);
    }
    
    // Update medicine stock
    $total_units = $dosage * $duration;
    $stmt = $conn->prepare("
        UPDATE medicines 
        SET current_stock = current_stock - ?
        WHERE id = ? AND current_stock >= ?
    ");
    if (!$stmt) {
        throw new Exception("Failed to prepare stock update query: " . $conn->error);
    }
    $stmt->bind_param("iii", $total_units, $medicine_id, $total_units);
    if (!$stmt->execute()) {
        throw new Exception("Failed to update medicine stock: " . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception("Insufficient stock for " . $medicine['name']);
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Prescription created successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
