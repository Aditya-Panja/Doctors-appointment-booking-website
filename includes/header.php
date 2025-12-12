<?php

header("Cache-Control: no-cache, no-store, must-revalidate"); // HTTP 1.1.
header("Pragma: no-cache"); // HTTP 1.0.
header("Expires: 0"); // Proxies.

// Ensure the session is started, as it's needed to check login status
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Appointment System</title>
    <link rel="stylesheet" href="/doctor-appointment/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <script src="/doctor-appointment/js/script.js" defer></script>
</head>
<body>

    <div class="top-bar">
        <div class="container">
            <div class="contact-info">
                <span><i class="fas fa-phone-alt"></i> (033) 4012 7000</span>
                <span><i class="fas fa-envelope"></i> pnh.enquiry@abchospital.com</span>
            </div>
            <div class="top-right">
    <a href="https://www.google.com/maps/place/Heritage+CME+Building/@22.5172,88.4187955,17z/data=!4m14!1m7!3m6!1s0x3a0273f58b9feec5:0x30f8067b73c45d8!2sHeritage+Institute+of+Technology,+Kolkata!8m2!3d22.5172!4d88.4187955!16zL20vMGJnZjRx!3m5!1s0x3a02740a62054e8b:0x1683b7c9f2436d90!8m2!3d22.5176862!4d88.4187117!16s%2Fg%2F11gg9blxmf?entry=ttu&g_ep=EgoyMDI1MTIwNy4wIKXMDSoKLDEwMDc5MjA2OUgBUAM%3D" target="_blank" style="text-decoration: none; color: #555;">
        <i class="fas fa-map-marker-alt" style="color: #007bff;"></i> 360, Anandapur, Kolkata
    </a>
</div>
        </div>
    </div>

    <header class="main-header">
        <div class="container">
            <a href="/doctor-appointment/index.php" class="logo-link">
                <img src="images\images.png" alt="Hospital Logo" class="logo">
            </a>
            <nav class="main-nav">
                <ul>
                    <li><a href="/doctor-appointment/index.php#about-us">About Us</a></li>
                    <li><a href="#">Patients</a></li>
                    
                    <li class="nav-item-dropdown">
                        <a href="#">Departments</a>
                        <div class="dropdown-menu">
                            <div class="dropdown-container">
                                <?php
                                // This block fetches departments for the dropdown
                                // We need a new connection here because db.php isn't included yet
                                $db_host = defined('DB_SERVER') ? DB_SERVER : 'localhost';
                                $db_user = defined('DB_USERNAME') ? DB_USERNAME : 'root';
                                $db_pass = defined('DB_PASSWORD') ? DB_PASSWORD : '';
                                $db_name = defined('DB_NAME') ? DB_NAME : 'appointment_system';
                                
                                $header_conn = new mysqli($db_host, $db_user, $db_pass, $db_name);
                                
                                $dept_sql = "SELECT DISTINCT specialization FROM doctors ORDER BY specialization ASC";
                                $dept_result = $header_conn->query($dept_sql);
                                $departments = [];
                                if ($dept_result && $dept_result->num_rows > 0) {
                                    while($row = $dept_result->fetch_assoc()) {
                                        $departments[] = $row['specialization'];
                                    }
                                }
                                $header_conn->close();

                                $column_count = 3;
                                $items_per_column = ceil(count($departments) / $column_count);

                                for ($i = 0; $i < $column_count; $i++) {
                                    echo '<ul class="dropdown-column">';
                                    $column_items = array_slice($departments, $i * $items_per_column, $items_per_column);
                                    foreach ($column_items as $dept) {
                                        echo '<li><a href="/doctor-appointment/department_details.php?dept=' . urlencode($dept) . '">' . htmlspecialchars($dept) . '</a></li>';
                                    }
                                    echo '</ul>';
                                }
                                ?>
                            </div>
                        </div>
                    </li>
                    
                    <li><a href="/doctor-appointment/index.php#services">Services</a></li>
                    <li><a href="/doctor-appointment/index.php#our-doctors">Our Doctors</a></li>
                    
                    <li><a href="/doctor-appointment/index.php#contact-us">Contact Us</a></li>
                    
                    <?php if(isset($_SESSION["loggedin"]) && $_SESSION["loggedin"] === true): ?>
                        
                        <li>
                            <?php 
                            // Set dashboard link based on role
                            $dashboard_link = "dashboard.php";
                            if ($_SESSION["role"] === 'admin') {
                                $dashboard_link = "/doctor-appointment/admin/index.php";
                            } elseif ($_SESSION["role"] === 'doctor') {
                                $dashboard_link = "/doctor-appointment/doctor/dashboard.php";
                            }
                            ?>
                            <a href="<?php echo $dashboard_link; ?>" class="nav-btn nav-btn-primary">Dashboard</a>
                        </li>
                        <li>
                            <a href="/doctor-appointment/logout.php" class="nav-btn nav-btn-secondary">Logout</a>
                        </li>
                        
                    <?php else: ?>
                        
                        <li>
                            <a href="/doctor-appointment/login.php" class="nav-btn nav-btn-primary">Login</a>
                        </li>
                        <li>
                            <a href="/doctor-appointment/register.php" class="nav-btn nav-btn-secondary">Register</a>
                        </li>

                    <?php endif; ?>
                    </ul>
            </nav>
        </div>
    </header>
    
    <main class="container">