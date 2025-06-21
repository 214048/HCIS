<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a pharmacist
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only pharmacists can dispense medicine.']);
    exit();
}

// Check if prescription ID is provided
if (!isset($_POST['prescription_id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing prescription ID']);
    exit();
}

try {
    $prescription_id = $_POST['prescription_id'];
    $pharmacist_id = $_SESSION['user_id'];
    
    // Start transaction
    $conn->begin_transaction();
    
    // Get prescription details
    $stmt = $conn->prepare("
        SELECT p.*, 
               GROUP_CONCAT(CONCAT(m.name, ' (', m.strength, ')') SEPARATOR ', ') as medicines,
               m.quantity as current_stock
        FROM prescriptions p
        LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
        LEFT JOIN medicines m ON pi.medicine_id = m.id
        WHERE p.id = ? AND p.status = 'active'
        GROUP BY p.id
    ");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $prescription = $stmt->get_result()->fetch_assoc();
    
    if (!$prescription) {
        throw new Exception('Prescription not found or already dispensed');
    }
    
    if ($prescription['quantity'] <= 0) {
        throw new Exception('Medicine out of stock');
    }
    
    // Update prescription status
    $stmt = $conn->prepare("
        UPDATE prescriptions 
        SET status = 'dispensed', 
            dispensed_by = ?,
            dispensed_at = NOW() 
        WHERE id = ?
    ");
    $stmt->bind_param("ii", $pharmacist_id, $prescription_id);
    $stmt->execute();
    
    // Update medicine stock
    $stmt = $conn->prepare("
        UPDATE medicines 
        SET quantity = quantity - 1 
        WHERE id = ? AND quantity > 0
    ");
    $stmt->bind_param("i", $prescription['medicine_id']);
    $stmt->execute();
    
    // Create notification for the patient
    $stmt = $conn->prepare("
        INSERT INTO notifications 
        (user_id, type, message, reference_id, created_at)
        VALUES (?, 'medicine_dispensed', 'Your medicine has been dispensed', ?, NOW())
    ");
    $stmt->bind_param("ii", $prescription['patient_id'], $prescription_id);
    $stmt->execute();
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Medicine dispensed successfully'
    ]);
    
} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
