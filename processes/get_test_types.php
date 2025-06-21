<?php
header('Content-Type: application/json');
require_once '../includes/database.php';

try {
    $sql = "SELECT id, name, description, base_price FROM test_types ORDER BY name";
    $result = $conn->query($sql);
    
    $test_types = [];
    while ($row = $result->fetch_assoc()) {
        $test_types[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $test_types]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching test types']);
}
?>
