<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $user_id = $_POST['user_id'];

    // Start transaction
    $conn->begin_transaction();

    // Check if user exists and is not an admin
    $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 0) {
        throw new Exception('User not found');
    }
    
    $user = $result->fetch_assoc();
    if ($user['role'] === 'admin') {
        throw new Exception('Cannot delete admin users');
    }

    // Delete user's appointments
    $stmt = $conn->prepare("DELETE FROM appointments WHERE patient_id = ? OR doctor_id = ?");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();

    // Delete user's prescriptions
    $stmt = $conn->prepare("DELETE FROM prescriptions WHERE patient_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    // Delete user's lab tests
    $stmt = $conn->prepare("DELETE FROM lab_tests WHERE patient_id = ? OR doctor_id = ?");
    $stmt->bind_param("ii", $user_id, $user_id);
    $stmt->execute();

    // Finally, delete the user
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        throw new Exception('Failed to delete user');
    }

    $conn->commit();
    echo json_encode(['success' => true, 'message' => 'User deleted successfully']);

} catch (Exception $e) {
    if ($conn->connect_error === null) {
        $conn->rollback();
    }
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
