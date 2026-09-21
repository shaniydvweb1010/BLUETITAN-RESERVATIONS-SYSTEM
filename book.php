<?php
session_start();
require_once "config/database.php";

// 1. Security Check: Must be logged in
if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// 2. Security Check: Admins shouldn't be booking boats
if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: boats.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$boat_id = isset($_GET['boat_id']) ? (int)$_GET['boat_id'] : 0;

// Get boat details
$query = "SELECT * FROM boats WHERE id = ? AND available = 1";
$stmt = $db->prepare($query);
$stmt->execute([$boat_id]);
$boat = $stmt->fetch(PDO::FETCH_ASSOC);

// Redirect if boat doesn't exist or isn't available
if(!$boat) {
    header("Location: boats.php");
    exit();
}

$error = '';
$success = '';

// Handle Booking Submission
if($_POST) {
    $booking_date = $_POST['booking_date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];
    
    // Validate form data
    if(empty($booking_date) || empty($start_time) || empty($end_time)) {
        $error = "All fields are required.";
    } elseif(strtotime($booking_date) < strtotime(date('Y-m-d'))) {
        $error = "Booking date cannot be in the past.";
    } elseif(strtotime($end_time) <= strtotime($start_time)) {
        $error = "End time must be after start time.";
    } else {
        // Calculate total price
        $start = strtotime($start_time);
        $end = strtotime($end_time);
        $hours = ($end - $start) / 3600;
        
        if($hours <= 0) {
            $error = "Invalid time selection.";
        } else {
            $total_price = $hours * $boat['price_per_hour'];
            
            // Insert booking as 'Pending'
            $query = "INSERT INTO bookings (user_id, boat_id, booking_date, start_time, end_time, total_price, status) 
                      VALUES (?, ?, ?, ?, ?, ?, 'Pending')";
            $stmt = $db->prepare($query);
            
            try {
                if($stmt->execute([$_SESSION['user_id'], $boat_id, $booking_date, $start_time, $end_time, $total_price])) {
                    
                    $success = "🎉 Reservation request sent! Awaiting Admin approval.";
                    
                    // Redirect back to dashboard to wait for admin approval
                    echo "<script>setTimeout(() => { window.location.href = 'dashboard.php'; }, 2000);</script>";
                    
                    // Clear form
                    $_POST = array();
                } else {
                    $error = "❌ Booking failed. Please try again.";
                }
            } catch (PDOException $e) {
                $error = "❌ Booking error: " . $e->getMessage();
            }
        }
    }
}

// Define base URL
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/bbs/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reserve <?php echo htmlspecialchars($boat['name']); ?> - BoatBooking</title>
    <style>
        /* Shared Modern Variables */
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary-blue: #00d2ff;
            --dark-blue: #3a7bd5;
            --navy: #0f2027;
            --accent: #ff4b2b;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --gray: #ecf0f1;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --red: #e74c3c;
            --green: #27ae60;
            --shadow-sm: 0 4px 10px rgba(0,0,0,0.05);
            --shadow-lg: 0 15px 40px rgba(0,0,0,0.15);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            background: linear-gradient(-45deg, #f8f9fa, #e0eafc, #cfdef3);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @keyframes gradientBG { 0% {background-position: 0% 50%;} 50% {background-position: 100% 50%;} 100% {background-position: 0% 50%;} }

        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }

        /* Navbar */
        header {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px);
            padding: 1rem 0; position: sticky; top: 0; z-index: 1000; box-shadow: var(--shadow-sm);
        }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .nav-links { display: flex; list-style: none; gap: 1.5rem; align-items: center; }
        .nav-links a { color: var(--navy); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 20px; transition: 0.3s; }
        .nav-links a:hover { background: rgba(0, 210, 255, 0.1); color: var(--primary-blue); }

        /* Booking Layout */
        .booking-container {
            display: grid;
            grid-template-columns: 1fr 1.2fr;
            gap: 3rem;
            padding: 4rem 0;
            align-items: start;
            animation: slideUp 0.6s ease forwards;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* Boat Summary Card */
        .boat-summary-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 2.5rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.4);
            position: sticky;
            top: 100px;
        }

        .boat-summary-card h2 { color: var(--navy); margin-bottom: 1.5rem; font-size: 2rem; font-weight: 800; line-height: 1.2; }

        .boat-image-preview {
            width: 100%; height: 250px; border-radius: 15px; object-fit: cover;
            margin-bottom: 1.5rem; box-shadow: var(--shadow-sm);
        }

        .boat-details { display: flex; flex-direction: column; gap: 1rem; }
        .detail-item { display: flex; justify-content: space-between; align-items: center; padding: 0.8rem 0; border-bottom: 1px solid var(--gray); }
        .detail-item:last-child { border-bottom: none; }
        
        .detail-label { font-weight: 600; color: var(--text-dark); display: flex; align-items: center; gap: 0.5rem; }
        .detail-value { color: var(--text-light); font-weight: 500; }
        
        .price-highlight {
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white); padding: 0.5rem 1.2rem; border-radius: 10px;
            font-weight: bold; font-size: 1.2rem; box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
        }

        .boat-description { margin-top: 1.5rem; padding-top: 1.5rem; border-top: 1px solid var(--gray); color: var(--text-light); line-height: 1.6; font-size: 0.95rem; }

        /* Booking Form Card */
        .booking-form-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            padding: 3rem;
            box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255, 255, 255, 0.4);
        }

        .booking-form-card h1 { color: var(--navy); margin-bottom: 2rem; font-size: 2.2rem; display: flex; align-items: center; gap: 10px; }
        .booking-form-card p.subtitle { color: var(--text-light); margin-top: -1.5rem; margin-bottom: 2rem; }

        /* Alerts */
        .alert { padding: 1.2rem; border-radius: 12px; margin-bottom: 2rem; font-weight: 600; animation: fadeIn 0.5s ease; border-left: 4px solid transparent; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }
        .error { background: #fee2e2; color: #991b1b; border-left-color: var(--red); }
        .success { background: #dcfce7; color: #166534; border-left-color: var(--green); }

        /* Form Styles */
        .form-group { margin-bottom: 1.8rem; }
        .form-group label { display: block; margin-bottom: 0.7rem; font-weight: 600; color: var(--navy); display: flex; align-items: center; gap: 0.5rem; }
        
        input[type="date"], input[type="time"] {
            width: 100%; padding: 1.2rem; border: 2px solid var(--gray); border-radius: 12px;
            font-size: 1rem; transition: all 0.3s ease; background: var(--white); color: var(--navy); font-family: inherit;
        }
        input:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1); transform: translateY(-2px); }

        .time-inputs { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }

        /* Interactive Price Calculator */
        .price-calculator {
            background: linear-gradient(135deg, var(--navy), #1a2a3a);
            color: var(--white); padding: 2rem; border-radius: 15px; margin: 2rem 0;
            text-align: center; box-shadow: 0 10px 25px rgba(15, 32, 39, 0.3);
            position: relative; overflow: hidden; transition: all 0.3s ease;
        }
        
        .price-calculator::before {
            content: ''; position: absolute; top: -50%; left: -50%; width: 200%; height: 200%;
            background: radial-gradient(circle, rgba(0, 210, 255, 0.1) 0%, transparent 60%);
            animation: pulse 4s linear infinite; pointer-events: none;
        }

        @keyframes pulse { 0% { transform: scale(0.95); opacity: 0.5; } 50% { transform: scale(1.05); opacity: 0.8; } 100% { transform: scale(0.95); opacity: 0.5; } }

        .price-calculator h4 { margin-bottom: 0.5rem; font-size: 1.1rem; color: rgba(255,255,255,0.8); text-transform: uppercase; letter-spacing: 1px; }
        .calculated-price { font-size: 3rem; font-weight: 800; margin: 0.5rem 0; color: var(--primary-blue); text-shadow: 0 2px 10px rgba(0, 210, 255, 0.4); }
        .duration-display { font-size: 1rem; opacity: 0.9; }

        /* Button */
        .btn-submit {
            display: flex; justify-content: center; align-items: center; gap: 10px; width: 100%;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); color: var(--white);
            padding: 1.2rem 2.5rem; border: none; border-radius: 12px; font-size: 1.1rem;
            font-weight: 700; cursor: pointer; transition: all 0.3s ease;
            text-transform: uppercase; letter-spacing: 1px; box-shadow: 0 8px 20px rgba(0, 210, 255, 0.3);
        }
        .btn-submit:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0, 210, 255, 0.4); }
        .btn-submit:disabled { background: var(--gray); color: var(--text-light); cursor: not-allowed; box-shadow: none; transform: none; }

        /* Footer */
        footer { background: #0a141a; color: rgba(255,255,255,0.7); padding: 3rem 0; text-align: center; margin-top: auto; }

        /* Loading Animation */
        .loading { width: 20px; height: 20px; border: 3px solid rgba(255,255,255,.3); border-radius: 50%; border-top-color: var(--white); animation: spin 1s ease-in-out infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 968px) {
            .booking-container { grid-template-columns: 1fr; gap: 2rem; padding: 2rem 0; }
            .boat-summary-card { position: static; }
        }
        @media (max-width: 600px) {
            .time-inputs { grid-template-columns: 1fr; gap: 1rem; }
            .booking-form-card { padding: 2rem; }
        }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <a href="<?php echo $base_url; ?>index.php" class="logo">⛵ BoatBooking</a>
            <nav>
                <ul class="nav-links">
                    <li><a href="<?php echo $base_url; ?>index.php">Home</a></li>
                    <li><a href="<?php echo $base_url; ?>boats.php">Fleet</a></li>
                    <li><a href="<?php echo $base_url; ?>dashboard.php">Dashboard</a></li>
                    <li><a href="<?php echo $base_url; ?>profile.php">Profile</a></li>
                    <li><a href="<?php echo $base_url; ?>logout.php" style="color: var(--accent);">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <section class="container">
        <div class="booking-container">
            
            <div class="boat-summary-card">
                <h2><?php echo htmlspecialchars($boat['name']); ?></h2>
                
                <img src="classes/assets/images/<?php echo htmlspecialchars($boat['image']); ?>" 
                     alt="<?php echo htmlspecialchars($boat['name']); ?>" 
                     class="boat-image-preview"
                     onerror="this.src='https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80'">
                
                <div class="boat-details">
                    <div class="detail-item">
                        <span class="detail-label">🛥️ Vessel Class</span>
                        <span class="detail-value"><?php echo htmlspecialchars($boat['type']); ?></span>
                    </div>
                    <div class="detail-item">
                        <span class="detail-label">👥 Max Capacity</span>
                        <span class="detail-value"><?php echo htmlspecialchars($boat['capacity']); ?> Guests</span>
                    </div>
                    <div class="detail-item" style="margin-top: 0.5rem;">
                        <span class="detail-label">💰 Base Rate</span>
                        <span class="price-highlight">₹<?php echo number_format($boat['price_per_hour'], 2); ?> / hr</span>
                    </div>
                </div>

                <div class="boat-description">
                    <strong>About this vessel:</strong><br>
                    <?php echo htmlspecialchars($boat['description']); ?>
                </div>
            </div>

            <div class="booking-form-card">
                <h1>📅 Reserve Your Date</h1>
                <p class="subtitle">Complete the form below to send a reservation request to the Admin.</p>
                
                <?php if($error): ?>
                    <div class="alert error">⚠️ <?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert success"><?php echo $success; ?></div>
                <?php endif; ?>

                <form method="post" action="" id="bookingForm">
                    <div class="form-group">
                        <label for="booking_date">Select Date</label>
                        <input type="date" id="booking_date" name="booking_date" required 
                               min="<?php echo date('Y-m-d'); ?>" 
                               value="<?php echo isset($_POST['booking_date']) ? htmlspecialchars($_POST['booking_date']) : ''; ?>">
                    </div>

                    <div class="time-inputs">
                        <div class="form-group">
                            <label for="start_time">Boarding Time (Start)</label>
                            <input type="time" id="start_time" name="start_time" required
                                   value="<?php echo isset($_POST['start_time']) ? htmlspecialchars($_POST['start_time']) : ''; ?>">
                        </div>

                        <div class="form-group">
                            <label for="end_time">Return Time (End)</label>
                            <input type="time" id="end_time" name="end_time" required
                                   value="<?php echo isset($_POST['end_time']) ? htmlspecialchars($_POST['end_time']) : ''; ?>">
                        </div>
                    </div>

                    <div class="price-calculator" id="priceCalculator" style="display: none;">
                        <h4>Estimated Total</h4>
                        <div class="calculated-price" id="calculatedPrice">₹0.00</div>
                        <div class="duration-display" id="durationDisplay">0 hours</div>
                    </div>

                    <button type="submit" class="btn-submit" id="submitBtn" disabled>
                        <span id="btnText">Submit Request 📝</span>
                        <span id="btnLoading" style="display: none;" class="loading"></span>
                    </button>
                </form>
            </div>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> BoatBooking System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        const boatPricePerHour = <?php echo $boat['price_per_hour']; ?>;
        const bookingDate = document.getElementById('booking_date');
        const startTime = document.getElementById('start_time');
        const endTime = document.getElementById('end_time');
        const priceCalculator = document.getElementById('priceCalculator');
        const calculatedPrice = document.getElementById('calculatedPrice');
        const durationDisplay = document.getElementById('durationDisplay');
        const submitBtn = document.getElementById('submitBtn');

        // Prevent booking in the past for today
        const today = new Date().toISOString().split('T')[0];
        
        bookingDate.addEventListener('change', function() {
            if(this.value === today) {
                const now = new Date();
                const currentTime = now.getHours().toString().padStart(2, '0') + ':' + 
                                  now.getMinutes().toString().padStart(2, '0');
                startTime.min = currentTime;
                
                // Reset times if they violate new constraints
                if(startTime.value && startTime.value < currentTime) {
                    startTime.value = '';
                    endTime.value = '';
                }
            } else {
                startTime.min = '00:00';
            }
            calculatePrice();
        });

        // Trigger calculation on time change
        startTime.addEventListener('input', calculatePrice);
        endTime.addEventListener('input', calculatePrice);

        function calculatePrice() {
            const start = startTime.value;
            const end = endTime.value;
            const date = bookingDate.value;
            
            if (date && start && end) {
                const [startHours, startMinutes] = start.split(':').map(Number);
                const [endHours, endMinutes] = end.split(':').map(Number);
                
                let hours = endHours - startHours;
                let minutes = endMinutes - startMinutes;
                
                if (minutes < 0) {
                    hours--;
                    minutes += 60;
                }
                
                const totalHours = hours + (minutes / 60);
                
                if (totalHours > 0) {
                    const totalPrice = totalHours * boatPricePerHour;
                    
                    // Format currency nicely
                    calculatedPrice.textContent = `₹${totalPrice.toLocaleString('en-IN', {minimumFractionDigits: 2, maximumFractionDigits: 2})}`;
                    durationDisplay.textContent = `Duration: ${totalHours.toFixed(1)} hours`;
                    
                    priceCalculator.style.display = 'block';
                    submitBtn.disabled = false; // Enable checkout button
                } else {
                    showErrorState();
                }
            } else {
                priceCalculator.style.display = 'none';
                submitBtn.disabled = true;
            }
        }

        function showErrorState() {
            priceCalculator.style.display = 'none';
            submitBtn.disabled = true;
            if(startTime.value && endTime.value && startTime.value >= endTime.value) {
                // Minor visual feedback on the input fields
                endTime.style.borderColor = 'var(--red)';
                setTimeout(() => { endTime.style.borderColor = 'var(--gray)'; }, 2000);
            }
        }

        // Form submission loading state
        document.getElementById('bookingForm').addEventListener('submit', function() {
            if(submitBtn.disabled) return false;
            
            document.getElementById('btnText').textContent = 'Submitting...';
            document.getElementById('btnLoading').style.display = 'inline-block';
            submitBtn.style.opacity = '0.8';
        });

        // Run calculation on load if values exist (e.g., from validation fail redirect)
        document.addEventListener('DOMContentLoaded', function() {
            if (bookingDate.value && startTime.value && endTime.value) {
                calculatePrice();
            }
        });
    </script>
</body>
</html>