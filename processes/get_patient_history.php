<?php
// processes Endpoint: get_patient_history.php
// Fetches detailed patient information, appointments, prescriptions, lab tests, and medical history for a specific patient.
// Only accessible to doctors. Returns HTML for patient details and history.
require_once '../includes/database.php'; // Database connection
require_once '../includes/session_manager.php'; // Session management utilities

checkSession('doctor'); // Ensure only doctors can access

if (isset($_GET['patient_id'])) {
    // If patient_id is provided, fetch all related patient data.
    $patient_id = $_GET['patient_id'];

    // Fetch patient's detailed information
    $stmt = $conn->prepare("SELECT id, name, email, phone, address, gender, date_of_birth, blood_group, allergies FROM users WHERE id = ?");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $patient = $stmt->get_result()->fetch_assoc();

    // Fetch patient's appointments
    $stmt = $conn->prepare("SELECT appointment_date, status FROM appointments WHERE patient_id = ? ORDER BY appointment_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch patient's medical history
    /*
    $stmt = $conn->prepare("SELECT condition_name, status, diagnosis_date, notes FROM medical_history WHERE patient_id = ? ORDER BY diagnosis_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $medical_history = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    */

    // Fetch patient's prescriptions with medicine details
    $stmt = $conn->prepare("
        SELECT p.id, p.prescribed_date, p.notes, p.status,
               GROUP_CONCAT(CONCAT(m.brand_name, ' (', m.strength, ')') SEPARATOR ', ') as medicines
        FROM prescriptions p
        LEFT JOIN prescription_items pi ON p.id = pi.prescription_id
        LEFT JOIN medicines m ON pi.medicine_id = m.id
        WHERE p.patient_id = ?
        GROUP BY p.id
        ORDER BY p.prescribed_date DESC
    ");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $prescriptions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    // Fetch patient's lab tests
    $stmt = $conn->prepare("SELECT test_type, notes, requested_date FROM lab_tests WHERE patient_id = ? ORDER BY requested_date DESC");
    $stmt->bind_param("i", $patient_id);
    $stmt->execute();
    $lab_tests = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    echo "<h4>Patient Information</h4>";
    if ($patient) {
        echo "<ul>";
        echo "<li><strong>Name:</strong> " . htmlspecialchars($patient['name']) . "</li>";
        echo "<li><strong>Email:</strong> " . htmlspecialchars($patient['email']) . "</li>";
        echo "<li><strong>Phone:</strong> " . htmlspecialchars($patient['phone']) . "</li>";
        echo "<li><strong>Gender:</strong> " . htmlspecialchars(ucfirst($patient['gender'])) . "</li>";
        echo "<li><strong>Date of Birth:</strong> " . htmlspecialchars($patient['date_of_birth']) . "</li>";
        echo "<li><strong>Address:</strong> " . htmlspecialchars($patient['address']) . "</li>";
        echo "<li><strong>Blood Group:</strong> " . htmlspecialchars($patient['blood_group']) . "</li>";
        echo "<li><strong>Allergies:</strong> " . htmlspecialchars($patient['allergies']) . "</li>";
        echo "</ul>";
    } else {
        echo "<p>Patient information not found.</p>";
    }

    echo "<h4>Appointment History</h4>";
    if ($appointments) {
        echo "<ul>";
        foreach ($appointments as $appointment) {
            echo "<li>" . date('M j, Y H:i', strtotime($appointment['appointment_date'])) . " - Status: " . htmlspecialchars($appointment['status']) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No appointment history found.</p>";
    }

    echo "<h4>Prescription History</h4>";
    if ($prescriptions) {
        echo "<ul>";
        foreach ($prescriptions as $prescription) {
            echo "<li><strong>Date:</strong> " . date('M j, Y', strtotime($prescription['prescribed_date'])) . 
                 " - <strong>Medicines:</strong> " . htmlspecialchars($prescription['medicines'] ?: 'None') . 
                 " - <strong>Status:</strong> " . htmlspecialchars(ucfirst($prescription['status'])) . 
                 " - <strong>Notes:</strong> " . htmlspecialchars($prescription['notes'] ?: 'None') . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No prescription history found.</p>";
    }

    echo "<h4>Lab Test History</h4>";
    if ($lab_tests) {
        echo "<ul>";
        foreach ($lab_tests as $lab_test) {
            echo "<li>" . htmlspecialchars($lab_test['test_type']) . " - Notes: " . htmlspecialchars($lab_test['notes']) . " - Requested Date: " . date('M j, Y', strtotime($lab_test['requested_date'])) . "</li>";
        }
        echo "</ul>";
    } else {
        echo "<p>No lab test history found.</p>";
    }
} else {
    echo "<p>Patient ID not provided.</p>";
}
?>
