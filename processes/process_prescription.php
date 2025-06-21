<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['prescription_id'])) {
    echo json_encode(['success' => false, 'message' => 'Prescription ID is required']);
    exit();
}

$prescription_id = $_POST['prescription_id'];

try {
    // Start transaction
    $conn->begin_transaction();

    // Get prescription details with prescription items
    $stmt = $conn->prepare("
        SELECT p.*, pi.id as item_id, pi.medicine_id, m.quantity
        FROM prescriptions p 
        JOIN prescription_items pi ON p.id = pi.prescription_id
        JOIN medicines m ON pi.medicine_id = m.id
        WHERE p.id = ? AND p.status = 'active'
    ");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('Prescription not found or already processed');
    }
    
    $prescription_items = $result->fetch_all(MYSQLI_ASSOC);
    
    // Check if all medications are in stock
    foreach ($prescription_items as $item) {
        if ($item['quantity'] <= 0) {
            throw new Exception('Medicine ' . $item['medicine_id'] . ' is out of stock');
        }
    }

    // Update prescription status
    $stmt = $conn->prepare("UPDATE prescriptions SET status = 'completed' WHERE id = ?");
    $stmt->bind_param("i", $prescription_id);
    $stmt->execute();

    // Update medicine stock for each item
    $stmt = $conn->prepare("UPDATE medicines SET quantity = quantity - 1 WHERE id = ?");
    foreach ($prescription_items as $item) {
        $stmt->bind_param("i", $item['medicine_id']);
        $stmt->execute();
    }

    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Prescription processed successfully']);
} catch (Exception $e) {
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
