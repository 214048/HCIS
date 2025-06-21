<?php
require_once '../includes/database.php';
require_once '../includes/session_manager.php';

// Check if user is logged in and is a pharmacist
checkSession('pharmacist');

header('Content-Type: application/json');

try {
    if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
        throw new Exception("Invalid medicine ID");
    }
    
    $id = (int)$_GET['id'];
    
    $stmt = $conn->prepare("
        SELECT * FROM medicines WHERE id = ?
    ");
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($medicine = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'data' => $medicine
        ]);
    } else {
        throw new Exception("Medicine not found");
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 