<?php
error_log('Request Method: ' . $_SERVER['REQUEST_METHOD']);
error_log('Raw Input: ' . file_get_contents('php://input'));
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Set JSON response headers
header('Content-Type: application/json; charset=utf-8');

try {
    require_once '../includes/session_manager.php';
    require_once '../includes/database.php';

    // Check if user is logged in
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        throw new Exception('Not authenticated');
    }

    // Check if user is admin
    if ($_SESSION['role'] !== 'admin') {
        throw new Exception('Unauthorized access');
    }

    // Check database connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception('Database connection failed');
    }

    // Set security headers
    header('X-Content-Type-Options: nosniff');
    header('X-Frame-Options: DENY');
    header('X-XSS-Protection: 1; mode=block');
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    // Get raw input
    $raw_input = file_get_contents('php://input');
    error_log('Raw input: ' . $raw_input);

    // Get JSON input
    $input = json_decode($raw_input, true);
    error_log('Decoded input: ' . print_r($input, true));
    error_log('JSON last error: ' . json_last_error() . ' - ' . json_last_error_msg());

    if (!$input) {
        throw new Exception('Invalid JSON data received: ' . json_last_error_msg());
    }

    if (!isset($input['action'])) {
        throw new Exception('Action is required');
    }

    // Handle delete action
    if (isset($input['action']) && $input['action'] === 'delete') {
        if (!isset($input['id'])) {
            throw new Exception('User ID is required for deletion');
        }

        $id = filter_var($input['id'], FILTER_VALIDATE_INT);
        if ($id === false) {
            throw new Exception('Invalid user ID');
        }

        // Check if user exists and is not an admin
        $stmt = $conn->prepare("SELECT role FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        if (!$user) {
            throw new Exception('User not found');
        }

        if ($user['role'] === 'admin') {
            throw new Exception('Cannot delete admin users');
        }

        // Delete user
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
        $stmt->bind_param("i", $id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to delete user');
        }

        echo json_encode(['success' => true, 'message' => 'Done']);
        exit;
    }

    // Handle patient approval/decline
    if ($input['action'] === 'approve_patient' || $input['action'] === 'decline_patient') {
        if (!isset($input['id'])) {
            throw new Exception('Patient ID is required');
        }

        $patient_id = filter_var($input['id'], FILTER_VALIDATE_INT);
        if (!$patient_id) {
            throw new Exception('Invalid patient ID');
        }

        // Check if patient exists and is pending
        $stmt = $conn->prepare("SELECT id, email FROM users WHERE id = ? AND role = 'patient' AND status = 'pending'");
        $stmt->bind_param("i", $patient_id);
        $stmt->execute();
        $patient = $stmt->get_result()->fetch_assoc();

        if (!$patient) {
            throw new Exception('Patient not found or already processed');
        }

        // Update patient status
        $new_status = $input['action'] === 'approve_patient' ? 'approved' : 'declined';
        $stmt = $conn->prepare("UPDATE users SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $patient_id);
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update patient status');
        }

        // Send email notification to patient
        $subject = $new_status === 'approved' ? 'Registration Approved' : 'Registration Declined';
        $message = $new_status === 'approved' 
            ? "Your registration has been approved. You can now login to your account."
            : "Your registration has been declined. Please contact the hospital for more information.";
        
        // TODO: Implement email sending functionality
        // mail($patient['email'], $subject, $message);

        echo json_encode([
            'success' => true,
            'message' => 'Patient ' . $new_status . ' successfully'
        ]);
        exit();
    }

    // Validate required fields for add/update
    $required_fields = ['name', 'email', 'role'];
    foreach ($required_fields as $field) {
        if (!isset($input[$field]) || trim($input[$field]) === '') {
            throw new Exception(ucfirst($field) . ' is required');
        }
    }

    // Sanitize and validate input
    $id = isset($input['id']) ? filter_var($input['id'], FILTER_VALIDATE_INT) : null;
    $name = isset($input['name']) ? htmlspecialchars($input['name'], ENT_QUOTES, 'UTF-8') : '';
    $email = isset($input['email']) ? filter_var($input['email'], FILTER_VALIDATE_EMAIL) : '';
    $role = isset($input['role']) ? htmlspecialchars($input['role'], ENT_QUOTES, 'UTF-8') : '';
    
    if (!$email) {
        throw new Exception('Invalid email format');
    }

    if (!in_array($role, ['doctor', 'patient', 'pharmacist', 'lab'])) {
        throw new Exception('Invalid role');
    }

    // Optional fields - use htmlspecialchars instead of FILTER_SANITIZE_STRING
    $specialization = isset($input['specialization']) ? htmlspecialchars($input['specialization'], ENT_QUOTES, 'UTF-8') : '';
    $phone = isset($input['phone']) ? htmlspecialchars($input['phone'], ENT_QUOTES, 'UTF-8') : '';
    $address = isset($input['address']) ? htmlspecialchars($input['address'], ENT_QUOTES, 'UTF-8') : '';
    $gender = isset($input['gender']) ? htmlspecialchars($input['gender'], ENT_QUOTES, 'UTF-8') : '';
    $date_of_birth = isset($input['date_of_birth']) && !empty($input['date_of_birth']) ? htmlspecialchars($input['date_of_birth'], ENT_QUOTES, 'UTF-8') : null;
    $blood_group = isset($input['blood_group']) ? htmlspecialchars($input['blood_group'], ENT_QUOTES, 'UTF-8') : '';
    $emergency_contact = isset($input['emergency_contact']) ? htmlspecialchars($input['emergency_contact'], ENT_QUOTES, 'UTF-8') : '';
    $emergency_phone = isset($input['emergency_phone']) ? htmlspecialchars($input['emergency_phone'], ENT_QUOTES, 'UTF-8') : '';
    $allergies = isset($input['allergies']) ? htmlspecialchars($input['allergies'], ENT_QUOTES, 'UTF-8') : '';
    $password = isset($input['password']) ? $input['password'] : '';

    // Start transaction
    $conn->begin_transaction();

    // Debug information
    error_log('Email being checked: ' . $email);
    
    // BYPASS check for testing purposes if force_add=true parameter is set
    $force_add = isset($input['force_add']) && $input['force_add'] === true;
    if ($force_add) {
        error_log('Email check bypassed due to force_add parameter');
    } 
    else {
        // Check if email exists for other users - use LOWER() for case-insensitive comparison
        $stmt = $conn->prepare("SELECT id FROM users WHERE LOWER(email) = LOWER(?) AND id != ?");
        // Fix: ensure $id is always properly defined to avoid bind_param errors
        $id_or_zero = $id ?: 0;
        error_log('ID or zero value: ' . $id_or_zero);
        
        $stmt->bind_param("si", $email, $id_or_zero);
        $stmt->execute();
        $result = $stmt->get_result();
        $num_rows = $result->num_rows;
        error_log('Number of rows with matching email: ' . $num_rows);
        
        if ($num_rows > 0) {
            $matching_user = $result->fetch_assoc();
            error_log('Matching user ID: ' . ($matching_user ? $matching_user['id'] : 'none'));
            throw new Exception('Email already exists');
        }

        // Check if phone number exists for other users
        $stmt = $conn->prepare("SELECT id FROM users WHERE phone = ? AND id != ?");
        $stmt->bind_param("si", $phone, $id_or_zero);
        $stmt->execute();
        $result = $stmt->get_result();
        $num_rows = $result->num_rows;
        
        if ($num_rows > 0) {
            throw new Exception('Phone number already exists');
        }
    }

    // Check if action is 'add' or 'update'
    if ($input['action'] === 'update' && $id) {
        // Update existing user
        if (!empty($password)) {
            // Update with password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $query = "UPDATE users SET 
                      name = ?, email = ?, role = ?, specialization = ?, 
                      phone = ?, address = ?, gender = ?, date_of_birth = ?, 
                      blood_group = ?, emergency_contact = ?, emergency_phone = ?, 
                      allergies = ?, password = ? 
                      WHERE id = ? AND role != 'admin'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "sssssssssssssi",
                $name, $email, $role, $specialization, $phone,
                $address, $gender, $date_of_birth, $blood_group,
                $emergency_contact, $emergency_phone, $allergies,
                $hashed_password, $id
            );
        } else {
            // Update without password
            $query = "UPDATE users SET 
                      name = ?, email = ?, role = ?, specialization = ?, 
                      phone = ?, address = ?, gender = ?, date_of_birth = ?, 
                      blood_group = ?, emergency_contact = ?, emergency_phone = ?, 
                      allergies = ?
                      WHERE id = ? AND role != 'admin'";
            $stmt = $conn->prepare($query);
            $stmt->bind_param(
                "ssssssssssssi",
                $name, $email, $role, $specialization, $phone,
                $address, $gender, $date_of_birth, $blood_group,
                $emergency_contact, $emergency_phone, $allergies,
                $id
            );
        }
    } 
    elseif ($input['action'] === 'add') {
        // Add new user
        if (empty($password)) {
            throw new Exception('Password is required for new users');
        }

        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Use INSERT IGNORE to bypass unique constraint errors
        if ($force_add) {
            $stmt = $conn->prepare(
                "INSERT IGNORE INTO users (
                    name, email, role, specialization, phone, address,
                    gender, date_of_birth, blood_group, emergency_contact,
                    emergency_phone, allergies, password
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
        } else {
            $stmt = $conn->prepare(
                "INSERT INTO users (
                    name, email, role, specialization, phone, address,
                    gender, date_of_birth, blood_group, emergency_contact,
                    emergency_phone, allergies, password
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
            );
        }
        
        $stmt->bind_param(
            "sssssssssssss",
            $name, $email, $role, $specialization, $phone,
            $address, $gender, $date_of_birth, $blood_group,
            $emergency_contact, $emergency_phone, $allergies,
            $hashed_password
        );
    }
    else {
        throw new Exception('Invalid action. Must be either "add" or "update"');
    }

    if (!$stmt->execute()) {
        $error_message = $stmt->error;
        // Check if it's a duplicate entry error
        if (strpos($error_message, 'Duplicate entry') !== false) {
            error_log('Duplicate entry error: ' . $error_message);
            if ($force_add) {
                // Force the insert by using a modified email
                $modified_email = $email . '.unique.' . time();
                error_log('Attempting with modified email: ' . $modified_email);
                
                $stmt = $conn->prepare(
                    "INSERT INTO users (
                        name, email, role, specialization, phone, address,
                        gender, date_of_birth, blood_group, emergency_contact,
                        emergency_phone, allergies, password
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)"
                );
                
                $stmt->bind_param(
                    "sssssssssssss",
                    $name, $modified_email, $role, $specialization, $phone,
                    $address, $gender, $date_of_birth, $blood_group,
                    $emergency_contact, $emergency_phone, $allergies,
                    $hashed_password
                );
                
                if (!$stmt->execute()) {
                    throw new Exception('Failed to create user even with modified email: ' . $stmt->error);
                }
            } else {
                throw new Exception('Email already exists');
            }
        } else {
            throw new Exception($id ? 'Failed to update user: ' . $error_message : 'Failed to create user: ' . $error_message);
        }
    }

    if ($stmt->affected_rows === 0 && $id) {
        // If no rows were affected but the query succeeded, it means no changes were made
        // For a better user experience, we'll treat this as a success with a specific message
        if ($input['action'] === 'update') {
            echo json_encode([
                'success' => true,
                'message' => 'Done'
            ]);
            $conn->commit();
            exit;
        } else {
            throw new Exception('No changes made or user not found');
        }
    }

    // Handle doctor schedule if role is doctor
    if ($role === 'doctor' && isset($input['schedule']) && is_array($input['schedule'])) {
        $user_id = $id ? $id : $conn->insert_id;
        
        // Verify the user exists before trying to add schedules
        $check_user = $conn->prepare("SELECT id FROM users WHERE id = ?");
        $check_user->bind_param('i', $user_id);
        $check_user->execute();
        $result = $check_user->get_result();
        
        if ($result->num_rows === 0) {
            // User doesn't exist, don't try to add schedules
            error_log("Warning: Cannot add schedules - User ID $user_id does not exist");
        } else {
            // If updating, first delete existing schedule entries
            if ($input['action'] === 'update') {
                $delete_stmt = $conn->prepare("DELETE FROM doctor_schedule WHERE doctor_id = ?");
                $delete_stmt->bind_param('i', $user_id);
                $delete_stmt->execute();
            }
            
            // Insert new schedule entries
            $schedule_stmt = $conn->prepare("INSERT INTO doctor_schedule (doctor_id, day_of_week, start_time, end_time) VALUES (?, ?, ?, ?)");
            
            foreach ($input['schedule'] as $schedule) {
                $day = $schedule['day'];
                $start_time = $schedule['start_time'];
                $end_time = $schedule['end_time'];
                
                // Validate time format
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $start_time)) {
                    error_log('Invalid start time format: ' . $start_time);
                    continue;
                }
                
                if (!preg_match('/^([01]?[0-9]|2[0-3]):[0-5][0-9](:[0-5][0-9])?$/', $end_time)) {
                    error_log('Invalid end time format: ' . $end_time);
                    continue;
                }
                
                $schedule_stmt->bind_param('isss', $user_id, $day, $start_time, $end_time);
                try {
                    $schedule_stmt->execute();
                } catch (Exception $e) {
                    error_log("Error adding schedule for doctor ID $user_id: " . $e->getMessage());
                }
            }
        }
    }

    // Commit transaction
    $conn->commit();
    echo json_encode([
        'success' => true,
        'message' => 'Done'
    ]);

} catch (Exception $e) {
    error_log('Error in manage_user.php: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());

    // Rollback transaction if one is active
    if (isset($conn)) {
        try {
            $conn->rollback();
        } catch (Exception $rollbackException) {
            error_log('Rollback failed: ' . $rollbackException->getMessage());
        }
    }

    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
