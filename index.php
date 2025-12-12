<?php
// This includes the db.php file, starts the session, and connects to the database
require_once 'includes/db.php';

// This includes the top bar, main navigation, and opening HTML tags
include 'includes/header.php';
?>

<a href="book_appointment.php" class="sticky-book-appointment">Book an Appointment</a>

<section class="hero-section">
    <div class="container">
        <div class="hero-text">
            <p class="breadcrumbs">Home / Our Hospital</p>
            <h1>Your Health, Our Priority</h1>
        </div>
    </div>
    <div class="hero-image-container">
        <img src="/doctor-appointment/images/hero-doctor.png" alt="Doctor" class="hero-doctor-image">
    </div>
</section>

<section class="search-section">
    <div class="search-form-container">
        <h2 class="section-title" style="margin-bottom: 20px;">Search Doctors and Book Your Appointment</h2>

        <form class="search-form" action="doctor_details.php" method="GET">
            <?php
            // Fetch distinct departments from the database to populate the first dropdown
            $departments_query = "SELECT DISTINCT specialization FROM doctors ORDER BY specialization ASC";
            $departments_result = $conn->query($departments_query);
            ?>
            <div class="form-group">
                <select id="department-select" name="department" required>
                    <option value="">Search By Department</option>
                    <?php
                    if ($departments_result->num_rows > 0) {
                        while($row = $departments_result->fetch_assoc()) {
                            echo '<option value="' . htmlspecialchars($row['specialization']) . '">' . htmlspecialchars($row['specialization']) . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
            <div class="form-group">
                <select id="doctor-select" name="id" required> <option value="">Select Department First</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">View Details</button>
        </form>
    </div>
</section>

<section id="our-doctors" class="doctors-grid-section">
    <div class="container">
        <h3 class="section-title">Our Expert Doctors 👨‍⚕️</h3>
        <div class="doctors-grid">
            <?php
            // SQL query to fetch all available doctors, including their image
            $sql_doctors = "SELECT u.id, u.full_name, d.specialization, d.fees, d.image 
                            FROM users u 
                            JOIN doctors d ON u.id = d.user_id 
                            WHERE u.role = 'doctor'";
            
            $result_doctors = $conn->query($sql_doctors);

            if ($result_doctors && $result_doctors->num_rows > 0) {
                // Loop through each doctor record and display it as a card
                while($doctor = $result_doctors->fetch_assoc()) {
                    // Determine which image to display (doctor's own or default)
                    $doctor_image_filename = $doctor['image'] ? htmlspecialchars($doctor['image']) : 'default_doctor.png';
            ?>
                    <div class="doctor-card">
                        <div class="doctor-card-img">
                            <img src="/doctor-appointment/images/<?php echo $doctor_image_filename; ?>" alt="<?php echo htmlspecialchars($doctor['full_name']); ?>">
                        </div>
                        <div class="doctor-card-content">
                            <h3><?php echo htmlspecialchars($doctor['full_name']); ?></h3>
                            <p class="doctor-dept"><?php echo htmlspecialchars($doctor['specialization']); ?></p>
                            <p class="doctor-qual">Fees: $<?php echo htmlspecialchars($doctor['fees']); ?></p>
                            <div class="doctor-card-buttons">
                                <a href="doctor_details.php?id=<?php echo $doctor['id']; ?>" class="btn btn-secondary">View Details</a>
                                <a href="book_appointment.php?doctor_id=<?php echo $doctor['id']; ?>" class="btn btn-primary">Book Now</a>
                            </div>
                        </div>
                    </div>
            <?php
                } // End of while loop
            } else {
                // Display a message if no doctors are found
                echo "<p style='text-align:center;'>No doctors are available at the moment. Add doctors from the admin panel.</p>";
            }
            ?>
        </div> </div> </section>

<hr class="section-divider">

<section id="services" class="services-section">
    <div class="container">
        <h3 class="section-title">Our Services</h3>
        <div class="services-grid">
            
            <div class="service-card">
                <i class="fas fa-heartbeat"></i>
                <h4>Cardiology</h4>
                <p>We provide advanced diagnostics and treatments for all heart-related conditions.</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-brain"></i>
                <h4>Neurology</h4>
                <p>Expert care for disorders of the nervous system, including the brain and spinal cord.</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-tooth"></i>
                <h4>Dental Care</h4>
                <p>Complete dental services, from routine checkups and cleanings to advanced oral surgery.</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-bone"></i>
                <h4>Orthopedics</h4>
                <p>Treatment for bone, joint, and muscle injuries and conditions to restore movement.</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-baby"></i>
                <h4>Pediatrics</h4>
                <p>Compassionate care for infants, children, and adolescents to ensure their healthy development.</p>
            </div>
            
            <div class="service-card">
                <i class="fas fa-eye"></i>
                <h4>Ophthalmology</h4>
                <p>Comprehensive eye care services, including vision testing and surgical procedures.</p>
            </div>

        </div>
    </div>
</section>

<section id="about-us" class="about-us-section">
    <div class="container">
        <h3 class="section-title">About Our Hospital</h3>
        <div class="about-us-content">
            <div class="about-us-text">
                <h4>Welcome to Your Trusted Health Partner</h4>
                <p>For over 20 years, our hospital has been a pillar of health and wellness in the community. We are dedicated to providing compassionate, world-class medical care with a patient-first approach.</p>
                <p>Our state-of-the-art facility is home to some of the most respected medical professionals in the country, specializing in a wide range of fields. From routine checkups to advanced surgical procedures, our mission is to ensure you and your loved ones receive the best care possible in a safe and welcoming environment.</p>
                <ul>
                    <li><i class="fas fa-check-circle"></i> 24/7 Emergency & Trauma Care</li>
                    <li><i class="fas fa-check-circle"></i> Advanced Diagnostic Labs</li>
                    <li><i class="fas fa-check-circle"></i> Patient-Centered Approach</li>
                </ul>
            </div>
            <div class="about-us-image">
                <img src="\doctor-appointment\images\hospital-building-modern-parking-lot-59693686.webp" alt="Our Hospital Building">
            </div>
        </div>
    </div>
</section>

<section id="contact-us" class="contact-us-section">
    <div class="container">
        <h3 class="section-title">Contact Us</h3>

        <?php
        // Check for a contact form submission status from the URL
        if (isset($_GET['contact'])) {
            if ($_GET['contact'] == 'success') {
                echo '<div class="form-message success" style="margin-bottom: 2rem; max-width: 900px; margin-left: auto; margin-right: auto;">Your message has been sent successfully! We will get back to you soon.</div>';
            } elseif ($_GET['contact'] == 'error') {
                echo '<div class="form-message error" style="margin-bottom: 2rem; max-width: 900px; margin-left: auto; margin-right: auto;">Sorry, there was an error sending your message. Please try again.</div>';
            } elseif ($_GET['contact'] == 'error_email') {
                echo '<div class="form-message error" style="margin-bottom: 2rem; max-width: 900px; margin-left: auto; margin-right: auto;">Please enter a valid email address.</div>';
            }
        }
        ?>

        <div class="contact-content-wrapper">
            
            <div class="contact-info-block">
                <h4>Location</h4>
                <p><i class="fas fa-map-marker-alt"></i> 360 Panchasayar, Kolkata - 700 094, West Bengal, India</p>

                <h4>Connect</h4>
                <p><i class="fas fa-phone"></i> 033 1111 2222 / 033 4444 0075 / 033 2432</p>
                <p style="margin-top: -10px;"><i class="fas fa-phone"></i> 4989 / 033 2462 2394</p>
                <p style="margin-top: -10px;"><i class="fas fa-phone"></i> 033 4033 3333</p>
                <p><i class="fas fa-envelope"></i> ph.enquiry@abchospital.com</p>

                <h4>Emergency Contact</h4>
                <p><i class="fas fa-phone"></i> +91 9999955555</p>

                <h4>International Helpdesk</h4>
                <p><i class="fas fa-phone"></i> 033 2462 2462</p>
                <p><i class="fas fa-envelope"></i> internationaldesk@abchospital.com</p>

                <h4>Follow Us</h4>
                <div class="social-icons">
                    <a href="#" target="_blank"><i class="fab fa-facebook-f"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-instagram"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-youtube"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-linkedin-in"></i></a>
                    <a href="#" target="_blank"><i class="fab fa-twitter"></i></a>
                </div>
            </div>

            <div class="contact-form-block">
                <h4>GET IN TOUCH</h4>
                
                <form action="handle_contact.php" method="POST" class="contact-form">
                    <input type="text" name="name" placeholder="Enter Your Name*" required>
                    <input type="email" name="email" placeholder="Enter Your Email Id*" required>
                    <input type="text" name="mobile" placeholder="Enter Your Mobile No*" required>
                    <input type="text" name="subject" placeholder="Enter Your Subject*" required>
                    <textarea name="message" placeholder="Write Your Message*" rows="5" required></textarea>
                    <button typeD="submit" class="btn btn-primary">Submit</button>
                </form>
            </div>

        </div>
    </div>
</section>

<?php 
// This includes the footer and closing HTML tags
include 'includes/footer.php'; 
?>