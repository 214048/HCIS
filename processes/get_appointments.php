<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// processes Endpoint: get_appointments.php
// Fetches patient information, appointments, prescriptions, and lab tests for a specific patient.
// Only accessible to doctors. Returns HTML for patient details and history.
require_once '../includes/database.php'; // Database connection
require_once '../includes/session_manager.php'; // Session management utilities

checkSession('doctor'); // Ensure only doctors can access

// If doctor_id is provided, return all appointments for that doctor as JSON (for dashboard)
if (isset($_GET['doctor_id'])) {
    $doctor_id = $_GET['doctor_id'];

    // Fetch all appointments for this doctor (today or as needed)
    $stmt = $conn->prepare("
        SELECT a.*, u.name as patient_name, u.id as patient_id
        FROM appointments a
        JOIN users u ON a.patient_id = u.id
        WHERE a.doctor_id = ?
        ORDER BY a.appointment_date ASC
    ");
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Return as JSON
    header('Content-Type: application/json');
    echo json_encode($appointments);
    exit();
}

if (isset($_GET['patient_id'])) {
    // If patient_id is provided, fetch all related patient data.
    $patient_id = $_GET['patient_id'];

    // Fetch patient's basic information from the users table
    // Used for displaying patient profile details.
    $stmt = $conn->prepare("SELECT id, name, email, phone, gender, date_of_birth, blood_group, allergies FROM users WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    // Fetch patient's appointments
    // Retrieves all appointments for the patient.
    $stmt = $conn->prepare("SELECT appointment_date, status FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch patient's prescriptions
    // Retrieves all prescriptions for the patient.
    $stmt = $conn->prepare("
        SELECT p.*, 
               GROUP_CONCAT(CONCAT(m.name, ' (', m.strength, ')') SEPARATOR ', ') as medicines
        FROM prescriptions p
        LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
        LEFT JOIN medicines m ON pi.medicine_id = m.id
        WHERE p.patient_id = ?
        GROUP BY p.id
        ORDER BY p.created_at DESC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch patient's lab tests
    // Retrieves all lab tests for the patient.
    $stmt = $conn->prepare("SELECT test_type, notes, requested_date FROM lab_tests WHERE patient_id = ? ORDER BY requested_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Output patient information as HTML for display in the dashboard/modal
    echo "<h4>Patient Information</h4>";
    if ($patient) {
        echo "<p><strong>Name:</strong> " . htmlspecialchars($patient['name']) . "</p>";
        echo "<p><strong>Email:</strong> " . htmlspecialchars($patient['email']) . "</p>";
        echo "<p><strong>Phone:</strong> " . htmlspecialchars($patient['phone']) . "</p>";
        echo "<p><strong>Gender:</strong> " . htmlspecialchars($patient['gender']) . "</p>";
        echo "<p><strong>Date of Birth:</strong> " . htmlspecialchars($patient['date_of_birth']) . "</p>";
        echo "<p><strong>Blood Group:</strong> " . htmlspecialchars($patient['blood_group']) . "</p>";
        echo "<p><strong>Allergies:</strong> " . htmlspecialchars($patient['allergies']) . "</p>";
    } else {
        echo "<p>No patient information found.</p>";
    }
    
    // Output appointments
    
}