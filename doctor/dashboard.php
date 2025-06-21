<?php
require_once '../includes/session_manager.php'; // Session management utilities
require_once '../includes/database.php'; // Database connection

// Check if user is logged in and is a doctor
// Ensures only authorized doctors can access this dashboard.
checkSession('doctor');

// Get doctor's information
// Fetches logged-in doctor's details for display.
$doctor_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$doctor = $stmt->get_result()->fetch_assoc();

// Ensure we got a valid doctor
if (!$doctor || $doctor['role'] !== 'doctor') {
    header('Location: ../login.php');
    exit();
}

// Get all appointments
$stmt = $conn->prepare("
    SELECT a.*, p.name as patient_name, p.id as patient_id
    FROM appointments a 
    JOIN users p ON a.patient_id = p.id 
    WHERE a.doctor_id = ?
    ORDER BY a.appointment_date ASC
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent prescriptions
// Fetches latest prescriptions written by the doctor.
$stmt = $conn->prepare("
    SELECT DISTINCT p.*, 
           u.name as patient_name,
           GROUP_CONCAT(CONCAT(m.brand_name, ' (', pi.dosage, ' ', pi.frequency, ' ', pi.duration) SEPARATOR ', ') as medicines
    FROM prescriptions p
    JOIN users u ON p.patient_id = u.id
    LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
    LEFT JOIN medicines m ON pi.medicine_id = m.id
    WHERE p.doctor_id = ?
    GROUP BY p.id
    ORDER BY p.created_at DESC
    LIMIT 10
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent lab requests
// Fetches latest lab test requests made by the doctor.
$stmt = $conn->prepare("
    SELECT l.*, u.name as patient_name
    FROM lab_tests l
    JOIN users u ON l.patient_id = u.id
    WHERE l.doctor_id = ?
    ORDER BY l.requested_date DESC
    LIMIT 10
");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$lab_requests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get statistics
// Fetches summary statistics for the doctor's dashboard.
$stats = [
    'total_patients' => 0,
    'total_prescriptions' => 0,
    'total_lab_requests' => 0
];

$stmt = $conn->prepare("SELECT COUNT(DISTINCT patient_id) as count FROM appointments WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stats['total_patients'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM prescriptions WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stats['total_prescriptions'] = $stmt->get_result()->fetch_assoc()['count'];

$stmt = $conn->prepare("SELECT COUNT(*) as count FROM lab_tests WHERE doctor_id = ?");
$stmt->bind_param("i", $doctor_id);
$stmt->execute();
$stats['total_lab_requests'] = $stmt->get_result()->fetch_assoc()['count'];

// Get the patient_id from the GET request
$patient_id = null;
if (isset($_GET['patient_id'])) {
    $patient_id = $_GET['patient_id'];
}

// Ensure patient_id is valid (e.g., not empty and is a number if expected)
// You might add validation here

$sql = "SELECT l.*, u.name as patient_name
        FROM lab_tests l
        JOIN users u ON l.patient_id = u.id";

// Add WHERE clause to filter by patient_id if it exists
if ($patient_id !== null) {
    $sql .= " WHERE l.patient_id = ?";
}

$sql .= " ORDER BY l.requested_date DESC"; // Or whatever your desired order is

$stmt = $conn->prepare($sql);

// Bind the patient_id parameter if filtering
if ($patient_id !== null) {
    $stmt->bind_param("i", $patient_id); // Assuming patient_id is an integer
}

$stmt->execute();
$lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - E-Health</title>
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
.admin-dashboard, .doctor-dashboard {
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
.table, .table-responsive, .dashboard-table, #labRequestsTable {
    width: 100% !important;
    max-width: 100% !important;
    margin: 0 !important;
    box-sizing: border-box;
}
.table th, .table td {
    box-sizing: border-box;
}
.table th:nth-child(1), .table td:nth-child(1) { width: 20%; }
.table th:nth-child(2), .table td:nth-child(2) { width: 15%; }
.table th:nth-child(3), .table td:nth-child(3) { width: 15%; }
.table th:nth-child(4), .table td:nth-child(4) { width: 10%; }
.table th:nth-child(5), .table td:nth-child(5) { width: 40%; }
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
#labRequestsTable td:nth-child(5) {
    /* Remove display: flex and related properties */
}
#labRequestsTable td:nth-child(5) .action-buttons {
    display: flex;
    justify-content: flex-end;
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

.table.dashboard-table, .table#labRequestsTable {
    table-layout: fixed;
}
.table th:nth-child(5), .table td:nth-child(5) {
    width: 20%;
    text-align: center;
}
/* Doctor Dashboard Header Styles (EXACT COPY for Doctor Dashboard) */
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
.medicine-search-container {
    position: relative;
}

.medicine-search-results {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: white;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    z-index: 1000;
    max-height: 200px;
    overflow-y: auto;
}

.medicine-search-results .medicine-item {
    padding: 0.5rem 1rem;
    cursor: pointer;
    border-bottom: 1px solid #dee2e6;
}

.medicine-search-results .medicine-item:last-child {
    border-bottom: none;
}

.medicine-search-results .medicine-item:hover {
    background-color: #f8f9fa;
}

.medication-item {
    border: 1px solid #dee2e6;
    border-radius: 0.5rem;
    transition: all 0.2s ease;
}

.medication-item:hover {
    box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}

.medication-item .remove-medication {
    position: absolute;
    top: 0.5rem;
    right: 0.5rem;
    color: #dc3545;
    cursor: pointer;
    opacity: 0.5;
    transition: opacity 0.2s ease;
}

.medication-item .remove-medication:hover {
    opacity: 1;
}

.medicine-select {
    width: 100%;
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    color: #212529;
    background-color: #fff;
    border: 1px solid #ced4da;
    border-radius: 0.375rem;
    transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
}

.medicine-select:focus {
    border-color: #86b7fe;
    outline: 0;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
    </style>
<style>
/* Admin Dashboard Header Styles (EXACT COPY for Doctor Dashboard) */
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
<?php
// Doctor header (like admin, but "Doctor Panel")
?>
<nav class="navbar admin-navbar navbar-expand-lg" style="background: #fff; box-shadow: 0 2px 12px rgba(30,58,138,0.08); border-radius: 0 0 18px 18px; padding: 0.5rem 0;">
    <div class="container d-flex align-items-center justify-content-between py-1">
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand header-title" href="/hcisProject/index.php" style="font-size: 1.8rem; font-weight: bold; letter-spacing: 0.5px;">
                <span style="color:#ffffff; font-size: 2rem;">E</span>-Health
            </a>
            <span class="header-subtext ms-3 d-none d-md-inline" style="color: #64748B; font-size: 1.1rem; font-weight: 600;">Doctor Panel</span>
        </div>

        <div class="d-flex align-items-center gap-3">
            <a href="/hcisProject/profile.php" class="header-subtext d-none d-md-inline" style="text-decoration:none; color: #1E3A8A; font-weight: 600; font-size: 1.1rem;">
                <?php echo htmlspecialchars($doctor['name'] ?? 'Doctor'); ?>
            </a>
            <a class="btn btn-primary ms-2" href="/hcisProject/logout.php" title="Logout" style="background: #1E3A8A; color: #fff; border-radius: 25px; padding: 0.4rem 1.2rem; font-weight: 600; font-size: 1rem; border: none; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.18s, color 0.18s;">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

<div class="dashboard-container">
<div class="row mb-4">
<h2 class="mb-0">Doctor Dashboard</h2>

    </div>

        <div class="row g-4">
            <div class="col-md-3">
            <div class="dashboard-card stats-card">
                <div class="icon-circle">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                <div>
                    <h5 class="card-title mb-0">  Appointments</h5>
                    <h2 class="card-value"><?php echo count($appointments); ?></h2>
                    <p class="card-subtitle">
                        <?php echo count(array_filter($appointments, function($a) { return $a['status'] === 'pending'; })); ?> pending
                    </p>
                </div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="dashboard-card stats-card">
                <div class="icon-circle">
                            <i class="fas fa-users"></i>
                        </div>
                <div>
                    <h5 class="card-title mb-0">Total Patients</h5>
                    <h2 class="card-value"><?php echo $stats['total_patients']; ?></h2>
                    <p class="card-subtitle">Registered patients</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="dashboard-card stats-card">
                <div class="icon-circle">
                            <i class="fas fa-prescription"></i>
                        </div>
                <div>
                    <h5 class="card-title mb-0">Prescriptions</h5>
                    <h2 class="card-value"><?php echo $stats['total_prescriptions']; ?></h2>
                    <p class="card-subtitle">Total prescribed</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="dashboard-card stats-card">
                <div class="icon-circle">
                            <i class="fas fa-flask"></i>
                        </div>
                <div>
                    <h5 class="card-title mb-0">Lab Tests</h5>
                    <h2 class="card-value"><?php echo $stats['total_lab_requests']; ?></h2>
                    <p class="card-subtitle">Tests requested</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-12">
                <div class="dashboard-card">
                <ul class="nav nav-tabs dashboard-nav" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#schedule" type="button" role="tab">
                                <i class="fas fa-calendar-alt me-2"></i>Patient Schedule
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" data-bs-toggle="tab" data-bs-target="#labTests" type="button" role="tab">
                                <i class="fas fa-flask me-2"></i>Lab Tests
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content mt-3">
                        <!-- Schedule Tab -->
                        <div class="tab-pane fade show active" id="schedule" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <p>Appointments</p>
                            <button class="btn btn-outline-primary btn-sm refresh-btn" onclick="loadAppointments()">
                                    <i class="fas fa-sync-alt"></i>Refresh
                                </button>
                            </div>
                            <!-- Place this above the appointments table -->
                            <div class="mb-3 d-flex align-items-center gap-2">
                                <label for="appointmentDatePicker" class="form-label mb-0">Filter by Date:</label>
                                <input type="text" id="appointmentDatePicker" class="form-control" style="max-width: 180px;">
                                <button class="today-btn btn-sm" id="todayBtn">Today</button>
                            </div>
                            <div class="table-responsive">
                            <table class="table dashboard-table">
                                    <thead>
                                        <tr>
                                            <th>Patient Name</th>
                                            <th>Reason to Visit</th>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="appointmentsTableBody">
                                        <?php foreach ($appointments as $appointment): ?>
                                            <tr>
                                                <td>
                                                    <!-- Link to open the patient history modal for the selected appointment -->
                                                    <a href="#" data-bs-toggle="modal" data-bs-target="#patientHistoryModal" data-patient-id="<?php echo $appointment['patient_id']; ?>">
                                                        <?php echo htmlspecialchars($appointment['patient_name']); ?>
                                                    </a>
                                                </td>
                                                <td><?php echo htmlspecialchars($appointment['reason'] ?? 'Not specified'); ?></td>
                                                <td><?php echo date('M j, Y H:i', strtotime($appointment['appointment_date'])); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $appointment['status'] === 'completed' ? 'success' : ($appointment['status'] === 'cancelled' ? 'danger' : 'warning'); ?>">
                                                        <?php echo htmlspecialchars($appointment['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if ($appointment['status'] === 'pending'): ?>
                                                        <button class="btn btn-sm btn-success status-btn" data-appointment-id="<?php echo $appointment['id']; ?>" data-status="completed">
                                                            Complete
                                                        </button>
                                                        <button class="btn btn-sm btn-danger status-btn" data-appointment-id="<?php echo $appointment['id']; ?>" data-status="cancelled">
                                                            Cancel
                                                        </button>
                                                    <?php endif; ?>
                                                    <button class="btn btn-sm btn-primary prescribe-btn" data-bs-toggle="modal" data-bs-target="#prescribeModal" data-patient-id="<?php echo $appointment['patient_id']; ?>">
                                                        Prescribe
                                                    </button>
                                                    <button class="btn btn-sm btn-warning lab-test-btn" data-bs-toggle="modal" data-bs-target="#labTestModal" data-patient-id="<?php echo $appointment['patient_id']; ?>">
                                                        Lab Test
                                                    </button>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                        
                        <!-- Lab Tests Tab -->
                        <div class="tab-pane fade" id="labTests" role="tabpanel">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h5 class="mb-0"><i class="fas fa-flask me-2"></i>Lab Test Results</h5>
                                <button class="btn btn-sm btn-outline-primary" onclick="fetchLabRequests()">
                                    <i class="fas fa-sync-alt me-1"></i>Refresh
                                </button>
                            </div>
                            <div class="table-responsive">
                                <table class="table table-hover" id="labRequestsTable">
                                    <thead>
                                        <tr>
                                            <th>Patient</th>
                                            <th>Test Type</th>
                                            <th>Requested Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td colspan="5" class="text-center">
                                                <div class="spinner-border text-primary" role="status">
                                                    <span class="visually-hidden">Loading...</span>
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

<!-- Modals -->
<div class="modal fade dashboard-modal" id="prescribeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title"><i class="fas fa-prescription me-2"></i>Prescribe Medicine</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="prescribeError" class="alert alert-danger d-none">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <span id="prescribeErrorMessage"></span>
                </div>
                <div id="prescribeSuccess" class="alert alert-success d-none">
                    <i class="fas fa-check-circle me-2"></i>
                    <span id="prescribeSuccessMessage"></span>
                </div>
                <form id="prescribeForm" onsubmit="event.preventDefault(); submitPrescription();">
                    <input type="hidden" id="prescribe_patient_id" name="patient_id">
                    <div class="mb-4">
                        <label class="form-label fw-bold">Medications</label>
                        <div class="medication-list">
                            <div class="medication-item card mb-3">
                                <div class="card-body">
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Medicine</label>
                                            <div class="medicine-search-container">
                                                <input type="text" class="form-control medicine-search-input" placeholder="Search medicine..." autocomplete="off">
                                                <div class="medicine-search-results d-none"></div>
                                                <select class="form-select medicine-select d-none" required>
                                                    <option value="">Select Medicine</option>
                                                    <?php
                                                    $medicines_query = "SELECT id, brand_name, strength, quantity 
                                                                     FROM medicines 
                                                                     WHERE quantity > 0 
                                                                     ORDER BY brand_name ASC";
                                                    $medicines_result = $conn->query($medicines_query);
                                                    while ($medicine = $medicines_result->fetch_assoc()) {
                                                        echo "<option value='{$medicine['id']}' data-stock='{$medicine['quantity']}'>";
                                                        echo htmlspecialchars($medicine['brand_name']) . " (" . htmlspecialchars($medicine['strength']) . ")";
                                                        echo "</option>";
                                                    }
                                                    ?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Frequency</label>
                                            <input type="text" class="form-control" placeholder="e.g., Once daily" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Duration</label>
                                            <input type="text" class="form-control" placeholder="e.g., 7 days" required>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="addMedication()">
                            <i class="fas fa-plus me-1"></i>Add Another Medicine
                        </button>
                    </div>
                    <div class="mb-3">
                        <label for="prescription_notes" class="form-label fw-bold">Notes</label>
                        <textarea class="form-control" id="prescription_notes" name="notes" rows="3" placeholder="Add any special instructions or notes..."></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" onclick="submitPrescription()">
                    <i class="fas fa-save me-1"></i>Save Prescription
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade dashboard-modal" id="labTestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Request Lab Test</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="labTestError" class="alert alert-danger d-none">
                        <i class="fas fa-exclamation-circle me-2"></i>
                        <span id="labTestErrorMessage"></span>
                    </div>
                    <div id="labTestSuccess" class="alert alert-success d-none">
                        <i class="fas fa-check-circle me-2"></i>
                        <span id="labTestSuccessMessage"></span>
                    </div>
                    <form id="labTestForm">
    <input type="hidden" id="test_patient_id" name="patient_id">
                        <div class="mb-3">
                            <label class="form-label">Test Types</label>
                            <div class="test-list mb-2">
                                <div class="test-item input-group mb-2">
                                    <select class="form-select" required>
                                        <option value="">Select test type</option>
                                        <option value="Blood Test">Blood Test</option>
                                        <option value="Urine Test">Urine Test</option>
                                        <option value="X-Ray">X-Ray</option>
                                        <option value="CT Scan">CT Scan</option>
                                        <option value="MRI">MRI</option>
                                    </select>
                                    <input type="text" class="form-control" placeholder="Notes">
                                    <button type="button" class="btn btn-outline-danger" onclick="removeTest(this)">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </div>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="addTest()">
                                <i class="fas fa-plus me-1"></i>Add Test
                            </button>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="button" class="btn btn-primary" onclick="submitLabTest()">
                                <i class="fas fa-paper-plane me-1"></i>Submit
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

<div class="modal fade dashboard-modal" id="patientHistoryModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Patient Medical History</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="patientHistoryContent">
                    <!-- Patient history content will be loaded here via AJAX when the modal is opened -->
                </div>
                <div class="modal-footer flex-column align-items-stretch p-3" id="patientLabShortcut">
                    <!-- Lab result list will be loaded here via JS -->
                </div>
            </div>
        </div>
    </div>

<div class="modal fade dashboard-modal" id="labResultsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Lab Test Results</h5>
                    <div class="alert alert-info small mb-0">
                        <i class="fas fa-info-circle me-2"></i>
                        Results are color-coded: <span class="badge status-normal">Normal</span> 
                        <span class="badge status-abnormal">Abnormal</span> 
                        <span class="badge status-critical">Critical</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="labResultsContent">
                    <!-- Lab results will be loaded here via AJAX -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/flatpickr"></script>
<script>
    //  submitPrescription function 
    async function submitPrescription() {
        const form = document.getElementById('prescribeForm');
        const patientId = form.querySelector('#prescribe_patient_id').value;
        
        if (!patientId) {
            showPrescribeError('Patient ID is missing.');
            return;
        }
        
        // Collect all medications
        const medications = [];
        const medicationItems = document.querySelectorAll('.medication-item');
        
        medicationItems.forEach(item => {
            const medicineSelect = item.querySelector('.medicine-select');
            const inputFields = item.querySelectorAll('input');
            
            if (medicineSelect && medicineSelect.value) {
                const medicineData = {
                    medicine_id: medicineSelect.value,
                    frequency: inputFields[0] ? inputFields[0].value : '',
                    duration: inputFields[1] ? inputFields[1].value : ''
                };
                
                if (medicineData.frequency && medicineData.duration) {
                    medications.push(medicineData);
                }
            }
        });

        if (medications.length === 0) {
            showPrescribeError('Please add at least one medication with all fields filled.');
            return;
        }

        // Prepare data for submission
        const formData = new FormData();
        formData.append('patient_id', patientId);
        formData.append('medicines', JSON.stringify(medications));
        formData.append('notes', form.querySelector('#prescription_notes').value);

        try {
            const response = await fetch('../processes/prescribe.php', {
                method: 'POST',
                body: formData
            });

            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }

            const result = await response.json();
            
            if (result.success) {
                showPrescribeSuccess(result.message || 'Prescription saved successfully!');
                // Clear form after successful submission
                form.reset();
                medicationItems.forEach(item => {
                    // Keep the first item and clear its values
                    if (item === medicationItems[0]) {
                        if (item.querySelector('select')) item.querySelector('select').value = '';
                        item.querySelectorAll('input').forEach(input => input.value = '');
                    } else {
                        // Remove additional items
                        item.remove();
                    }
                });
                
                // Auto close and refresh after success
                setTimeout(function() {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('prescribeModal'));
                    if (modal) {
                        modal.hide();
                    }
                    location.reload();
                }, 2000);
            } else {
                showPrescribeError(result.message || 'Failed to save prescription.');
            }
        } catch (error) {
            console.error('Error:', error);
            showPrescribeError('An error occurred while submitting the prescription: ' + error.message);
        }
    }

    // Also move these helper functions outside
    function showPrescribeError(message) {
        const errorDiv = document.getElementById('prescribeError');
        const errorMessage = document.getElementById('prescribeErrorMessage');
        errorMessage.textContent = message;
        errorDiv.classList.remove('d-none');
        document.getElementById('prescribeSuccess').classList.add('d-none');
    }

    function showPrescribeSuccess(message) {
        const successDiv = document.getElementById('prescribeSuccess');
        const successMessage = document.getElementById('prescribeSuccessMessage');
        successMessage.textContent = message;
        successDiv.classList.remove('d-none');
        document.getElementById('prescribeError').classList.add('d-none');
    }

    document.addEventListener('DOMContentLoaded', function() {

        // Attach patient history modal trigger
        document.addEventListener('click', function(event) {
            // Revert to original trigger for patient history modal
            if (event.target.matches('a[data-bs-target="#patientHistoryModal"]')) { // Changed selector back
                event.preventDefault();
                var patientId = event.target.getAttribute('data-patient-id');
                
                // Call original functions to load history and lab shortcut
                loadPatientHistory(patientId);
                loadPatientLabShortcut(patientId);

            } else if (event.target.classList.contains('view-results-btn')) {
                // Event listener for View Results button
                event.preventDefault();
                const testId = event.target.getAttribute('data-test-id');
                fetchLabResults(testId);
            }
        });

        // Load latest lab result shortcut for patient
        // This function is no longer directly called from the patient name click, 
        // but its logic is adapted in fetchAndDisplayPatientLabResults
        
        function loadPatientLabShortcut(patientId) {
            fetch('../processes/get_lab_tests.php?patient_id=' + encodeURIComponent(patientId))
                .then(response => response.json())
                .then(data => {
                    const footer = document.getElementById('patientLabShortcut');
                    footer.innerHTML = ''; // Clear existing content
                    
                    if (data.success && data.lab_tests && data.lab_tests.length > 0) {
                        // Add a heading for the lab tests section in the footer
                        const heading = document.createElement('h6');
                        heading.className = 'mb-2';
                        heading.innerHTML = '<i class="fas fa-flask me-2"></i>Recent Lab Tests for this Patient</h6>';
                        footer.appendChild(heading);

                        const labTestList = document.createElement('ul');
                        labTestList.className = 'list-group list-group-flush'; // Use Bootstrap list group for styling
                        labTestList.style.maxHeight = '200px';
                        labTestList.style.overflowY = 'auto';

                        // Sort tests by date (newest first)
                        const sortedTests = data.lab_tests.sort((a, b) => 
                            new Date(b.requested_date || 0) - new Date(a.requested_date || 0)
                        );
                        
                        sortedTests.forEach(test => {
                            const listItem = document.createElement('li');
                            listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                            listItem.style.padding = '0.5rem 0';
                            listItem.style.borderBottom = '1px solid #e9ecef';

                            const testInfo = document.createElement('span');
                            testInfo.innerHTML = `
                                ${test.test_type} - ${test.requested_date ? new Date(test.requested_date).toLocaleDateString() : 'N/A'}
                                <span class="badge bg-${test.status === 'completed' ? 'success' : (test.status === 'processing' ? 'info' : 'warning')} ms-2">${test.status || 'pending'}</span>
                            `;
                            listItem.appendChild(testInfo);

                            const actions = document.createElement('span');
                            

                            // PDF button if available
                            if (test.result_pdf) {
                                const pdfLink = document.createElement('a');
                                pdfLink.href = `../${test.result_pdf}`; // Link to the PDF file
                                pdfLink.target = '_blank'; // Open in new tab
                                pdfLink.className = 'btn btn-sm btn-primary';
                                pdfLink.innerHTML = '<i class="fas fa-file-pdf me-1"></i>PDF';
                                actions.appendChild(pdfLink);
                            }

                            listItem.appendChild(actions);
                            labTestList.appendChild(listItem);
                        });
                        
                        footer.appendChild(labTestList);

                    } else {
                        footer.innerHTML = '<span class="text-muted">No lab tests found for this patient.</span>';
                    }
                })
                .catch(error => {
                    console.error('Error loading lab tests:', error);
                    document.getElementById('patientLabShortcut').innerHTML = 
                        '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading lab test list.</span>';
                });
        }

        // New function to fetch and display all lab results for a patient
        async function fetchAndDisplayPatientLabResults(patientId) {
            const resultsContentDiv = document.getElementById('labResultsContent');
            const resultsModalTitle = document.querySelector('#labResultsModal .modal-title');

            // Update modal title
            resultsModalTitle.textContent = 'Patient Lab Test Results';

            // Show loading state
            resultsContentDiv.innerHTML = `
                <div class="text-center p-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading lab tests...</p>
                </div>
            `;

            try {
                // Fetch the list of lab tests for the patient
                const response = await fetch('../processes/get_lab_tests.php?patient_id=' + encodeURIComponent(patientId));
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json();

                resultsContentDiv.innerHTML = ''; // Clear loading state

                if (!data.success || !data.lab_tests || data.lab_tests.length === 0) {
                    resultsContentDiv.innerHTML = `
                        <div class="alert alert-info mb-0">
                            <i class="fas fa-info-circle me-2"></i>No lab tests found for this patient.
                        </div>
                    `;
                    return;
                }

                // Sort lab tests by requested date (newest first)
                data.lab_tests.sort((a, b) => {
                    return new Date(b.requested_date || 0) - new Date(a.requested_date || 0);
                });

                // Fetch and display results for each test
                for (const test of data.lab_tests) {
                    // Add a heading for each test
                     const testHeading = document.createElement('h6');
                     testHeading.innerHTML = `<i class="fas fa-flask me-2"></i>${test.test_type} - ${test.requested_date ? new Date(test.requested_date).toLocaleDateString() : 'N/A'} <span class="badge bg-${test.status === 'completed' ? 'success' : (test.status === 'processing' ? 'info' : 'warning')}">${test.status || 'pending'}</span>`;
                     if (test.result_pdf) {
                         testHeading.innerHTML += ` <a href="../${test.result_pdf}" target="_blank" class="btn btn-primary btn-sm ms-2"><i class="fas fa-file-pdf me-1"></i>View PDF</a>`;
                     }

                    resultsContentDiv.appendChild(testHeading);

                    // Create a container for the individual test result
                    const testResultContainer = document.createElement('div');
                    testResultContainer.id = `test-result-${test.id}`;
                    resultsContentDiv.appendChild(testResultContainer);

                    // Fetch and display the detailed result for this test
                    await fetchLabResultsIntoContainer(test.id, testResultContainer);

                    // Add a separator if it's not the last test
                    if (test !== data.lab_tests[data.lab_tests.length - 1]) {
                         resultsContentDiv.appendChild(document.createElement('hr'));
                    }
                }

            } catch (error) {
                console.error('Error fetching patient lab tests:', error);
                resultsContentDiv.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Error loading lab tests: ${error.message || 'Unknown error'}
                    </div>
                `;
            }
        }
        
         // Function to fetch lab results for a given test and put into a specific container
        async function fetchLabResultsIntoContainer(testId, containerElement) {
            if (!testId) {
                console.error('Test ID is missing');
                containerElement.innerHTML = '<div class="alert alert-danger">Test ID is missing.</div>';
                return;
            }
            
            // Show loading indicator in the container
            containerElement.innerHTML = `
                <div class="text-center p-3">
                    <div class="spinner-border text-secondary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-muted small">Loading results for test ${testId}...</p>
                </div>
            `;
            
            try {
                const response = await fetch(`../processes/get_lab_results.php?test_id=${encodeURIComponent(testId)}`);
                console.log('processes Response Status for test', testId, ':', response.status);
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                const data = await response.json().catch(err => {
                    console.error('JSON parsing error for test', testId, ':', err);
                    throw new Error('Invalid response format from server for test ' + testId);
                });
                
                console.log('processes Response Data for test', testId, ':', data);

                if (data.success && data.html) {
                    containerElement.innerHTML = data.html;
                } else {
                    const errorMsg = data.message || `Failed to load results for test ${testId}.`;
                    console.error('processes Error Message for test', testId, ':', errorMsg);
                    containerElement.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            ${errorMsg}
                        </div>
                    `;
                }
            } catch (error) {
                 console.error('Error fetching lab results for test', testId, ':', error);
                 containerElement.innerHTML = `
                     <div class="alert alert-danger">
                         <i class="fas fa-exclamation-triangle me-2"></i>
                         ${error.message || `Network error while loading results for test ${testId}`}
                     </div>
                 `;
            }
        }

        // Modify the existing fetchLabResults function to just open the modal and call the new function
        // This is kept for compatibility with view-results-btn, but we'll update it slightly
        function fetchLabResults(testId) {
            if (!testId) {
                console.error('Test ID is missing');
                return;
            }

             var resultsModal = new bootstrap.Modal(document.getElementById('labResultsModal'));
             resultsModal.show();

             // Clear previous content
             document.getElementById('labResultsContent').innerHTML = '';

            // Call the new function to fetch and display a single test result in the modal content area
            fetchLabResultsIntoContainer(testId, document.getElementById('labResultsContent'));

            // Update modal title for single test result
            document.querySelector('#labResultsModal .modal-title').textContent = 'Lab Test Results';

        }

        // Function to submit prescription
        async function submitPrescription() {
            const form = document.getElementById('prescribeForm');
            const patientId = form.querySelector('#prescribe_patient_id').value;
            
            if (!patientId) {
                showPrescribeError('Patient ID is missing.');
                return;
            }
            
            // Collect all medications
            const medications = [];
            const medicationItems = document.querySelectorAll('.medication-item');
            
            medicationItems.forEach(item => {
                const medicineSelect = item.querySelector('.medicine-select');
                const inputFields = item.querySelectorAll('input');
                
                if (medicineSelect && medicineSelect.value) {
                    const medicineData = {
                        medicine_id: medicineSelect.value,
                        frequency: inputFields[0] ? inputFields[0].value : '',
                        duration: inputFields[1] ? inputFields[1].value : ''
                    };
                    
                    if (medicineData.frequency && medicineData.duration) {
                        medications.push(medicineData);
                    }
                }
            });

            if (medications.length === 0) {
                showPrescribeError('Please add at least one medication with all fields filled.');
                return;
            }

            // Prepare data for submission
            const formData = new FormData();
            formData.append('patient_id', patientId);
            formData.append('medicines', JSON.stringify(medications));
            formData.append('notes', form.querySelector('#prescription_notes').value);

            try {
                const response = await fetch('../processes/prescribe.php', {
                    method: 'POST',
                    body: formData
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const result = await response.json();
                
                if (result.success) {
                    showPrescribeSuccess(result.message || 'Prescription saved successfully!');
                    // Clear form after successful submission
                    form.reset();
                    medicationItems.forEach(item => {
                        // Keep the first item and clear its values
                        if (item === medicationItems[0]) {
                            if (item.querySelector('select')) item.querySelector('select').value = '';
                            item.querySelectorAll('input').forEach(input => input.value = '');
                        } else {
                            // Remove additional items
                            item.remove();
                        }
                    });
                    
                    // Auto close and refresh after success
                    setTimeout(function() {
                        const modal = bootstrap.Modal.getInstance(document.getElementById('prescribeModal'));
                        if (modal) {
                            modal.hide();
                        }
                        location.reload();
                    }, 2000);
                } else {
                    showPrescribeError(result.message || 'Failed to save prescription.');
                }
            } catch (error) {
                console.error('Error:', error);
                showPrescribeError('An error occurred while submitting the prescription: ' + error.message);
            }
        }

        // Patient name click event
        document.querySelectorAll('.table tbody tr td a[data-bs-toggle="modal"]').forEach(function(link) {
            link.addEventListener('click', function(event) {
                event.preventDefault();
                var patientId = this.dataset.patientId;
                loadPatientHistory(patientId);
            });
        });

        // Prescribe button click event (event delegation)
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('prescribe-btn')) {
                var patientId = event.target.dataset.patientId;
                document.getElementById('prescribe_patient_id').value = patientId;
            }
        });

        // Lab test button click event (event delegation)
        document.addEventListener('click', function(event) {
            if (event.target.classList.contains('lab-test-btn')) {
                var patientId = event.target.dataset.patientId;
                document.getElementById('test_patient_id').value = patientId;
            }
        });

          // Appointment status update buttons (event delegation)
          document.addEventListener('click', function(event) {
            if (event.target.classList.contains('status-btn')) {
                const button = event.target;
                const appointmentId = button.dataset.appointmentId;
                const newStatus = button.dataset.status;
                console.log('Attempting to update appointment:', appointmentId, 'to', newStatus);

                if (!confirm(`Are you sure you want to mark this appointment as ${newStatus}?`)) return;

                fetch('../processes/update_appointment_status.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: `appointment_id=${encodeURIComponent(appointmentId)}&status=${encodeURIComponent(newStatus)}`
                })
                .then(response => {
                    console.log('processes response status:', response.status);
                    return response.json();
                })
                .then(result => {
                    console.log('processes response body:', result);
                    if (result.success) {
                        location.reload();
                    } else {
                        alert('Update failed: ' + (result.message || result.error || 'Unknown error'));
                    }
                })
                .catch(e => {
                    alert('Network error');
                    console.error('Fetch error:', e);
                });
            }
        });
    });

    function loadPatientHistory(patientId) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../processes/get_patient_history.php?patient_id=' + patientId, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                document.getElementById('patientHistoryContent').innerHTML = xhr.responseText;
            } else {
                document.getElementById('patientHistoryContent').innerHTML = '<p>Failed to load patient history.</p>';
            }
        };
        xhr.onerror = function() {
            document.getElementById('patientHistoryContent').innerHTML = '<p>Network error occurred.</p>';
        };
        xhr.send();
    }

    // Function to initialize medicine search
    function initializeMedicineSearch(container) {
        const searchInput = container.querySelector('.medicine-search-input');
        const searchResults = container.querySelector('.medicine-search-results');
        const medicineSelect = container.querySelector('.medicine-select');
        
        // Get all medicines from the select options
        const medicines = Array.from(medicineSelect.options).slice(1).map(option => ({
            id: option.value,
            name: option.text.split('(')[0].trim(),
            strength: option.text.match(/\((.*?)\)/)?.[1] || '',
            stock: option.text.match(/Stock: (\d+)/)?.[1] || '0'
        }));
        
        // Show/hide search results
        searchInput.addEventListener('focus', () => {
            searchResults.classList.remove('d-none');
            filterMedicines(searchInput.value.toLowerCase());
        });

        // Hide results when clicking outside
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.classList.add('d-none');
            }
        });

        // Filter medicines as user types
        searchInput.addEventListener('input', () => {
            filterMedicines(searchInput.value.toLowerCase());
        });

        function filterMedicines(searchTerm) {
            searchResults.innerHTML = '';
            const filteredMedicines = medicines.filter(medicine => 
                medicine.name.toLowerCase().includes(searchTerm) || 
                medicine.strength.toLowerCase().includes(searchTerm)
            );

            filteredMedicines.forEach(medicine => {
                const div = document.createElement('div');
                div.className = 'medicine-search-item';
                div.innerHTML = `
                    <div class="medicine-info">
                        <div class="medicine-name">${medicine.name}</div>
                        <div class="medicine-details">${medicine.strength}</div>
                    </div>
                    <div class="medicine-stock ${parseInt(medicine.stock) < 10 ? 'low' : ''}">
                        Stock: ${medicine.stock}
                    </div>
                `;
                
                div.addEventListener('click', () => {
                    medicineSelect.value = medicine.id;
                    searchInput.value = `${medicine.name} (${medicine.strength})`;
                    searchResults.classList.add('d-none');
                });
                
                searchResults.appendChild(div);
            });

            searchResults.style.display = filteredMedicines.length ? 'block' : 'none';
        }
    }

    // Initialize medicine search for all existing items
    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('.medication-item').forEach(item => {
            initializeMedicineSearch(item);
        });
    });

    // Function to add new medication
    function addMedication() {
        const container = document.querySelector('.medication-list');
        const newItem = document.createElement('div');
        newItem.className = 'medication-item card mb-3';
        
        newItem.innerHTML = `
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Medicine</label>
                        <div class="medicine-search-container">
                            <input type="text" class="form-control medicine-search-input" placeholder="Search medicine..." autocomplete="off">
                            <div class="medicine-search-results d-none"></div>
                            <select class="form-select medicine-select d-none" required>
                                ${document.querySelector('.medicine-select').innerHTML}
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Frequency</label>
                        <input type="text" class="form-control" placeholder="e.g., Once daily" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Duration</label>
                        <input type="text" class="form-control" placeholder="e.g., 7 days" required>
                    </div>
                </div>
                <button type="button" class="btn btn-outline-danger btn-sm position-absolute top-0 end-0 m-2" onclick="removeMedication(this)">
                    <i class="fas fa-times"></i>
                </button>
            </div>
        `;
        
        container.appendChild(newItem);
        initializeMedicineSearch(newItem);
    }

    // Function to remove medication
    function removeMedication(button) {
        const container = document.querySelector('.medication-list');
        if (container.children.length > 1) {
            button.closest('.medication-item').remove();
        }
    }

    // Test list management
    function addTest() {
        const container = document.querySelector('.test-list');
        const item = document.querySelector('.test-item').cloneNode(true);
        item.querySelector('select').value = '';
        item.querySelector('input').value = '';
        container.appendChild(item);
    }

    function removeTest(button) {
        const container = document.querySelector('.test-list');
        if (container.children.length > 1) {
            button.closest('.test-item').remove();
        }
    }

    // Submit lab test
    async function submitLabTest() {
        console.log('submitLabTest called!');
        const form = document.getElementById('labTestForm');
        const patientId = form.patient_id.value.trim();
        if (!patientId) {
            showLabTestError('Patient ID is missing.');
            return;
        }

        const tests = [];
        document.querySelectorAll('.test-item').forEach(item => {
            const test_type = item.querySelector('select').value;
            const notes = item.querySelector('input').value.trim();
            if (test_type) {
                tests.push({ test_type, notes });
            }
        });

        if (tests.length === 0) {
            showLabTestError('Please add at least one test type.');
            return;
        }

        const data = new FormData();
        data.append('patient_id', patientId);
        data.append('tests', JSON.stringify(tests));

        try {
            const response = await fetch('../processes/submit_lab_test.php', {
                method: 'POST',
                body: data
            });
            const result = await response.json();
            if (result.success) {
                document.getElementById('labTestSuccessMessage').textContent = 'Done';
                document.getElementById('labTestSuccess').classList.remove('d-none');
                document.getElementById('labTestError').classList.add('d-none');
                form.reset();
                setTimeout(() => {
                    const modal = bootstrap.Modal.getInstance(document.getElementById('labTestModal'));
                    modal.hide();
                    location.reload();
                }, 1500);
            } else {
                showLabTestError(result.error || 'Submission failed');
            }
        } catch (e) {
            showLabTestError('Network error');
        }
    }

    function showLabTestError(message) {
        const errorDiv = document.getElementById('labTestError');
        document.getElementById('labTestErrorMessage').textContent = message;
        errorDiv.classList.remove('d-none');
        document.getElementById('labTestSuccess').classList.add('d-none');
    }

        // Function to fetch and display appointments
        async function loadAppointments() {
        try {
            const response = await fetch('../processes/get_appointments.php?doctor_id=<?php echo $doctor_id; ?>');
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            const appointments = await response.json();
            displayAppointments(appointments);
        } catch (error) {
            console.error('Could not fetch appointments:', error);
            document.getElementById('appointmentsTableBody').innerHTML = '<tr><td colspan="4">Failed to load appointments.</td></tr>';
        }
    }

    function displayAppointments(appointments) {
        let tableBody = document.getElementById('appointmentsTableBody');
        tableBody.innerHTML = ''; // Clear existing data

        if (appointments.length === 0) {
            tableBody.innerHTML = '<tr><td colspan="5">No appointments found.</td></tr>';
            return;
        }

        appointments.forEach(appointment => {
            let row = tableBody.insertRow();

            // Patient Name cell with a link
            let patientCell = row.insertCell(0);
            patientCell.innerHTML = `<a href="#" data-bs-toggle="modal" data-bs-target="#patientHistoryModal" data-patient-id="${appointment.patient_id}">${appointment.patient_name}</a>`;

            // Reason to Visit cell
            let reasonCell = row.insertCell(1);
            reasonCell.textContent = appointment.reason || 'Not specified';

            // Date & Time cell
            let dateTimeCell = row.insertCell(2);
            dateTimeCell.textContent = new Date(appointment.appointment_date).toLocaleString();

            // Status cell
            let statusCell = row.insertCell(3);
            statusCell.innerHTML = `<span class="badge bg-${appointment.status === 'completed' ? 'success' : (appointment.status === 'cancelled' ? 'danger' : 'warning')}">${appointment.status}</span>`;

            // Actions cell
            let actionsCell = row.insertCell(4);

            // Only show action buttons if status is 'pending'
            if (appointment.status === 'pending') {
                let actionsButtons = '';
                actionsButtons += `<button class="btn btn-sm btn-success status-btn" data-appointment-id="${appointment.id}" data-status="completed">Complete</button>
                <button class="btn btn-sm btn-danger status-btn" data-appointment-id="${appointment.id}" data-status="cancelled">Cancel</button>`;
                actionsButtons += `<button class="btn btn-sm btn-primary prescribe-btn" data-bs-toggle="modal" data-bs-target="#prescribeModal" data-patient-id="${appointment.patient_id}">Prescribe</button>
                <button class="btn btn-sm btn-warning lab-test-btn" data-bs-toggle="modal" data-bs-target="#labTestModal" data-patient-id="${appointment.patient_id}">Lab Test</button>`;
                actionsCell.innerHTML = actionsButtons;
            } else {
                actionsCell.innerHTML = '';
            }
        });
    }


    // Load appointments and lab tests on page load
    window.onload = function() {
        loadAppointments();
        fetchLabRequests();
    }

    // Fetch lab requests for doctor
    function fetchLabRequests() {
        const tbody = document.querySelector('#labRequestsTable tbody');
        
        // Show loading state
        tbody.innerHTML = `
            <tr>
                <td colspan="5" class="text-center">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2">Loading lab tests...</p>
                </td>
            </tr>
        `;
        
        fetch('../processes/get_lab_tests.php')
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! Status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                tbody.innerHTML = '';
                
                if (!data.success || !data.lab_tests || data.lab_tests.length === 0) {
                    tbody.innerHTML = `
                        <tr>
                            <td colspan="5" class="text-center">
                                <div class="alert alert-info mb-0">
                                    <i class="fas fa-info-circle me-2"></i>No lab requests found
                                </div>
                            </td>
                        </tr>
                    `;
                    return;
                }
                
                // Sort lab tests by requested date (newest first)
                data.lab_tests.sort((a, b) => {
                    return new Date(b.requested_date || 0) - new Date(a.requested_date || 0);
                });
                
                data.lab_tests.forEach(test => {
                    const row = document.createElement('tr');
                    
                    // Add appropriate row class based on status
                    if (test.status === 'completed') {
                        row.classList.add('table-success');
                    } else if (test.status === 'pending') {
                        row.classList.add('table-warning');
                    }
                    
                    row.innerHTML = `
                        <td>${test.patient_name}</td>
                        <td>${test.test_type}</td>
                        <td>${test.requested_date ? new Date(test.requested_date).toLocaleDateString() : ''}</td>
                        <td>
                            <span class="badge bg-${test.status === 'completed' ? 'success' : (test.status === 'processing' ? 'info' : 'warning')}">
                                ${test.status || 'pending'}
                            </span>
                        </td>
                        <td>
                            <div class="action-buttons">
                                <button class="btn btn-info btn-sm view-results-btn" data-test-id="${test.id}">
                                    <i class="fas fa-eye me-1"></i>View
                                </button>
                                ${test.result_pdf ? `
                                    <a href="../${test.result_pdf}" target="_blank" class="btn btn-primary btn-sm ms-1">
                                        <i class="fas fa-file-pdf me-1"></i>PDF
                                    </a>
                                ` : ''}
                            </div>
                        </td>
                    `;
                    tbody.appendChild(row);
                });
            })
            .catch(error => {
                console.error('Error fetching lab tests:', error);
                tbody.innerHTML = `
                    <tr>
                        <td colspan="5" class="text-center">
                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle me-2"></i>
                                Error loading lab requests: ${error.message || 'Unknown error'}
                            </div>
                        </td>
                    </tr>
                `;
            });
    }

    // Pass appointment dates from PHP to JS
    const appointmentDates = <?php echo json_encode(array_values(array_unique(array_map(function($a) { return date('Y-m-d', strtotime($a['appointment_date'])); }, $appointments)))); ?>;

    // Get today's date in YYYY-MM-DD
    const today = new Date();
    const yyyy = today.getFullYear();
    const mm = String(today.getMonth() + 1).padStart(2, '0');
    const dd = String(today.getDate()).padStart(2, '0');
    const todayStr = `${yyyy}-${mm}-${dd}`;

    // Initialize flatpickr with defaultDate as today
    const fp = flatpickr("#appointmentDatePicker", {
        dateFormat: "Y-m-d",
        defaultDate: todayStr,
        onReady: function(selectedDates, dateStr, instance) {
            // Set input value to today on load
            instance.input.value = todayStr;
            filterAppointmentsByDate(todayStr);
            highlightAppointmentDays(instance);
        },
        onChange: function(selectedDates, dateStr, instance) {
            filterAppointmentsByDate(dateStr);
        },
        onMonthChange: function(selectedDates, dateStr, instance) {
            highlightAppointmentDays(instance);
        }
    });

    // Function to highlight days with appointments
    function highlightAppointmentDays(instance) {
        const days = instance.days.childNodes;
        days.forEach(day => {
            if (day.classList && day.classList.contains('flatpickr-day')) {
                const date = day.getAttribute('aria-label');
                if (date) {
                    const dateStr = new Date(date).toISOString().split('T')[0];
                    if (appointmentDates.includes(dateStr)) {
                        day.classList.add('has-appointment');
                    } else {
                        day.classList.remove('has-appointment');
                    }
                }
            }
        });
    }

    // Today button click handler
    document.getElementById('todayBtn').addEventListener('click', function() {
        fp.setDate(todayStr, true);
        filterAppointmentsByDate(todayStr);
    });

    // Filter function: expects dateStr in YYYY-MM-DD
    function filterAppointmentsByDate(dateStr) {
        const rows = document.querySelectorAll('#appointmentsTableBody tr');
        rows.forEach(row => {
            const dateCell = row.querySelector('td:nth-child(3)'); // Changed to column 3 which contains the date
            if (!dateCell) return;
            
            // Get the date part only from the cell text (format: "M j, Y H:i")
            const cellText = dateCell.textContent.trim();
            const cellDate = cellText.split(' ').slice(0, 3).join(' '); // Get "M j, Y" part
            
            // Convert both dates to YYYY-MM-DD for comparison
            const cellDateObj = new Date(cellDate);
            const filterDateObj = new Date(dateStr);
            
            const cellDateStr = cellDateObj.toISOString().split('T')[0];
            const filterDateStr = filterDateObj.toISOString().split('T')[0];
            
            if (cellDateStr === filterDateStr) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    // Reinstated and modified function to load latest lab result shortcut for patient
    function loadPatientLabShortcut(patientId) {
        fetch('../processes/get_lab_tests.php?patient_id=' + encodeURIComponent(patientId))
            .then(response => response.json())
            .then(data => {
                const footer = document.getElementById('patientLabShortcut');
                footer.innerHTML = ''; // Clear existing content
                
                if (data.success && data.lab_tests && data.lab_tests.length > 0) {
                    // Add a heading for the lab tests section in the footer
                    const heading = document.createElement('h6');
                    heading.className = 'mb-2';
                    heading.innerHTML = '<i class="fas fa-flask me-2"></i>Recent Lab Tests for this Patient</h6>';
                    footer.appendChild(heading);

                    const labTestList = document.createElement('ul');
                    labTestList.className = 'list-group list-group-flush'; // Use Bootstrap list group for styling
                    labTestList.style.maxHeight = '200px';
                    labTestList.style.overflowY = 'auto';

                    // Sort tests by date (newest first)
                    const sortedTests = data.lab_tests.sort((a, b) => 
                        new Date(b.requested_date || 0) - new Date(a.requested_date || 0)
                    );
                    
                    sortedTests.forEach(test => {
                        const listItem = document.createElement('li');
                        listItem.className = 'list-group-item d-flex justify-content-between align-items-center';
                        listItem.style.padding = '0.5rem 0';
                        listItem.style.borderBottom = '1px solid #e9ecef';

                        const testInfo = document.createElement('span');
                        testInfo.innerHTML = `
                            ${test.test_type} - ${test.requested_date ? new Date(test.requested_date).toLocaleDateString() : 'N/A'}
                            <span class="badge bg-${test.status === 'completed' ? 'success' : (test.status === 'processing' ? 'info' : 'warning')} ms-2">${test.status || 'pending'}</span>
                        `;
                        listItem.appendChild(testInfo);

                        const actions = document.createElement('span');
                        // Link to view results (opens in new tab/window)
                        const viewLink = document.createElement('a');
                        viewLink.href = `../processes/view_lab_results_page.php?test_id=${test.id}`; // Assuming a view page exists or create one
                        viewLink.target = '_blank'; // Open in new tab
                        viewLink.className = 'btn btn-sm btn-info me-2';
                        actions.appendChild(viewLink);

                        // PDF button if available
                        if (test.result_pdf) {
                            const pdfLink = document.createElement('a');
                            pdfLink.href = `../${test.result_pdf}`; // Link to the PDF file
                            pdfLink.target = '_blank'; // Open in new tab
                            pdfLink.className = 'btn btn-sm btn-primary';
                            pdfLink.innerHTML = '<i class="fas fa-file-pdf me-1"></i>PDF';
                            actions.appendChild(pdfLink);
                        }

                        listItem.appendChild(actions);
                        labTestList.appendChild(listItem);
                    });
                    
                    footer.appendChild(labTestList);

                } else {
                    footer.innerHTML = '<span class="text-muted">No lab tests found for this patient.</span>';
                }
            })
            .catch(error => {
                console.error('Error loading lab tests:', error);
                document.getElementById('patientLabShortcut').innerHTML = 
                    '<span class="text-danger"><i class="fas fa-exclamation-triangle me-2"></i>Error loading lab test list.</span>';
            });
    }

    // Reinstating the original loadPatientHistory function (assuming it fetches and displays history in #patientHistoryContent)
    function loadPatientHistory(patientId) {
        var xhr = new XMLHttpRequest();
        xhr.open('GET', '../processes/get_patient_history.php?patient_id=' + patientId, true);
        xhr.onload = function() {
            if (xhr.status >= 200 && xhr.status < 300) {
                document.getElementById('patientHistoryContent').innerHTML = xhr.responseText;
            } else {
                document.getElementById('patientHistoryContent').innerHTML = '<p>Failed to load patient history.</p>';
            }
        };
        xhr.onerror = function() {
            document.getElementById('patientHistoryContent').innerHTML = '<p>Network error occurred.</p>';
        };
        xhr.send();
    }

    // Keep the existing fetchLabResults function for compatibility with view-results-btn if needed elsewhere
    // Modify it to open the modal and load content if it's still used for single test views.
    function fetchLabResults(testId) {
         if (!testId) {
             console.error('Test ID is missing');
             return;
         }
         // You might want to add logic here to fetch and display single test results 
         // in the labResultsModal if view-results-btn is used outside the patient history context.
         console.log("fetchLabResults called for test ID: " + testId + ". Implement single test result display if needed.");
         // For now, just opening the modal without content loading:
         var resultsModal = new bootstrap.Modal(document.getElementById('labResultsModal'));
         resultsModal.show();
          // Assuming get_lab_results.php returns HTML, you could load it here:
         /*
         fetch(`../processes/get_lab_results.php?test_id=${encodeURIComponent(testId)}`)
             .then(response => response.text())
             .then(html => {
                 document.getElementById('labResultsContent').innerHTML = html;
                 document.querySelector('#labResultsModal .modal-title').textContent = 'Lab Test Results for Test ' + testId;
             })
             .catch(error => {
                 console.error('Error loading single lab result:', error);
                 document.getElementById('labResultsContent').innerHTML = '<div class="alert alert-danger">Failed to load results.</div>';
             });
         */
    }
</script>

<?php include_once '../includes/js_links.php'; ?>

</body>
</html>

