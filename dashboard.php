<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Get user's role and redirect to appropriate dashboard
$role = $_SESSION['role'] ?? '';

switch ($role) {
    case 'doctor':
        header('Location: doctor/dashboard.php');
        break;
    case 'patient':
        header('Location: patient/dashboard.php');
        break;
    case 'pharmacist':
        header('Location: pharmacy/dashboard.php');
        break;
    case 'lab':
        header('Location: lab/dashboard.php');
        break;
    case 'admin':
        header('Location: admin/dashboard.php');
        break;
    default:
        // If role is not recognized, redirect to home page
        header('Location: index.php');
        break;
}
exit();
?>

<meta http-equiv="refresh" content="300"> 