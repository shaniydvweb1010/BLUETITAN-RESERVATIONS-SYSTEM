<?php
session_start();
require_once "config/database.php";
require_once "classes/User.php";

// 1. Security Check: Must be logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user = new User($db);

$error = '';
$success = '';

// Get current user data
$user_data = $user->getUserById($_SESSION['user_id']);
if (!$user_data) {
    session_destroy();
    header("Location: login.php");
    exit();
}

// --- HANDLE PROFILE UPDATE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_profile'])) {
    $user->id = $_SESSION['user_id'];
    $user->full_name = trim($_POST['full_name']);
    $user->email = trim($_POST['email']);
    $user->phone = trim($_POST['phone']);
    
    if (!filter_var($user->email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } else {
        // Check if email belongs to someone else
        $check_email = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $check_email->execute([$user->email, $_SESSION['user_id']]);
        
        if ($check_email->rowCount() > 0) {
            $error = "Email already in use by another account.";
        } else {
            if ($user->updateProfile()) {
                $success = "Profile updated successfully!";
                $_SESSION['full_name'] = $user->full_name; // Update session name
                $user_data = $user->getUserById($_SESSION['user_id']); // Refresh data
            } else {
                $error = "Failed to update profile. Please try again.";
            }
        }
    }
}

// --- HANDLE PASSWORD CHANGE ---
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['change_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    if ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    } elseif (strlen($new_password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } else {
        if ($user->verifyPassword($_SESSION['user_id'], $current_password)) {
            if ($user->updatePassword($_SESSION['user_id'], $new_password)) {
                $success = "Password updated successfully!";
            } else {
                $error = "Failed to update password. Please try again.";
            }
        } else {
            $error = "Current password is incorrect.";
        }
    }
}

// --- FETCH USER BOOKINGS ---
$bookings_query = "SELECT b.*, bt.name as boat_name, bt.image as boat_image, bt.type as boat_type 
                   FROM bookings b 
                   JOIN boats bt ON b.boat_id = bt.id 
                   WHERE b.user_id = ? 
                   ORDER BY b.created_at DESC";
$bookings_stmt = $db->prepare($bookings_query);
$bookings_stmt->execute([$_SESSION['user_id']]);
$user_bookings = $bookings_stmt->fetchAll();

// Define base URL
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/bbs/';

// Generate Avatar Initials
$names = explode(' ', $user_data['full_name']);
$initials = '';
foreach($names as $n) { 
    if(!empty($n)) $initials .= strtoupper(substr($n, 0, 1)); 
}
$initials = substr($initials, 0, 2);
if(empty($initials)) $initials = "U";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - BoatBooking</title>
    <style>
        /* Shared Modern Variables */
        * { margin: 0; padding: 0; box-sizing: border-box; }
        :root {
            --primary-blue: #00d2ff;
            --dark-blue: #3a7bd5;
            --navy: #0f2027;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --gray: #ecf0f1;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --red: #e74c3c;
            --green: #27ae60;
            --accent: #ff4b2b;
            --shadow-sm: 0 4px 10px rgba(0,0,0,0.05);
            --shadow-lg: 0 15px 30px rgba(0,0,0,0.1);
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

        /* Navbar */
        header {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px);
            padding: 1rem 0; position: sticky; top: 0; z-index: 1000; box-shadow: var(--shadow-sm);
        }
        header .container { display: flex; justify-content: space-between; align-items: center; max-width: 1200px; margin: 0 auto; width: 90%; }
        .logo { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .nav-links { display: flex; list-style: none; gap: 1.5rem; align-items: center; }
        .nav-links a { color: var(--navy); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 20px; transition: 0.3s; }
        .nav-links a:hover, .nav-links a.active { background: rgba(0, 210, 255, 0.1); color: var(--primary-blue); }

        /* Layout */
        .profile-wrapper {
            display: grid; grid-template-columns: 320px 1fr; gap: 2rem;
            max-width: 1100px; width: 90%; margin: 3rem auto; flex: 1;
            animation: slideUp 0.6s ease forwards;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(30px); } to { opacity: 1; transform: translateY(0); } }

        /* Sidebar */
        .profile-sidebar {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px);
            border-radius: 20px; padding: 2.5rem 2rem; text-align: center;
            box-shadow: var(--shadow-lg); border: 1px solid rgba(255,255,255,0.4);
            height: fit-content;
        }

        .avatar {
            width: 120px; height: 120px; border-radius: 50%;
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: white; font-size: 2.5rem; font-weight: bold;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.5rem; box-shadow: 0 8px 20px rgba(0, 210, 255, 0.3);
            border: 4px solid var(--white);
        }

        .profile-name { font-size: 1.5rem; color: var(--navy); margin-bottom: 0.2rem; font-weight: 800; }
        .profile-username { color: var(--text-light); font-size: 0.95rem; margin-bottom: 1.5rem; }
        
        .role-badge {
            display: inline-block; padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.85rem; font-weight: bold; margin-bottom: 1.5rem; text-transform: uppercase; letter-spacing: 1px;
        }
        .role-admin { background: rgba(231, 76, 60, 0.1); color: var(--red); }
        .role-user { background: rgba(0, 210, 255, 0.1); color: var(--dark-blue); }

        .join-date { font-size: 0.85rem; color: var(--text-light); border-top: 1px solid var(--gray); padding-top: 1rem; }

        /* Main Content */
        .profile-content {
            background: rgba(255, 255, 255, 0.9); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: var(--shadow-lg);
            border: 1px solid rgba(255,255,255,0.4); overflow: hidden;
        }

        /* Tabs */
        .tabs-header { display: flex; background: rgba(248, 249, 250, 0.5); border-bottom: 1px solid var(--gray); }
        .tab-btn {
            flex: 1; padding: 1.2rem; text-align: center; font-weight: 600; color: var(--text-light);
            cursor: pointer; transition: 0.3s; border-bottom: 3px solid transparent;
        }
        .tab-btn:hover { color: var(--dark-blue); background: rgba(0,0,0,0.02); }
        .tab-btn.active { color: var(--dark-blue); border-bottom-color: var(--primary-blue); background: var(--white); }

        .tab-body { padding: 2.5rem; display: none; animation: fadeIn 0.4s ease; }
        .tab-body.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; } to { opacity: 1; } }

        .section-header { margin-bottom: 2rem; }
        .section-header h2 { color: var(--navy); font-size: 1.8rem; font-weight: 800; }
        .section-header p { color: var(--text-light); font-size: 0.95rem; }

        /* Forms */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: var(--navy); }
        
        input {
            width: 100%; padding: 0.9rem 1.2rem; border: 2px solid var(--gray); border-radius: 12px;
            font-size: 1rem; transition: 0.3s; background: var(--white); color: var(--navy);
        }
        input:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1); }
        input[readonly] { background: var(--light-gray); color: var(--text-light); cursor: not-allowed; }

        .password-container { position: relative; }
        .toggle-password { position: absolute; right: 15px; top: 50%; transform: translateY(-50%); background: none; border: none; cursor: pointer; color: var(--text-light); }

        /* Buttons */
        .btn-submit {
            padding: 1rem 2rem; background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white); border: none; border-radius: 12px; font-size: 1rem; font-weight: 700;
            cursor: pointer; transition: 0.3s; box-shadow: 0 8px 20px rgba(0, 210, 255, 0.3); display: inline-flex; align-items: center; gap: 8px;
        }
        .btn-submit:hover { transform: translateY(-2px); box-shadow: 0 12px 25px rgba(0, 210, 255, 0.4); }

        /* Alerts */
        .alert { padding: 1rem 1.2rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600; font-size: 0.95rem; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--red); }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid var(--green); }

        /* Booking Cards */
        .booking-list { display: flex; flex-direction: column; gap: 1.5rem; }
        .booking-card {
            background: var(--white); border-radius: 15px; padding: 1.5rem;
            box-shadow: var(--shadow-sm); border: 1px solid var(--gray);
            display: flex; gap: 1.5rem; align-items: center; transition: 0.3s;
        }
        .booking-card:hover { transform: translateY(-3px); box-shadow: var(--shadow-lg); border-color: rgba(0, 210, 255, 0.3); }
        .b-img { width: 120px; height: 90px; border-radius: 10px; object-fit: cover; }
        .b-info { flex: 1; }
        .b-info h4 { color: var(--navy); font-size: 1.2rem; margin-bottom: 0.3rem; }
        .b-meta { display: flex; gap: 1rem; color: var(--text-light); font-size: 0.85rem; margin-bottom: 0.5rem; }
        .b-price { font-weight: bold; color: var(--dark-blue); font-size: 1.1rem; }
        
        .b-status { padding: 0.4rem 1rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; text-transform: uppercase; }
        .status-Pending { background: #fef3c7; color: #92400e; }
        .status-Confirmed { background: #dcfce7; color: #166534; }
        .status-Cancelled { background: #fee2e2; color: #991b1b; }

        .empty-state { text-align: center; padding: 3rem 1rem; color: var(--text-light); }
        .empty-state span { font-size: 4rem; display: block; margin-bottom: 1rem; opacity: 0.5; }

        /* Footer */
        footer { background: #0a141a; color: rgba(255,255,255,0.7); padding: 3rem 0; text-align: center; margin-top: auto; }

        /* Responsive */
        @media (max-width: 900px) {
            .profile-wrapper { grid-template-columns: 1fr; }
            .tabs-header { overflow-x: auto; white-space: nowrap; }
            .booking-card { flex-direction: column; text-align: center; }
            .b-meta { justify-content: center; flex-wrap: wrap; }
        }
        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; }
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
                    
                    <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                        <li><a href="<?php echo $base_url; ?>admin.php" style="color: var(--accent);">Admin Panel</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>dashboard.php">Dashboard</a></li>
                        <li><a href="<?php echo $base_url; ?>profile.php" class="active">Profile</a></li>
                    <?php endif; ?>
                    
                    <li><a href="<?php echo $base_url; ?>logout.php" style="color: var(--accent);">Logout</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="profile-wrapper">
        
        <div class="profile-sidebar">
            <div class="avatar"><?php echo $initials; ?></div>
            <h2 class="profile-name"><?php echo htmlspecialchars($user_data['full_name']); ?></h2>
            <p class="profile-username">@<?php echo htmlspecialchars($user_data['username']); ?></p>
            
            <div class="role-badge <?php echo ($user_data['role'] === 'admin') ? 'role-admin' : 'role-user'; ?>">
                <?php echo ($user_data['role'] === 'admin') ? '🛡️ Administrator' : '🌟 Premium Member'; ?>
            </div>

            <p class="join-date">Joined <?php echo date('F Y', strtotime($user_data['created_at'])); ?></p>
        </div>

        <div class="profile-content">
            
            <div class="tabs-header">
                <div class="tab-btn active" onclick="switchTab('profile', this)">👤 Personal Info</div>
                <div class="tab-btn" onclick="switchTab('password', this)">🔐 Security</div>
                <div class="tab-btn" onclick="switchTab('bookings', this)">📅 My Bookings</div>
            </div>

            <?php if($error): ?>
                <div style="padding: 0 2.5rem; margin-top: 1.5rem;"><div class="alert alert-error">⚠️ <?php echo $error; ?></div></div>
            <?php endif; ?>
            <?php if($success): ?>
                <div style="padding: 0 2.5rem; margin-top: 1.5rem;"><div class="alert alert-success">✅ <?php echo $success; ?></div></div>
            <?php endif; ?>

            <div class="tab-body active" id="tab-profile">
                <div class="section-header">
                    <h2>Personal Information</h2>
                    <p>Update your contact details and identity.</p>
                </div>

                <form method="POST">
                    <input type="hidden" name="update_profile" value="1">
                    
                    <div class="form-group">
                        <label>Full Name</label>
                        <input type="text" name="full_name" required value="<?php echo htmlspecialchars($user_data['full_name']); ?>">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label>Username (Cannot be changed)</label>
                            <input type="text" value="@<?php echo htmlspecialchars($user_data['username']); ?>" readonly>
                        </div>
                        <div class="form-group">
                            <label>Phone Number</label>
                            <input type="tel" name="phone" value="<?php echo htmlspecialchars($user_data['phone'] ?? ''); ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Email Address</label>
                        <input type="email" name="email" required value="<?php echo htmlspecialchars($user_data['email']); ?>">
                    </div>

                    <button type="submit" class="btn-submit">💾 Save Changes</button>
                </form>
            </div>

            <div class="tab-body" id="tab-password">
                <div class="section-header">
                    <h2>Change Password</h2>
                    <p>Ensure your account remains secure with a strong password.</p>
                </div>

                <form method="POST" id="passwordForm">
                    <input type="hidden" name="change_password" value="1">

                    <div class="form-group">
                        <label>Current Password</label>
                        <div class="password-container">
                            <input type="password" id="current_password" name="current_password" required>
                            <button type="button" class="toggle-password" onclick="togglePassword('current_password', this)">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>New Password</label>
                        <div class="password-container">
                            <input type="password" id="new_password" name="new_password" required minlength="8">
                            <button type="button" class="toggle-password" onclick="togglePassword('new_password', this)">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Confirm New Password</label>
                        <div class="password-container">
                            <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                            <button type="button" class="toggle-password" onclick="togglePassword('confirm_password', this)">👁️</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit">🔑 Update Password</button>
                </form>
            </div>

            <div class="tab-body" id="tab-bookings">
                <div class="section-header">
                    <h2>My Bookings</h2>
                    <p>Review your past and upcoming aquatic adventures.</p>
                </div>

                <div class="booking-list">
                    <?php if (empty($user_bookings)): ?>
                        <div class="empty-state">
                            <span>🌊</span>
                            <h3>No bookings yet</h3>
                            <p>You haven't set sail with us yet. <br><a href="boats.php" style="color:var(--primary-blue); font-weight:bold; text-decoration:none; margin-top:10px; display:inline-block;">Explore the fleet →</a></p>
                        </div>
                    <?php else: ?>
                        <?php foreach($user_bookings as $booking): ?>
                            <div class="booking-card">
                                <img src="classes/assets/images/<?php echo htmlspecialchars($booking['boat_image']); ?>" 
                                     alt="Boat" class="b-img"
                                     onerror="this.src='https://images.unsplash.com/photo-1544644181-1484b3fdfc62?ixlib=rb-4.0.3&auto=format&fit=crop&w=300&q=80'">
                                <div class="b-info">
                                    <h4><?php echo htmlspecialchars($booking['boat_name']); ?></h4>
                                    <div class="b-meta">
                                        <span>📅 <?php echo date('M j, Y', strtotime($booking['booking_date'])); ?></span>
                                        <span>⏰ <?php echo date('g:i A', strtotime($booking['start_time'])) . ' - ' . date('g:i A', strtotime($booking['end_time'])); ?></span>
                                        <span>🛥️ <?php echo htmlspecialchars($booking['boat_type']); ?></span>
                                    </div>
                                    <div class="b-price">₹<?php echo number_format($booking['total_price'], 2); ?></div>
                                </div>
                                <div class="b-status status-<?php echo $booking['status']; ?>">
                                    <?php echo $booking['status']; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> BoatBooking System. Crafted for maritime excellence.</p>
        </div>
    </footer>

    <script>
        // Tab Switcher Logic
        function switchTab(tabId, clickedBtn) {
            // Update buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            if(clickedBtn) {
                clickedBtn.classList.add('active');
            } else {
                // If triggered via PHP, fallback to select by nth-child
                if(tabId === 'password') document.querySelectorAll('.tab-btn')[1].classList.add('active');
            }

            // Update contents
            document.querySelectorAll('.tab-body').forEach(body => body.classList.remove('active'));
            document.getElementById('tab-' + tabId).classList.add('active');
        }

        // Password Visibility Toggle
        function togglePassword(inputId, btn) {
            const input = document.getElementById(inputId);
            if (input.type === 'password') {
                input.type = 'text';
                btn.textContent = '🔒';
            } else {
                input.type = 'password';
                btn.textContent = '👁️';
            }
        }
        
        // Show specific tab if redirected back after a password change attempt
        <?php if(isset($_POST['change_password'])): ?>
            switchTab('password', null);
        <?php endif; ?>
    </script>
</body>
</html>