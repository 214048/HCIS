<?php
require_once '../includes/session_manager.php';
require_once '../includes/db_connection.php';

// Check if user is logged in and is an admin
checkSession('admin');

// Get all users
$stmt = $conn->prepare("
    SELECT id, name, email, role, specialization, phone, address, gender, 
           date_of_birth, blood_group, emergency_contact, emergency_phone, allergies, created_at 
    FROM users 
    WHERE role != 'admin'
    ORDER BY role, name
");
$stmt->execute();
$users = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get user counts by role
$stmt = $conn->prepare("
    SELECT role, COUNT(*) as count 
    FROM users 
    GROUP BY role
");
$stmt->execute();
$role_counts = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Format role counts into a more usable structure
$role_stats = [
    'doctor' => 0,
    'patient' => 0,
    'pharmacist' => 0,
    'lab' => 0
];
foreach ($role_counts as $rc) {
    if (isset($role_stats[$rc['role']])) {
        $role_stats[$rc['role']] = $rc['count'];
    }
}

// Get all patients (pending and approved)
$patients_stmt = $conn->prepare("SELECT * FROM users WHERE role = 'patient' ORDER BY FIELD(status, 'pending', 'approved', 'declined'), name");
$patients_stmt->execute();
$patients = $patients_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get all hospital workers (not patients)
$workers_stmt = $conn->prepare("SELECT * FROM users WHERE role IN ('doctor','pharmacist','lab') ORDER BY role, name");
$workers_stmt->execute();
$workers = $workers_stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// --- Pagination logic for patients ---
$patientsPerPage = 5;
$patientPage = isset($_GET['patientPage']) ? max(1, intval($_GET['patientPage'])) : 1;
$totalPatients = count($patients);
$totalPatientPages = ceil($totalPatients / $patientsPerPage);
$patientsToShow = array_slice($patients, ($patientPage-1)*$patientsPerPage, $patientsPerPage);

// --- Pagination logic for workers ---
$workersPerPage = 5;
$workerPage = isset($_GET['workerPage']) ? max(1, intval($_GET['workerPage'])) : 1;
$totalWorkers = count($workers);
$totalWorkerPages = ceil($totalWorkers / $workersPerPage);
$workersToShow = array_slice($workers, ($workerPage-1)*$workersPerPage, $workersPerPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - E-Health</title>
    <?php include_once '../includes/head_elements.php'; ?>
    <?php include_once '../includes/css_links.php'; ?>
    <style>
body {
    min-height: 100vh;
    background: #F0F9FF;
    font-family: 'Segoe UI', Arial, sans-serif;
    margin: 0;
    color: #0F172A;
}
.admin-dashboard {
    margin-top: 2rem;
}
h2, h3, h4, h5, h6 {
    color: #1E3A8A;
    font-weight: 800;
    letter-spacing: 0.5px;
}
.stats-row {
    display: flex;
    gap: 2rem;
    margin-bottom: 2rem;
    flex-wrap: wrap;
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
.table {
    width: 100%;
    border-collapse: separate;
    border-spacing: 0;
    overflow: hidden;
}
.table th, .table td {
    width: 20%;
    min-width: 120px;
    max-width: 1px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.table th:nth-child(1), .table td:nth-child(1) { width: 22%; }
.table th:nth-child(2), .table td:nth-child(2) { width: 28%; }
.table th:nth-child(3), .table td:nth-child(3) { width: 15%; }
.table th:nth-child(4), .table td:nth-child(4) { width: 20%; }
.table th:nth-child(5), .table td:nth-child(5) { width: 15%; }
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
/* Buttons */
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
/* Nav links */
.nav-link {
    color: #1E3A8A;
    font-weight: 600;
    transition: color 0.2s, text-decoration 0.2s;
}
.nav-link:hover, .nav-link:focus {
    color: #1D4ED8;
    text-decoration: underline;
}
/* Sidebar */
.sidebar {
    background: #1E293B;
    color: #CBD5E1;
}
.sidebar .nav-link {
    color: #CBD5E1;
}
.sidebar .nav-link.active, .sidebar .nav-link:hover {
    color: #60A5FA;
    background: #334155;
}
.sidebar .icon {
    color: #CBD5E1;
}
.sidebar .nav-link.active .icon, .sidebar .nav-link:hover .icon {
    color: #60A5FA;
}
/* Card hover */
.card:hover, .stats-card:hover {
    box-shadow: 0 8px 24px rgba(30,58,138,0.10);
    border-color: #60A5FA;
}
/* Alerts */
.alert-info {
    background: #E0F2FE;
    color: #0284C7;
    border: none;
}
.alert-success {
    background: #D1FAE5;
    color: #10B981;
    border: none;
}
.alert-warning {
    background: #FEF9C3;
    color: #CA8A04;
    border: none;
}
.alert-danger {
    background: #FEE2E2;
    color: #DC2626;
    border: none;
}
/* Misc */
.section-title {
    color: #1E3A8A;
    font-size: 2.2rem;
    font-weight: 800;
    margin-bottom: 1.5rem;
}
.section-description {
    color: #64748B;
    font-size: 1.1rem;
    margin-bottom: 2rem;
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
.role-doctor, .role-patient, .role-pharmacist, .role-lab {
    background: none !important;
    color: #000 !important;
}
    </style>
    <style>
/* Admin Dashboard Header Styles */
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
    <?php include_once '../includes/header.php'; ?>

    <div class="container-fluid p-4 admin-dashboard">
        <div id="alertContainer" class="mb-3"></div>
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="mb-0">Admin Dashboard</h2>
            <div>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="fas fa-user-plus me-2"></i>Add New User
                </button>
            </div>
        </div>

        <!-- Horizontal Info Cards -->
        <div class="row g-3">
            <!-- Doctors Card -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-user-md fa-2x me-2"></i>
                            <h5 class="mb-0">Doctors</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-success me-2"><i class="fas fa-check-circle"></i></span>
                            <span>Active: <?php echo $role_stats['doctor']; ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-warning me-2"><i class="fas fa-clock"></i></span>
                            <span>Pending: 0</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-danger me-2"><i class="fas fa-times-circle"></i></span>
                            <span>Inactive: 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patients Card -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-header bg-info text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-users fa-2x me-2"></i>
                            <h5 class="mb-0">Patients</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-success me-2"><i class="fas fa-check-circle"></i></span>
                            <span>Approved: <?php echo $role_stats['patient']; ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-warning me-2"><i class="fas fa-clock"></i></span>
                            <span>Pending: 0</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-danger me-2"><i class="fas fa-times-circle"></i></span>
                            <span>Declined: 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pharmacists Card -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-header bg-success text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-pills fa-2x me-2"></i>
                            <h5 class="mb-0">Pharmacists</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-success me-2"><i class="fas fa-check-circle"></i></span>
                            <span>Active: <?php echo $role_stats['pharmacist']; ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-warning me-2"><i class="fas fa-clock"></i></span>
                            <span>Pending: 0</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-danger me-2"><i class="fas fa-times-circle"></i></span>
                            <span>Inactive: 0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Lab Staff Card -->
            <div class="col-md-3">
                <div class="card h-100">
                    <div class="card-header bg-warning text-white">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-flask fa-2x me-2"></i>
                            <h5 class="mb-0">Lab Staff</h5>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-success me-2"><i class="fas fa-check-circle"></i></span>
                            <span>Active: <?php echo $role_stats['lab']; ?></span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-warning me-2"><i class="fas fa-clock"></i></span>
                            <span>Pending: 0</span>
                        </div>
                        <div class="d-flex align-items-center mb-3">
                            <span class="badge bg-danger me-2"><i class="fas fa-times-circle"></i></span>
                            <span>Inactive: 0</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-3">
                <div class="stats-card role-doctor-glow" data-role="doctor">
                    <div class="stats-icon">
                        <i class="fas fa-user-md"></i>
                    </div>
                    <div class="stats-number"><?php echo $role_stats['doctor']; ?></div>
                    <div class="stats-label">Doctors</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card role-patient-glow" data-role="patient">
                    <div class="stats-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stats-number"><?php echo $role_stats['patient']; ?></div>
                    <div class="stats-label">Patients</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card role-pharmacist-glow" data-role="pharmacist">
                    <div class="stats-icon">
                        <i class="fas fa-pills"></i>
                    </div>
                    <div class="stats-number"><?php echo $role_stats['pharmacist']; ?></div>
                    <div class="stats-label">Pharmacists</div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="stats-card role-lab-glow" data-role="lab">
                    <div class="stats-icon">
                        <i class="fas fa-flask"></i>
                    </div>
                    <div class="stats-number"><?php echo $role_stats['lab']; ?></div>
                    <div class="stats-label">Lab Staff</div>
                </div>
            </div>
        </div>

        <!-- Add refresh buttons to each dynamic section -->
      

        <!-- Patients Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Patients (Pending & Approved)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover patient-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Status</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($patientsToShow as $patient): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($patient['name']); ?></td>
                                <td><?php echo htmlspecialchars($patient['email']); ?></td>
                                <td>
                                    <span class="badge <?php echo $patient['status'] === 'pending' ? 'bg-warning' : ($patient['status'] === 'approved' ? 'bg-success' : 'bg-danger'); ?>">
                                        <?php echo ucfirst($patient['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($patient['phone']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-link text-info p-0 me-2" onclick="viewUserDetails(<?php echo $patient['id']; ?>)" data-bs-toggle="tooltip" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-link text-primary p-0 me-2" onclick="editUser(<?php echo $patient['id']; ?>)" data-bs-toggle="tooltip" title="Edit User"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-link text-danger p-0" onclick="deleteUser(<?php echo $patient['id']; ?>)" data-bs-toggle="tooltip" title="Delete User"><i class="fas fa-trash"></i></button>
                                    <?php if ($patient['status'] === 'pending'): ?>
                                        <button class="btn btn-success btn-sm ms-2" onclick="approvePatient(<?php echo $patient['id']; ?>)">Approve</button>
                                        <button class="btn btn-danger btn-sm ms-1" onclick="declinePatient(<?php echo $patient['id']; ?>)">Decline</button>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Pagination for patients -->
                    <nav>
                        <ul class="pagination justify-content-end">
                            <li class="page-item<?php if($patientPage <= 1) echo ' disabled'; ?>">
                                <a class="page-link" href="?patientPage=<?php echo $patientPage-1; ?>">Previous</a>
                            </li>
                            <?php for($i = 1; $i <= $totalPatientPages; $i++): ?>
                                <li class="page-item<?php if($i == $patientPage) echo ' active'; ?>">
                                    <a class="page-link" href="?patientPage=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item<?php if($patientPage >= $totalPatientPages) echo ' disabled'; ?>">
                                <a class="page-link" href="?patientPage=<?php echo $patientPage+1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>

        <!-- Hospital Workers Section -->
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Hospital Workers</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover user-table">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Phone</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($workersToShow as $user): ?>
                            <tr id="userRow_<?php echo $user['id']; ?>">
                                <td><?php echo htmlspecialchars($user['name']); ?></td>
                                <td><?php echo htmlspecialchars($user['email']); ?></td>
                                <td>
                                    <span class="badge role-<?php echo $user['role']; ?>">
                                        <?php echo ucfirst($user['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($user['phone']); ?></td>
                                <td class="action-buttons">
                                    <button class="btn btn-link text-info p-0 me-2" onclick="viewUserDetails(<?php echo $user['id']; ?>)" data-bs-toggle="tooltip" title="View Details"><i class="fas fa-eye"></i></button>
                                    <button class="btn btn-link text-primary p-0 me-2" onclick="editUser(<?php echo $user['id']; ?>)" data-bs-toggle="tooltip" title="Edit User"><i class="fas fa-edit"></i></button>
                                    <button class="btn btn-link text-danger p-0 btn-delete-user-<?php echo $user['id']; ?>" onclick="deleteUser(<?php echo $user['id']; ?>)" data-bs-toggle="tooltip" title="Delete User"><i class="fas fa-trash"></i></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    <!-- Pagination for workers -->
                    <nav>
                        <ul class="pagination justify-content-end">
                            <li class="page-item<?php if($workerPage <= 1) echo ' disabled'; ?>">
                                <a class="page-link" href="?workerPage=<?php echo $workerPage-1; ?>">Previous</a>
                            </li>
                            <?php for($i = 1; $i <= $totalWorkerPages; $i++): ?>
                                <li class="page-item<?php if($i == $workerPage) echo ' active'; ?>">
                                    <a class="page-link" href="?workerPage=<?php echo $i; ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                            <li class="page-item<?php if($workerPage >= $totalWorkerPages) echo ' disabled'; ?>">
                                <a class="page-link" href="?workerPage=<?php echo $workerPage+1; ?>">Next</a>
                            </li>
                        </ul>
                    </nav>
                </div>
            </div>
        </div>
    </div>

    <!-- Add User Modal -->
    <div class="modal fade" id="addUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-plus me-2"></i>Add New User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="addUserForm" onsubmit="event.preventDefault(); addUser();">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="user_email" required
                                           onchange="this.value = this.value.toLowerCase()" 
                                           placeholder="Enter email (lowercase)">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    <select class="form-select" name="role" id="add_role" required>
                                        <option value="">Select Role</option>
                                        <option value="doctor">Doctor</option>
                                        <option value="pharmacist">Pharmacist</option>
                                        <option value="lab">Laboratory Staff</option>
                                    </select>
                                </div>
                                <div id="specializationField" style="display:none; margin-top: 1rem;">
                                    <label class="form-label">Specialization</label>
                                    <select class="form-select" name="specialization" id="specialization">
                                        <!-- Options will be populated by JS -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone Number</label>
                                <div class="input-group">
                                    <span class="input-group-text">🇪🇬 +20</span>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           maxlength="10" 
                                           placeholder="10-digit number" required>
                                </div>
                                <small class="text-muted">Enter 10 digits without the country code</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    <select class="form-select" name="gender" required>
                                        <option value="">Select Gender</option>
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea class="form-control" name="address" rows="2" required></textarea>
                                </div>
                            </div>
                        </div>
                        <div id="doctorScheduleFields" style="display:none;">
                            <h5 class="mt-3 mb-3">Doctor Availability Schedule</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Please select the days and hours when the doctor will be available for appointments.
                                    </div>
                                </div>
                            </div>
                            <div id="scheduleContainer">
                                <div class="schedule-item row mb-3">
                                    <div class="col-md-4">
                                        <label class="form-label">Day</label>
                                        <select class="form-select" name="schedule_day[]">
                                            <option value="Monday">Monday</option>
                                            <option value="Tuesday">Tuesday</option>
                                            <option value="Wednesday">Wednesday</option>
                                            <option value="Thursday">Thursday</option>
                                            <option value="Friday">Friday</option>
                                            <option value="Saturday">Saturday</option>
                                            <option value="Sunday">Sunday</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">Start Time</label>
                                        <input type="time" class="form-control" name="schedule_start[]">
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label">End Time</label>
                                        <input type="time" class="form-control" name="schedule_end[]">
                                    </div>
                                    <div class="col-md-2 d-flex align-items-end">
                                        <button type="button" class="btn btn-outline-danger btn-remove-schedule mb-2 d-none">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-outline-primary btn-add-schedule">
                                        <i class="fas fa-plus me-2"></i>Add Another Day
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Close
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-reset-form">
                        <i class="fas fa-redo me-2"></i>Reset Form
                    </button>
                    <button type="button" class="btn btn-primary" onclick="addUser()">
                        <i class="fas fa-save me-2"></i>Add User
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="modal fade" id="editUserModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-user-edit me-2"></i>Edit User
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editUserForm" onsubmit="event.preventDefault(); updateUser();">
                        <input type="hidden" name="id" id="edit_user_id">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Name</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                    <input type="text" class="form-control" name="name" id="edit_name" required>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Email</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                    <input type="email" class="form-control" name="email" id="edit_email" required
                                           onchange="this.value = this.value.toLowerCase()" 
                                           placeholder="Enter email (lowercase)">
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Password (leave blank to keep current)</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                    <input type="password" class="form-control" name="password" id="edit_password">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Phone</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-phone"></i></span>
                                    <input type="tel" class="form-control" name="phone" id="edit_phone" required>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Date of Birth</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-calendar"></i></span>
                                    <input type="date" class="form-control" name="date_of_birth" id="edit_dob">
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Role</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-user-tag"></i></span>
                                    <select class="form-select" name="role" id="edit_role" required>
                                        <option value="admin">Admin</option>
                                        <option value="doctor">Doctor</option>
                                        <option value="pharmacist">Pharmacist</option>
                                        <option value="lab">Lab Staff</option>
                                        <option value="patient">Patient</option>
                                    </select>
                                </div>
                                <div id="editSpecializationField" style="display:none; margin-top: 1rem;">
                                    <label class="form-label">Specialization</label>
                                    <select class="form-select" name="specialization" id="edit_specialization">
                                        <!-- Options will be populated by JS -->
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Gender</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-venus-mars"></i></span>
                                    <select class="form-select" name="gender" id="edit_gender">
                                        <option value="male">Male</option>
                                        <option value="female">Female</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-12 mb-3">
                                <label class="form-label">Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                                    <textarea class="form-control" name="address" id="edit_address" rows="2"></textarea>
                                </div>
                            </div>
                        </div>
                        <div id="editDoctorScheduleFields" style="display:none;">
                            <h5 class="mt-3 mb-3">Doctor Availability Schedule</h5>
                            <div class="row">
                                <div class="col-md-12 mb-3">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Please select the days and hours when the doctor will be available for appointments.
                                    </div>
                                </div>
                            </div>
                            <div id="editScheduleContainer">
                                <!-- Schedule items will be loaded dynamically -->
                            </div>
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <button type="button" class="btn btn-outline-primary btn-add-edit-schedule">
                                        <i class="fas fa-plus me-2"></i>Add Another Day
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="alert alert-danger" id="editErrorAlert" style="display: none;"></div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="fas fa-times me-2"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-primary" onclick="updateUser()">
                        <i class="fas fa-save me-2"></i>Save Changes
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">User Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="userDetails">
                    <!-- Content will be loaded dynamically -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/js_links.php'; ?>
    <script>
        // Global functions
        function showAlert(message, type = 'success') {
            $('#alertContainer').empty();
            const alertDiv = $(`
                <div class="alert alert-${type} alert-dismissible fade show mt-2" role="alert">
                    ${message}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            `);
            $('#alertContainer').append(alertDiv);
            setTimeout(() => alertDiv.alert('close'), 5000);
        }

        // Specialization arrays
        const doctorSpecializations = [
            "اسنان", "جراحة", "باطنة", "انف و اذن و حنجرة", "عيون", "مسالك بولية", 
            "نساء و ولادة", "جلدية", "عظام", "طب نفسي", "طب أطفال", "طب طوارئ"
        ];
        const labSpecializations = [
            "تحاليل", "اشعة"
        ];

        function populateSpecialization(role, selectId) {
            const select = document.getElementById(selectId);
            if (!select) return;
            
            select.innerHTML = '';
            let options = [];
            if (role === 'doctor') {
                options = doctorSpecializations;
            } else if (role === 'lab') {
                options = labSpecializations;
            }
            options.forEach(spec => {
                const opt = document.createElement('option');
                opt.value = spec;
                opt.textContent = spec;
                select.appendChild(opt);
            });
        }

        function toggleSpecialization(roleSelect, specializationField, selectId) {
            if (!roleSelect || !specializationField) return;

            if (roleSelect.value === 'doctor' || roleSelect.value === 'lab') {
                $(specializationField).slideDown();
                populateSpecialization(roleSelect.value, selectId);
                if (roleSelect.value === 'doctor') {
                    $('#doctorScheduleFields').slideDown();
                    $('#editDoctorScheduleFields').slideDown();
                } else {
                    $('#doctorScheduleFields').slideUp();
                    $('#editDoctorScheduleFields').slideUp();
                }
            } else {
                $(specializationField).slideUp();
                $('#doctorScheduleFields').slideUp();
                $('#editDoctorScheduleFields').slideUp();
            }
        }

        function addEditScheduleItem(day = '', startTime = '', endTime = '') {
            const scheduleItem = `
                <div class="schedule-item row mb-3">
                    <div class="col-md-4">
                        <label class="form-label">Day</label>
                        <select class="form-select" name="edit_schedule_day[]">
                            <option value="Monday" ${day === 'Monday' ? 'selected' : ''}>Monday</option>
                            <option value="Tuesday" ${day === 'Tuesday' ? 'selected' : ''}>Tuesday</option>
                            <option value="Wednesday" ${day === 'Wednesday' ? 'selected' : ''}>Wednesday</option>
                            <option value="Thursday" ${day === 'Thursday' ? 'selected' : ''}>Thursday</option>
                            <option value="Friday" ${day === 'Friday' ? 'selected' : ''}>Friday</option>
                            <option value="Saturday" ${day === 'Saturday' ? 'selected' : ''}>Saturday</option>
                            <option value="Sunday" ${day === 'Sunday' ? 'selected' : ''}>Sunday</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Start Time</label>
                        <input type="time" class="form-control" name="edit_schedule_start[]" value="${startTime}">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">End Time</label>
                        <input type="time" class="form-control" name="edit_schedule_end[]" value="${endTime}">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-danger btn-remove-schedule mb-2">
                            <i class="fas fa-trash"></i>
                        </button>
                    </div>
                </div>
            `;
            $('#editScheduleContainer').append(scheduleItem);
        }

        function viewUserDetails(userId) {
            $.get('../processes/get_user.php', { user_id: userId }, function(response) {
                    if (response.success) {
                    const user = response.data;
                    let html = '<div class="table-responsive">' +
                        '<table class="table table-bordered">' +
                            '<tr>' +
                                '<th width="30%">Name</th>' +
                                '<td>' + user.name + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Email</th>' +
                                '<td>' + user.email + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Role</th>' +
                                '<td><span class="badge role-' + user.role + '">' + user.role.charAt(0).toUpperCase() + user.role.slice(1) + '</span></td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Phone</th>' +
                                '<td>' + user.phone + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Date of Birth</th>' +
                                '<td>' + (user.date_of_birth || 'Not specified') + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Gender</th>' +
                                '<td>' + (user.gender || 'Not specified') + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Blood Group</th>' +
                                '<td>' + (user.blood_group || 'Not specified') + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Address</th>' +
                                '<td>' + (user.address || 'Not specified') + '</td>' +
                            '</tr>' +
                            (user.role === 'doctor' ? 
                                '<tr>' +
                                    '<th>Specialization</th>' +
                                    '<td>' + (user.specialization || 'Not specified') + '</td>' +
                                '</tr>'
                            : '') +
                            '<tr>' +
                                '<th>Emergency Contact</th>' +
                                '<td>' + (user.emergency_contact || 'Not specified') + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Emergency Phone</th>' +
                                '<td>' + (user.emergency_phone || 'Not specified') + '</td>' +
                            '</tr>' +
                            '<tr>' +
                                '<th>Allergies</th>' +
                                '<td>' + (user.allergies || 'None') + '</td>' +
                            '</tr>' +
                        '</table>' +
                    '</div>';
                    $('#userDetails').html(html);
                    $('#viewDetailsModal').modal('show');
                    } else {
                    showAlert(response.message, 'danger');
                }
            }).fail(function(xhr) {
                const response = xhr.responseJSON || {};
                showAlert(response.message || 'An error occurred while fetching user details.', 'danger');
            });
        }

        function editUser(userId) {
            $.get('../processes/get_user.php', { user_id: userId }, function(response) {
                if (response.success) {
                    const user = response.data;
                    $('#edit_user_id').val(user.id);
                    $('#edit_name').val(user.name);
                    $('#edit_email').val(user.email);
                    $('#edit_phone').val(user.phone);
                    $('#edit_dob').val(user.date_of_birth);
                    $('#edit_role').val(user.role);
                    $('#edit_specialization').val(user.specialization || '');
                    $('#edit_gender').val(user.gender);
                    $('#edit_address').val(user.address);
                    
                    // Show/hide specialization field based on role
                    toggleSpecialization(
                        document.getElementById('edit_role'),
                        document.getElementById('editSpecializationField'),
                        'edit_specialization'
                    );
                    
                    // Load doctor schedule if role is doctor
                    if (user.role === 'doctor') {
                        // Clear previous items
                        $('#editScheduleContainer').empty();
                        
                        // Load schedule data
                        $.get('../processes/get_doctor_schedule.php', { doctor_id: userId }, function(scheduleResponse) {
                            if (scheduleResponse.success && scheduleResponse.data.length > 0) {
                                // Add each schedule item
                                scheduleResponse.data.forEach(schedule => {
                                    addEditScheduleItem(
                                        schedule.day_of_week,
                                        schedule.start_time,
                                        schedule.end_time
                                    );
                                });
                            } else {
                                // Add at least one empty schedule item
                                addEditScheduleItem();
                            }
                        }).fail(function() {
                            // If request fails, add an empty schedule item
                            addEditScheduleItem();
                        });
                    }
                    
                    $('#editUserModal').modal('show');
                } else {
                    showAlert(response.message, 'danger');
                }
            }).fail(function(xhr) {
                const response = xhr.responseJSON || {};
                showAlert(response.message || 'An error occurred while fetching user details.', 'danger');
            });
        }

        function deleteUser(userId) {
            if (!userId) {
                showAlert('User ID is required', 'danger');
                return;
            }

            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                // Show loading state
                const deleteBtn = $(`#userRow_${userId} .btn-delete-user-${userId}`);
                const originalHtml = deleteBtn.html();
                deleteBtn.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);

                $.ajax({
                    url: '../processes/manage_user.php',
                    type: 'POST',
                    contentType: 'application/json',
                    dataType: 'json',
                    processData: false,
                    data: JSON.stringify({
                        action: 'delete',
                        id: userId
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert(response.message || 'User deleted successfully', 'success');
                            $(`#userRow_${userId}`).fadeOut(400, function() {
                                $(this).remove();
                            });
                        } else {
                            showAlert(response.message || 'Failed to delete user', 'danger');
                            deleteBtn.html(originalHtml).prop('disabled', false);
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred while deleting the user.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                        showAlert(errorMsg, 'danger');
                        deleteBtn.html(originalHtml).prop('disabled', false);
                    }
                });
            }
        }

        function addUser() {
            const form = document.getElementById('addUserForm');
            if (!form) {
                showAlert('Form not found', 'danger');
                return;
            }

            const formData = new FormData(form);
            const data = {
                action: 'add',
                name: formData.get('name'),
                email: formData.get('email'),
                password: formData.get('password'),
                role: formData.get('role'),
                phone: '+20' + formData.get('phone'),
                gender: formData.get('gender'),
                address: formData.get('address'),
                specialization: formData.get('specialization') || null
            };

            // Show loading state
            const submitBtn = document.querySelector('#addUserModal .btn-primary');
            if (!submitBtn) {
                showAlert('Submit button not found', 'danger');
                return;
            }

            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Adding User...';

            // Submit form data
            fetch('../processes/manage_user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Show success message
                    showAlert(data.message || 'User added successfully!', 'success');
                    
                    // Close modal and reset form
                    const modal = bootstrap.Modal.getInstance(document.getElementById('addUserModal'));
                    if (modal) {
                        modal.hide();
                    }
                    form.reset();
                    
                    // Reload the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    // Show error message
                    showAlert(data.message || 'Failed to add user', 'danger');
                    
                    // Reset button state
                    submitBtn.disabled = false;
                    submitBtn.innerHTML = originalBtnText;
                }
            })
            .catch(error => {
                // Show error message
                showAlert('An error occurred. Please try again.', 'danger');
                
                // Reset button state
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
            });
        }

        function approvePatient(id) {
            if (confirm('Approve this patient?')) {
            $.ajax({
                url: '../processes/manage_user.php',
                type: 'POST',
                contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'approve_patient',
                        id: id
                    }),
                success: function(response) {
                    if (response.success) {
                            showAlert('Patient approved!', 'success');
                            setTimeout(function() { window.location.reload(); }, 1000);
                    } else {
                            showAlert(response.message || 'Failed to approve patient', 'danger');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred while approving the patient.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.message) {
                            errorMsg = response.message;
                        }
                    } catch (e) {
                            console.error('Error parsing response:', e);
                    }
                    showAlert(errorMsg, 'danger');
                    }
                });
            }
        }

        function declinePatient(id) {
            if (confirm('Decline this patient?')) {
                $.ajax({
                    url: '../processes/manage_user.php',
                    type: 'POST',
                    contentType: 'application/json',
                    data: JSON.stringify({
                        action: 'decline_patient',
                        id: id
                    }),
                    success: function(response) {
                        if (response.success) {
                            showAlert('Patient declined.', 'success');
                            setTimeout(function() { window.location.reload(); }, 1000);
                        } else {
                            showAlert(response.message || 'Failed to decline patient', 'danger');
                        }
                    },
                    error: function(xhr) {
                        let errorMsg = 'An error occurred while declining the patient.';
                        try {
                            const response = JSON.parse(xhr.responseText);
                            if (response && response.message) {
                                errorMsg = response.message;
                            }
                        } catch (e) {
                            console.error('Error parsing response:', e);
                        }
                        showAlert(errorMsg, 'danger');
                    }
                });
            }
        }

        function validatePhone(input) {
            if (!input) return;
            // Remove any non-numeric characters
            input.value = input.value.replace(/\D/g, '');
            // Limit to 10 digits
            if (input.value.length > 10) {
                input.value = input.value.slice(0, 10);
            }
        }

        function updateUser() {
            const form = document.getElementById('editUserForm');
            if (!form.checkValidity()) {
                form.reportValidity();
                return;
            }

            const formData = new FormData(form);
            const data = {
                action: 'update'
            };
            
            // Convert FormData to object
            for (let [key, value] of formData.entries()) {
                data[key] = value;
            }

            // Extract schedule data for doctors
            if (data.role === 'doctor') {
                const scheduleData = [];
                const days = formData.getAll('edit_schedule_day[]');
                const startTimes = formData.getAll('edit_schedule_start[]');
                const endTimes = formData.getAll('edit_schedule_end[]');
                
                for (let i = 0; i < days.length; i++) {
                    // Only add schedule if all fields are filled
                    if (days[i] && startTimes[i] && endTimes[i]) {
                        scheduleData.push({
                            day: days[i],
                            start_time: startTimes[i],
                            end_time: endTimes[i]
                        });
                    }
                }
                
                // Check if we have any valid schedule data
                if (scheduleData.length > 0) {
                    data.schedule = scheduleData;
                }
            }

            // Show loading state
            const submitBtn = $('#editUserModal .btn-primary');
            submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Saving...').prop('disabled', true);

            $.ajax({
                url: '../processes/manage_user.php',
                type: 'POST',
                contentType: 'application/json',
                data: JSON.stringify(data),
                success: function(response) {
                if (response.success) {
                        $('#editUserModal').modal('hide');
                        showAlert(response.message || 'User updated successfully!', 'success');
                        setTimeout(function() { window.location.reload(); }, 1500);
                } else {
                        showAlert(response.message || 'Failed to update user', 'danger');
                    }
                },
                error: function(xhr) {
                    let errorMsg = 'An error occurred while updating the user.';
                    try {
                        const response = JSON.parse(xhr.responseText);
                        if (response && response.message) {
                            errorMsg = response.message;
                        }
                    } catch (e) {
                        console.error('Error parsing response:', e);
                    }
                    showAlert(errorMsg, 'danger');
                },
                complete: function() {
                    submitBtn.html('<i class="fas fa-save me-2"></i>Save Changes').prop('disabled', false);
                }
            });
        }

        // Document ready function
        $(document).ready(function() {
            // Initialize tooltips
            $('[data-bs-toggle="tooltip"]').tooltip();

            // Initialize modals
            const editModal = new bootstrap.Modal(document.getElementById('editUserModal'));
            const viewModal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));

            // Phone number validation
            const phoneInput = document.getElementById('phone');
            const emergencyPhoneInput = document.getElementById('emergency_phone');

            if (phoneInput) {
                phoneInput.addEventListener('input', () => validatePhone(phoneInput));
            }
            if (emergencyPhoneInput) {
                emergencyPhoneInput.addEventListener('input', () => validatePhone(emergencyPhoneInput));
            }

            // Add country code on form submission
            const addUserForm = document.getElementById('addUserForm');
            if (addUserForm) {
                addUserForm.addEventListener('submit', function(e) {
                    if (phoneInput) {
                        phoneInput.value = '+20' + phoneInput.value;
                    }
                    if (emergencyPhoneInput) {
                        emergencyPhoneInput.value = '+20' + emergencyPhoneInput.value;
                    }
                });
            }

            // Rest of your document.ready code...
        });
    </script>
    
    
</body>
</html>
