<?php
require_once '../includes/session_manager.php'; // Session management utilities
require_once '../includes/database.php'; // Database connection

// Check if user is logged in and is a lab technician
// Ensures only authorized lab staff can access this dashboard.
checkSession('lab');

// Get lab staff information
// Fetches logged-in lab technician's details for display.
$lab_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'lab'");
$stmt->bind_param("i", $lab_id);
$stmt->execute();
$lab_staff = $stmt->get_result()->fetch_assoc();

// Get pending tests
// Fetches all lab tests that are pending and need to be processed.
$stmt = $conn->prepare("
    SELECT t.*, p.name as patient_name, d.name as doctor_name 
    FROM lab_tests t 
    JOIN users p ON t.patient_id = p.id 
    JOIN users d ON t.doctor_id = d.id 
    WHERE t.status = 'pending'
    ORDER BY t.urgency DESC, t.requested_date ASC
");
$stmt->execute();
$pending_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get in-processing tests
// Fetches lab tests currently being processed.
$stmt = $conn->prepare("
    SELECT t.*, p.name as patient_name, d.name as doctor_name 
    FROM lab_tests t 
    JOIN users p ON t.patient_id = p.id 
    JOIN users d ON t.doctor_id = d.id 
    WHERE t.status = 'processing'
    ORDER BY t.requested_date ASC
");
$stmt->execute();
$processing_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get completed tests
// Fetches recently completed lab tests.
$stmt = $conn->prepare("
    SELECT t.*, p.name as patient_name, d.name as doctor_name 
    FROM lab_tests t 
    JOIN users p ON t.patient_id = p.id 
    JOIN users d ON t.doctor_id = d.id 
    WHERE t.status = 'completed'
    ORDER BY t.completed_date DESC
    LIMIT 50
");
$stmt->execute();
$completed_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get today's completed tests
// Fetches count of lab tests completed today for stats.
$today = date('Y-m-d');
$stmt = $conn->prepare("
    SELECT COUNT(*) as count 
    FROM lab_tests 
    WHERE DATE(completed_date) = ? AND status = 'completed'
");
$stmt->bind_param("s", $today);
$stmt->execute();
$completed_today = $stmt->get_result()->fetch_assoc()['count'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Dashboard - E-Health</title>
    <?php include_once '../includes/head_elements.php'; ?>
    <?php include_once '../includes/css_links.php'; ?>
    <!-- Add flatpickr CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/flatpickr/dist/flatpickr.min.css">
    <style>
body {
    min-height: 100vh;
    background: #F0F9FF;
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    color: #0F172A;
    font-size: 0.95rem;
}
.container, .container-fluid, .dashboard-container {
    max-width: 1400px !important;
    padding-left: 40px !important;
    padding-right: 40px !important;
    margin-left: auto !important;
    margin-right: auto !important;
}
h2, h3, h4, h5, h6 {
    color: #1E3A8A;
    font-weight: 800;
    letter-spacing: 0.5px;
    font-size: 1.1em;
}
.stats-row {
    gap: 1rem;
    margin-bottom: 1rem;
}
.stats-card {
    padding: 1.2rem 1rem;
    min-width: 120px;
    margin-bottom: 0.5rem;
}
.stats-card .stats-number {
    font-size: 1.3rem;
}
.stats-card .stats-label {
    font-size: 0.95rem;
}
.card, .table {
    border-radius: 10px;
    margin-bottom: 0.7rem;
}
.table th, .table td {
    font-size: 0.9rem;
    padding: 0.35rem 0.35rem;
}
.section-title {
    font-size: 1.3rem;
    margin-bottom: 0.7rem;
}
.section-description {
    font-size: 0.98rem;
    margin-bottom: 1rem;
}
.admin-dashboard, .doctor-dashboard, .lab-dashboard {
    margin-top: 2rem;
}
.stats-card {
    flex: 1 1 200px;
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    padding: 2rem 1.5rem;
    display: flex;
    flex-direction: column;
    align-items: flex-start;
    min-width: 180px;
    margin-bottom: 1rem;
    border: 1px solid #E2E8F0;
}
.stats-card .stats-number {
    font-size: 2.2rem;
    font-weight: 800;
    color: #1E3A8A;
    margin-bottom: 0.5rem;
}
.stats-card .stats-label {
    font-size: 1.1rem;
    color: #64748B;
    font-weight: 600;
}
.card, .table {
    background: #FFFFFF;
    border-radius: 16px;
    box-shadow: 0 4px 14px rgba(0,0,0,0.05);
    border: 1px solid #E2E8F0;
    margin-bottom: 1.5rem;
}
.table, .table-responsive, .dashboard-table {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
}
.table th:nth-child(1), .table td:nth-child(1) { width: 10%; }
.table th:nth-child(2), .table td:nth-child(2) { width: 20%; }
.table th:nth-child(3), .table td:nth-child(3) { width: 20%; }
.table th:nth-child(4), .table td:nth-child(4) { width: 20%; }
.table th:nth-child(5), .table td:nth-child(5) { width: 30%; }
.table th {
    background: #E0F2FE;
    color: #1E3A8A;
    font-weight: 700;
    border-top: none;
    padding: 1rem 0.75rem;
}
.table td {
    background: #FFFFFF;
    color: #0F172A;
    padding: 0.75rem;
    border-top: 1px solid #E2E8F0;
}
.table tr:first-child td {
    border-top: none;
}
.table tr {
    border-radius: 12px;
}
.table thead th {
    border-bottom: 2px solid #E2E8F0;
}
.table tbody tr {
    border-radius: 12px;
}
.badge {
    border-radius: 8px;
    padding: 0.25em 0.75em;
    font-weight: 600;
    font-size: 1em;
}
.badge.bg-success, .status-approved {
    background: none !important;
    color: #10B981 !important;
}
.badge.bg-danger, .status-declined {
    background: none !important;
    color: #DC2626 !important;
}
.badge.bg-warning, .status-pending {
    background: none !important;
    color: #CA8A04 !important;
}
.badge.bg-info, .status-info {
    background: none !important;
    color: #0284C7 !important;
}
.role-doctor, .role-patient, .role-pharmacist, .role-lab {
    background: none !important;
    color: #000 !important;
}
.btn-primary {
    background: #1E3A8A;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    box-shadow: 0 2px 8px rgba(30,58,138,0.08);
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-primary:hover, .btn-primary:focus {
    background: #1D4ED8;
    color: #FFFFFF;
    box-shadow: 0 4px 14px rgba(30,58,138,0.12);
}
.btn-secondary {
    background: #60A5FA;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-secondary:hover, .btn-secondary:focus {
    background: #3B82F6;
    color: #FFFFFF;
}
.btn-cyan {
    background: #06B6D4;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    transition: background 0.2s, box-shadow 0.2s;
}
.btn-cyan:hover, .btn-cyan:focus {
    background: #0891B2;
    color: #FFFFFF;
}
.btn:disabled, .btn.disabled {
    background: #CBD5E1 !important;
    color: #FFFFFF !important;
    border: none !important;
    box-shadow: none !important;
}
.nav-link {
    color: #1E3A8A;
    font-weight: 600;
    transition: color 0.2s, text-decoration 0.2s;
}
.nav-link:hover, .nav-link:focus {
    color: #1D4ED8;
    text-decoration: underline;
}
hr, .divider {
    border-color: #E2E8F0;
}
.text-primary {
    color: #1E3A8A !important;
}
.text-secondary {
    color: #64748B !important;
}
.text-cyan {
    color: #06B6D4 !important;
}
.text-slate {
    color: #64748B !important;
}
.text-navy {
    color: #0F172A !important;
}
.table .action-buttons .btn {
    margin-right: 0.3rem;
    margin-bottom: 0.2rem;
    min-width: 32px;
    padding: 0.3rem 0.7rem;
    font-size: 0.95rem;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
}
.table .action-buttons .btn:last-child {
    margin-right: 0;
}
#labRequestsTable td:last-child {
    display: flex;
    gap: 0.4rem;
    align-items: center;
    flex-wrap: nowrap;
}
/* Remove icon background in stats cards */
.icon-circle {
    background: none !important;
    box-shadow: none !important;
    border: none !important;
    width: auto !important;
    height: auto !important;
    display: inline-flex !important;
    align-items: center !important;
    justify-content: center !important;
    font-size: 1rem !important;
    margin-bottom: 0.5rem !important;
    padding: 0 !important;
}
/* Make flatpickr calendar text black */
.flatpickr-calendar, .flatpickr-day, .flatpickr-months, .flatpickr-weekdays, .flatpickr-current-month, .flatpickr-monthDropdown-months, .flatpickr-weekday, .flatpickr-time, .flatpickr-am-pm, .flatpickr-prev-month, .flatpickr-next-month, .flatpickr-day.selected, .flatpickr-day.today {
    color: #000 !important;
}

/* Style for days with appointments */
.flatpickr-day.has-appointment {
    background-color: #f0f0f0 !important;
    border-color: #f0f0f0 !important;
}

/* Style for the Today button */
.today-btn {
    background: #1E3A8A;
    color: #FFFFFF;
    border: none;
    border-radius: 8px;
    font-weight: 600;
    padding: 0.4rem 1rem;
    margin-left: 0.5rem;
    transition: background 0.2s;
}

.today-btn:hover {
    background: #1D4ED8;
}
    </style>
<style>
/* Admin Dashboard Header Styles (EXACT COPY for Lab Dashboard) */
.admin-header, .navbar.admin-navbar {
    background: #1E3A8A !important;
    color: #FFFFFF !important;
    box-shadow: 0 2px 8px rgba(30,58,138,0.08);
    border-bottom: none;
}
.admin-header .header-title, .navbar.admin-navbar .navbar-brand {
    color: #FFFFFF !important;
    font-weight: 800;
    font-size: 2rem;
    letter-spacing: 0.5px;
}
.admin-header .header-subtext, .navbar.admin-navbar .header-subtext {
    color: #E0E7FF !important;
    font-size: 1rem;
    font-weight: 500;
}
.admin-header .header-icon, .navbar.admin-navbar .header-icon {
    color: #A5F3FC;
    font-size: 1.5rem;
    transition: color 0.18s, background 0.18s, box-shadow 0.18s, transform 0.18s;
    border-radius: 50%;
    padding: 0.5rem;
    background: transparent;
}
.admin-header .header-icon:hover, .navbar.admin-navbar .header-icon:hover,
.admin-header .header-icon.active, .navbar.admin-navbar .header-icon.active {
    color: #06B6D4;
    background: #1E40AF;
    box-shadow: 0 2px 8px rgba(6,182,212,0.10);
    transform: scale(1.08);
}
.admin-header .user-avatar, .navbar.admin-navbar .user-avatar {
    border: 2px solid #60A5FA;
    border-radius: 50%;
    width: 40px; height: 40px;
    object-fit: cover;
    box-shadow: 0 2px 8px rgba(96,165,250,0.10);
}
.admin-header .notification-badge, .navbar.admin-navbar .notification-badge {
    background: #60A5FA;
    color: #fff;
    border-radius: 50%;
    font-size: 0.85rem;
    padding: 0.25em 0.5em;
    position: absolute;
    top: -6px; right: -6px;
    border: 2px solid #fff;
    transition: transform 0.18s, box-shadow 0.18s;
}
.admin-header .notification-badge:hover, .navbar.admin-navbar .notification-badge:hover {
    background: #06B6D4;
    transform: scale(1.12);
    box-shadow: 0 0 8px #06B6D4;
}
.admin-header .search-input, .navbar.admin-navbar .search-input {
    background: #F0F9FF;
    color: #0F172A;
    border: 1px solid #E2E8F0;
    border-radius: 8px;
    padding: 0.5rem 1rem;
    font-size: 1rem;
    transition: border 0.18s, box-shadow 0.18s;
}
.admin-header .search-input:focus, .navbar.admin-navbar .search-input:focus {
    border-color: #60A5FA;
    box-shadow: 0 0 0 2px #E0F2FE;
    outline: none;
}
.admin-header .menu-item, .navbar.admin-navbar .nav-link {
    color: #FFFFFF;
    font-weight: 600;
    border-radius: 6px;
    transition: background 0.18s, color 0.18s;
    padding: 0.5rem 1rem;
}
.admin-header .menu-item:hover, .navbar.admin-navbar .nav-link:hover {
    background: #E0F2FE;
    color: #3B82F6 !important;
    text-decoration: none;
}
.admin-header .menu-item.active, .navbar.admin-navbar .nav-link.active {
    color: #1E3A8A !important;
    background: #E0F2FE;
}
</style>
</head>
<body>
<nav class="navbar admin-navbar navbar-expand-lg" style="background: #fff; box-shadow: 0 2px 12px rgba(30,58,138,0.08); border-radius: 0 0 18px 18px; padding: 0.5rem 0;">
    <div class="container d-flex align-items-center justify-content-between py-1">
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand header-title" href="/hcisProject/index.php" style="font-size: 1.8rem; font-weight: bold; letter-spacing: 0.5px;">
                <span style="color:#ffffff; font-size: 2rem;">E</span>-Health
            </a>
            <span class="header-subtext ms-3 d-none d-md-inline" style="color: #64748B; font-size: 1.1rem; font-weight: 600;">Lab Panel</span>
                                </div>
        <div class="d-flex align-items-center gap-3">
            <a href="/hcisProject/profile.php" class="header-subtext d-none d-md-inline" style="text-decoration:none; color: #1E3A8A; font-weight: 600; font-size: 1.1rem;">
                <?php echo htmlspecialchars($lab_staff['name'] ?? 'Lab'); ?>
            </a>
            <a class="btn btn-primary ms-2" href="/hcisProject/logout.php" title="Logout" style="background: #1E3A8A; color: #fff; border-radius: 25px; padding: 0.4rem 1.2rem; font-weight: 600; font-size: 1rem; border: none; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.18s, color 0.18s;">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>


  

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Pending Tests</h5>
                        <h2><?php echo count($pending_tests); ?></h2>
                        <p class="text-muted"><?php echo count(array_filter($pending_tests, function($t) { return $t['urgency'] === 'urgent'; })); ?> urgent</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Completed Today</h5>
                        <h2><?php echo $completed_today; ?></h2>
                        <p class="text-muted">Tests processed</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs">
                            <li class="nav-item">
                                <a class="nav-link active" href="#pending">Pending Tests</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#processing">In Processing</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#completed">Completed Tests</a>
                            </li>
                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <div class="tab-pane active" id="pending">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Pending Tests</h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary refresh-btn" id="refreshPendingTestsBtn" onclick="location.reload()">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                    <div class="card-body" id="pendingTestsSection">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Patient</th>
                                                        <th>Test Type</th>
                                                        <th>Requesting Doctor</th>
                                                        <th>Requested Date</th>
                                                        <th>Urgency</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($pending_tests)): ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No pending tests</td>
                                                    </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($pending_tests as $test): ?>
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
                                                            <td>
                                                                <?php echo htmlspecialchars($test['doctor_name']); ?>
                                                            </td>
                                                            <td>
                                                                <?php echo date('M d, Y', strtotime($test['requested_date'])); ?>
                                                                <small class="text-muted d-block"><?php echo date('h:i A', strtotime($test['requested_date'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <span class="badge bg-<?php echo $test['urgency'] === 'urgent' ? 'danger' : 'info'; ?>">
                                                                    <?php echo ucfirst($test['urgency']); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-primary" onclick="processTest(<?php echo $test['id']; ?>)">
                                                                    <i class="fas fa-flask me-1"></i>Start Processing
                                                                </button>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="tab-pane" id="processing">
                                <div class="table-responsive">
                                    <table class="table">
                                        <thead>
                                            <tr>
                                                <th>Patient</th>
                                                <th>Test Type</th>
                                                <th>Started</th>
                                                <th>Status</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($processing_tests)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center">No tests in processing</td>
                                            </tr>
                                            <?php else: ?>
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
                                                    <td>
                                                        <?php echo isset($test['processing_start_date']) && $test['processing_start_date'] ? date('M d, Y', strtotime($test['processing_start_date'])) : '-'; ?>
                                                        <small class="text-muted d-block"><?php echo isset($test['processing_start_date']) && $test['processing_start_date'] ? date('h:i A', strtotime($test['processing_start_date'])) : '-'; ?></small>
                                                    </td>
                                                    <td>
                                                        <span class="badge bg-warning">Processing</span>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-success" onclick="completeTest(<?php echo $test['id']; ?>)">
                                                            <i class="fas fa-check me-1"></i>Complete
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <div class="tab-pane" id="completed">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Completed Tests</h5>
                                        <button type="button" class="btn btn-sm btn-outline-primary refresh-btn" id="refreshCompletedTestsBtn" onclick="location.reload()">
                                            <i class="fas fa-sync-alt"></i> Refresh
                                        </button>
                                    </div>
                                    <div class="card-body" id="completedTestsSection">
                                        <div class="table-responsive">
                                            <table class="table">
                                                <thead>
                                                    <tr>
                                                        <th>Patient</th>
                                                        <th>Test Type</th>
                                                        <th>Completed</th>
                                                        <th>Result</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($completed_tests)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No completed tests</td>
                                                    </tr>
                                                    <?php else: ?>
                                                        <?php foreach ($completed_tests as $test): ?>
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
                                                            <td>
                                                                <?php echo date('M d, Y', strtotime($test['completed_date'])); ?>
                                                                <small class="text-muted d-block"><?php echo date('h:i A', strtotime($test['completed_date'])); ?></small>
                                                            </td>
                                                            <td>
                                                                <?php
                                                                $result_data = json_decode($test['results'], true);
                                                                $result_type = isset($result_data['result_type']) ? $result_data['result_type'] : 'normal';
                                                                $badge_color = $result_type === 'normal' ? 'success' : 
                                                                    ($result_type === 'abnormal' ? 'warning' : 'danger');
                                                                ?>
                                                                <span class="badge bg-<?php echo $badge_color; ?>">
                                                                    <?php echo ucfirst($result_type); ?>
                                                                </span>
                                                            </td>
                                                            <td>
                                                                <button class="btn btn-sm btn-info" onclick="viewResults(<?php echo $test['id']; ?>)">
                                                                    <i class="fas fa-eye me-1"></i>View Results
                                                                </button>
                                                                <?php if (!empty($test['result_pdf'])): ?>
                                                                    <a href="../<?php echo htmlspecialchars($test['result_pdf']); ?>" target="_blank" class="btn btn-primary btn-sm ms-1">
                                                                        <i class="fas fa-file-pdf me-1"></i>View PDF
                                                                    </a>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Complete Test Modal -->
    <div class="modal fade" id="completeTestModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Complete Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="completeTestForm" autocomplete="off" enctype="multipart/form-data">
    <!-- Prevent default submit and use JS only -->
                        <input type="hidden" id="test_id" name="test_id">
                        <div class="mb-3">
                            <label for="result" class="form-label">Test Result</label>
                            <select class="form-select" id="result" name="result" required>
                                <option value="">Select Result</option>
                                <option value="normal">Normal</option>
                                <option value="abnormal">Abnormal</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="details" class="form-label">Result Details</label>
                            <textarea class="form-control" id="details" name="details" rows="4" required></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="recommendations" class="form-label">Recommendations</label>
                            <textarea class="form-control" id="recommendations" name="recommendations" rows="2"></textarea>
                        </div>
                        <div class="mb-3">
                            <label for="result_pdf" class="form-label">Upload Result PDF <span style="color:red">*</span></label>
                            <input type="file" class="form-control" id="result_pdf" name="result_pdf" accept="application/pdf" required>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="submitResultsBtn">Submit Results</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Prevent default form submission and always use submitResults()
            $('#completeTestForm').on('submit', function(event) {
                event.preventDefault();
                submitResults(event);
            });
            // Also trigger submitResults when button is clicked
            $('#submitResultsBtn').on('click', function(event) {
                submitResults(event);
            });
            // Initialize tab functionality
            $('.nav-tabs a').click(function(e) {
                e.preventDefault();
                $(this).tab('show');
            });
        });

        function processTest(testId) {
            if (confirm('Are you sure you want to start processing this test?')) {
                $.post('../processes/process_test.php', {
                    test_id: testId
                }, function(response) {
                    if (response.success) {
                        alert('Test marked as processing');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                });
            }
        }

        function completeTest(testId) {
            $('#test_id').val(testId);
            $('#completeTestForm')[0].reset();
            $('#completeTestModal').modal('show');
        }

        function submitResults(event) {
            if (event) event.preventDefault();
            const formData = new FormData($('#completeTestForm')[0]);
            
            $.ajax({
                url: '../processes/complete_test.php',
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                success: function(response) {
                    if (response.success) {
                        alert('Test results submitted successfully');
                        $('#completeTestModal').modal('hide');
                        location.reload();
                    } else {
                        alert('Error: ' + response.message);
                    }
                },
                error: function(xhr) {
                    let msg = 'An error occurred while submitting results';
                    if (xhr.responseText) {
                        try {
                            let resp = JSON.parse(xhr.responseText);
                            if (resp.message) msg = resp.message;
                        } catch (e) {
                            msg += '\n' + xhr.responseText;
                        }
                    }
                    alert(msg);
                }
            });
        }

        function viewResults(testId) {
            window.location.href = `view_results.php?test_id=${testId}`;
        }
    </script>
<?php include_once '../includes/js_links.php'; ?>
</body>
</html>
