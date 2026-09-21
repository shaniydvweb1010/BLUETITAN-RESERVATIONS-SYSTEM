<?php
session_start();
require_once "config/database.php";

// Security Check
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$page = isset($_GET['page']) ? $_GET['page'] : 'dashboard';
$msg = '';

$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/bbs/';

// --- POST HANDLERS ---

// Handle Add New Boat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_boat'])) {
    $name = $_POST['name'];
    $type = $_POST['type'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price_per_hour'];
    $desc = $_POST['description'];
    
    $image = 'default_boat.jpg'; 
    if(isset($_FILES["boat_image"]) && $_FILES["boat_image"]["error"] == 0){
        $target_dir = "classes/assets/images/";
        if (!file_exists($target_dir)) mkdir($target_dir, 0777, true);
        $image = time() . '_' . basename($_FILES["boat_image"]["name"]); 
        move_uploaded_file($_FILES["boat_image"]["tmp_name"], $target_dir . $image);
    }

    $stmt = $db->prepare("INSERT INTO boats (name, type, capacity, price_per_hour, description, image, available) VALUES (?, ?, ?, ?, ?, ?, 1)");
    if($stmt->execute([$name, $type, $capacity, $price, $desc, $image])){
        $msg = "<div class='alert success'>✅ Boat added successfully to the fleet!</div>";
    }
}

// Handle Update Boat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_boat'])) {
    $boat_id = $_POST['boat_id'];
    $name = $_POST['name'];
    $type = $_POST['type'];
    $capacity = $_POST['capacity'];
    $price = $_POST['price_per_hour'];
    $desc = $_POST['description'];
    
    if(isset($_FILES["boat_image"]) && $_FILES["boat_image"]["error"] == 0){
        $target_dir = "classes/assets/images/";
        $image = time() . '_' . basename($_FILES["boat_image"]["name"]); 
        move_uploaded_file($_FILES["boat_image"]["tmp_name"], $target_dir . $image);
        $stmt = $db->prepare("UPDATE boats SET name=?, type=?, capacity=?, price_per_hour=?, description=?, image=? WHERE id=?");
        $stmt->execute([$name, $type, $capacity, $price, $desc, $image, $boat_id]);
    } else {
        $stmt = $db->prepare("UPDATE boats SET name=?, type=?, capacity=?, price_per_hour=?, description=? WHERE id=?");
        $stmt->execute([$name, $type, $capacity, $price, $desc, $boat_id]);
    }
    $msg = "<div class='alert success'>✅ Boat updated successfully!</div>";
}

// Handle Delete Boat
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['delete_boat'])) {
    $boat_id = $_POST['delete_id'];
    $stmt = $db->prepare("DELETE FROM boats WHERE id = ?");
    if($stmt->execute([$boat_id])) {
        $msg = "<div class='alert success'>🗑️ Boat removed from fleet successfully!</div>";
    }
}

// Handle Update Booking Status
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $booking_id = $_POST['booking_id'];
    $new_status = $_POST['status'];
    $stmt = $db->prepare("UPDATE bookings SET status = ? WHERE id = ?");
    if($stmt->execute([$new_status, $booking_id])) {
        $msg = "<div class='alert success'>✅ Booking #$booking_id status updated to $new_status!</div>";
    }
}

// Fetch Boat for Editing
$edit_boat = null;
if (isset($_GET['edit_boat'])) {
    $stmt = $db->prepare("SELECT * FROM boats WHERE id = ?");
    $stmt->execute([$_GET['edit_boat']]);
    $edit_boat = $stmt->fetch(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - BoatBooking</title>
    <style>
        :root {
            --primary-blue: #3498db; --dark-blue: #2980b9; --navy: #2c3e50; 
            --red: #e74c3c; --dark-red: #c0392b; --green: #27ae60; --orange: #f39c12;
            --white: #ffffff; --gray: #ecf0f1; --text-dark: #2c3e50; --text-light: #7f8c8d;
            --shadow: 0 4px 6px rgba(0, 0, 0, 0.1); --shadow-lg: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', sans-serif; }
        body { display: flex; min-height: 100vh; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: var(--text-dark); }
        
        .sidebar { width: 260px; background: rgba(255, 255, 255, 0.95); border-right: 1px solid rgba(255,255,255,0.2); display: flex; flex-direction: column; box-shadow: var(--shadow); z-index: 10; }
        .sidebar-brand { padding: 2rem 1.5rem; font-size: 1.8rem; font-weight: bold; color: var(--primary-blue); text-align: center; border-bottom: 2px solid var(--gray); }
        .nav-items { flex: 1; padding: 1.5rem 0; }
        .nav-item { display: block; padding: 1rem 2rem; color: var(--navy); text-decoration: none; transition: 0.3s; border-left: 4px solid transparent; font-weight: 600; }
        .nav-item:hover, .nav-item.active { background: rgba(52, 152, 219, 0.1); color: var(--primary-blue); border-left-color: var(--primary-blue); }
        .logout-btn { margin-top: auto; background: var(--red); color: var(--white); text-align: center; }
        
        .main-content { flex: 1; padding: 2rem; overflow-y: auto; }
        .top-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem; color: var(--white); }
        .glass-panel { background: rgba(255, 255, 255, 0.95); padding: 2rem; border-radius: 20px; box-shadow: var(--shadow); margin-bottom: 2rem; }
        .glass-panel h2 { color: var(--navy); margin-bottom: 1.5rem; border-bottom: 2px solid var(--gray); padding-bottom: 0.5rem; }
        
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 1.5rem; margin-bottom: 2rem; }
        .stat-card { background: rgba(255, 255, 255, 0.95); padding: 2rem; border-radius: 15px; text-align: center; box-shadow: var(--shadow); border-top: 4px solid var(--primary-blue); }
        .stat-card h3 { font-size: 2.5rem; color: var(--navy); }
        
        table { width: 100%; border-collapse: collapse; margin-top: 1rem; }
        th, td { padding: 1.2rem 1rem; text-align: left; border-bottom: 1px solid var(--gray); }
        th { color: var(--text-light); text-transform: uppercase; font-size: 0.85rem; }
        
        .badge { padding: 0.4rem 0.8rem; border-radius: 20px; font-size: 0.8rem; font-weight: bold; }
        .badge-pending { background: #fff3cd; color: #856404; }
        .badge-approved { background: #e1f5fe; color: #0288d1; } /* New Badge Color */
        .badge-confirmed { background: #d1edff; color: var(--dark-blue); }
        .badge-cancelled { background: #f8d7da; color: var(--dark-red); }
        
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; font-weight: 600; color: var(--navy); margin-bottom: 0.5rem; }
        input, select, textarea { width: 100%; padding: 0.8rem 1rem; border: 2px solid var(--gray); border-radius: 10px; font-size: 1rem; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 10px; background: var(--primary-blue); color: white; font-weight: 600; cursor: pointer; transition: 0.3s; }
        .btn:hover { transform: translateY(-2px); box-shadow: var(--shadow); }
        .btn-small { padding: 0.4rem 0.8rem; font-size: 0.85rem; border-radius: 6px; }
        .btn-danger { background: var(--red); color: white; }
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-brand">⛵ Admin Panel</div>
        <div class="nav-items">
            <a href="?page=dashboard" class="nav-item <?= $page=='dashboard'?'active':'' ?>">📊 Dashboard</a>
            <a href="?page=bookings" class="nav-item <?= $page=='bookings'?'active':'' ?>">📅 Manage Bookings</a>
            <a href="?page=boats" class="nav-item <?= $page=='boats' || isset($_GET['edit_boat']) ?'active':'' ?>">🚤 Fleet Management</a>
            <a href="?page=users" class="nav-item <?= $page=='users'?'active':'' ?>">👥 Client Directory</a>
        </div>
        <a href="logout.php" class="nav-item logout-btn">🚪 Logout</a>
    </div>

    <div class="main-content">
        <div class="top-header">
            <h1>Workspace - <?= ucfirst($page) ?></h1>
            <p>Admin: <strong><?= htmlspecialchars($_SESSION['full_name']); ?></strong> | <?= date('l, jS M Y'); ?></p>
        </div>
        
        <?= $msg; ?>

        <?php if($page == 'dashboard'): 
            $u_count = $db->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
            $b_count = $db->query("SELECT COUNT(*) FROM boats")->fetchColumn();
            $bk_count = $db->query("SELECT COUNT(*) FROM bookings")->fetchColumn();
            $rev = $db->query("SELECT SUM(total_price) FROM bookings WHERE status='Confirmed'")->fetchColumn();
        ?>
            <div class="stats-grid">
                <div class="stat-card"><h3><?= $bk_count ?></h3><p>Total Bookings</p></div>
                <div class="stat-card" style="border-top-color: var(--green);"><h3>₹<?= number_format((float)$rev, 2) ?></h3><p>Revenue</p></div>
                <div class="stat-card" style="border-top-color: var(--dark-navy);"><h3><?= $b_count ?></h3><p>Fleet Size</p></div>
                <div class="stat-card" style="border-top-color: var(--orange);"><h3><?= $u_count ?></h3><p>Clients</p></div>
            </div>

            <div class="glass-panel">
                <h2>🚨 New Reservation Requests (Pending)</h2>
                <table>
                    <tr><th>ID</th><th>Client</th><th>Boat</th><th>Schedule</th><th>Amount</th><th>Status</th><th>Quick Action</th></tr>
                    <?php 
                    $pending_bookings = $db->query("SELECT b.*, u.full_name, bt.name as boat FROM bookings b JOIN users u ON b.user_id = u.id JOIN boats bt ON b.boat_id = bt.id WHERE b.status = 'Pending' ORDER BY b.created_at DESC")->fetchAll();
                    
                    if(count($pending_bookings) > 0):
                        foreach($pending_bookings as $b): ?>
                        <tr>
                            <td><strong>#<?= $b['id'] ?></strong></td>
                            <td><?= htmlspecialchars($b['full_name']) ?></td>
                            <td><?= htmlspecialchars($b['boat']) ?></td>
                            <td><?= date('M j, Y', strtotime($b['booking_date'])) ?></td>
                            <td>₹<?= number_format((float)$b['total_price'], 2) ?></td>
                            <td><span class="badge badge-pending"><?= $b['status'] ?></span></td>
                            <td>
                                <form method="POST" style="display:flex; gap:5px;">
                                    <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                    <input type="hidden" name="status" value="Approved">
                                    <button type="submit" name="update_status" class="btn btn-small" style="background: var(--primary-blue);">Approve</button>
                                </form>
                            </td>
                        </tr>
                        <?php endforeach; 
                    else: ?>
                        <tr><td colspan="7" style="text-align: center; padding: 2rem; color: var(--text-light);">No new orders pending. Everything is caught up!</td></tr>
                    <?php endif; ?>
                </table>
            </div>

        <?php elseif($page == 'bookings'): ?>
            <div class="glass-panel">
                <h2>All Transactions & History</h2>
                <table>
                    <tr><th>ID</th><th>Client</th><th>Boat</th><th>Schedule</th><th>Amount</th><th>Status</th><th>Update</th></tr>
                    <?php 
                    $bookings = $db->query("SELECT b.*, u.full_name, bt.name as boat FROM bookings b JOIN users u ON b.user_id = u.id JOIN boats bt ON b.boat_id = bt.id ORDER BY b.created_at DESC")->fetchAll();
                    foreach($bookings as $b): ?>
                    <tr>
                        <td>#<?= $b['id'] ?></td>
                        <td><?= htmlspecialchars($b['full_name']) ?></td>
                        <td><?= htmlspecialchars($b['boat']) ?></td>
                        <td><?= date('M j, Y', strtotime($b['booking_date'])) ?></td>
                        <td>₹<?= number_format((float)$b['total_price'], 2) ?></td>
                        <td><span class="badge badge-<?= strtolower($b['status']) ?>"><?= $b['status'] ?></span></td>
                        <td>
                            <form method="POST" style="display:flex; gap:5px;">
                                <input type="hidden" name="booking_id" value="<?= $b['id'] ?>">
                                <select name="status">
                                    <option <?= $b['status']=='Pending'?'selected':'' ?>>Pending</option>
                                    <option <?= $b['status']=='Approved'?'selected':'' ?>>Approved</option>
                                    <option <?= $b['status']=='Confirmed'?'selected':'' ?>>Confirmed</option>
                                    <option <?= $b['status']=='Cancelled'?'selected':'' ?>>Cancelled</option>
                                </select>
                                <button type="submit" name="update_status" class="btn btn-small">Save</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>

        <?php elseif($page == 'boats' || isset($_GET['edit_boat'])): ?>
            
            <div class="glass-panel">
                <h2><?= $edit_boat ? 'Edit Boat Details' : 'Add New Boat' ?></h2>
                <form method="POST" enctype="multipart/form-data">
                    <?php if($edit_boat): ?>
                        <input type="hidden" name="boat_id" value="<?= $edit_boat['id'] ?>">
                    <?php endif; ?>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label>Boat Name</label>
                            <input type="text" name="name" value="<?= $edit_boat ? htmlspecialchars($edit_boat['name']) : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Vessel Category</label>
                            <select name="type" required>
                                <?php $types = ['Speedboat', 'Yacht', 'Fishing Boat', 'Pontoon', 'Ship', 'Sailboat', 'Catamaran', 'Other']; 
                                foreach($types as $t): ?>
                                    <option value="<?= $t ?>" <?= ($edit_boat && $edit_boat['type'] == $t) ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Capacity</label>
                            <input type="number" name="capacity" value="<?= $edit_boat ? $edit_boat['capacity'] : '' ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Price/Hr (₹)</label>
                            <input type="number" step="0.01" name="price_per_hour" value="<?= $edit_boat ? $edit_boat['price_per_hour'] : '' ?>" required>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <textarea name="description" rows="3" required><?= $edit_boat ? htmlspecialchars($edit_boat['description']) : '' ?></textarea>
                    </div>
                    <div class="form-group">
                        <label>Image (Leave blank to keep existing)</label>
                        <input type="file" name="boat_image" accept="image/*">
                    </div>
                    
                    <button type="submit" name="<?= $edit_boat ? 'update_boat' : 'add_boat' ?>" class="btn">
                        <?= $edit_boat ? '💾 Save Changes' : '➕ Register Boat' ?>
                    </button>
                    <?php if($edit_boat): ?>
                        <a href="?page=boats" class="btn btn-danger" style="text-decoration: none; margin-left: 10px; display: inline-block;">Cancel Edit</a>
                    <?php endif; ?>
                </form>
            </div>

            <div class="glass-panel">
                <h2>Current Active Fleet</h2>
                <table>
                    <tr><th>Image</th><th>Name</th><th>Category</th><th>Capacity</th><th>Pricing</th><th>Actions</th></tr>
                    <?php 
                    $boats = $db->query("SELECT * FROM boats ORDER BY id DESC")->fetchAll();
                    foreach($boats as $bt): ?>
                    <tr>
                        <td><img src="classes/assets/images/<?= htmlspecialchars($bt['image']) ?>" width="60" style="border-radius:5px;" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iODAiIGhlaWdodD0iNTAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PHJlY3Qgd2lkdGg9IjEwMCUiIGhlaWdodD0iMTAwJSIgZmlsbD0iIzM0OThkYiIvPjx0ZXh0IHg9IjUwJSIgeT0iNTAlIiBmb250LWZhbWlseT0iQXJpYWwiIGZvbnQtc2l6ZT0iMTIiIGZpbGw9IndoaXRlIiB0ZXh0LWFuY2hvcj0ibWlkZGxlIiBkeT0iLjNlbSI+Qm9hdDwvdGV4dD48L3N2Zz4='"></td>
                        <td><strong><?= htmlspecialchars($bt['name']) ?></strong></td>
                        <td><?= htmlspecialchars($bt['type']) ?></td>
                        <td><?= $bt['capacity'] ?></td>
                        <td>₹<?= number_format((float)$bt['price_per_hour'], 2) ?></td>
                        <td style="display: flex; gap: 5px;">
                            <a href="?page=boats&edit_boat=<?= $bt['id'] ?>" class="btn btn-small">Edit</a>
                            <form method="POST" onsubmit="return confirm('Are you sure you want to delete this boat?');">
                                <input type="hidden" name="delete_id" value="<?= $bt['id'] ?>">
                                <button type="submit" name="delete_boat" class="btn btn-small btn-danger">Delete</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            
        <?php elseif($page == 'users'): ?>
            <div class="glass-panel">
                <h2>Registered Clients</h2>
                <table>
                    <tr><th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Joined</th></tr>
                    <?php 
                    $users = $db->query("SELECT * FROM users WHERE role='user' ORDER BY created_at DESC")->fetchAll();
                    foreach($users as $u): ?>
                    <tr>
                        <td>#<?= $u['id'] ?></td>
                        <td><strong><?= htmlspecialchars($u['full_name']) ?></strong></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><?= htmlspecialchars($u['phone']) ?></td>
                        <td><?= date('M j, Y', strtotime($u['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>