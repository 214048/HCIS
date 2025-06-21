<?php
require_once '../includes/session_manager.php';
require_once '../includes/database.php';

// Check if user is logged in and is a patient
checkSession('patient');

$patient_id = $_SESSION['user_id'];

// Get upcoming appointments
$stmt = $conn->prepare("
    SELECT a.*, u.name as doctor_name, u.specialization 
    FROM appointments a 
    JOIN users u ON a.doctor_id = u.id 
    WHERE a.patient_id = ? AND a.status != 'cancelled' 
    AND a.appointment_date >= CURDATE()
    ORDER BY a.appointment_date ASC
    LIMIT 5
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$upcoming_appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get active prescriptions
$stmt = $conn->prepare("
    SELECT DISTINCT p.*, 
           u.name as doctor_name,
           GROUP_CONCAT(CONCAT(m.brand_name, ' (', pi.dosage, ' ', pi.frequency, ' ', pi.duration) SEPARATOR ', ') as medicines
    FROM prescriptions p 
    JOIN users u ON p.doctor_id = u.id 
    LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
    LEFT JOIN medicines m ON pi.medicine_id = m.id
    WHERE p.patient_id = ? AND p.status = 'pending'
    GROUP BY p.id
    ORDER BY p.prescribed_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$active_prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get recent lab tests
$stmt = $conn->prepare("
    SELECT l.*, u.name as doctor_name 
    FROM lab_tests l 
    JOIN users u ON l.doctor_id = u.id 
    WHERE l.patient_id = ? 
    ORDER BY l.requested_date DESC
    LIMIT 5
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$recent_lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get medical history
$stmt = $conn->prepare("
    SELECT h.*, u.name as doctor_name 
    FROM medical_history h 
    JOIN users u ON h.doctor_id = u.id 
    WHERE h.patient_id = ? AND h.status = 'active'
    ORDER BY h.diagnosis_date DESC
");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$medical_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Get patient information
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $patient_id);
$stmt->execute();
$patient = $stmt->get_result()->fetch_assoc();

// Ensure we got a valid patient
if (!$patient || $patient['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

// Calculate age from date_of_birth
$age = '';
if ($patient['date_of_birth']) {
    $dob = new DateTime($patient['date_of_birth']);
    $now = new DateTime();
    $age = $dob->diff($now)->y;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - E-Health</title>
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
.admin-dashboard, .doctor-dashboard, .patient-dashboard {
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
.table th:nth-child(2), .table td:nth-child(2) { width: 18%; }
.table th:nth-child(3), .table td:nth-child(3) { width: 14%; }
.table th:nth-child(4), .table td:nth-child(4) { width: 14%; }
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
    /* Admin Dashboard Header Styles (EXACT COPY for Patient Dashboard) */
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
            <span class="header-subtext ms-3 d-none d-md-inline" style="color: #64748B; font-size: 1.1rem; font-weight: 600;">Patient Panel</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="/hcisProject/profile.php" class="header-subtext d-none d-md-inline" style="text-decoration:none; color: #1E3A8A; font-weight: 600; font-size: 1.1rem;">
                <?php echo htmlspecialchars($patient['name'] ?? 'Patient'); ?>
            </a>
            <a class="btn btn-primary ms-2" href="/hcisProject/logout.php" title="Logout" style="background: #1E3A8A; color: #fff; border-radius: 25px; padding: 0.4rem 1.2rem; font-weight: 600; font-size: 1rem; border: none; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.18s, color 0.18s;">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>

    <div class="container py-4">
        <!-- Personal Information Card -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8">
                                <h4 class="card-title mb-4">Personal Information</h4>
                                <div class="row">
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-4">Name:</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($patient['name'] ?? 'N/A'); ?></dd>
                                            
                                            <dt class="col-sm-4">Age:</dt>
                                            <dd class="col-sm-8"><?php echo $age ? $age . ' years' : '-'; ?></dd>
                                            
                                            <dt class="col-sm-4">Gender:</dt>
                                            <dd class="col-sm-8"><?php echo ucfirst(htmlspecialchars($patient['gender'] ?? '-')); ?></dd>
                                            
                                            <dt class="col-sm-4">Blood Group:</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($patient['blood_group'] ?? '-'); ?></dd>
                                        </dl>
                                    </div>
                                    <div class="col-md-6">
                                        <dl class="row">
                                            <dt class="col-sm-4">Phone:</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($patient['phone'] ?? '-'); ?></dd>
                                            
                                            <dt class="col-sm-4">Email:</dt>
                                            <dd class="col-sm-8"><?php echo htmlspecialchars($patient['email'] ?? 'N/A'); ?></dd>
                                            
                                            <dt class="col-sm-4">Address:</dt>
                                            <dd class="col-sm-8"><?php echo nl2br(htmlspecialchars($patient['address'] ?? '-')); ?></dd>
                                        </dl>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="alert alert-info">
                                    <h6 class="alert-heading">Emergency Contact</h6>
                                    <p class="mb-0">
                                        <strong>Name:</strong> <?php echo htmlspecialchars($patient['emergency_contact'] ?? '-'); ?><br>
                                        <strong>Phone:</strong> <?php echo htmlspecialchars($patient['emergency_phone'] ?? '-'); ?>
                                    </p>
                                </div>
                                <?php if ($patient['allergies']): ?>
                                <div class="alert alert-warning">
                                    <h6 class="alert-heading">Allergies</h6>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($patient['allergies'])); ?></p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Medical Information Tabs -->
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <ul class="nav nav-tabs card-header-tabs" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#appointments">
                                    <i class="fas fa-calendar-check me-1"></i>Upcoming Appointments
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#prescriptions">
                                    <i class="fas fa-prescription me-1"></i>Active Prescriptions
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link" data-bs-toggle="tab" data-bs-target="#lab-tests">
                                    <i class="fas fa-flask me-1"></i>Lab Tests
                                </button>
                            </li>

                        </ul>
                    </div>
                    <div class="card-body">
                        <div class="tab-content">
                            <!-- Appointments Tab -->
                            <div class="tab-pane fade show active" id="appointments" role="tabpanel">
                                <div class="card mb-4">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h5 class="mb-0">Upcoming Appointments</h5>
                                    </div>
                                    <div class="card-body" id="appointmentsSection">
                                        <div class="d-flex justify-content-between align-items-center mb-3">
                                            <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#bookAppointmentModal">
                                                <i class="fas fa-calendar-plus me-1"></i>Book New Appointment
                                            </button>
                                        </div>
                                        <div class="table-responsive">
                                            <table class="table table-hover">
                                                <thead>
                                                    <tr>
                                                        <th>Date & Time</th>
                                                        <th>Doctor</th>
                                                        <th>Type</th>
                                                        <th>Status</th>
                                                        <th>Notes</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php if (empty($upcoming_appointments)): ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No upcoming appointments</td>
                                                    </tr>
                                                    <?php else: ?>
                                                    <?php foreach ($upcoming_appointments as $appointment): ?>
                                                    <tr>
                                                        <td><?php echo date('M d, Y h:i A', strtotime($appointment['appointment_date'])); ?></td>
                                                        <td>Dr. <?php echo htmlspecialchars($appointment['doctor_name']); ?><br>
                                                            <small class="text-muted"><?php echo htmlspecialchars($appointment['specialization']); ?></small>
                                                        </td>
                                                        <td><?php echo ucfirst($appointment['type']); ?></td>
                                                        <td>
                                                            <span class="badge bg-<?php 
                                                                echo $appointment['status'] === 'pending' ? 'warning' : 
                                                                    ($appointment['status'] === 'completed' ? 'success' : 'info'); 
                                                            ?>">
                                                                <?php echo ucfirst($appointment['status']); ?>
                                                            </span>
                                                        </td>
                                                        <td><?php echo nl2br(htmlspecialchars($appointment['notes'] ?? '')); ?></td>
                                                    </tr>
                                                    <?php endforeach; ?>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                               
                            </div>

                            <!-- Book Appointment Modal -->
                            <div class="modal fade" id="bookAppointmentModal" tabindex="-1">
                                <div class="modal-dialog modal-lg">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Book New Appointment</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                        </div>
                                        <div class="modal-body">
                                            <form id="appointmentForm" method="POST" action="process_appointment.php">
                                                <input type="hidden" name="patient_id" value="<?php echo $patient_id; ?>">
                                                
                                                <div class="row mb-3">
                                                    <label class="col-sm-3 col-form-label">Select Doctor</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-select" name="doctor_id" id="doctor_select" required>
                                                            <option value="">Select a doctor</option>
                                                            <?php
                                                            $stmt = $conn->prepare("SELECT id, name, specialization FROM users WHERE role = 'doctor' ORDER BY name");
                                                            $stmt->execute();
                                                            $doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
                                                            foreach ($doctors as $doctor):
                                                            ?>
                                                            <option value="<?php echo $doctor['id']; ?>">
                                                                <?php echo htmlspecialchars($doctor['name']); ?> - <?php echo htmlspecialchars($doctor['specialization']); ?>
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label class="col-sm-3 col-form-label">Available Dates</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-select" name="appointment_date" id="available_dates" required disabled>
                                                            <option value="">-- Select Doctor First --</option>
                                                        </select>
                                                        <div class="form-text text-muted mt-1" id="date_help">Only dates when the doctor is available are shown.</div>
                                                    </div>
                                                </div>
                                                
                                                <div class="row mb-3">
                                                    <label class="col-sm-3 col-form-label">Available Time Slots</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-select" name="slot_time" id="time_slot" required disabled>
                                                            <option value="">-- Select Date First --</option>
                                                        </select>
                                                        <div class="form-text text-muted mt-1">Each appointment is 30 minutes in duration.</div>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label class="col-sm-3 col-form-label">Type</label>
                                                    <div class="col-sm-9">
                                                        <select class="form-select" name="type" required>
                                                            <option value="consultation">Consultation</option>
                                                            <option value="follow-up">Follow-up</option>
                                                            <option value="emergency">Emergency</option>
                                                        </select>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label class="col-sm-3 col-form-label">Reason for Visit</label>
                                                    <div class="col-sm-9">
                                                        <textarea class="form-control" name="reason" rows="3" required></textarea>
                                                    </div>
                                                </div>

                                                <div class="row mb-3">
                                                    <label class="col-sm-3 col-form-label">Additional Notes</label>
                                                    <div class="col-sm-9">
                                                        <textarea class="form-control" name="notes" rows="3"></textarea>
                                                    </div>
                                                </div>
                                                
                                                <div id="appointment-error" class="alert alert-danger" style="display: none;"></div>
                                            </form>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                            <button type="submit" form="appointmentForm" class="btn btn-primary">Book Appointment</button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Prescriptions Tab -->
                            <div class="tab-pane" id="prescriptions" role="tabpanel">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Recent Prescriptions</h5>
                                    </div>
                                    <div class="card-body" id="prescriptionsSection">
                                        <div class="table-responsive mt-3">
                                            <table class="table table-striped">
                                                <thead>
                                                    <tr>
                                                        <th>Date</th>
                                                        <th>Doctor</th>
                                                        <th>Medicines</th>
                                                        <th>Notes</th>
                                                        <th>Status</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php
                                                    // Get patient's prescriptions
                                                    $prescription_query = $conn->prepare("
                                                        SELECT 
                                                            p.id, 
                                                            p.prescribed_date, 
                                                            p.notes, 
                                                            p.status, 
                                                            d.name as doctor_name,
                                                            GROUP_CONCAT(CONCAT(m.brand_name, ' (', pi.dosage, ', ', pi.frequency, ', ', pi.duration, ')') SEPARATOR '<br>') as medicines
                                                        FROM prescriptions p
                                                        LEFT JOIN users d ON p.doctor_id = d.id
                                                        LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
                                                        LEFT JOIN medicines m ON pi.medicine_id = m.id
                                                        WHERE p.patient_id = ?
                                                        GROUP BY p.id
                                                        ORDER BY p.prescribed_date DESC
                                                    ");
                                                    $prescription_query->bind_param("i", $patient_id);
                                                    $prescription_query->execute();
                                                    $prescriptions = $prescription_query->get_result();
                                                    
                                                    if ($prescriptions->num_rows === 0) {
                                                        echo '<tr><td colspan="5" class="text-center">No prescriptions found</td></tr>';
                                                    } else {
                                                        while ($prescription = $prescriptions->fetch_assoc()) {
                                                            // Format date
                                                            $prescribed_date = date('M d, Y', strtotime($prescription['prescribed_date']));
                                                            
                                                            // Set status badge class
                                                            $status_class = 'secondary';
                                                            if ($prescription['status'] === 'active') {
                                                                $status_class = 'success';
                                                            } elseif ($prescription['status'] === 'completed') {
                                                                $status_class = 'primary';
                                                            } elseif ($prescription['status'] === 'cancelled') {
                                                                $status_class = 'danger';
                                                            }
                                                            
                                                            echo '<tr>';
                                                            echo '<td>' . htmlspecialchars($prescribed_date) . '</td>';
                                                            echo '<td>' . htmlspecialchars($prescription['doctor_name']) . '</td>';
                                                            echo '<td>' . $prescription['medicines'] . '</td>';
                                                            echo '<td>' . htmlspecialchars($prescription['notes'] ?? 'No notes') . '</td>';
                                                            echo '<td><span class="badge bg-' . $status_class . '">' . ucfirst($prescription['status']) . '</span></td>';
                                                            echo '</tr>';
                                                        }
                                                    }
                                                    ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Lab Tests Tab -->
                            <div class="tab-pane fade" id="lab-tests">
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h5 class="mb-0">Lab Test Results</h5>
                                    </div>
                                    <div class="card-body" id="labResultsSection">
                                        <div class="table-responsive">
                                            <table class="table table-hover" id="labTestsTable">
                                                <thead>
                                                    <tr>
                                                        <th>Test Type</th>
                                                        <th>Doctor</th>
                                                        <th>Status</th>
                                                        <th>Result</th>
                                                        <th>Date</th>
                                                        <th>Actions</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($recent_lab_tests as $test): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($test['test_type']); ?></td>
                                                            <td><?php echo htmlspecialchars($test['doctor_name']); ?></td>
                                                            <td><span class="badge status-<?php echo $test['status']; ?>" style="background:none !important; color:<?php
                                                                if ($test['status'] === 'completed') echo '#10B981';
                                                                elseif ($test['status'] === 'pending') echo '#CA8A04';
                                                                elseif ($test['status'] === 'processing') echo '#0284C7';
                                                                elseif ($test['status'] === 'cancelled') echo '#DC2626';
                                                                else echo '#64748B';
                                                            ?> !important; font-weight:600;">
                                                                <?php echo ucfirst($test['status']); ?>
                                                            </span></td>
                                                            <td>
                                                                <?php if ($test['status'] === 'completed'):
                                                                    $result_data = json_decode($test['results'] ?? '{}', true);
                                                                    $result_type = isset($result_data['result_type']) ? $result_data['result_type'] : '';
                                                                    if ($result_type): ?>
                                                                        <span class="badge status-<?php echo $result_type; ?>" style="background:none !important; color:<?php
                                                                            if ($result_type === 'normal') echo '#10B981';
                                                                            elseif ($result_type === 'abnormal') echo '#DC2626';
                                                                            elseif ($result_type === 'critical') echo '#CA8A04';
                                                                            else echo '#64748B';
                                                                        ?> !important; font-weight:600;">
                                                                            <?php echo ucfirst($result_type); ?>
                                                                        </span>
                                                                    <?php else: ?>-
                                                                    <?php endif; ?>
                                                                <?php else: ?>-
                                                                <?php endif; ?>
                                                            </td>
                                                            <td><?php echo date('M j, Y', strtotime($test['requested_date'])); ?></td>
                                                            <td>
                                                                <?php if ($test['status'] === 'completed'): ?>
                                                                    <button class="btn btn-sm btn-primary" onclick="viewLabResults(<?php echo $test['id']; ?>)">
                                                                        <i class="fas fa-file-medical me-1"></i>View 
                                                                    </button>
                                                                    <?php if (!empty($test['result_pdf'])): ?>
                                                                        <a href="../<?php echo htmlspecialchars($test['result_pdf']); ?>" target="_blank" class="btn btn-primary btn-sm ms-1">
                                                                            <i class="fas fa-file-pdf me-1"></i>PDF
                                                                        </a>
                                                                    <?php endif; ?>
                                                                <?php endif; ?>
                                                            </td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <?php if (empty($recent_lab_tests)): ?>
                                                        <tr>
                                                            <td colspan="6" class="text-center">No lab tests found.</td>
                                                        </tr>
                                                    <?php endif; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if (!empty($medical_history)): ?>
                            <!-- Medical History Tab -->
                            <div class="tab-pane fade" id="history">
                                <h5 class="mb-3">Active Medical Conditions</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Condition</th>
                                                <th>Diagnosed</th>
                                                <th>Doctor</th>
                                                <th>Notes</th>
                                                <th>Status</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($medical_history as $condition): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($condition['condition_name']); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($condition['diagnosis_date'])); ?></td>
                                                <td>Dr. <?php echo htmlspecialchars($condition['doctor_name']); ?></td>
                                                <td><?php echo nl2br(htmlspecialchars($condition['notes'] ?? '')); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        echo $condition['status'] === 'active' ? 'warning' : 'success'; 
                                                    ?>">
                                                        <?php echo ucfirst($condition['status']); ?>
                                                    </span>
                                                </td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Lab Results Modal -->
    <div class="modal fade" id="labResultsModal" tabindex="-1">
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
                    Loading...
                </div>
            </div>
        </div>
    </div>

    <?php include_once '../includes/footer.php'; ?>

    <script>
    $(document).ready(function() {
        // Initialize variables
        let selectedDoctor = null;
        let selectedDate = null;
        
        // Handle doctor selection
        $('#doctor_select').change(function() {
            selectedDoctor = $(this).val();
            if (selectedDoctor) {
                loadAvailableDates();
            } else {
                // Reset date and time selects
                resetDateAndTimeSelects();
            }
        });
        
        // Handle date selection
        $('#available_dates').change(function() {
            selectedDate = $(this).val();
            if (selectedDate) {
                loadAvailableTimeSlots();
            } else {
                // Reset time select only
                resetTimeSelect();
            }
        });
        
        // Function to reset date and time selects
        function resetDateAndTimeSelects() {
            $('#available_dates').html('<option value="">-- Select Doctor First --</option>');
            $('#available_dates').prop('disabled', true);
            resetTimeSelect();
        }
        
        // Function to reset time select
        function resetTimeSelect() {
            $('#time_slot').html('<option value="">-- Select Date First --</option>');
            $('#time_slot').prop('disabled', true);
        }
        
        // Function to load available dates for selected doctor
        function loadAvailableDates() {
            const dateSelect = $('#available_dates');
            const errorDiv = $('#appointment-error');
            
            // Reset date select
            dateSelect.html('<option value="">-- Loading Available Dates --</option>');
            dateSelect.prop('disabled', true);
            errorDiv.hide();
            
            // Reset time select
            resetTimeSelect();
            
            // Fetch available dates using AJAX
            $.ajax({
                url: '../processes/get_available_days.php',
                type: 'GET',
                data: {
                    doctor_id: selectedDoctor
                },
                dataType: 'json',
                success: function(response) {
                    dateSelect.empty();
                    
                    if (response.success) {
                        if (response.data.length > 0) {
                            dateSelect.append('<option value="">-- Select an Available Date --</option>');
                            
                            // Add dates to dropdown
                            response.data.forEach(function(dateInfo) {
                                dateSelect.append(
                                    `<option value="${dateInfo.date}">${dateInfo.formatted_date}</option>`
                                );
                            });
                            
                            dateSelect.prop('disabled', false);
                            $('#date_help').text(`Doctor is available on: ${response.working_days.join(', ')}`);
                        } else {
                            dateSelect.append('<option value="">No available dates</option>');
                            errorDiv.text('This doctor has no available dates. Please select another doctor.').show();
                            $('#date_help').text("Doctor has no scheduled availability.");
                        }
                    } else {
                        dateSelect.append('<option value="">-- Error Loading Dates --</option>');
                        errorDiv.text(response.message || 'Error loading available dates').show();
                    }
                },
                error: function() {
                    dateSelect.html('<option value="">-- Error Loading Dates --</option>');
                    errorDiv.text('Error connecting to server. Please try again.').show();
                }
            });
        }
        
        // Function to load available time slots for selected date
        function loadAvailableTimeSlots() {
            const timeSlotSelect = $('#time_slot');
            const errorDiv = $('#appointment-error');
            
            // Reset time slot dropdown
            timeSlotSelect.html('<option value="">-- Loading Time Slots --</option>');
            timeSlotSelect.prop('disabled', true);
            errorDiv.hide();
            
            // Fetch available time slots using AJAX
            $.ajax({
                url: '../processes/get_available_slots.php',
                type: 'GET',
                data: {
                    doctor_id: selectedDoctor,
                    date: selectedDate
                },
                dataType: 'json',
                success: function(response) {
                    timeSlotSelect.empty();
                    
                    if (response.success) {
                        if (response.data.length > 0) {
                            timeSlotSelect.append('<option value="">-- Select a Time Slot --</option>');
                            
                            // Add time slots to dropdown
                            response.data.forEach(function(slot) {
                                timeSlotSelect.append(
                                    `<option value="${slot.time}">${slot.formatted_time}</option>`
                                );
                            });
                            
                            timeSlotSelect.prop('disabled', false);
                        } else {
                            timeSlotSelect.append('<option value="">No available slots for this day</option>');
                            errorDiv.text('No available time slots for the selected date. Please choose another date.').show();
                        }
                    } else {
                        timeSlotSelect.append('<option value="">Error loading time slots</option>');
                        errorDiv.text(response.message || 'Error loading time slots').show();
                    }
                },
                error: function() {
                    timeSlotSelect.html('<option value="">-- Error Loading Time Slots --</option>');
                    errorDiv.text('Error connecting to server. Please try again.').show();
                }
            });
        }
        
        // Form validation before submission
        $('#appointmentForm').submit(function(e) {
            const errorDiv = $('#appointment-error');
            errorDiv.hide();
            
            // Basic validation
            if (!$('#doctor_select').val()) {
                e.preventDefault();
                errorDiv.text('Please select a doctor.').show();
                return;
            }
            
            if (!$('#available_dates').val()) {
                e.preventDefault();
                errorDiv.text('Please select an available date.').show();
                return;
            }
            
            if (!$('#time_slot').val()) {
                e.preventDefault();
                errorDiv.text('Please select an available time slot.').show();
                return;
            }
        });
    });
    </script>
</body>
</html>
