<?php
// processes Endpoint: save_prescription.php
// Handles prescription creation by doctors, including validation, transaction management, stock update, and alternative medicine suggestions.
// Returns JSON response with success status and alternatives if available.
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is a doctor
// Ensures only authorized doctors can submit prescriptions.
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'doctor') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Only doctors can write prescriptions.']);
    exit();
}

// Check if required parameters are present
// Ensures all necessary data for prescription is provided.
if (!isset($_POST['patient_id']) || !isset($_POST['medicine_id']) || !isset($_POST['dosage']) || !isset($_POST['duration'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
    exit();
}

try {
    // Main logic block: Handles validation, transaction, prescription creation, stock update, and alternative medicine retrieval.
    $doctor_id = $_SESSION['user_id'];
    $patient_id = $_POST['patient_id'];
    $medicine_id = $_POST['medicine_id'];
    $dosage = $_POST['dosage'];
    $duration = intval($_POST['duration']);
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Validate patient exists
    // Ensures the patient ID refers to a valid patient in the system.
    $stmt = $conn->prepare("SELECT id FROM users WHERE id = ? AND role = 'patient'");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    if (!$stmt->get_result()->fetch_assoc()) {
        // If patient ID is invalid, return an error response with a 400 status code.
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid patient ID']);
        exit();
    }
    
    // Validate medicine exists and is in stock
    // Ensures the selected medicine is available and has sufficient stock.
    // This check prevents prescriptions from being created for medicines that are out of stock.
    $stmt = $conn->prepare("
        SELECT id, quantity, name 
        FROM medicines 
        WHERE id = ? AND quantity > 0
    ");
    $stmt->bind_param("i", $medicine_id);
    $stmt->execute();
    $medicine = $stmt->get_result()->fetch_assoc();
    if (!$medicine) {
        // If medicine is not available or out of stock, return an error response with a 400 status code.
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Medicine not available']);
        exit();
    }
    
    // Start transaction
    // Begin database transaction to ensure atomicity of prescription and stock update.
    $conn->begin_transaction();
    
    // Insert prescription
    // Adds the new prescription to the database with status 'active'.
    $stmt = $conn->prepare("
        INSERT INTO prescriptions 
        (patient_id, doctor_id, medicine_id, dosage, duration, notes, status, created_at) 
        VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())
    ");
    $stmt->bind_param("iiisis", $patient_id, $doctor_id, $medicine_id, $dosage, $duration, $notes);
    
    if ($stmt->execute()) {
        // If prescription insert is successful, update medicine stock and fetch alternatives.
        // Update medicine stock
        // Decrease current stock by 1 for the prescribed medicine.
        $stmt = $conn->prepare("UPDATE medicines SET current_stock = current_stock - 1 WHERE id = ?");
        $stmt->bind_param("i", $medicine_id);
        $stmt->execute();
        
        // Get alternative medicines
        // Suggest alternative medicines from the same category with available stock.
        $stmt = $conn->prepare("
            SELECT m.id, m.name, m.strength 
            FROM medicines m 
            WHERE m.category = (
                SELECT category FROM medicines WHERE id = ?
            )
            AND m.id != ?
            AND m.current_stock > 0
            LIMIT 5
        ");
        $stmt->bind_param("ii", $medicine_id, $medicine_id);
        $stmt->execute();
        $alternatives = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
        
        // Commit transaction
        // Finalize database changes if all operations succeed.
        $conn->commit();
        
        echo json_encode([
            'success' => true,
            'message' => 'Prescription saved successfully',
            'alternatives' => $alternatives
        ]);
    } else {
        // If prescription insert fails, throw an exception to trigger rollback
        throw new Exception('Failed to save prescription');
    }
} catch (Exception $e) {
    // Error handling: Rollback transaction and return error message.
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
