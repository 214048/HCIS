<?php
require_once '../includes/session_manager.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

try {
    if (!isset($_GET['user_id'])) {
        throw new Exception("User ID is required");
    }

    $user_id = $_GET['user_id'];
    
    $stmt = $conn->prepare("
        SELECT id, name, email, role, specialization, phone, address,
               gender, date_of_birth, blood_group, emergency_contact,
               emergency_phone, allergies, created_at
        FROM users 
        WHERE id = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    echo json_encode([
        'success' => true,
        'data' => $user
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
