<?php
require_once '../includes/session_manager.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is a lab technician
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'lab') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$lab_id = isset($_GET['lab_id']) ? (int)$_GET['lab_id'] : 0;

if (!$lab_id) {
    echo json_encode(['success' => false, 'message' => 'Invalid lab ID']);
    exit;
}

try {
    // Get processing tests
    $stmt = $conn->prepare("
        SELECT t.*, p.name as patient_name, d.name as doctor_name 
        FROM lab_tests t 
        JOIN users p ON t.patient_id = p.id 
        JOIN users d ON t.doctor_id = d.id 
        WHERE t.status = 'processing' AND t.lab_technician_id = ?
        ORDER BY t.requested_date ASC
    ");
    $stmt->bind_param("i", $lab_id);
    $stmt->execute();
    $processing_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Generate HTML for the tests
    ob_start();
    if (!empty($processing_tests)) {
        ?>
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Patient</th>
                        <th>Test Type</th>
                        <th>Requesting Doctor</th>
                        <th>Requested Date</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($processing_tests as $test): ?>
                    <tr>
                        <td>
                            <?php echo htmlspecialchars($test['patient_name']); ?>
                            <small class="text-muted d-block">ID: <?php echo $test['patient_id']; ?></small>
                        </td>
                        <td>
                            <?php echo htmlspecialchars($test['test_type']); ?>
                            <?php if ($test['notes']): ?>
                            <small class="text-muted d-block"><?php echo htmlspecialchars($test['notes']); ?></small>
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($test['doctor_name']); ?></td>
                        <td>
                            <?php echo date('M d, Y', strtotime($test['requested_date'])); ?>
                            <small class="text-muted d-block"><?php echo date('h:i A', strtotime($test['requested_date'])); ?></small>
                        </td>
                        <td>
                            <span class="badge bg-warning">In Progress</span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-success" onclick="completeTest(<?php echo $test['id']; ?>)">
                                <i class="fas fa-check me-1"></i>Complete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
    } else {
        echo '<div class="alert alert-info">No tests in progress.</div>';
    }
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving processing tests: ' . $e->getMessage()
    ]);
}
