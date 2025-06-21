<?php
// Only start session if one isn't already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database connection at the top level
require_once __DIR__ . '/../includes/database.php';

function checkSession($required_role = null) {
    global $conn;  // Make database connection available inside function
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header('Location: ../login.php');
        exit();
    }

    // Check session timeout (1 hour = 3600 seconds)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 3600)) {
        // Session has expired
        session_unset();
        session_destroy();
        header('Location: ../login.php?timeout=1');
        exit();
    }

    // Check role if specified
    if ($required_role !== null && $_SESSION['role'] !== $required_role) {
        header('Location: ../login.php');
        exit();
    }

    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Store user info in session if not already stored
    if (!isset($_SESSION['user_info'])) {
        $stmt = $conn->prepare("SELECT name, email, role FROM users WHERE id = ?");
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        if ($user = $result->fetch_assoc()) {
            $_SESSION['user_info'] = $user;
        }
        $stmt->close();
    }
}

function getSessionUser() {
    return isset($_SESSION['user_info']) ? $_SESSION['user_info'] : null;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

// Function to check if session is still valid via AJAX
function checkSessionStatus() {
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['last_activity'])) {
        return ['valid' => false];
    }
    
    if ((time() - $_SESSION['last_activity']) > 3600) {
        return ['valid' => false];
    }
    
    $_SESSION['last_activity'] = time();
    return ['valid' => true];
}

// Handle AJAX session check
if (isset($_GET['check_session'])) {
    header('Content-Type: application/json');
    echo json_encode(checkSessionStatus());
    exit();
}
?>
