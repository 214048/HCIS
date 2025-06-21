<?php
require_once '../includes/database.php';
require_once '../includes/session_manager.php';

// Check if user is logged in and is a pharmacist
checkSession('pharmacist');

header('Content-Type: application/json');

try {
    // Validate input
    if (!isset($_POST['medicine_id']) || !is_numeric($_POST['medicine_id'])) {
        throw new Exception("Invalid medicine ID");
    }
    
    if (!isset($_POST['quantity']) || !is_numeric($_POST['quantity']) || $_POST['quantity'] <= 0) {
        throw new Exception("Invalid quantity");
    }
    
    if (!isset($_POST['action']) || !in_array($_POST['action'], ['add', 'subtract'])) {
        throw new Exception("Invalid action");
    }
    
    $medicine_id = (int)$_POST['medicine_id'];
    $quantity = (int)$_POST['quantity'];
    $action = $_POST['action'];
    
    // Start transaction
    $conn->begin_transaction();
    
    try {
        // Get current quantity
        $stmt = $conn->prepare("SELECT quantity FROM medicines WHERE id = ? FOR UPDATE");
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if (!$medicine = $result->fetch_assoc()) {
            throw new Exception("Medicine not found");
        }
        
        // Calculate new quantity
        $current_quantity = $medicine['quantity'];
        $new_quantity = $action === 'add' ? 
            $current_quantity + $quantity : 
            $current_quantity - $quantity;
        
        // Check if we have enough stock for subtraction
        if ($action === 'subtract' && $new_quantity < 0) {
            throw new Exception("Not enough stock available");
        }
        
        // Update quantity
        $stmt = $conn->prepare("UPDATE medicines SET quantity = ? WHERE id = ?");
        $stmt->bind_param("ii", $new_quantity, $medicine_id);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update stock");
        }
        
        // Commit transaction
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Stock updated successfully',
            'new_quantity' => $new_quantity
        ]);
        
    } catch (Exception $e) {
        // Rollback transaction on error
        $conn->rollback();
        throw $e;
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
