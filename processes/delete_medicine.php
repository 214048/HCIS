<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

if (!isset($_POST['medicine_id'])) {
    echo json_encode(['success' => false, 'message' => 'Medicine ID is required']);
    exit();
}

$medicine_id = $_POST['medicine_id'];

try {
    // Start transaction
    $conn->begin_transaction();
    
    // Check if this medicine is referenced in any prescription_items
    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescription_items WHERE medicine_id = ?");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    if ($result['count'] > 0) {
        throw new Exception('Cannot delete medicine: it is used in ' . $result['count'] . ' prescriptions');
    }
    
    // Delete from stock_logs if table exists
    $tables = $conn->query("SHOW TABLES LIKE 'stock_logs'");
    if ($tables->num_rows > 0) {
        $stmt = $conn->prepare("DELETE FROM stock_logs WHERE medicine_id = ?");
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
    }
    
    // Delete the medicine
    $stmt = $conn->prepare("DELETE FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('Medicine not found');
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode(['success' => true, 'message' => 'Medicine deleted successfully']);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 