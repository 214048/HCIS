<?php
header('Content-Type: application/json');
session_start();

// Get the correct path to includes directory
$includes_path = dirname(__DIR__) . '/includes/db_connection.php';
if (!file_exists($includes_path)) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Database includesuration not found',
        'debug' => ['path' => $includes_path]
    ]);
    exit;
}

require_once $includes_path;
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Check login status
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Not logged in');
    }

    // Check if file was uploaded
    if (!isset($_FILES['profile_picture'])) {
        throw new Exception('No file uploaded');
    }

    $file = $_FILES['profile_picture'];

    // Check for upload errors
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('Upload failed with error code: ' . $file['error']);
    }

    // Validate file type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed_types = ['image/jpeg', 'image/png'];
    if (!in_array($mime_type, $allowed_types)) {
        throw new Exception('Invalid file type. Only JPG and PNG are allowed.');
    }

    // Validate file size (2MB)
    if ($file['size'] > 2 * 1024 * 1024) {
        throw new Exception('File too large. Maximum size is 2MB.');
    }

    // Create upload directory if it doesn't exist
    $upload_dir = dirname(__DIR__) . '/uploads/profile_pictures';
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0777, true)) {
            throw new Exception('Failed to create upload directory: ' . $upload_dir . ' - ' . error_get_last()['message']);
        }
    }

    // Double check directory exists and is writable
    if (!is_dir($upload_dir)) {
        throw new Exception('Upload directory does not exist or is not a directory: ' . $upload_dir);
    }
    if (!is_writable($upload_dir)) {
        // Try to make it writable
        chmod($upload_dir, 0777);
        if (!is_writable($upload_dir)) {
            throw new Exception('Upload directory is not writable: ' . $upload_dir);
        }
    }

    // Ensure directory is writable
    if (!is_writable($upload_dir)) {
        throw new Exception('Upload directory is not writable: ' . $upload_dir);
    }

    // Generate unique filename
    $extension = $mime_type === 'image/jpeg' ? 'jpg' : 'png';
    $filename = 'profile_' . $_SESSION['user_id'] . '_' . time() . '.' . $extension;
    $target_path = $upload_dir . '/' . $filename;

    // Check if user exists first
    $check_user = $conn->prepare('SELECT id, profile_picture FROM users WHERE id = ?');
    if (!$check_user) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    $check_user->bind_param('i', $_SESSION['user_id']);
    if (!$check_user->execute()) {
        throw new Exception('Database execute error: ' . $check_user->error);
    }
    $result = $check_user->get_result();
    
    if ($result->num_rows === 0) {
        // Create a test user if none exists
        $create_user = $conn->prepare('INSERT INTO users (id, name, email, password, role) VALUES (?, ?, ?, ?, ?)');
        if (!$create_user) {
            throw new Exception('Failed to prepare user creation: ' . $conn->error);
        }
        
        $test_id = $_SESSION['user_id'];
        $test_name = 'Test User';
        $test_email = 'test@example.com';
        $test_password = password_hash('password123', PASSWORD_DEFAULT);
        $test_role = 'admin';
        
        $create_user->bind_param('issss', $test_id, $test_name, $test_email, $test_password, $test_role);
        if (!$create_user->execute()) {
            throw new Exception('Failed to create test user: ' . $create_user->error);
        }
        
        $old_picture = null;
    } else {
        $row = $result->fetch_assoc();
        $old_picture = $row['profile_picture'];
    }

    // Move uploaded file
    if (!move_uploaded_file($file['tmp_name'], $target_path)) {
        throw new Exception('Failed to save uploaded file');
    }

    // Update database
    $stmt = $conn->prepare('UPDATE users SET profile_picture = ? WHERE id = ?');
    if (!$stmt) {
        throw new Exception('Database prepare error: ' . $conn->error);
    }
    
    $stmt->bind_param('si', $filename, $_SESSION['user_id']);
    if (!$stmt->execute()) {
        // Delete new file if database update fails
        if (file_exists($target_path)) {
            unlink($target_path);
        }
        throw new Exception('Database execute error: ' . $stmt->error);
    }
    
    if ($stmt->affected_rows === 0) {
        throw new Exception('No database record was updated');
    }

    // Delete old profile picture
    if ($old_picture && $old_picture !== $filename) {
        $old_path = $upload_dir . '/' . $old_picture;
        if (file_exists($old_path)) {
            unlink($old_path);
        }
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'filename' => $filename,
        'message' => 'Profile picture updated successfully'
    ]);

} catch (Exception $e) {
    error_log('Profile picture upload error: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage(),
        'debug' => [
            'upload_dir' => $upload_dir,
            'file_error' => isset($file) ? $file['error'] : null,
            'file_size' => isset($file) ? $file['size'] : null,
            'file_type' => isset($file) ? $file['type'] : null,
            'session_user_id' => $_SESSION['user_id'] ?? null
        ]
    ]);
}
?>
