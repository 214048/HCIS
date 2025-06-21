<?php
// processes Endpoint: get_doctors.php
// Returns a list of all active doctors in the system as a JSON array.
// Only accessible to authenticated users.
header('Content-Type: application/json');
session_start(); // Start PHP session for authentication
require_once '../includes/database.php'; // Database connection

// Check if user is logged in
// Ensures only authenticated users can access this endpoint.
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    // Main logic block: Fetch all active doctors from the users table.
    // Get active doctors
    // Selects all users with role 'doctor' and status active.
    $stmt = $conn->prepare("
        SELECT id, name, specialization 
        FROM users 
        WHERE role = 'doctor'
        ORDER BY name
    ");
    $stmt->execute();
    $result = $stmt->get_result();
    
    $doctors = []; // Array to hold doctor records
    while ($row = $result->fetch_assoc()) {
        // Add each doctor to the array
        $doctors[] = $row;
    }
    
    if (empty($doctors)) {
        // If no doctors found, return a message indicating none are available.
        echo json_encode([
            'success' => false, 
            'message' => 'No doctors available at the moment'
        ]);
        exit();
    }
    
    // Return the list of doctors as a JSON response
    echo json_encode(['success' => true, 'data' => $doctors]);

} catch (Exception $e) {
    // Error handling: Return a 500 error and message if fetching fails.
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Error fetching doctors. Please try again later.'
    ]);
}
?>
