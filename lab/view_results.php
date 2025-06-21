<?php
session_start();
require_once '../includes/database.php';

// Check if user is logged in and has appropriate role
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['lab', 'doctor', 'patient'])) {
    header('Location: ../login.php');
    exit();
}

if (!isset($_GET['test_id'])) {
    header('Location: dashboard.php');
    exit();
}

$test_id = $_GET['test_id'];

// Get test details with patient and doctor information
$stmt = $conn->prepare("
    SELECT t.*, 
           p.name as patient_name, p.id as patient_id,
           d.name as doctor_name, d.specialization as doctor_specialization
    FROM lab_tests t 
    JOIN users p ON t.patient_id = p.id 
    JOIN users d ON t.doctor_id = d.id
    WHERE t.id = ?
");
$stmt->bind_param("i", $test_id);
$stmt->execute();
$test = $stmt->get_result()->fetch_assoc();

// Check if test exists and user has permission to view it
if (!$test || ($_SESSION['role'] === 'patient' && $test['patient_id'] !== $_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lab Test Results - E-Health</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="../css/style.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container">
            <a class="navbar-brand" href="#" style="font-size: 1.8rem; font-weight: bold;"><span class="text-primary" style="font-size: 2rem;">E</span>-Health Lab Results</a>
            <div class="collapse navbar-collapse">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../<?php echo $_SESSION['role']; ?>/dashboard.php">Back to Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Lab Test Results</h5>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Patient Information</h6>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($test['patient_name']); ?></p>
                                <p class="mb-1"><strong>ID:</strong> <?php echo $test['patient_id']; ?></p>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Test Information</h6>
                                <p class="mb-1"><strong>Test Type:</strong> <?php echo htmlspecialchars($test['test_type']); ?></p>
                                <p class="mb-1"><strong>Requested Date:</strong> <?php echo date('M d, Y h:i A', strtotime($test['requested_date'])); ?></p>
                                <p class="mb-1"><strong>Status:</strong> 
                                    <?php 
                                    $result_data = null;
                                    $result_type = 'normal';
                                    if ($test['status'] === 'completed' && !empty($test['results'])) {
                                        $result_data = json_decode($test['results'], true);
                                        $result_type = isset($result_data['result_type']) ? $result_data['result_type'] : 'normal';
                                    }
                                    $badge_color = $test['status'] === 'completed' ? 
                                        ($result_type === 'normal' ? 'success' : 
                                            ($result_type === 'abnormal' ? 'warning' : 'danger')) : 
                                        'info';
                                    ?>
                                    <span class="badge bg-<?php echo $badge_color; ?>">
                                        <?php echo ucfirst($test['status']); ?>
                                        <?php if ($test['status'] === 'completed') echo " - " . ucfirst($result_type); ?>
                                    </span>
                                </p>
                            </div>
                        </div>

                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Requesting Doctor</h6>
                                <p class="mb-1"><strong>Name:</strong> <?php echo htmlspecialchars($test['doctor_name']); ?></p>
                                <p class="mb-1"><strong>Specialization:</strong> <?php echo htmlspecialchars($test['doctor_specialization']); ?></p>
                            </div>
                            <?php if ($test['status'] === 'completed'): ?>
                            <div class="col-md-6">
                                <h6 class="text-muted">Result Information</h6>
                                <p class="mb-1"><strong>Completed Date:</strong> <?php echo date('M d, Y h:i A', strtotime($test['completed_date'])); ?></p>
                            </div>
                            <?php endif; ?>
                        </div>

                        <?php if ($test['status'] === 'completed'): ?>
                        <div class="row">
                            <div class="col-md-12">
                                <h6 class="text-muted">Test Results</h6>
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h6>Details:</h6>
                                        <?php
                                        $result_data = json_decode($test['results'], true);
                                        $details = isset($result_data['details']) ? $result_data['details'] : '';
                                        $recommendations = isset($result_data['recommendations']) ? $result_data['recommendations'] : '';
                                        ?>
                                        <p class="mb-4"><?php echo nl2br(htmlspecialchars($details)); ?></p>
                                        
                                        <?php if (!empty($recommendations)): ?>
                                        <h6>Recommendations:</h6>
                                        <p class="mb-0"><?php echo nl2br(htmlspecialchars($recommendations)); ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($test['result_pdf'])): ?>
                                        <hr>
                                        <a href="../<?php echo htmlspecialchars($test['result_pdf']); ?>" target="_blank" class="btn btn-primary me-2">
                                            <i class="fas fa-file-pdf me-1"></i>View PDF
                                        </a>
                                        <a href="../<?php echo htmlspecialchars($test['result_pdf']); ?>" download class="btn btn-secondary">
                                            <i class="fas fa-download me-1"></i>Download PDF
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</body>
</html>
