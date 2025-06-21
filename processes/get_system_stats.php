<?php
require_once '../includes/session_manager.php';
require_once '../includes/database.php';

header('Content-Type: application/json');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

try {
    // Get user counts by role
    $stmt = $conn->prepare("
        SELECT role, COUNT(*) as count 
        FROM users 
        WHERE role != 'admin'
        GROUP BY role
    ");
    $stmt->execute();
    $role_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    
    // Format role counts
    $stats = [
        'doctors' => 0,
        'patients' => 0,
        'pharmacists' => 0,
        'lab_technicians' => 0
    ];
    
    foreach ($role_counts as $rc) {
        switch ($rc['role']) {
            case 'doctor':
                $stats['doctors'] = (int)$rc['count'];
                break;
            case 'patient':
                $stats['patients'] = (int)$rc['count'];
                break;
            case 'pharmacist':
                $stats['pharmacists'] = (int)$rc['count'];
                break;
            case 'lab':
                $stats['lab_technicians'] = (int)$rc['count'];
                break;
        }
    }
    
    // Get appointment stats
    $stmt = $conn->query("
        SELECT 
            COUNT(*) as total_appointments,
            SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_appointments,
            SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled_appointments
        FROM appointments
        WHERE appointment_date >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $appointment_stats = $stmt->fetch_assoc();
    
    // Get recent activities
    $stmt = $conn->query("
        SELECT 'Appointment' as type, id, 'Booked' as action, created_at 
        FROM appointments 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        UNION ALL
        SELECT 'Prescription' as type, id, 'Issued' as action, created_at 
        FROM prescriptions 
        WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $recent_activities = $stmt->fetch_all(MYSQLI_ASSOC);
    
    // Generate HTML for the stats
    ob_start();
    ?>
    <div class="row g-4">
        <!-- User Statistics -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header">
                    <h6 class="mb-0">User Statistics</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(13, 110, 253, 0.1);">
                                <div class="bg-primary bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-user-md fs-4 text-primary"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['doctors']; ?></h4>
                                    <small class="text-muted">Doctors</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(25, 135, 84, 0.1);">
                                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-user-injured fs-4 text-success"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['patients']; ?></h4>
                                    <small class="text-muted">Patients</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(13, 202, 240, 0.1);">
                                <div class="bg-info bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-pills fs-4 text-info"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['pharmacists']; ?></h4>
                                    <small class="text-muted">Pharmacists</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(255, 193, 7, 0.1);">
                                <div class="bg-warning bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-flask fs-4 text-warning"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo $stats['lab_technicians']; ?></h4>
                                    <small class="text-muted">Lab Technicians</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Appointment Statistics -->
        <div class="col-md-6">
            <div class="card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="mb-0">Appointment Statistics (Last 30 Days)</h6>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(111, 66, 193, 0.1);">
                                <div class="bg-purple bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-calendar-check fs-4 text-purple"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo (int)$appointment_stats['total_appointments']; ?></h4>
                                    <small class="text-muted">Total Appointments</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(25, 135, 84, 0.1);">
                                <div class="bg-success bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-check-circle fs-4 text-success"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo (int)$appointment_stats['completed_appointments']; ?></h4>
                                    <small class="text-muted">Completed</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex align-items-center p-3 rounded-3" style="background-color: rgba(220, 53, 69, 0.1);">
                                <div class="bg-danger bg-opacity-10 p-3 rounded me-3">
                                    <i class="fas fa-times-circle fs-4 text-danger"></i>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?php echo (int)$appointment_stats['cancelled_appointments']; ?></h4>
                                    <small class="text-muted">Cancelled</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Activities -->
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h6 class="mb-0">Recent Activities</h6>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($recent_activities)): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($recent_activities as $activity): ?>
                                <div class="list-group-item">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <?php if ($activity['type'] === 'Appointment'): ?>
                                                <div class="avatar-sm rounded-circle bg-primary bg-opacity-10 p-2">
                                                    <i class="fas fa-calendar-check text-primary"></i>
                                                </div>
                                            <?php else: ?>
                                                <div class="avatar-sm rounded-circle bg-success bg-opacity-10 p-2">
                                                    <i class="fas fa-prescription text-success"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="flex-grow-1">
                                            <h6 class="mb-1">
                                                <?php echo htmlspecialchars($activity['type']); ?> #<?php echo $activity['id']; ?>
                                                <span class="text-muted"><?php echo htmlspecialchars($activity['action']); ?></span>
                                            </h6>
                                            <p class="mb-0 text-muted small">
                                                <i class="far fa-clock me-1"></i>
                                                <?php echo date('M d, Y h:i A', strtotime($activity['created_at'])); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center p-4">
                            <div class="avatar-lg mx-auto mb-3">
                                <div class="avatar-title bg-light text-primary rounded-circle">
                                    <i class="fas fa-inbox fs-2"></i>
                                </div>
                            </div>
                            <h5>No recent activities</h5>
                            <p class="text-muted">Activities will appear here</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <?php
    $html = ob_get_clean();

    echo json_encode([
        'success' => true,
        'html' => $html
    ]);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Error retrieving system statistics: ' . $e->getMessage()
    ]);
}
