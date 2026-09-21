<?php
// Database credentials
$host = "localhost";
$username = "root"; // Default XAMPP username
$password = "";     // Default XAMPP password

try {
    // Connect to MySQL server (without specifying a database yet)
    $conn = new PDO("mysql:host=$host", $username, $password);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 1. Drop existing database if it exists (Clean slate)
    $conn->exec("DROP DATABASE IF EXISTS boat_booking_system");
    
    // 2. Create the Database
    $conn->exec("CREATE DATABASE boat_booking_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $conn->exec("USE boat_booking_system");

    // 3. Create Users Table
    $table_users = "CREATE TABLE users (
        id INT AUTO_INCREMENT PRIMARY KEY,
        username VARCHAR(50) NOT NULL UNIQUE,
        email VARCHAR(100) NOT NULL UNIQUE,
        password VARCHAR(255) NOT NULL,
        full_name VARCHAR(100) NOT NULL,
        phone VARCHAR(20),
        role ENUM('admin', 'user') DEFAULT 'user',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($table_users);

    // 4. Create Boats Table (Updated with expanded categories)
    $table_boats = "CREATE TABLE boats (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        type ENUM('Speedboat', 'Yacht', 'Fishing Boat', 'Pontoon', 'Ship', 'Sailboat', 'Catamaran', 'Other') NOT NULL,
        capacity INT NOT NULL,
        price_per_hour DECIMAL(10, 2) NOT NULL,
        description TEXT,
        image VARCHAR(255) DEFAULT 'default_boat.jpg',
        available BOOLEAN DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $conn->exec($table_boats);

    // 5. Create Bookings Table (CRITICAL FIX: Added 'Approved' to ENUM)
    $table_bookings = "CREATE TABLE bookings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        boat_id INT NOT NULL,
        booking_date DATE NOT NULL,
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        total_price DECIMAL(10, 2) NOT NULL,
        status ENUM('Pending', 'Approved', 'Confirmed', 'Cancelled') DEFAULT 'Pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
        FOREIGN KEY (boat_id) REFERENCES boats(id) ON DELETE CASCADE
    )";
    $conn->exec($table_bookings);

    // 6. Insert Default Admin User
    $admin_password = password_hash('admin123', PASSWORD_BCRYPT);
    $insert_admin = "INSERT INTO users (username, email, password, full_name, phone, role) 
                     VALUES ('admin', 'admin@boatbooking.com', :password, 'System Administrator', '1234567890', 'admin')";
    $stmt = $conn->prepare($insert_admin);
    $stmt->execute([':password' => $admin_password]);

    // 7. Insert Sample Fleet Data
    $sample_boats = [
        ['Aqua Bullet', 'Speedboat', 4, 2000.00, 'High-speed thrill ride perfect for couples or small groups looking for adventure.', 'boat1.jpg'],
        ['Royal Voyager', 'Yacht', 20, 25000.00, 'Luxury yacht with premium amenities, perfect for corporate events and private parties.', 'boat2.jpg'],
        ['Deep Sea Hunter', 'Fishing Boat', 5, 2500.00, 'Fully equipped fishing vessel with sonar and live wells.', 'boat3.jpg'],
        ['Sunset Relaxer', 'Pontoon', 8, 1800.00, 'Stable and spacious pontoon, ideal for calm waters and family picnics.', 'boat4.jpg'],
        ['Ocean Whisper', 'Sailboat', 6, 3000.00, 'Experience the authentic sailing life with this beautiful wind-powered vessel.', 'boat5.jpg']
    ];

    $stmt = $conn->prepare("INSERT INTO boats (name, type, capacity, price_per_hour, description, image) VALUES (?, ?, ?, ?, ?, ?)");
    foreach ($sample_boats as $boat) {
        $stmt->execute($boat);
    }

    $success = true;

} catch(PDOException $e) {
    $success = false;
    $error_msg = $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Setup - BoatBooking</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #e0eafc; display: flex; justify-content: center; align-items: center; height: 100vh; margin: 0; }
        .setup-card { background: white; padding: 3rem; border-radius: 15px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); max-width: 500px; text-align: center; }
        .icon { font-size: 4rem; margin-bottom: 1rem; }
        h1 { color: #2c3e50; margin-bottom: 1rem; }
        p { color: #7f8c8d; line-height: 1.6; margin-bottom: 2rem; }
        .btn { display: inline-block; background: #3498db; color: white; padding: 0.8rem 2rem; border-radius: 8px; text-decoration: none; font-weight: bold; transition: 0.3s; }
        .btn:hover { background: #2980b9; transform: translateY(-2px); }
        .success-text { color: #27ae60; font-weight: bold; margin-bottom: 1rem; }
        .error-text { color: #e74c3c; font-weight: bold; margin-bottom: 1rem; }
    </style>
</head>
<body>

    <div class="setup-card">
        <?php if(isset($success) && $success): ?>
            <div class="icon">✅</div>
            <h1>Installation Complete</h1>
            <p class="success-text">Database schema has been successfully rebuilt!</p>
            <p>The <b>bookings</b> table has been upgraded to support the 3-step Authorization Flow ('Approved' status added).</p>
            
            <div style="background: #f8f9fa; padding: 1rem; border-radius: 8px; margin-bottom: 2rem; text-align: left; font-size: 0.9rem;">
                <strong>Admin Login:</strong><br>
                Email: admin@boatbooking.com<br>
                Password: admin123
            </div>

            <a href="login.php" class="btn">Go to Login</a>
        <?php else: ?>
            <div class="icon">❌</div>
            <h1>Installation Failed</h1>
            <p class="error-text">An error occurred during database setup.</p>
            <div style="background: #fee2e2; color: #991b1b; padding: 1rem; border-radius: 8px; text-align: left; font-size: 0.85rem; overflow-x: auto;">
                <?php echo $error_msg; ?>
            </div>
            <p style="margin-top: 1rem; font-size: 0.9rem;">Make sure your MySQL server (XAMPP/WAMP) is running.</p>
        <?php endif; ?>
    </div>

</body>
</html>