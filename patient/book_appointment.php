<?php
session_start();
require_once '../includes/database.php';
require_once '../includes/session_manager.php';

// Check if the user is logged in and is a patient
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'patient') {
    header('Location: ../login.php');
    exit();
}

// Fetch all doctors
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.specialization, COUNT(ds.id) AS schedule_count 
    FROM users u 
    LEFT JOIN doctor_schedule ds ON u.id = ds.doctor_id 
    WHERE u.role = 'doctor' 
    GROUP BY u.id 
    ORDER BY u.name
");
$stmt->execute();
$doctors = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

$page_title = 'Book Appointment';
include_once '../includes/header.php';
?>

<div class="container mt-4">
    <div class="row">
        <div class="col-md-12 mb-4">
            <div class="d-flex justify-content-between align-items-center">
                <h2><i class="fas fa-calendar-plus me-2"></i>Book an Appointment</h2>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <form id="appointmentForm">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="doctor" class="form-label">Select Doctor</label>
                                <select class="form-select" id="doctor" name="doctor_id" required>
                                    <option value="">-- Select a Doctor --</option>
                                    <?php foreach ($doctors as $doctor): ?>
                                        <?php if ($doctor['schedule_count'] > 0): ?>
                                            <option value="<?php echo $doctor['id']; ?>">
                                                Dr. <?php echo htmlspecialchars($doctor['name']); ?> 
                                                <?php if (!empty($doctor['specialization'])): ?>
                                                    (<?php echo htmlspecialchars($doctor['specialization']); ?>)
                                                <?php endif; ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="appointment_date" class="form-label">Appointment Date</label>
                                <input type="date" class="form-control" id="appointment_date" name="appointment_date" 
                                    min="<?php echo date('Y-m-d'); ?>" required>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="reason" class="form-label">Reason for Appointment</label>
                                <textarea class="form-control" id="reason" name="reason" rows="3" required></textarea>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label for="appointment_type" class="form-label">Appointment Type</label>
                                <select class="form-select" id="appointment_type" name="type">
                                    <option value="consultation">Consultation</option>
                                    <option value="follow-up">Follow-up</option>
                                    <option value="emergency">Emergency</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label for="time_slot" class="form-label">Available Time Slots</label>
                                <select class="form-select" id="time_slot" name="slot_time" required disabled>
                                    <option value="">-- Select Doctor & Date First --</option>
                                </select>
                            </div>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-12">
                                <label for="notes" class="form-label">Additional Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2"></textarea>
                            </div>
                        </div>

                        <div id="errorMessage" class="alert alert-danger" style="display: none;"></div>
                        <div id="successMessage" class="alert alert-success" style="display: none;"></div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary" id="bookButton">
                                <i class="fas fa-calendar-check me-2"></i>Book Appointment
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Variables to store form data
    let selectedDoctor = null;
    let selectedDate = null;
    
    // Handle doctor selection
    $('#doctor').change(function() {
        selectedDoctor = $(this).val();
        checkAndLoadTimeSlots();
    });
    
    // Handle date selection
    $('#appointment_date').change(function() {
        selectedDate = $(this).val();
        checkAndLoadTimeSlots();
    });
    
    // Load time slots when both doctor and date are selected
    function checkAndLoadTimeSlots() {
        const timeSlotSelect = $('#time_slot');
        
        // Reset time slot dropdown
        timeSlotSelect.html('<option value="">-- Loading Time Slots --</option>');
        timeSlotSelect.prop('disabled', true);
        
        // Hide any previous messages
        $('#errorMessage, #successMessage').hide();
        
        if (selectedDoctor && selectedDate) {
            // First check if the doctor is available on this day
            $.ajax({
                url: '../processes/get_available_days.php',
                type: 'GET',
                data: { doctor_id: selectedDoctor },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        // Check if the selected date is in the available dates
                        const isDateAvailable = response.data.some(date => date.date === selectedDate);
                        
                        if (isDateAvailable) {
                            // If date is available, fetch time slots
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
                                            $('#errorMessage').text('No available time slots for the selected date. Please choose another date.').show();
                                        }
                                    } else {
                                        timeSlotSelect.append('<option value="">Error loading time slots</option>');
                                        $('#errorMessage').text(response.message || 'Error loading time slots').show();
                                    }
                                },
                                error: function() {
                                    timeSlotSelect.html('<option value="">-- Error Loading Time Slots --</option>');
                                    $('#errorMessage').text('Error connecting to server. Please try again.').show();
                                }
                            });
                        } else {
                            timeSlotSelect.html('<option value="">Doctor not available on this day</option>');
                            $('#errorMessage').text('The doctor is not available on the selected date. Please choose another date.').show();
                        }
                    } else {
                        timeSlotSelect.html('<option value="">Error checking doctor availability</option>');
                        $('#errorMessage').text(response.message || 'Error checking doctor availability').show();
                    }
                },
                error: function() {
                    timeSlotSelect.html('<option value="">-- Error Checking Availability --</option>');
                    $('#errorMessage').text('Error connecting to server. Please try again.').show();
                }
            });
        } else {
            // If doctor or date not selected yet
            timeSlotSelect.html('<option value="">-- Select Doctor & Date First --</option>');
        }
    }
    
    // Form submission
    $('#appointmentForm').submit(function(e) {
        e.preventDefault();
        
        // Hide any previous messages
        $('#errorMessage, #successMessage').hide();
        
        // Disable submit button
        const submitBtn = $('#bookButton');
        submitBtn.prop('disabled', true);
        submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>Booking...');
        
        // Get form data
        const formData = {
            doctor_id: $('#doctor').val(),
            appointment_date: $('#appointment_date').val(),
            slot_time: $('#time_slot').val(),
            type: $('#appointment_type').val(),
            reason: $('#reason').val(),
            notes: $('#notes').val()
        };
        
        // Validate form
        if (!formData.doctor_id) {
            $('#errorMessage').text('Please select a doctor.').show();
            resetSubmitButton();
            return;
        }
        if (!formData.appointment_date) {
            $('#errorMessage').text('Please select an appointment date.').show();
            resetSubmitButton();
            return;
        }
        if (!formData.slot_time) {
            $('#errorMessage').text('Please select a time slot.').show();
            resetSubmitButton();
            return;
        }
        if (!formData.reason.trim()) {
            $('#errorMessage').text('Please provide a reason for the appointment.').show();
            resetSubmitButton();
            return;
        }
        
        // Submit appointment request
        $.ajax({
            url: '../processes/book_appointment.php',
            type: 'POST',
            data: formData,
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    $('#successMessage').text('Appointment booked successfully!').show();
                    $('#appointmentForm')[0].reset();
                    $('#time_slot').prop('disabled', true).html('<option value="">-- Select Doctor & Date First --</option>');
                    setTimeout(function() {
                        window.location.href = 'dashboard.php';
                    }, 2000);
                } else {
                    $('#errorMessage').text(response.message || 'Error booking appointment. Please try again.').show();
                }
            },
            error: function() {
                $('#errorMessage').text('Error connecting to server. Please try again.').show();
            },
            complete: function() {
                resetSubmitButton();
            }
        });
    });
    
    function resetSubmitButton() {
        const submitBtn = $('#bookButton');
        submitBtn.prop('disabled', false);
        submitBtn.html('<i class="fas fa-calendar-check me-2"></i>Book Appointment');
    }
});
</script>

<?php include_once '../includes/footer.php'; ?> 