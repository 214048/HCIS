<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: /hcisProject/login.php');
    exit();
}

// Get user's role for navigation
$role = $_SESSION['role'] ?? '';

// Define dashboard URL based on user role
$dashboardUrl = '/hcisProject/';
if ($role === 'doctor') {
    $dashboardUrl = '/hcisProject/doctor/dashboard.php';
} elseif ($role === 'patient') {
    $dashboardUrl = '/hcisProject/patient/dashboard.php';
} elseif ($role === 'pharmacist') {
    $dashboardUrl = '/hcisProject/pharmacy/dashboard.php';
} elseif ($role === 'lab') {
    $dashboardUrl = '/hcisProject/lab/dashboard.php';
} elseif ($role === 'admin') {
    $dashboardUrl = '/hcisProject/admin/dashboard.php';
}
?>
<?php if ($role === 'admin'): ?>
<nav class="navbar admin-navbar navbar-expand-lg" style="background: #fff; box-shadow: 0 2px 12px rgba(30,58,138,0.08); border-radius: 0 0 18px 18px; padding: 0.5rem 0;">
    <div class="container d-flex align-items-center justify-content-between py-1">
        <div class="d-flex align-items-center gap-3">
            <a class="navbar-brand header-title" href="/hcisProject/index.php" style="font-size: 1.8rem; font-weight: bold; letter-spacing: 0.5px;">
                <span style="color:rgb(255, 255, 255); font-size: 2rem;">E</span>-Health
            </a>
            <span class="header-subtext ms-3 d-none d-md-inline" style="color: #64748B; font-size: 1.1rem; font-weight: 600;">Admin Panel</span>
        </div>
        <div class="d-flex align-items-center gap-3">
            <a href="/hcisProject/profile.php" class="header-subtext d-none d-md-inline" style="text-decoration:none; color: #1E3A8A; font-weight: 600; font-size: 1.1rem;">
                <?php echo htmlspecialchars($_SESSION['name'] ?? 'Admin'); ?>
            </a>
            <a class="btn btn-primary ms-2" href="/hcisProject/logout.php" title="Logout" style="background: #1E3A8A; color: #fff; border-radius: 25px; padding: 0.4rem 1.2rem; font-weight: 600; font-size: 1rem; border: none; box-shadow: 0 2px 8px rgba(30,58,138,0.08); transition: background 0.18s, color 0.18s;">
                <i class="fas fa-sign-out-alt me-1"></i> Logout
            </a>
        </div>
    </div>
</nav>
<?php else: ?>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark shadow-sm">
    <div class="container">
        <div class="d-flex align-items-center">
            <a class="navbar-brand" href="/hcisProject/index.php" style="font-size: 1.8rem; font-weight: bold;" id="brand-text">
                <span style="color: #007bff; font-size: 2rem;">E</span>-Health
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
        </div>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav me-auto">
                <!-- Dashboard buttons removed -->
            </ul>
            <div class="header-profile-actions">
                <a href="/hcisProject/profile.php" class="username-link" style="background: #0097DB; color: #fff; font-weight:500; border-radius: 6px; padding: 0.4rem 1rem; display: inline-flex; align-items: center; text-decoration: none; transition: background 0.18s; font-size: 1rem; border: none;">
                    <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($_SESSION['name'] ?? 'User'); ?>
                </a>
                <a class="logout" href="/hcisProject/logout.php" style="background: #0097DB; color: #dc3545; border-radius: 6px; padding: 0.4rem 1rem; display: inline-flex; align-items: center; font-weight: 500; font-size: 1rem; text-decoration: none; transition: background 0.18s; border: none;">
                    <i class="fas fa-sign-out-alt me-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>
<?php endif; ?>

<!-- Add JavaScript to ensure dropdown works properly -->
<script>
// Simple function to toggle the user dropdown menu
function toggleUserDropdown(event) {
    event.preventDefault();
    event.stopPropagation();
    const dropdownMenu = document.getElementById('userDropdownMenu');
    if (dropdownMenu) {
        dropdownMenu.classList.toggle('show');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('userDropdown');
    const dropdownMenu = document.getElementById('userDropdownMenu');
    
    if (dropdownMenu && dropdownMenu.classList.contains('show')) {
        // Don't close if clicking on the dropdown toggle itself
        if (!dropdown.contains(event.target)) {
            dropdownMenu.classList.remove('show');
        }
    }
});

// Add custom styles for dropdown and handle brand text color
document.addEventListener('DOMContentLoaded', function() {
    // Add a style element for custom dropdown behavior
    const style = document.createElement('style');
    style.textContent = `
        /* Brand text color to be white by default */
        #brand-text {
            color: #ffffff;
        }
        
        .dropdown-menu.show {
            display: block;
            margin-top: 0.125rem;
            opacity: 1;
            visibility: visible;
        }
        
        .dropdown-menu {
            transition: all 0.2s;
            opacity: 0;
            visibility: hidden;
            display: block;
            margin-top: 0;
            position: absolute;
            z-index: 1000;
            right: 0;
            background-color: #1e2124;
            border: 1px solid #2c2f33;
            border-radius: 4px;
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.25);
        }
        
        .dropdown-item {
            color: #ffffff;
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover, .dropdown-item:focus {
            background-color: #2c2f33;
            color: #ffffff;
        }
        
        .dropdown-divider {
            border-top: 1px solid #2c2f33;
        }
        
        @media (max-width: 576px) {
            .dropdown-menu {
                position: absolute;
                right: 0;
                left: auto;
                width: 200px;
            }
        }
    `;
    document.head.appendChild(style);
});
</script>

<style>
/* Remove dropdown debug and force styles */
.header-profile-actions {
  display: flex;
  gap: 1rem;
  align-items: center;
  margin-left: auto;
}
.header-profile-actions a {
  display: inline-flex;
  align-items: center;
  padding: 0.4rem 1rem;
  border-radius: 6px;
  font-weight: 500;
  text-decoration: none;
  transition: background 0.18s;
  font-size: 1rem;
}
.header-profile-actions a.profile {
  background: #f3f4f6;
  color: #007bff;
  border: 1px solid #e0e7ef;
}
.header-profile-actions a.profile:hover {
  background: #e0e7ef;
}
.header-profile-actions a.logout {
  background: #fff0f0;
  color: #dc3545;
  border: 1px solid #f5c2c7;
}
.header-profile-actions a.logout:hover {
  background: #f5c2c7;
  color: #fff;
}
.header-profile-actions a.username-link,
.header-profile-actions a.logout {
  background: #212529;
  transition: background 0.18s;
}
.header-profile-actions a.username-link:hover,
.header-profile-actions a.logout:hover {
  background: #0d0e10 !important;
}
#brand-text > span {
  color: #007bff !important;
}
</style>
