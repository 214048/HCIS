<?php
header('Content-Type: application/json');
require_once '../includes/database.php';

try {
    $search = isset($_GET['search']) ? '%' . $_GET['search'] . '%' : '%';
    $show_all = isset($_GET['show_all']) && $_GET['show_all'] === 'true';
    
    $sql = "
        SELECT 
            id, 
            name, 
            strength, 
            category, 
            quantity,
            CASE 
                WHEN quantity = 0 THEN 'Out of Stock'
                WHEN quantity < 10 THEN 'Low Stock'
                ELSE 'In Stock'
            END as stock_status
        FROM medicines
        WHERE (name LIKE ? OR category LIKE ?)
    ";
    
    if (!$show_all) {
        $sql .= " AND quantity > 0";
    }
    
    $sql .= " ORDER BY 
        CASE 
            WHEN quantity = 0 THEN 2
            WHEN quantity < 10 THEN 1
            ELSE 0
        END,
        name ASC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ss", $search, $search);
    $stmt->execute();
    $result = $stmt->get_result();
    
    $medicines = [];
    while ($row = $result->fetch_assoc()) {
        $medicines[] = $row;
    }
    
    echo json_encode(['success' => true, 'data' => $medicines]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error fetching medicines']);
}
?>
