<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

$required_fields = ['name', 'category', 'strength', 'stock', 'price'];
foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || empty($_POST[$field])) {
        echo json_encode(['success' => false, 'message' => ucfirst($field) . ' is required']);
        exit();
    }
}

try {
    $stmt = $conn->prepare("
        INSERT INTO medicines (name, category, strength, current_stock, unit_price) 
        VALUES (?, ?, ?, ?, ?)
    ");
    $stmt->bind_param(
        "sssid", 
        $_POST['name'], 
        $_POST['category'], 
        $_POST['strength'], 
        $_POST['stock'], 
        $_POST['price']
    );
    $stmt->execute();
    
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Failed to add medicine: ' . $e->getMessage()]);
}
?>
