<?php
require_once '../includes/database.php';
require_once '../includes/session_manager.php';

// Check if user is logged in and is a pharmacist
checkSession('pharmacist');

header('Content-Type: application/json');

try {
    $data = $_POST;
    
    // Validate required fields
    $required_fields = [
        'brand_name', 'drug_ingredient', 'drug_class', 'dosage_form',
        'strength', 'drug_category', 'used_for_what', 'price', 'quantity'
    ];
    
    foreach ($required_fields as $field) {
        if (!isset($data[$field]) || empty($data[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }
    
    // Validate numeric fields
    if (!is_numeric($data['price']) || $data['price'] < 0) {
        throw new Exception("Invalid price value");
    }
    
    if (!is_numeric($data['quantity']) || $data['quantity'] < 0) {
        throw new Exception("Invalid quantity value");
    }
    
    // If ID is provided, update existing medicine
    if (!empty($data['id'])) {
        $stmt = $conn->prepare("
            UPDATE medicines SET 
                brand_name = ?,
                drug_ingredient = ?,
                drug_class = ?,
                dosage_form = ?,
                strength = ?,
                drug_category = ?,
                used_for_what = ?,
                price = ?,
                quantity = ?
            WHERE id = ?
        ");
        
        $stmt->bind_param(
            "sssssssdii",
            $data['brand_name'],
            $data['drug_ingredient'],
            $data['drug_class'],
            $data['dosage_form'],
            $data['strength'],
            $data['drug_category'],
            $data['used_for_what'],
            $data['price'],
            $data['quantity'],
            $data['id']
        );
    }
    // Otherwise insert new medicine
    else {
        $stmt = $conn->prepare("
            INSERT INTO medicines (
                brand_name, drug_ingredient, drug_class, dosage_form,
                strength, drug_category, used_for_what, price, quantity
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->bind_param(
            "sssssssdi",
            $data['brand_name'],
            $data['drug_ingredient'],
            $data['drug_class'],
            $data['dosage_form'],
            $data['strength'],
            $data['drug_category'],
            $data['used_for_what'],
            $data['price'],
            $data['quantity']
        );
    }
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => !empty($data['id']) ? 'Medicine updated successfully' : 'Medicine added successfully'
        ]);
    } else {
        throw new Exception("Database error: " . $stmt->error);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
} 