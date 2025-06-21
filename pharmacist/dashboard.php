<?php
require_once '../includes/session_manager.php'; // Session management utilities
require_once '../includes/database.php'; // Database connection

// Check if user is logged in and is a pharmacist
// Ensures only authorized pharmacists can access this dashboard.
checkSession('pharmacist');

// Get pharmacist's information
// Fetches logged-in pharmacist's details for display.
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$pharmacist_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND role = 'pharmacist'");
$stmt->bind_param("i", $pharmacist_id);
$stmt->execute();
$pharmacist = $stmt->get_result()->fetch_assoc();

// Ensure we got a valid pharmacist
if (!$pharmacist || $pharmacist['role'] !== 'pharmacist') {
    header('Location: ../login.php');
    exit();
}

// Get pending prescriptions
// Fetches all prescriptions that need to be dispensed.
$stmt = $conn->prepare("
    SELECT DISTINCT p.*, 
           u_patient.name as patient_name,
           u_doctor.name as doctor_name,
           GROUP_CONCAT(
               CONCAT(
                   '<div class=\"medicine-item mb-1 border-bottom border-opacity-25\">',
                   '<div class=\"medicine-name\">', m.brand_name, ' (', m.strength, ')</div>',
                   '<div class=\"medicine-details\">',
                   '<div>Drug: ', m.drug_ingredient, '</div>',
                   '<div>Frequency: ', pi.frequency, '</div>',
                   '<div>Duration: ', pi.duration, '</div>',
                   '<span class=\"badge ', CASE WHEN m.quantity > 10 THEN 'bg-success' WHEN m.quantity > 0 THEN 'bg-warning' ELSE 'bg-danger' END, '\">',
                   CASE WHEN m.quantity > 10 THEN 'In Stock' WHEN m.quantity > 0 THEN 'Low Stock' ELSE 'Out of Stock' END,
                   ' (', m.quantity, ')</span>',
                   '</div>',
                   '</div>'
               )
           SEPARATOR '') as medicines
    FROM prescriptions p 
    JOIN users u_patient ON p.patient_id = u_patient.id 
    JOIN users u_doctor ON p.doctor_id = u_doctor.id 
    JOIN prescription_items pi ON p.id = pi.prescription_id
    JOIN medicines m ON pi.medicine_id = m.id
    WHERE p.status = 'active'
    GROUP BY p.id
    ORDER BY p.prescribed_date ASC
");
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get low stock items
// Fetches medicines with stock below threshold for alerting.
$stmt = $conn->prepare("SELECT * FROM medicines WHERE quantity < 20 ORDER BY quantity ASC");
$stmt->execute();
$low_stock = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get page number from URL
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get search parameters
$search_brand = isset($_GET['search_brand']) ? $_GET['search_brand'] : '';
$search_ingredient = isset($_GET['search_ingredient']) ? $_GET['search_ingredient'] : '';
$search_dosage = isset($_GET['search_dosage']) ? $_GET['search_dosage'] : '';
$search_used_for = isset($_GET['search_used_for']) ? $_GET['search_used_for'] : '';

// Add low stock filter parameter
$show_low_stock = isset($_GET['show_low_stock']) ? $_GET['show_low_stock'] : '';

// Build WHERE clause for search
$where_conditions = [];
$params = [];
$param_types = '';

if (!empty($search_brand)) {
    $where_conditions[] = "brand_name LIKE ?";
    $params[] = "%$search_brand%";
    $param_types .= 's';
}
if (!empty($search_ingredient)) {
    $where_conditions[] = "drug_ingredient LIKE ?";
    $params[] = "%$search_ingredient%";
    $param_types .= 's';
}
if (!empty($search_dosage)) {
    $where_conditions[] = "dosage_form LIKE ?";
    $params[] = "%$search_dosage%";
    $param_types .= 's';
}
if (!empty($search_used_for)) {
    $where_conditions[] = "used_for_what LIKE ?";
    $params[] = "%$search_used_for%";
    $param_types .= 's';
}

// Modify the where conditions array to include low stock filter
if ($show_low_stock === '1') {
    $where_conditions[] = "quantity < 20";
}

$where_clause = !empty($where_conditions) ? "WHERE " . implode(" AND ", $where_conditions) : "";

// Get total number of medicines for pagination
$count_query = "SELECT COUNT(*) as total FROM medicines $where_clause";
$stmt = $conn->prepare($count_query);
if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$total_medicines = $stmt->get_result()->fetch_assoc()['total'];
$total_pages = ceil($total_medicines / $per_page);

// Get medicines with pagination and search
$query = "SELECT * FROM medicines $where_clause ORDER BY brand_name ASC LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);

// Add pagination parameters to the existing params array
$params[] = $per_page;
$params[] = $offset;
$param_types .= 'ii';

if (!empty($params)) {
    $stmt->bind_param($param_types, ...$params);
}
$stmt->execute();
$medicines = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="refresh" content="300">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pharmacist Dashboard - E-Health</title>
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
.admin-dashboard, .doctor-dashboard, .pharmacist-dashboard {
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
/* Admin Dashboard Header Styles (EXACT COPY for Pharmacist Dashboard) */
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
            <span class="header-subtext ms-3 d-none d-md-inline" style="color: #64748B; font-size: 1.1rem; font-weight: 600;">Pharmacist Panel</span>
                                </div>
        <div class="d-flex align-items-center gap-3">
            <a href="/hcisProject/profile.php" class="header-subtext d-none d-md-inline" style="text-decoration:none; color: #1E3A8A; font-weight: 600; font-size: 1.1rem;">
                <?php echo htmlspecialchars($pharmacist['name'] ?? 'Pharmacist'); ?>
            </a>
            <a class="btn btn-primary ms-2" href="/hcisProject/logout.php" title="Logout" style="background: #1E3A8A; color: #fff; border-radius: 25px; padding: 0.4rem 1.2rem; font-weight: 600; font-size: 1rem; border: none; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.18s, color 0.18s;">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
                                </div>
                            </div>
</nav>

<div class="dashboard-container">
   


    <div class="row">
        <div class="col-md-3">
            <div class="dashboard-card stats-card">
                <div class="icon-circle bg-primary">
                    <i class="fas fa-prescription"></i>
                </div>
                <div>
                    <h5 class="card-title mb-0">Pending Prescriptions</h5>
                    <h2 class="card-value"><?php echo count($prescriptions); ?></h2>
                    <p class="card-subtitle">New in the last hour</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <a href="?<?php 
                $params = $_GET;
                $params['show_low_stock'] = !isset($_GET['show_low_stock']) || $_GET['show_low_stock'] !== '1' ? '1' : '0';
                $params['tab'] = 'inventory';
                echo http_build_query($params);
            ?>" class="text-decoration-none">
                <div class="dashboard-card stats-card <?php echo isset($_GET['show_low_stock']) && $_GET['show_low_stock'] === '1' ? 'border-primary' : ''; ?>">
                    <div class="icon-circle bg-warning">
                        <i class="fas fa-exclamation-triangle"></i>
                    </div>
                    <div>
                        <h5 class="card-title mb-0">Low Stock Items</h5>
                        <h2 class="card-value"><?php echo count($low_stock); ?></h2>
                        <p class="card-subtitle">Need attention</p>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row mt-4">
        <div class="col-md-12">
            <div class="dashboard-card">
                <ul class="nav nav-tabs dashboard-nav" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" href="#prescriptions">Prescriptions Queue</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#inventory">Inventory Management</a>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane active" id="prescriptions">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5></h5>
                            <button type="button" class="btn btn-primary btn-sm" id="refreshPrescriptionsBtn" style="background: #1E3A8A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.2s, box-shadow 0.2s;">
                                <i class="fas fa-sync-alt"></i> Refresh
                            </button>
                        </div>
                        <div class="table-responsive">
                            <table class="table dashboard-table">
                                <thead>
                                    <tr>
                                        <th>Patient</th>
                                        <th>Medications</th>
                                        <th>Notes</th>
                                        <th>Prescribed By</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($prescriptions)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No pending prescriptions</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($prescriptions as $prescription): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($prescription['patient_name']); ?></td>
                                            <td class="medication-cell">
                                                <?php echo $prescription['medicines']; ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($prescription['notes'] ?? 'None'); ?>
                                            </td>
                                            <td>
                                                <?php echo htmlspecialchars($prescription['doctor_name']); ?>
                                                <small class="text-muted prescription-date d-block"><?php echo date('M d, Y', strtotime($prescription['prescribed_date'])); ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-primary">Pending</span>
                                            </td>
                                            <td>
                                                <button class="btn btn-sm btn-primary" onclick="processPrescription(<?php echo $prescription['id']; ?>)" title="Process Prescription">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane" id="inventory">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h5 class="mb-0"></h5>
                            <div>
                                <button class="btn btn-primary btn-sm me-2" onclick="location.reload()" title="Refresh" style="background: #1E3A8A; color: #FFFFFF; border: none; border-radius: 8px; font-weight: 600; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.2s, box-shadow 0.2s;">
                                    <i class="fas fa-sync-alt"></i> Refresh
                                </button>
                                <button class="btn btn-primary btn-sm" onclick="addMedicine()">
                                    <i class="fas fa-plus me-1"></i>Add Medicine
                                </button>
                            </div>
                        </div>

                        <!-- Search Filters -->
                        <div class="card mb-3">
                            <div class="card-body">
                                <form method="GET" class="row g-3">
                                    <?php if (isset($_GET['show_low_stock']) && $_GET['show_low_stock'] === '1'): ?>
                                    <input type="hidden" name="show_low_stock" value="1">
                                    <div class="col-12">
                                        <div class="alert alert-warning">
                                            <i class="fas fa-filter me-2"></i>Showing low stock items only
                                            <a href="?<?php 
                                                $params = $_GET;
                                                unset($params['show_low_stock']);
                                                echo http_build_query($params);
                                            ?>" class="float-end">Clear filter</a>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                    <div class="col-md-3">
                                        <label for="search_brand" class="form-label">Brand Name</label>
                                        <input type="text" class="form-control" id="search_brand" name="search_brand" 
                                            value="<?php echo htmlspecialchars($search_brand); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search_ingredient" class="form-label">Drug Ingredient</label>
                                        <input type="text" class="form-control" id="search_ingredient" name="search_ingredient"
                                            value="<?php echo htmlspecialchars($search_ingredient); ?>">
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search_dosage" class="form-label">Dosage Form</label>
                                        <select class="form-select" id="search_dosage" name="search_dosage">
                                            <option value="">All Forms</option>
                                            <option value="Tablet" <?php echo $search_dosage === 'Tablet' ? 'selected' : ''; ?>>Tablet</option>
                                            <option value="Capsule" <?php echo $search_dosage === 'Capsule' ? 'selected' : ''; ?>>Capsule</option>
                                            <option value="Syrup" <?php echo $search_dosage === 'Syrup' ? 'selected' : ''; ?>>Syrup</option>
                                            <option value="Injection" <?php echo $search_dosage === 'Injection' ? 'selected' : ''; ?>>Injection</option>
                                            <option value="Ointment" <?php echo $search_dosage === 'Ointment' ? 'selected' : ''; ?>>Ointment</option>
                                            <option value="Cream" <?php echo $search_dosage === 'Cream' ? 'selected' : ''; ?>>Cream</option>
                                            <option value="Drops" <?php echo $search_dosage === 'Drops' ? 'selected' : ''; ?>>Drops</option>
                                            <option value="Inhaler" <?php echo $search_dosage === 'Inhaler' ? 'selected' : ''; ?>>Inhaler</option>
                                            <option value="Suppository" <?php echo $search_dosage === 'Suppository' ? 'selected' : ''; ?>>Suppository</option>
                                            <option value="Powder" <?php echo $search_dosage === 'Powder' ? 'selected' : ''; ?>>Powder</option>
                                            <option value="Solution" <?php echo $search_dosage === 'Solution' ? 'selected' : ''; ?>>Solution</option>
                                            <option value="Suspension" <?php echo $search_dosage === 'Suspension' ? 'selected' : ''; ?>>Suspension</option>
                                            <option value="Lotion" <?php echo $search_dosage === 'Lotion' ? 'selected' : ''; ?>>Lotion</option>
                                            <option value="Gel" <?php echo $search_dosage === 'Gel' ? 'selected' : ''; ?>>Gel</option>
                                            <option value="Spray" <?php echo $search_dosage === 'Spray' ? 'selected' : ''; ?>>Spray</option>
                                            <option value="Patch" <?php echo $search_dosage === 'Patch' ? 'selected' : ''; ?>>Patch</option>
                                            <option value="Lozenges" <?php echo $search_dosage === 'Lozenges' ? 'selected' : ''; ?>>Lozenges</option>
                                            <option value="Inhalation Powder" <?php echo $search_dosage === 'Inhalation Powder' ? 'selected' : ''; ?>>Inhalation Powder</option>
                                            <option value="Implant" <?php echo $search_dosage === 'Implant' ? 'selected' : ''; ?>>Implant</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label for="search_used_for" class="form-label">Used For</label>
                                        <input type="text" class="form-control" id="search_used_for" name="search_used_for"
                                            value="<?php echo htmlspecialchars($search_used_for); ?>">
                                    </div>
                                    <div class="col-12">
                                        <button type="submit" class="btn btn-primary">Search</button>
                                        <a href="?tab=inventory" class="btn btn-secondary">Clear</a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Brand Name</th>
                                        <th>Drug Ingredient</th>
                                        <th>Drug Class</th>
                                        <th>Dosage Form</th>
                                        <th>Strength</th>
                                        <th>Category</th>
                                        <th>Used For</th>
                                        <th>Price</th>
                                        <th>Quantity</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($medicines)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center">No medicines found</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($medicines as $medicine): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($medicine['brand_name']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['drug_ingredient']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['drug_class']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['dosage_form']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['strength']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['drug_category']); ?></td>
                                            <td><?php echo htmlspecialchars($medicine['used_for_what']); ?></td>
                                            <td>EGP <?php echo number_format($medicine['price'], 2); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $medicine['quantity'] > 20 ? 'success' : 
                                                        ($medicine['quantity'] > 0 ? 'warning' : 'danger'); 
                                                ?>">
                                                    <?php echo $medicine['quantity']; ?> units
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group" role="group">
                                                    <button class="btn btn-sm btn-info" onclick="updateStock(<?php echo $medicine['id']; ?>)" title="Update Stock">
                                                        <i class="fas fa-sync-alt"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-warning" onclick="editMedicine(<?php echo $medicine['id']; ?>, '<?php echo str_replace("'", "\\'", $medicine['brand_name']); ?>', '<?php echo str_replace("'", "\\'", $medicine['drug_ingredient']); ?>', '<?php echo str_replace("'", "\\'", $medicine['drug_class']); ?>', '<?php echo str_replace("'", "\\'", $medicine['dosage_form']); ?>', '<?php echo str_replace("'", "\\'", $medicine['strength']); ?>', '<?php echo str_replace("'", "\\'", $medicine['drug_category']); ?>', '<?php echo str_replace("'", "\\'", $medicine['used_for_what']); ?>', <?php echo $medicine['price']; ?>, <?php echo $medicine['quantity']; ?>)" title="Edit Medicine">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-sm btn-danger" onclick="deleteMedicine(<?php echo $medicine['id']; ?>, '<?php echo str_replace("'", "\\'", $medicine['brand_name']); ?>')" title="Delete Medicine">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($total_pages > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page - 1); ?>&search_brand=<?php echo urlencode($search_brand); ?>&search_ingredient=<?php echo urlencode($search_ingredient); ?>&search_dosage=<?php echo urlencode($search_dosage); ?>&search_used_for=<?php echo urlencode($search_used_for); ?>">Previous</a>
                                </li>
                                <?php endif; ?>
                                
                                <?php
                                $max_links = 5; // Maximum number of page links to show (excluding first, last, and ellipsis)
                                $half = floor(($max_links - 1) / 2);
                                $start = max(1, $page - $half);
                                $end = min($total_pages, $start + $max_links - 1);
                                
                                // Adjust start if we're near the end
                                if ($end - $start < $max_links - 1) {
                                    $start = max(1, $end - $max_links + 1);
                                }
                                
                                // Always show first page with ellipsis if needed
                                if ($start > 1): ?>
                                    <li class="page-item <?php echo (1 == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=1&search_brand=<?php echo urlencode($search_brand); ?>&search_ingredient=<?php echo urlencode($search_ingredient); ?>&search_dosage=<?php echo urlencode($search_dosage); ?>&search_used_for=<?php echo urlencode($search_used_for); ?>">1</a>
                                    </li>
                                    <?php if ($start > 2): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif;
                                endif;
                                
                                // Show page numbers
                                for ($i = $start; $i <= $end; $i++):
                                    // Skip first and last page if they're already shown
                                    if (($i == 1 && $start > 1) || ($i == $total_pages && $end < $total_pages)) {
                                        continue;
                                    }
                                    ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $i; ?>&search_brand=<?php echo urlencode($search_brand); ?>&search_ingredient=<?php echo urlencode($search_ingredient); ?>&search_dosage=<?php echo urlencode($search_dosage); ?>&search_used_for=<?php echo urlencode($search_used_for); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor;
                                
                                // Always show last page with ellipsis if needed
                                if ($end < $total_pages):
                                    if ($end < $total_pages - 1): ?>
                                        <li class="page-item disabled">
                                            <span class="page-link">...</span>
                                        </li>
                                    <?php endif; ?>
                                    <li class="page-item <?php echo ($total_pages == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="?page=<?php echo $total_pages; ?>&search_brand=<?php echo urlencode($search_brand); ?>&search_ingredient=<?php echo urlencode($search_ingredient); ?>&search_dosage=<?php echo urlencode($search_dosage); ?>&search_used_for=<?php echo urlencode($search_used_for); ?>"><?php echo $total_pages; ?></a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?php echo ($page + 1); ?>&search_brand=<?php echo urlencode($search_brand); ?>&search_ingredient=<?php echo urlencode($search_ingredient); ?>&search_dosage=<?php echo urlencode($search_dosage); ?>&search_used_for=<?php echo urlencode($search_used_for); ?>">Next</a>
                                </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modals -->
<div class="modal fade dashboard-modal" id="medicineModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add/Edit Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="medicineForm">
                    <input type="hidden" id="medicine_id" name="id">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="brand_name" class="form-label">Brand Name</label>
                            <input type="text" class="form-control" id="brand_name" name="brand_name" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="drug_ingredient" class="form-label">Drug Ingredient</label>
                            <input type="text" class="form-control" id="drug_ingredient" name="drug_ingredient" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="drug_class" class="form-label">Drug Class</label>
                            <input type="text" class="form-control" id="drug_class" name="drug_class" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="dosage_form" class="form-label">Dosage Form</label>
                            <input type="text" class="form-control" id="dosage_form" name="dosage_form" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="strength" class="form-label">Strength</label>
                            <input type="text" class="form-control" id="strength" name="strength" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="drug_category" class="form-label">Drug Category</label>
                            <input type="text" class="form-control" id="drug_category" name="drug_category" required>
                        </div>
                        <div class="col-12 mb-3">
                            <label for="used_for_what" class="form-label">Used For</label>
                            <textarea class="form-control" id="used_for_what" name="used_for_what" required></textarea>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="price" class="form-label">Price (EGP)</label>
                            <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="quantity" class="form-label">Quantity</label>
                            <input type="number" class="form-control" id="quantity" name="quantity" required>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveMedicine()">Save Medicine</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade dashboard-modal" id="updateStockModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Update Stock</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="updateStockForm">
                    <input type="hidden" id="stock_medicine_id" name="medicine_id">
                    <div class="mb-3">
                        <label for="stock_action" class="form-label">Action</label>
                        <select class="form-select" id="stock_action" name="action" required>
                            <option value="add">Add Stock</option>
                            <option value="subtract">Remove Stock</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="stock_quantity" class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="stock_quantity" name="quantity" required min="1">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="saveStock()">Update Stock</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade dashboard-modal" id="editMedicineModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="editMedicineForm">
                    <input type="hidden" id="edit_medicine_id" name="medicine_id">
                    <div class="mb-3">
                        <label for="edit_brand_name" class="form-label">Brand Name</label>
                        <input type="text" class="form-control" id="edit_brand_name" name="brand_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_drug_ingredient" class="form-label">Drug Ingredient</label>
                        <input type="text" class="form-control" id="edit_drug_ingredient" name="drug_ingredient" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_drug_class" class="form-label">Drug Class</label>
                        <input type="text" class="form-control" id="edit_drug_class" name="drug_class" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_dosage_form" class="form-label">Dosage Form</label>
                        <input type="text" class="form-control" id="edit_dosage_form" name="dosage_form" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_strength" class="form-label">Strength</label>
                        <input type="text" class="form-control" id="edit_strength" name="strength" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_drug_category" class="form-label">Drug Category</label>
                        <input type="text" class="form-control" id="edit_drug_category" name="drug_category" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_used_for_what" class="form-label">Used For</label>
                        <textarea class="form-control" id="edit_used_for_what" name="used_for_what" required></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="edit_price" class="form-label">Price (EGP)</label>
                        <input type="number" step="0.01" class="form-control" id="edit_price" name="price" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_quantity" class="form-label">Quantity (leave empty to keep current quantity)</label>
                        <input type="number" class="form-control" id="edit_quantity" name="quantity">
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-primary" onclick="updateMedicine()">Save Changes</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize tab functionality
        $('.nav-tabs a').click(function(e) {
            e.preventDefault();
            $(this).tab('show');
        });
        
        // Add submit handlers for forms - use only form submission, not button clicks
        $('#updateStockForm').on('submit', function(e) {
            e.preventDefault();
            saveStock();
        });
        
        $('#editMedicineForm').on('submit', function(e) {
            e.preventDefault();
            updateMedicine();
        });
        
        $('#medicineForm').on('submit', function(e) {
            e.preventDefault();
            saveMedicine();
        });
        
        // Remove direct click handlers from buttons and use form submission instead
        // Direct button click handlers can cause double submissions
        
        // Log ready state
        console.log('Document ready, forms initialized');
    });

    function processPrescription(prescriptionId) {
        if (confirm('Are you sure you want to process this prescription?')) {
            $.ajax({
                url: '../processes/process_prescription.php',
                method: 'POST',
                data: {
                    prescription_id: prescriptionId
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Show success message
                        const alert = $('<div class="alert alert-success alert-dismissible fade show" role="alert">')
                            .text(response.message || 'Prescription processed successfully')
                            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        
                        $('.container').first().prepend(alert);
                        
                        // Auto dismiss after 5 seconds
                        setTimeout(function() {
                            alert.alert('close');
                        }, 5000);
                        
                        // Reload after a short delay
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        // Show error message
                        const alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                            .text('Error: ' + (response.message || 'Unknown error'))
                            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                        
                        $('.container').first().prepend(alert);
                    }
                },
                error: function() {
                    // Show network error message
                    const alert = $('<div class="alert alert-danger alert-dismissible fade show" role="alert">')
                        .text('Network error. Please try again.')
                        .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
                    
                    $('.container').first().prepend(alert);
                }
            });
        }
    }

    function addMedicine() {
        $('#medicine_id').val('');
        $('#medicineForm')[0].reset();
        $('#medicineModal').modal('show');
    }

    function updateStock(medicineId) {
        $('#stock_medicine_id').val(medicineId);
        $('#updateStockForm')[0].reset();
        $('#updateStockModal').modal('show');
    }

    function editMedicine(id, brand_name, drug_ingredient, drug_class, dosage_form, strength, drug_category, used_for_what, price, quantity) {
        $('#edit_medicine_id').val(id);
        $('#edit_brand_name').val(brand_name);
        $('#edit_drug_ingredient').val(drug_ingredient);
        $('#edit_drug_class').val(drug_class);
        $('#edit_dosage_form').val(dosage_form);
        $('#edit_strength').val(strength);
        $('#edit_drug_category').val(drug_category);
        $('#edit_used_for_what').val(used_for_what);
        $('#edit_price').val(price);
        $('#edit_quantity').val(''); // Clear quantity field
        $('#edit_quantity').attr('placeholder', quantity); // Show current quantity as placeholder
        
        $('#editMedicineModal').modal('show');
    }

    function deleteMedicine(id, name) {
        if (confirm(`Are you sure you want to delete "${name}"? This action cannot be undone.`)) {
            $.ajax({
                url: '../processes/delete_medicine.php',
                method: 'POST',
                data: {
                    medicine_id: id
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        showAlert('success', 'Medicine deleted successfully');
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    } else {
                        showAlert('danger', 'Error: ' + (response.message || 'Unknown error'));
                    }
                },
                error: function() {
                    showAlert('danger', 'Network error. Please try again.');
                }
            });
        }
    }

    function saveMedicine() {
        const formData = new FormData($('#medicineForm')[0]);
        
        $.ajax({
            url: '../processes/save_medicine.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Medicine added successfully');
                    $('#medicineModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', 'Error: ' + (response.message || 'Unknown error'));
                }
            },
            error: function() {
                showAlert('danger', 'An error occurred while adding the medicine');
            }
        });
    }

    function saveStock() {
        // Get form data
        const formData = new FormData($('#updateStockForm')[0]);
        
        // Add a unique request ID to prevent duplicate submissions
        const requestId = Date.now() + '-' + Math.random().toString(36).substring(2, 15);
        formData.append('request_id', requestId);
        
        // Disable submit button to prevent double-clicking
        $('#saveStockBtn').prop('disabled', true);
        
        // Print data for debugging
        console.log('Updating stock:', {
            request_id: requestId,
            medicine_id: formData.get('medicine_id'),
            quantity: formData.get('quantity'),
            stock_action: formData.get('stock_action')
        });
        
        // Send AJAX request
        $.ajax({
            url: '../processes/update_stock.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                console.log('Stock update response:', response);
                
                // Re-enable submit button
                $('#saveStockBtn').prop('disabled', false);
                
                if (response.success) {
                    if (response.duplicate) {
                        console.log('Duplicate request detected and handled safely');
                    }
                    
                    showAlert('success', 'Stock updated successfully' + (response.new_stock ? ' (New stock: ' + response.new_stock + ')' : ''));
                    $('#updateStockModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', 'Error: ' + (response.message || 'Unknown error'));
                }
            },
            error: function(xhr, status, error) {
                console.error('Stock update error:', xhr.responseText);
                
                // Re-enable submit button
                $('#saveStockBtn').prop('disabled', false);
                
                showAlert('danger', 'An error occurred while updating the stock');
            }
        });
    }

    function updateMedicine() {
        const formData = new FormData($('#editMedicineForm')[0]);
        
        // If quantity is empty, remove it from formData
        if (!formData.get('quantity')) {
            formData.delete('quantity');
        }
        
        // Add a unique request ID to prevent duplicate submissions
        const requestId = Date.now() + '-' + Math.random().toString(36).substring(2, 15);
        formData.append('request_id', requestId);
        
        // Disable submit button to prevent double submission
        $('#editMedicineModal .btn-primary').prop('disabled', true);
        
        $.ajax({
            url: '../processes/edit_medicine.php',
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    showAlert('success', 'Medicine updated successfully');
                    $('#editMedicineModal').modal('hide');
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showAlert('danger', 'Error: ' + (response.message || 'Unknown error'));
                    $('#editMedicineModal .btn-primary').prop('disabled', false);
                }
            },
            error: function(xhr, status, error) {
                console.error('Medicine update error:', xhr.responseText);
                showAlert('danger', 'An error occurred while updating the medicine');
                $('#editMedicineModal .btn-primary').prop('disabled', false);
            }
        });
    }

    // Helper function to show alerts
    function showAlert(type, message) {
        const alert = $(`<div class="alert alert-${type} alert-dismissible fade show" role="alert">`)
            .text(message)
            .append('<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>');
        
        $('.container').first().prepend(alert);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            alert.alert('close');
        }, 5000);
    }

    // Preserve active tab after form submission
    $(document).ready(function() {
        // Get the active tab from URL parameter
        const urlParams = new URLSearchParams(window.location.search);
        const activeTab = urlParams.get('tab');
        
        if (activeTab) {
            // Activate the tab
            $('.nav-tabs a[href="#' + activeTab + '"]').tab('show');
        }
        
        // Add tab parameter to search form
        $('form').submit(function() {
            const activeTabId = $('.nav-tabs .active').attr('href').substring(1);
            $(this).append('<input type="hidden" name="tab" value="' + activeTabId + '">');
        });

        // Initialize tooltips
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        const tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl, {
                placement: 'top',
                trigger: 'hover'
            });
        });
    });

    // Update pagination links to maintain low stock filter
    function updatePaginationLinks() {
        const lowStockParam = new URLSearchParams(window.location.search).get('show_low_stock');
        if (lowStockParam === '1') {
            document.querySelectorAll('.pagination .page-link').forEach(link => {
                const url = new URL(link.href, window.location.origin);
                url.searchParams.set('show_low_stock', '1');
                link.href = url.search;
            });
        }
    }

    $(document).ready(function() {
        // ... existing ready function code ...
        
        // Update pagination links
        updatePaginationLinks();
    });
</script>
<?php include_once '../includes/js_links.php'; ?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const refreshBtn = document.getElementById('refreshPrescriptionsBtn');
        if (refreshBtn) {
            refreshBtn.addEventListener('click', function() {
                const icon = this.querySelector('i');
                icon.classList.add('fa-spin');
                
                // Reload the page to refresh the prescriptions
                window.location.reload();
                
                // Note: In a real implementation, you would use AJAX to refresh just the prescriptions section
                // fetch('../processes/get_prescriptions.php')
                //     .then(response => response.json())
                //     .then(data => {
                //         if (data.success) {
                //             // Update the prescriptions table
                //             document.getElementById('prescriptionsTableBody').innerHTML = data.html;
                //         } else {
                //             showAlert('Failed to refresh prescriptions', 'danger');
                //         }
                //     })
                //     .catch(error => {
                //         console.error('Error refreshing prescriptions:', error);
                //         showAlert('Error refreshing prescriptions', 'danger');
                //     })
                //     .finally(() => {
                //         icon.classList.remove('fa-spin');
                //     });
            });
        }
    });
</script>
</body>
</html>
