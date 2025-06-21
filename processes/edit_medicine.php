<?php
session_start();
require_once '../includes/database.php';

header('Content-Type: application/json');

// Generate a unique request ID to detect duplicate requests
$request_id = $_POST['request_id'] ?? uniqid();
$cache_key = "medicine_edit_{$request_id}";

// Check if this request has already been processed
if (isset($_SESSION[$cache_key])) {
    error_log("Duplicate medicine edit request detected: {$request_id}");
    echo json_encode(['success' => true, 'message' => 'Medicine update already processed', 'duplicate' => true]);
    exit();
}

// Log the request
error_log('Edit Medicine Request: ' . json_encode($_POST));

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'pharmacist') {
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Check required fields
$required_fields = [
    'medicine_id',
    'brand_name',
    'drug_ingredient',
    'drug_class',
    'dosage_form',
    'strength',
    'drug_category',
    'used_for_what',
    'price'
];

foreach ($required_fields as $field) {
    if (!isset($_POST[$field]) || (is_string($_POST[$field]) && trim($_POST[$field]) === '')) {
        echo json_encode(['success' => false, 'message' => "Field {$field} is required", 'post_data' => $_POST]);
        exit();
    }
}

$medicine_id = intval($_POST['medicine_id']);
$brand_name = trim($_POST['brand_name']);
$drug_ingredient = trim($_POST['drug_ingredient']);
$drug_class = trim($_POST['drug_class']);
$dosage_form = trim($_POST['dosage_form']);
$strength = trim($_POST['strength']);
$drug_category = trim($_POST['drug_category']);
$used_for_what = trim($_POST['used_for_what']);
$price = floatval($_POST['price']);

// Quantity is optional
$quantity = isset($_POST['quantity']) && trim($_POST['quantity']) !== '' ? intval($_POST['quantity']) : null;

error_log("Processing medicine edit: ID: $medicine_id, Brand Name: $brand_name");

// Validate price
if ($price <= 0) {
    echo json_encode(['success' => false, 'message' => 'Price must be greater than 0']);
    exit();
}

try {
    // Check if medicine exists
    $stmt = $conn->prepare("SELECT id FROM medicines WHERE id = ?");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        echo json_encode(['success' => false, 'message' => 'Medicine not found']);
        exit();
    }
    
    // Build the update query based on whether quantity is provided
    if ($quantity !== null) {
        $sql = "UPDATE medicines SET 
                brand_name = ?, 
                drug_ingredient = ?, 
                drug_class = ?, 
                dosage_form = ?, 
                strength = ?, 
                drug_category = ?, 
                used_for_what = ?, 
                price = ?, 
                quantity = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssdii", 
            $brand_name, 
            $drug_ingredient, 
            $drug_class, 
            $dosage_form, 
            $strength, 
            $drug_category, 
            $used_for_what, 
            $price, 
            $quantity, 
            $medicine_id
        );
    } else {
        $sql = "UPDATE medicines SET 
                brand_name = ?, 
                drug_ingredient = ?, 
                drug_class = ?, 
                dosage_form = ?, 
                strength = ?, 
                drug_category = ?, 
                used_for_what = ?, 
                price = ? 
                WHERE id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssssssdi", 
            $brand_name, 
            $drug_ingredient, 
            $drug_class, 
            $dosage_form, 
            $strength, 
            $drug_category, 
            $used_for_what, 
            $price, 
            $medicine_id
        );
    }
    
    error_log("SQL Query: $sql");
    $stmt->execute();
    
    if ($stmt->error) {
        error_log("Error updating medicine: " . $stmt->error);
        throw new Exception("Database error: " . $stmt->error);
    }
    
    if ($stmt->affected_rows >= 0) {
        // Mark this request as processed
        $_SESSION[$cache_key] = true;
        
        echo json_encode([
            'success' => true, 
            'message' => 'Medicine updated successfully', 
            'affected_rows' => $stmt->affected_rows,
            'request_id' => $request_id
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Failed to update medicine']);
    }
} catch (Exception $e) {
    error_log("Medicine edit error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?> 