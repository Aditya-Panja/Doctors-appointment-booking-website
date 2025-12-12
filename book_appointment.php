<?php
require_once 'includes/db.php';
// Security Check
if (!isset($_SESSION["loggedin"]) || $_SESSION["loggedin"] !== true || $_SESSION["role"] !== 'patient') {
    header("location: login.php");
    exit;
}

$doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;
$message = '';
$error = '';

// --- 1. GET DOCTOR DETAILS (FEE & NAME) ---
$doctor = null;
if($stmt = $conn->prepare("SELECT u.full_name, d.fees FROM users u JOIN doctors d ON u.id = d.user_id WHERE u.id = ? AND u.role = 'doctor'")) {
    $stmt->bind_param("i", $doctor_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if($result->num_rows == 1) {
        $doctor = $result->fetch_assoc();
        $doctor_name = $doctor['full_name'];
        $base_fee = $doctor['fees'];
    }
    $stmt->close();
}

if (!$doctor) {
    include 'includes/header.php';
    echo '<div class="form-message error">Doctor not found.</div>';
    include 'includes/footer.php';
    exit;
}

// --- 2. HANDLE BOOKING SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $patient_id = $_SESSION['id'];
    $appointment_date = $_POST['selected_date'];
    $appointment_time = $_POST['selected_time'];
    $coupon_code = $_POST['coupon_code'];
    $final_price = $base_fee;

    // --- 3. CHECK IF DATE/TIME ARE SELECTED ---
    if (empty($appointment_date) || empty($appointment_time)) {
    $error = "Please select a valid date and time slot before confirming.";
} elseif (strtotime("$appointment_date $appointment_time") < time()) {
    // --- NEW CHECK: Stop past bookings ---
    $error = "You cannot book an appointment in the past. Please select a future time.";
} else {
        // --- 4. RE-VALIDATE COUPON ON SERVER-SIDE ---
        if (!empty($coupon_code)) {
            $sql_code = "SELECT discount_percentage FROM discount_codes WHERE code = ? AND doctor_id = ? AND is_active = 1";
            if ($stmt_code = $conn->prepare($sql_code)) {
                $stmt_code->bind_param("si", $coupon_code, $doctor_id);
                $stmt_code->execute();
                $result_code = $stmt_code->get_result();
                if ($result_code->num_rows == 1) {
                    $discount = $result_code->fetch_assoc();
                    $discount_amount = ($base_fee * $discount['discount_percentage']) / 100;
                    $final_price = $base_fee - $discount_amount;
                }
            }
        }

        // --- 5. CHECK FOR CONFLICTS ---
        $sql_check = "SELECT id FROM appointments WHERE doctor_id = ? AND appointment_date = ? AND appointment_time = ? AND status IN ('scheduled', 'confirmed')";
        $stmt_check = $conn->prepare($sql_check);
        $stmt_check->bind_param("iss", $doctor_id, $appointment_date, $appointment_time);
        $stmt_check->execute();
        $stmt_check->store_result();
        
        if($stmt_check->num_rows > 0) {
            $error = "This time slot has just been booked. Please select a different time.";
        } else {
            // --- 6. LOGIC FOR 100% OFF vs. PAID ---
            if ($final_price <= 0) {
                // 100% OFF: Book immediately
                $sql_insert = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'confirmed')";
                if($stmt_insert = $conn->prepare($sql_insert)){
                    $stmt_insert->bind_param("iiss", $patient_id, $doctor_id, $appointment_date, $appointment_time);
                    if($stmt_insert->execute()){
                        $message = "Appointment booked successfully (Free)! You will be redirected.";
                        header("refresh:3;url=dashboard.php");
                    } else { $error = "Error: Could not book appointment."; }
                    $stmt_insert->close();
                }
            } else {
                // PAID APPOINTMENT:
                // --- SIMULATION: Book directly ---
                $sql_insert = "INSERT INTO appointments (patient_id, doctor_id, appointment_date, appointment_time, status) VALUES (?, ?, ?, ?, 'confirmed')"; // Set to 'confirmed' as payment is "done"
                if($stmt_insert = $conn->prepare($sql_insert)){
                    $stmt_insert->bind_param("iiss", $patient_id, $doctor_id, $appointment_date, $appointment_time);
                    if($stmt_insert->execute()){
                        $message = "Appointment booked (Payment Simulated)! Redirecting...";
                        header("refresh:3;url=dashboard.php");
                    } else { $error = "Error: Could not book appointment."; }
                    $stmt_insert->close();
                }
            }
        }
        $stmt_check->close();
    }
}

include 'includes/header.php';
?>

<div class="form-container">
    <h2>Book Appointment with <?php echo htmlspecialchars($doctor_name); ?></h2>
    
    <?php if($message): ?>
        <div class="form-message success"><?php echo $message; ?></div>
    <?php elseif($error): ?>
        <div class="form-message error"><?php echo $error; ?></div>
    <?php else: ?>
        <form action="book_appointment.php?doctor_id=<?php echo $doctor_id; ?>" method="post" id="booking-form">
            
            <label>Select a Day:</label>
            <div id="day-selector-grid" class="day-selector-grid">...</div>
            
            <label id="time-slot-label" style="display:none;">Select Time Slot:</label>
            <div id="time-slot-grid" class="time-slot-grid"></div>
            
            <div class="discount-section">
                <label for="coupon_code">Discount Code (optional):</label>
                <div class="discount-input-group">
                    <input type="text" id="coupon_code_input" name="coupon_code">
                    <button type="button" id="apply-coupon-btn" class="btn btn-secondary">Apply</button>
                </div>
                <p id="coupon-message"></p>
            </div>

            <div class="price-summary">
                <p>Base Fee: <span id="base-fee">$<?php echo number_format($base_fee, 2); ?></span></p>
                <p>Discount: <span id="discount-amount">-$0.00</span></p>
                <hr>
                <h4>Total Due: <span id="final-price">$<?php echo number_format($base_fee, 2); ?></span></h4>
            </div>
            
            <input type="hidden" id="selected_date" name="selected_date" value="">
            <input type="hidden" id="selected_time" name="selected_time" value="">
            
            <button type="submit" id="confirm-booking-btn" class="btn btn-primary" style="width:100%;">Confirm Booking</button>
        </form>
    <?php endif; ?>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const doctorId = <?php echo $doctor_id; ?>;
    const dayGrid = document.getElementById('day-selector-grid');
    const slotGrid = document.getElementById('time-slot-grid');
    const slotLabel = document.getElementById('time-slot-label');
    const selectedDateInput = document.getElementById('selected_date');
    const selectedTimeInput = document.getElementById('selected_time');
    
    // New discount elements
    const couponInput = document.getElementById('coupon_code_input');
    const applyBtn = document.getElementById('apply-coupon-btn');
    const couponMsg = document.getElementById('coupon-message');
    const baseFeeEl = document.getElementById('base-fee');
    const discountEl = document.getElementById('discount-amount');
    const finalPriceEl = document.getElementById('final-price');
    
    // --- THIS IS THE FIX ---
    // 1. Get the confirm button
    const confirmBtn = document.getElementById('confirm-booking-btn');
    
    // 2. Disable the button by default
    confirmBtn.disabled = true;
    confirmBtn.style.opacity = '0.6';
    confirmBtn.style.cursor = 'not-allowed';
    // --- END FIX ---

    // --- FUNCTION 1: Load 7-Day Availability ---
    function loadUpcomingAvailability() {
        // ... (rest of this function is unchanged) ...
        dayGrid.innerHTML = '<p>Loading availability...</p>';
        fetch('ajax_get_upcoming_availability.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ doctor_id: doctorId })
        })
        .then(response => response.json())
        .then(days => {
            dayGrid.innerHTML = '';
            days.forEach(day => {
                const dayButton = document.createElement('button');
                dayButton.type = 'button';
                dayButton.className = 'day-card';
                dayButton.dataset.date = day.date;
                dayButton.innerHTML = `<span class="day-name">${day.day_name}</span><span class="day-num">${day.day_num}</span>`;
                if (day.available_slots > 0) {
                    dayButton.innerHTML += `<span class="slots-available">${day.available_slots} slots</span>`;
                    dayButton.addEventListener('click', function() {
                        dayGrid.querySelectorAll('.day-card').forEach(btn => btn.classList.remove('active'));
                        this.classList.add('active');
                        selectedDateInput.value = this.dataset.date;
                        selectedTimeInput.value = '';
                        
                        // --- THIS IS THE FIX ---
                        // 3. Disable button when a day is clicked (time not yet selected)
                        confirmBtn.disabled = true;
                        confirmBtn.style.opacity = '0.6';
                        confirmBtn.style.cursor = 'not-allowed';
                        // --- END FIX ---

                        fetchSlotsForDate(this.dataset.date);
                    });
                } else {
                    dayButton.innerHTML += `<span class="slots-unavailable">No Slots</span>`;
                    dayButton.disabled = true;
                    dayButton.classList.add('disabled');
                }
                dayGrid.appendChild(dayButton);
            });
        });
    }

    // --- FUNCTION 2: Fetch Time Slots for Selected Date ---
    function fetchSlotsForDate(selectedDate) {
        // ... (rest of this function is unchanged) ...
        slotGrid.innerHTML = '<p>Loading time slots...</p>';
        slotLabel.style.display = 'block';
        fetch('ajax_get_slots.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ doctor_id: doctorId, date: selectedDate })
        })
        .then(response => response.json())
        .then(slots => {
            slotGrid.innerHTML = '';
            if (slots.length > 0) {
                slots.forEach(slot => {
                    const slotButton = document.createElement('button');
                    slotButton.type = 'button';
                    slotButton.className = `slot ${slot.status}`;
                    const [hours, minutes] = slot.time.split(':');
                    const d = new Date(1970, 0, 1, hours, minutes);
                    const displayTime = d.toLocaleTimeString('en-US', { hour: 'numeric', minute: '2-digit', hour12: true });
                    slotButton.textContent = displayTime;
                    slotButton.dataset.time = slot.time;
                    if (slot.status === 'booked') {
                        slotButton.disabled = true;
                    } else {
                        slotButton.addEventListener('click', function() {
                            slotGrid.querySelectorAll('.slot.active').forEach(btn => btn.classList.remove('active'));
                            this.classList.add('active');
                            selectedTimeInput.value = this.dataset.time;

                            // --- THIS IS THE FIX ---
                            // 4. Enable the button only when a time slot is clicked
                            confirmBtn.disabled = false;
                            confirmBtn.style.opacity = '1';
                            confirmBtn.style.cursor = 'pointer';
                            // --- END FIX ---
                        });
                    }
                    slotGrid.appendChild(slotButton);
                });
            } else {
                slotGrid.innerHTML = '<p>No available slots for this day.</p>';
            }
        });
    }

    // --- FUNCTION 3: Apply Coupon Code ---
    applyBtn.addEventListener('click', function() {
        // ... (rest of this function is unchanged) ...
        const code = couponInput.value.trim();
        if (!code) {
            couponMsg.textContent = 'Please enter a code.';
            couponMsg.className = 'error';
            return;
        }
        fetch('ajax_apply_coupon.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ coupon_code: code, doctor_id: doctorId })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                couponMsg.textContent = data.message;
                couponMsg.className = 'success';
                baseFeeEl.textContent = '$' + data.base_fee;
                discountEl.textContent = '-$' + data.discount_amount;
                finalPriceEl.textContent = '$' + data.final_price;
                if (parseFloat(data.final_price) <= 0) {
                    confirmBtn.textContent = 'Confirm Free Booking';
                } else {
                    confirmBtn.textContent = 'Proceed to Pay $' + data.final_price;
                }
            } else {
                couponMsg.textContent = data.message;
                couponMsg.className = 'error';
            }
        });
    });

    // Initial load
    loadUpcomingAvailability();
});
</script>

<?php include 'includes/footer.php'; ?>