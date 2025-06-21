<?php
require_once 'includes/session_manager.php';
require_once 'includes/db_connection.php';

// Fetch doctors from users table with specialization
$doctors_query = "SELECT id, name, email, specialization, profile_picture, phone FROM users WHERE role = 'doctor' LIMIT 6";
$doctors_result = $conn->query($doctors_query);
$doctors = $doctors_result->fetch_all(MYSQLI_ASSOC);

// Fetch all doctor schedules
$schedules_query = "SELECT doctor_id, day_of_week, start_time, end_time FROM doctor_schedule ORDER BY FIELD(day_of_week, 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday')";
$schedules_result = $conn->query($schedules_query);
$schedules = [];
if ($schedules_result) {
    while ($row = $schedules_result->fetch_assoc()) {
        $schedules[$row['doctor_id']][] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta http-equiv="X-UA-Compatible" content="ie=edge">
  <title>Healthcare Information System</title>
  <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link rel="shortcut icon" href="assets/img/favicon.svg" type="image/svg+xml">
  <link rel="stylesheet" href="assets/css/maicons.css">
  <link rel="stylesheet" href="assets/css/bootstrap.css">
  <link rel="stylesheet" href="assets/vendor/owl-carousel/css/owl.carousel.css">
  <link rel="stylesheet" href="assets/vendor/animate/animate.css">
  <link rel="stylesheet" href="assets/css/theme.css">
  <link rel="stylesheet" href="assets/css/home-enhancements.css">
  <style>
    body {
      position: relative;
      min-height: 100vh;
      font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
      background-color: #f8f9fa;
      color: #212529;
      line-height: 1.6;
      padding-top: 90px;
    }
    body::before {
      content: '';
      position: fixed;
      top: 0; left: 0; width: 100vw; height: 100vh;
      opacity: 0.2;
      z-index: -1;
      pointer-events: none;
    }
    body, h1, h2, h3, h4, h5, h6, p, li, .nav-link, .btn {
      text-align: initial !important;
    }
    h1, h2, h3, h4, h5, h6 {
      font-weight: 700;
      color: #2c3e50;
      margin-bottom: 1rem;
    }
    p, li {
      color: #495057;
      font-size: 1.08rem;
    }
    a {
      color: #00D9A5;
      text-decoration: none;
      transition: color 0.2s;
    }
    a:hover {
      color: #4E5AFE;
      text-decoration: underline;
    }
    .container {
      max-width: 1200px;
      margin: 0 auto;
      padding: 0 1.5rem;
    }
    /* Enhanced Features Section */
    .features-section {
      padding: 4rem 0;
      background-color: transparent !important;
    }
    .features-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
      gap: 2.5rem;
      margin-top: 2.5rem;
    }
    .feature-card {
      background: #ffffff;
      border-radius: 22px;
      box-shadow: 0 4px 24px rgba(60,60,60,0.10);
      padding: 2.5rem 1.5rem 2rem 1.5rem;
      text-align: center;
      transition: transform 0.22s, box-shadow 0.22s;
      display: flex;
      flex-direction: column;
      align-items: center;
      border: 1.5px solid #e0e7ef;
      position: relative;
      overflow: hidden;
    }
    .feature-card::after {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background: linear-gradient(120deg, rgba(0,217,165,0.07) 0%, rgba(78,90,254,0.07) 100%);
      z-index: 0;
      pointer-events: none;
    }
    .feature-card:hover {
      transform: translateY(-10px) scale(1.035);
      box-shadow: 0 16px 40px rgba(60,60,60,0.16);
      border-color: #00D9A5;
    }
    .feature-icon {
      width: 60px;
      height: 60px;
      background: #ffffff;
      border-radius: 50%;
      display: flex;
      align-items: center;
      justify-content: center;
      margin-bottom: 1.5rem;
      color: #3498db;
      font-size: 1.5rem;
      box-shadow: 0 2px 8px rgba(52,152,219,0.10);
    }
    .feature-card:nth-child(1) .feature-icon {
      background: #3498db;
      color: #fff;
    }
    .feature-card:nth-child(2) .feature-icon {
      background: #3498db;
      color: #fff;
    }
    .feature-card:nth-child(3) .feature-icon {
      background: #3498db;
      color: #fff;
    }
    .feature-card:nth-child(4) .feature-icon {
      background: #3498db;
      color: #fff;
    }
    .feature-card:hover .feature-icon {
      transform: scale(1.12) rotate(-8deg);
      box-shadow: 0 6px 24px rgba(0,217,165,0.18);
    }
    .feature-content h3 {
      font-size: 1.35rem;
      font-weight: 700;
      color: #212529;
      margin-bottom: 0.5rem;
      letter-spacing: 0.5px;
    }
    .feature-content p {
      color: #6c757d;
      font-size: 1.08rem;
      margin-bottom: 0;
      line-height: 1.6;
    }
    /* Card improvements */
    .card, .service-card, .doctor-card {
      background-color: #fff;
      border: 1px solid #dee2e6;
      border-radius: 16px;
      box-shadow: 0 4px 16px rgba(60,60,60,0.08);
      transition: all 0.2s;
    }
    .card:hover, .service-card:hover, .doctor-card:hover {
      box-shadow: 0 12px 32px rgba(60,60,60,0.13);
      transform: translateY(-4px) scale(1.01);
    }
    /* Buttons */
    .btn, .btn-primary {
      border-radius: 25px;
      padding: 0.75rem 1.5rem;
      font-weight: 500;
      font-size: 1rem;
      transition: all 0.2s;
      background: #0d6efd;
      color: #fff;
      border: none;
      box-shadow: 0 2px 8px rgba(13,110,253,0.08);
    }
    .btn:hover, .btn-primary:hover {
      background: #0d2efb;
      color: #fff;
      box-shadow: 0 6px 18px rgba(13,110,253,0.16);
      transform: translateY(-2px) scale(1.03);
    }
    /* Section headers */
    .section-header, .section-title {
      text-align: center;
      margin-bottom: 2.5rem;
    }
    .section-title {
      font-size: 2.5rem;
      font-weight: 800;
      color: #2c3e50;
      margin-bottom: 1rem;
      letter-spacing: 0.5px;
      position: relative;
      display: inline-block;
      text-align: center;
    }
    .section-title::after {
      content: '';
      position: absolute;
      bottom: -10px;
      left: 0;
      right: 0;
      height: 3px;
      background: linear-gradient(90deg, #00D9A5 60%, #4E5AFE 100%);
      border-radius: 2px;
    }
    .section-description {
      color: #6c757d;
      max-width: 600px;
      margin: 0 auto;
      font-size: 1.1rem;
    }
    /* Responsive Tweaks */
    @media (max-width: 900px) {
      .features-section {
        padding: 2.5rem 0.5rem 2rem 0.5rem;
        border-radius: 18px;
      }
      .features-grid {
        gap: 1.2rem;
      }
      .feature-card {
        padding: 1.5rem 0.5rem 1.2rem 0.5rem;
      }
    }
    @media (max-width: 600px) {
      .features-section {
        padding: 1.2rem 0.2rem 1rem 0.2rem;
        border-radius: 10px;
      }
      .features-grid {
        gap: 0.7rem;
      }
      .feature-card {
        padding: 1rem 0.2rem 0.7rem 0.2rem;
      }
      .feature-icon {
        width: 60px; height: 60px;
        font-size: 1.5rem;
      }
      .feature-content h3 {
        font-size: 1.1rem;
      }
    }
    .hospital-footer {
      background: #223040 !important;
      color: #fff !important;
      padding: 4rem 0 2rem;
      font-size: 1.08rem;
      box-shadow: 0 -2px 16px rgba(0,0,0,0.08);
      opacity: 1 !important;
    }
    .hospital-footer .footer-section h3,
    .hospital-footer .footer-section p,
    .hospital-footer .footer-links li,
    .hospital-footer .footer-links a,
    .hospital-footer .contact-info li,
    .hospital-footer .footer-bottom,
    .hospital-footer .footer-bottom p {
      color: #fff !important;
      text-shadow: none !important;
      opacity: 1 !important;
    }
    .hospital-footer .footer-links a:hover {
      color: #00D9A5 !important;
    }
    .hospital-footer .contact-info li i {
      color: #00D9A5 !important;
    }
    .hospital-footer .social-links a {
      color: #fff !important;
    }
    .hospital-footer .social-links a:hover {
      color: #00D9A5 !important;
    }
    .doctor-card .contact-info {
      margin: 1.5rem 0;
      display: flex;
      flex-direction: row;
      justify-content: center;
      align-items: center;
      gap: 1.5rem;
      width: 100%;
    }
    .doctor-card .contact-info a {
      display: flex;
      align-items: center;
      justify-content: center;
      width: 44px;
      height: 44px;
      border-radius: 50%;
      font-size: 1.5rem;
      transition: background 0.2s;
    }
    .doctor-card .contact-info a[title="Send Email"],
    .doctor-card .contact-info a[title="Chat on WhatsApp"] {
      background: #0d6efd !important;
      color: #fff !important;
    }
    .doctor-card .contact-info a[title="Send Email"]:hover,
    .doctor-card .contact-info a[title="Chat on WhatsApp"]:hover {
      background: #0d34fd !important;
      color: #fff !important;
    }
    .navbar .btn.btn-primary {
      background: #0d6efd !important;
      border: none !important;
      color: #fff !important;
      box-shadow: 0 2px 8px rgba(13,110,253,0.08);
      border-radius: 12px;
      font-weight: 600;
      padding: 0.5rem 1.5rem;
      font-size: 1rem;
      transition: background 0.2s, color 0.2s;
    }
    .navbar .btn.btn-primary:hover,
    .navbar .btn.btn-primary:focus {
      background: #0d2efb !important;
      color: #fff !important;
    }
    .page-hero.bg-image.overlay-dark {
      position: relative;
      background-image: url('assets/New.jpg');
      background-size: cover;
      background-position: center;
      background-repeat: no-repeat;
      min-height: 420px;
    }
    .page-hero.bg-image.overlay-dark::before {
      content: '';
      position: absolute;
      top: 0; left: 0; width: 100%; height: 100%;
      background: rgba(30, 42, 70, 0.45);
      z-index: 1;
      pointer-events: none;
    }
    .page-hero .hero-section {
      position: relative;
      z-index: 2;
      color: #222;
      text-align: center;
      max-width: 800px;
    }
    .page-hero .hero-section .subhead {
      font-size: 1.5rem;
      font-weight: 600;
      color: #222;
      margin-bottom: 1.5rem;
      text-shadow: none;
    }
    .page-hero .hero-section h1 {
      font-size: 3.5rem;
      font-weight: 800;
      margin-bottom: 2rem;
      color: #111;
      text-shadow: none;
      letter-spacing: 1px;
    }
    @media (max-width: 600px) {
      .page-hero .hero-section h1 {
        font-size: 2.2rem;
      }
      .page-hero .hero-section {
        padding: 2.5rem 0.5rem 2rem 0.5rem;
      }
    }
    .services-section {
      padding: 4rem 0;
      background-color: transparent !important;
    }
    .services-section > .container > .section-title,
    .doctors-section > .container > .section-title {
      display: block;
      text-align: center;
      margin-left: auto;
      margin-right: auto;
    }
    header {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      z-index: 1000;
      background: rgba(255,255,255,0.8);
      box-shadow: none;
      min-height: 40px;
    }
    .navbar {
      min-height: 48px;
      padding-top: 0.2rem;
      padding-bottom: 0.2rem;
    }
    .see-doctors-btn {
      background: #0d6efd;
      color: #fff;
      border: none;
      border-radius: 25px;
      font-weight: 600;
      font-size: 1.15rem;
      padding: 0.75rem 2.5rem;
      box-shadow: 0 2px 8px rgba(13,110,253,0.08);
      transition: background 0.2s, color 0.2s, transform 0.15s;
      margin-top: 2rem;
      margin-bottom: 0.5rem;
      display: inline-block;
    }
    .see-doctors-btn:hover, .see-doctors-btn:focus {
      background: #0d2efb;
      color: #fff;
      transform: translateY(-2px) scale(1.04);
      text-decoration: none;
    }
  </style>
</head>
<body>

  <!-- Back to top button -->
  <div class="back-to-top"></div>

  <header>
   

    <nav class="navbar navbar-expand-lg navbar-light shadow-sm">
      <div class="container">
      <a class="navbar-brand"  href="#" style="font-size: 1.8rem; font-weight: bold;"><span style="color: #007bff; font-size: 2rem;">E</span>-Health</a>

        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupport" aria-controls="navbarSupport" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbarSupport">
          <ul class="navbar-nav ml-auto">
            
            <?php if (!isLoggedIn()) { ?>
            <li class="nav-item">
              <a class="btn btn-primary ml-lg-3" href="login.php">Login</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-primary ml-lg-3" href="register.php">Register</a>
            </li>
            <?php } else { ?>
            <li class="nav-item">
              <a class="btn btn-primary ml-lg-3" href="logout.php">Logout</a>
            </li>
            <?php } ?>
          </ul>
        </div>
      </div>
    </nav>
  </header>

  <div class="page-hero bg-image overlay-dark">
    <div class="hero-section">
      <div class="container text-center wow zoomIn">
        <span class="subhead">Your health is our Priority</span>
        <h1 class="display-4">Welcome to E-Health</h1>

        <?php if (isLoggedIn()) { ?>
          <a href="<?php 
            $role = $_SESSION['role'] ?? '';
            if ($role === 'doctor') echo 'doctor/dashboard.php';
            elseif ($role === 'patient') echo 'patient/dashboard.php';
            elseif ($role === 'pharmacist') echo 'pharmacy/dashboard.php';
            elseif ($role === 'lab') echo 'lab/dashboard.php';
            elseif ($role === 'admin') echo 'admin/dashboard.php';
            else echo '#';
          ?>" class="btn btn-primary">Go to Dashboard</a>
        <?php } ?>

        <?php if (!isLoggedIn()) { ?>
          <a href="#our-medical-team" class="btn btn-primary">See Our Doctors</a>

        <?php } ?>
      </div>
    </div>
  </div>



  <!-- About Section -->
  <div class="page-section pb-0">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6 py-3 wow fadeInUp">
          <h1>Welcome to Your Health Center</h1>
          <p class="text-grey mb-4">Our E-Health system is designed to streamline and improve the healthcare experience for both patients and healthcare providers. We understand the importance of efficient communication and data management in healthcare.</p>
          <p class="text-grey">Our mission is to provide a seamless, secure, and user-friendly platform that enhances the quality of healthcare delivery while maintaining the highest standards of patient privacy and data security.</p>
        </div>
        <div class="col-lg-6 wow fadeInRight" data-wow-delay="400ms">
          <div class="img-place custom-img-1">
            <img src="assets/img/bg-doctor.png" alt="">
          </div>
        </div>
      </div>
    </div>
  </div> 

  <!-- Features Section -->
  <section class="features-section">
    <div class="container">
      <div class="section-header text-center">
        <h2 class="section-title">Why Choose Us</h2>
      </div>
      <div class="features-grid">
        <div class="feature-card">
          <div class="feature-icon">
            <!-- Stethoscope SVG for Expert Doctors -->
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M7 3v7a5 5 0 0 0 10 0V3" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <path d="M12 17v2a3 3 0 0 1-6 0v-2" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <circle cx="18" cy="18" r="3" stroke="#fff" stroke-width="2"/>
            </svg>
          </div>
          <div class="feature-content">
            <h3>Expert Doctors</h3>
            <p>Access our team of highly qualified medical professionals</p>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <!-- Calendar SVG for Easy Appointments -->
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <rect x="3" y="5" width="18" height="16" rx="2" stroke="#fff" stroke-width="2"/>
              <path d="M16 3v4M8 3v4" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
              <path d="M3 9h18" stroke="#fff" stroke-width="2"/>
            </svg>
          </div>
          <div class="feature-content">
            <h3>Easy Appointments</h3>
            <p>Book appointments online with just a few clicks</p>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <!-- Headset SVG for 24/7 Support -->
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M4 15v-3a8 8 0 1 1 16 0v3" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              <rect x="2" y="15" width="4" height="6" rx="2" stroke="#fff" stroke-width="2"/>
              <rect x="18" y="15" width="4" height="6" rx="2" stroke="#fff" stroke-width="2"/>
              <path d="M8 21h8" stroke="#fff" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </div>
          <div class="feature-content">
            <h3>24/7 Support</h3>
            <p>Round-the-clock medical assistance</p>
          </div>
        </div>
        <div class="feature-card">
          <div class="feature-icon">
            <!-- Heart SVG for Patient Care -->
            <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
              <path d="M12 21s-6-4.35-9-7.5C-1.5 9 2.5 3 7.5 5.5 9.24 6.36 12 9 12 9s2.76-2.64 4.5-3.5C21.5 3 25.5 9 21 13.5c-3 3.15-9 7.5-9 7.5z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
          </div>
          <div class="feature-content">
            <h3>Patient Care</h3>
            <p>Comprehensive care for all your health needs</p>
          </div>
        </div>
      </div>
    </div>
  </section>



  <!-- Services Section -->
  <div class="services-section">
    <div class="container">
    <div class="section-header text-center">
        <h2 class="section-title">Our services</h2>
      </div>      <div class="row">
        <div class="col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <!-- Heart SVG for Cardiology -->
              <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 21s-6-4.35-9-7.5C-1.5 9 2.5 3 7.5 5.5 9.24 6.36 12 9 12 9s2.76-2.64 4.5-3.5C21.5 3 25.5 9 21 13.5c-3 3.15-9 7.5-9 7.5z" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Cardiology</h3>
            <p><i class="mai-heart-pulse"></i> Comprehensive heart care and treatment</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <!-- Brain SVG for Neurology -->
              <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M8 4a4 4 0 0 0-4 4v8a4 4 0 0 0 4 4m8-16a4 4 0 0 1 4 4v8a4 4 0 0 1-4 4M8 4v16m8-16v16" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Neurology</h3>
            <p><i class="mai-brain-wave"></i> Expert neurological care and diagnostics</p>
          </div>
        </div>
        <div class="col-md-4">
          <div class="service-card">
            <div class="service-icon">
              <!-- Lungs SVG for Pulmonology -->
              <svg width="38" height="38" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                <path d="M12 12v8M12 12l-4-4M12 12l4-4M8 8V4M16 8V4M4 20c0-4 4-8 8-8s8 4 8 8" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
              </svg>
            </div>
            <h3>Pulmonology</h3>
            <p><i class="mai-lungs"></i> Respiratory health and lung care</p>
          </div>
        </div>
      </div>
    </div>
  </div>



  <!-- Doctors Section -->
  <div class="doctors-section" id="our-medical-team">
    <div class="container">
    <div class="section-header text-center">
        <h2 class="section-title" >Our Medical Team</h2>
      </div>     
       <div class="row">
        <?php if (count($doctors) > 0): ?>
          <?php foreach ($doctors as $doctor): ?>
            <div class="col-md-6 col-lg-3">
              <div class="doctor-card">
                <div class="doctor-image" style="width:100%; height:260px; overflow:hidden; border-top-left-radius:16px; border-top-right-radius:16px;">
                  <img src="<?php 
                    if (!empty($doctor['profile_picture'])) {
                        echo (strpos($doctor['profile_picture'], 'upload') === 0) 
                            ? htmlspecialchars($doctor['profile_picture']) 
                            : 'uploads/profile_pictures/' . htmlspecialchars($doctor['profile_picture']);
                    } else {
                        echo 'assets/img/doctors/doctor_1.jpg';
                    }
                  ?>" alt="Dr. <?php echo htmlspecialchars($doctor['name']); ?>" style="width:100%; height:100%; object-fit:cover; display:block; border-top-left-radius:16px; border-top-right-radius:16px;">
                </div>
                <div class="doctor-info" style="padding: 0.7rem 1rem 1.1rem 1rem;">
                  <h3 style="font-size:1.1rem; margin-bottom:0.3rem; font-weight:700;">Dr. <?php echo htmlspecialchars($doctor['name']); ?></h3>
                  <p class="specialty" style="font-size:0.95rem; margin-bottom:0.5rem; color:#3498db; font-weight:500;"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                  <div class="schedule-info" style="margin-top: 0.7rem; text-align: left; font-size: 0.9rem;">
                    <h6 style="font-weight: 600; font-size: 0.95rem; margin-bottom: 0.4rem; color: #2c3e50;">Availability</h6>
                    <?php if (isset($schedules[$doctor['id']])): ?>
                      <ul style="list-style: none; padding-left: 0; margin-bottom: 0;">
                        <?php foreach ($schedules[$doctor['id']] as $schedule): ?>
                          <li style="margin-bottom: 0.2rem; display: flex; justify-content: space-between;">
                            <span style="font-weight: 500;"><?php echo htmlspecialchars($schedule['day_of_week']); ?>:</span>
                            <span style="color: #495057;"><?php echo date('g:i A', strtotime($schedule['start_time'])) . ' - ' . date('g:i A', strtotime($schedule['end_time'])); ?></span>
                          </li>
                        <?php endforeach; ?>
                      </ul>
                    <?php else: ?>
                      <p style="font-size: 0.9rem; color: #6c757d; margin-bottom: 0;">No schedule available.</p>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <div class="col-12 text-center">
            <p>No doctors available at the moment.</p>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>




  <!-- Footer -->
  <footer class="hospital-footer">
    <div class="container">
      <div class="row">
        <div class="col-md-4">
          <div class="footer-section">
            <h3>About Us</h3>
            <p>Our hospital is dedicated to providing exceptional healthcare services with compassion and excellence. We strive to create a healing environment that promotes wellness and recovery.</p>
          </div>
        </div>
<div class="col-md-4">
  
</div>      
        <div class="col-md-4">
          <div class="footer-section">
            <h3>Contact Information</h3>
            <ul class="contact-info">
              <li><i class="mai-location"></i> 123 Medical Street, Healthcare City</li>
              <li><i class="mai-call"></i> +1 (234) 567-8900</li>
              <li><i class="mai-mail"></i> info@hospital.com</li>
            </ul>
          </div>
        </div>
      </div>
      <div class="footer-bottom">
        <div class="row">
          <div class="col-md-6">
            <p>&copy; 2024 Healthcare Information System. All rights reserved.</p>
          </div>
          <div class="col-md-6 text-md-right">
            <ul class="social-links">
              <li><a href="#" title="Facebook"><i class="mai-logo-facebook"></i></a></li>
              <li><a href="#" title="Twitter"><i class="mai-logo-twitter"></i></a></li>
              <li><a href="#" title="Instagram"><i class="mai-logo-instagram"></i></a></li>
            </ul>
          </div>
        </div>
      </div>
    </div>
  </footer>

  <script src="assets/js/jquery-3.5.1.min.js"></script>
  <script src="assets/js/bootstrap.bundle.min.js"></script>
  <script src="assets/vendor/owl-carousel/js/owl.carousel.min.js"></script>
  <script src="assets/vendor/wow/wow.min.js"></script>
  <script src="assets/js/theme.js"></script>
  <script src="assets/js/main.js"></script>
  <script src="assets/js/doctor-search.js"></script>
  <script src="assets/js/appointment.js"></script>
  <script>
    // Smooth scroll to doctors section
    document.querySelector('a[href="#doctors-section"]').addEventListener('click', function(e) {
      e.preventDefault();
      document.querySelector('#doctors-section').scrollIntoView({
        behavior: 'smooth'
      });
    });

    // Add active class to contact button on hover to keep dropdown open
    const contactDropdown = document.getElementById('contactDropdown');
    if (contactDropdown) {
      const dropdownMenu = contactDropdown.nextElementSibling;
      
      // Show dropdown on hover
      contactDropdown.parentElement.addEventListener('mouseenter', function() {
        dropdownMenu.classList.add('show');
        contactDropdown.setAttribute('aria-expanded', 'true');
      });
      
      // Hide dropdown when mouse leaves the dropdown area
      contactDropdown.parentElement.addEventListener('mouseleave', function() {
        dropdownMenu.classList.remove('show');
        contactDropdown.setAttribute('aria-expanded', 'false');
      });
    }

    // Smooth scroll for See Our Doctors button
    var btn = document.getElementById('seeDoctorsBtn');
    if (btn) {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const doctorsSection = document.getElementById('doctors-section');
            if (doctorsSection) {
                doctorsSection.scrollIntoView({ 
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    }
  </script>
</body>
</html>

