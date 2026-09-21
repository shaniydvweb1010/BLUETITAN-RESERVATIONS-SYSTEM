<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin') {
    header("Location: admin.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$msg = '';

// Handle Booking Cancellation (Supports Pending, Approved, AND Confirmed)
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_booking'])) {
    $booking_id = $_POST['booking_id'];
    
    $stmt = $db->prepare("UPDATE bookings SET status = 'Cancelled' WHERE id = ? AND user_id = ? AND status IN ('Pending', 'Approved', 'Confirmed')");
    
    if ($stmt->execute([$booking_id, $_SESSION['user_id']]) && $stmt->rowCount() > 0) {
        $msg = "<div class='alert success'>✅ Booking #$booking_id has been successfully cancelled.</div>";
    } else {
        $msg = "<div class='alert error'>❌ Cannot cancel this booking. It may already be cancelled.</div>";
    }
}

// SECURE QUERY: Using LEFT JOIN so if a boat is deleted by Admin, the booking history still loads
$query = "SELECT b.*, bt.name as boat_name, bt.type as boat_type, bt.image as boat_image
          FROM bookings b 
          LEFT JOIN boats bt ON b.boat_id = bt.id 
          WHERE b.user_id = ? 
          ORDER BY b.created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute([$_SESSION['user_id']]);
$bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);

// SECURE STATS: Coalesce handles nulls safely
$stats_query = "SELECT 
    COUNT(*) as total_bookings,
    COALESCE(SUM(CASE WHEN status = 'Confirmed' THEN total_price ELSE 0 END), 0) as total_spent,
    COUNT(CASE WHEN status = 'Pending' THEN 1 END) as pending_bookings
    FROM bookings WHERE user_id = ?";
$stats_stmt = $db->prepare($stats_query);
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch(PDO::FETCH_ASSOC);

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/bbs/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Dashboard - BoatBooking</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-blue: #00d2ff; --dark-blue: #3a7bd5; --navy: #0f2027; --white: #ffffff;
            --text-dark: #2c3e50; --text-light: #7f8c8d; --red: #e74c3c; --green: #27ae60; --orange: #f39c12;
            --shadow-sm: 0 4px 10px rgba(0,0,0,0.05); --shadow-lg: 0 15px 30px rgba(0,0,0,0.1);
        }
        body { font-family: 'Segoe UI', sans-serif; background: #f4f7f6; color: var(--text-dark); min-height: 100vh; display: flex; flex-direction: column; }
        .container { width: 90%; max-width: 1200px; margin: 0 auto; padding: 0 15px; }

        header { background: rgba(255, 255, 255, 0.95); padding: 1rem 0; box-shadow: var(--shadow-sm); }
        header .container { display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.8rem; font-weight: bold; color: var(--dark-blue); text-decoration: none; }
        .nav-links { display: flex; list-style: none; gap: 1.5rem; }
        .nav-links a { color: var(--navy); text-decoration: none; font-weight: 600; transition: 0.3s; }
        .nav-links a.active { color: var(--primary-blue); }

        .dashboard-header { background: var(--navy); color: white; padding: 3rem 0 6rem; text-align: center; }
        .dashboard-header h1 { font-size: 2.5rem; margin-bottom: 0.5rem; }

        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 1.5rem; margin-top: -3rem; position: relative; }
        .stat-card { background: white; padding: 2rem; border-radius: 15px; box-shadow: var(--shadow-lg); display: flex; align-items: center; gap: 1.5rem; }
        .stat-icon { width: 60px; height: 60px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 2rem; background: #f0f4f8; }
        .stat-info h3 { font-size: 1.8rem; color: var(--navy); }
        .stat-info p { color: var(--text-light); font-weight: bold; font-size: 0.8rem; text-transform: uppercase; }

        .quick-actions { margin: 2rem 0; display: flex; gap: 1rem; }
        .action-btn { background: white; padding: 1rem 2rem; border-radius: 10px; text-decoration: none; color: var(--navy); font-weight: bold; box-shadow: var(--shadow-sm); border: 1px solid #ddd; }
        .action-btn:hover { border-color: var(--primary-blue); color: var(--primary-blue); }

        .bookings-grid { display: flex; flex-direction: column; gap: 1.5rem; margin-bottom: 4rem; }
        .booking-card { background: white; border-radius: 15px; padding: 1.5rem; box-shadow: var(--shadow-sm); display: flex; gap: 2rem; align-items: center; border: 1px solid #eee; }
        .booking-img { width: 160px; height: 100px; border-radius: 10px; object-fit: cover; background: #eee; }
        .booking-details { flex: 1; }
        .booking-details h3 { font-size: 1.4rem; color: var(--navy); margin-bottom: 0.5rem; }
        .booking-meta { display: flex; gap: 1.5rem; color: var(--text-light); font-size: 0.95rem; margin-bottom: 0.5rem; }
        .booking-price { font-size: 1.4rem; font-weight: bold; color: var(--dark-blue); }

        .booking-actions { display: flex; flex-direction: column; gap: 0.8rem; min-width: 180px; text-align: center; }
        .badge { padding: 0.5rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; text-transform: uppercase; }
        
        .status-Pending { background: #fff3cd; color: #856404; }
        .status-Approved { background: #e1f5fe; color: #0288d1; border: 1px solid #b3e5fc; }
        .status-Confirmed { background: #dcfce7; color: #166534; }
        .status-Cancelled { background: #fee2e2; color: #991b1b; }

        .btn-green { background: var(--green); color: white; padding: 0.6rem; border-radius: 8px; text-decoration: none; font-weight: bold; display: block;}
        .btn-blue { background: var(--primary-blue); color: white; padding: 0.6rem; border-radius: 8px; text-decoration: none; font-weight: bold; display: block;}
        .btn-cancel { background: transparent; border: 2px solid var(--red); color: var(--red); padding: 0.5rem; border-radius: 8px; cursor: pointer; font-weight: bold; width: 100%; transition: 0.3s; }
        .btn-cancel:hover { background: var(--red); color: white; }

        .alert { padding: 1rem; border-radius: 8px; margin: 1rem 0; font-weight: bold; text-align: center; }
        .success { background: #dcfce7; color: #166534; }
        .error { background: #fee2e2; color: #991b1b; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <a href="index.php" class="logo">⛵ BoatBooking</a>
            <nav>
                <ul class="nav-links">
                    <li><a href="index.php">Home</a></li>
                    <li><a href="boats.php">Fleet</a></li>
                    <li><a href="dashboard.php" class="active">Dashboard</a></li>
                    <li><a href="profile.php">Profile</a></li>
                    <li><a href="logout.php">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="dashboard-header">
        <div class="container">
            <h1>Welcome aboard, <?= htmlspecialchars(explode(' ', $_SESSION['full_name'])[0] ?? 'User') ?>! 👋</h1>
            <p>Your oceanic adventures at a glance.</p>
        </div>
    </div>

    <section class="container">
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon">📊</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['total_bookings'] ?></h3><p>Total Bookings</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">💰</div>
                <div class="stat-info">
                    <h3>₹<?= number_format((float)$stats['total_spent'], 2) ?></h3><p>Confirmed Spending</p>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon">⏳</div>
                <div class="stat-info">
                    <h3><?= (int)$stats['pending_bookings'] ?></h3><p>Pending Requests</p>
                </div>
            </div>
        </div>

        <?= $msg ?>

        <div class="quick-actions">
            <a href="boats.php" class="action-btn">🚤 Reserve New Boat</a>
            <a href="profile.php" class="action-btn">👤 Edit Profile</a>
        </div>

        <h2 style="margin-bottom:1rem;">🎫 My Reservations</h2>
        
        <div class="bookings-grid">
            <?php if(empty($bookings)): ?>
                <div style="text-align:center; padding: 4rem; background:white; border-radius:15px;">
                    <h3 style="color:var(--text-light);">No reservations yet. Let's get you on the water!</h3>
                </div>
            <?php else: ?>
                <?php foreach($bookings as $b): 
                    // Safely trim the status to avoid invisible space matching bugs
                    $status = trim($b['status']);
                ?>
                    <div class="booking-card">
                        
                        <img src="classes/assets/images/<?= htmlspecialchars($b['boat_image'] ?? 'default.jpg') ?>" 
                             alt="Boat" class="booking-img"
                             onerror="this.src='https://images.unsplash.com/photo-1544644181-1484b3fdfc62?ixlib=rb-4.0.3&auto=format&fit=crop&w=400&q=80'">
                        
                        <div class="booking-details">
                            <h3><?= htmlspecialchars($b['boat_name'] ?? 'Unknown Boat (Deleted)') ?></h3>
                            <div class="booking-meta">
                                <span>📅 <?= date('M j, Y', strtotime($b['booking_date'])) ?></span>
                                <span>⏱️ <?= date('g:i A', strtotime($b['start_time'])) ?></span>
                                <span>#️⃣ ID: <?= $b['id'] ?></span>
                            </div>
                            <div class="booking-price">₹<?= number_format((float)$b['total_price'], 2) ?></div>
                        </div>

                        <div class="booking-actions">
                            <div class="badge status-<?= $status ?>"><?= htmlspecialchars($status) ?></div>
                            
                            <?php if($status === 'Pending'): ?>
                                <button disabled class="btn-cancel" style="border: 1px dashed #ccc; color: #95a5a6; cursor: not-allowed; text-transform: none;">⏳ Awaiting Approval</button>
                                <form method="POST" onsubmit="return confirm('Cancel this pending request?');">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn-cancel">Cancel Request</button>
                                </form>

                            <?php elseif($status === 'Approved'): ?>
                                <a href="payment.php?id=<?= $b['id'] ?>" class="btn-green">💳 Pay Now</a>
                                <form method="POST" onsubmit="return confirm('Cancel this approved booking?');">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn-cancel">Cancel Booking</button>
                                </form>

                            <?php elseif($status === 'Confirmed'): ?>
                                <a href="ticket.php?id=<?= $b['id'] ?>" class="btn-blue">🎟️ Download Ticket</a>
                                <form method="POST" onsubmit="return confirm('Are you sure you want to cancel this paid booking?');">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <button type="submit" name="cancel_booking" class="btn-cancel">Cancel Booking</button>
                                </form>

                            <?php elseif($status === 'Cancelled'): ?>
                                <a href="book.php?boat_id=<?= $b['boat_id'] ?>" class="btn-green" style="text-align: center;">Book Again</a>
                            
                            <?php else: ?>
                                <span style="color: #e74c3c; font-size: 0.8rem;">Status Error: Unknown</span>
                            <?php endif; ?>
                        </div>

                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>
</body>
</html>