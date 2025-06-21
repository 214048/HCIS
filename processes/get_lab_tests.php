
<?php
header('Content-Type: application/json');
session_start();
require_once '../includes/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

try {
    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'];
    $patient_id = isset($_GET['patient_id']) ? (int)$_GET['patient_id'] : null;

    // Base query depending on user role
    $query = "
        SELECT l.*,
               p.name as patient_name,
               d.name as doctor_name
        FROM lab_tests l
        JOIN users p ON l.patient_id = p.id
        JOIN users d ON l.doctor_id = d.id
    ";

    // Add WHERE clause based on role and patient_id
    if ($role === 'doctor') {
        $query .= " WHERE l.doctor_id = ?";
        $param_type = "i";
        $param_value = $user_id;
        
        // If patient_id is provided, add it to the WHERE clause
        if ($patient_id) {
            $query .= " AND l.patient_id = ?";
            $param_type = "ii";
            $params = [$user_id, $patient_id];
        }
    } elseif ($role === 'patient') {
        $query .= " WHERE l.patient_id = ?";
        $param_type = "i";
        $param_value = $user_id;
    } elseif ($role === 'lab') {
        if ($patient_id) {
            $query .= " WHERE l.patient_id = ?";
            $param_type = "i";
            $param_value = $patient_id;
        } else {
            $query .= " WHERE 1=1"; // Show all tests for lab staff
        }
    } else {
        throw new Exception("Invalid role");
    }

    // Add order by
    $query .= " ORDER BY l.requested_date DESC";

    $stmt = $conn->prepare($query);
    if (!$stmt) {
        throw new Exception("Failed to prepare query: " . $conn->error);
    }

    // Bind parameters
    if (isset($param_type)) {
        if (isset($params)) {
            $stmt->bind_param($param_type, ...$params);
        } else {
            $stmt->bind_param($param_type, $param_value);
        }
    }

    if (!$stmt->execute()) {
        throw new Exception("Failed to execute query: " . $stmt->error);
    }

    $result = $stmt->get_result();
    $lab_tests = [];

    while ($row = $result->fetch_assoc()) {
        // Format dates
        $row['requested_date'] = date('Y-m-d H:i:s', strtotime($row['requested_date']));
        if (isset($row['completed_date']) && $row['completed_date']) {
            $row['completed_date'] = date('Y-m-d H:i:s', strtotime($row['completed_date']));
        }
        $lab_tests[] = $row;
    }

    echo json_encode([
        'success' => true,
        'lab_tests' => $lab_tests
    ]);

} catch (Exception $e) {
    http_response_code(500);
    error_log('get_lab_tests.php error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'lab_tests' => []
    ]);
}
?>
