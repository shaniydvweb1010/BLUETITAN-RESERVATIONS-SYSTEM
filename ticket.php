<?php
session_start();
require_once "config/database.php";

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$booking_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch full booking details (Removed the 'Confirmed' restriction so we can load the ticket and evaluate its state)
$query = "SELECT b.*, bt.name as boat_name, bt.type as boat_type, bt.image as boat_image, 
                 u.full_name, u.email, u.phone 
          FROM bookings b 
          JOIN boats bt ON b.boat_id = bt.id 
          JOIN users u ON b.user_id = u.id
          WHERE b.id = ? AND b.user_id = ?";
$stmt = $db->prepare($query);
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$ticket = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$ticket) {
    echo "<script>alert('Ticket not found.'); window.location.href='dashboard.php';</script>";
    exit();
}

// Evaluate Ticket Status
$is_valid = ($ticket['status'] === 'Confirmed');
$watermark = '';
$badge_color = '#27ae60'; // Default Green
$badge_text = 'PAID & CONFIRMED';

if ($ticket['status'] === 'Cancelled') {
    $watermark = 'VOID - CANCELLED';
    $badge_color = '#e74c3c'; // Red
    $badge_text = 'BOOKING CANCELLED';
} elseif ($ticket['status'] === 'Pending') {
    $watermark = 'PENDING PAYMENT';
    $badge_color = '#f39c12'; // Orange
    $badge_text = 'PAYMENT REQUIRED';
}

// Generate unique Ticket ID & QR Code
$ticket_no = "TKT-" . date('Ymd') . "-" . str_pad($ticket['id'], 4, '0', STR_PAD_LEFT);
$qr_data = "Ticket:$ticket_no|Name:{$ticket['full_name']}|Boat:{$ticket['boat_name']}|Status:{$ticket['status']}";
$qr_url = "https://api.qrserver.com/v1/create-qr-code/?size=150x150&data=" . urlencode($qr_data);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>E-Ticket - <?= $ticket_no ?></title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: #e0eafc; display: flex; flex-direction: column; align-items: center; padding: 3rem 1rem; color: #333; min-height: 100vh; }
        
        .actions { margin-bottom: 2rem; display: flex; gap: 1rem; z-index: 100; }
        .btn { padding: 0.8rem 1.5rem; border: none; border-radius: 8px; cursor: pointer; font-weight: bold; color: white; display: flex; align-items: center; gap: 8px; text-decoration: none; transition: 0.3s;}
        .btn-download { background: #3498db; }
        .btn-download:hover:not(:disabled) { background: #2980b9; transform: translateY(-2px); }
        .btn-home { background: #2c3e50; }
        .btn-home:hover { background: #1a252f; transform: translateY(-2px); }
        .btn:disabled { background: #95a5a6; cursor: not-allowed; opacity: 0.7; }
        
        /* Ticket Design */
        .ticket-container {
            display: flex;
            background: #fff;
            width: 100%;
            max-width: 850px;
            border-radius: 15px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            position: relative;
            overflow: hidden;
        }
        
        /* Watermark Overlay */
        .watermark {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%) rotate(-35deg);
            font-size: 5rem;
            font-weight: 900;
            color: rgba(231, 76, 60, 0.15); /* Faded Red */
            pointer-events: none;
            z-index: 10;
            white-space: nowrap;
            letter-spacing: 5px;
        }
        
        /* Left Panel */
        .ticket-left { flex: 2; padding: 2.5rem; border-right: 2px dashed #ccc; position: relative; z-index: 2; background: rgba(255,255,255,0.9); }
        .ticket-left::before, .ticket-left::after {
            content: ''; position: absolute; right: -15px; width: 30px; height: 30px; background: #e0eafc; border-radius: 50%; z-index: 5;
        }
        .ticket-left::before { top: -15px; }
        .ticket-left::after { bottom: -15px; }

        .ticket-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f0f0f0; padding-bottom: 1rem; margin-bottom: 2rem; }
        .brand { font-size: 1.8rem; font-weight: 800; color: #2980b9; }
        .tkt-num { color: #7f8c8d; font-weight: bold; background: #f8f9fa; padding: 0.4rem 0.8rem; border-radius: 8px;}

        .ticket-body { display: grid; grid-template-columns: 1fr 1fr; gap: 2rem; margin-bottom: 1.5rem; }
        .info-block label { display: block; font-size: 0.85rem; color: #7f8c8d; text-transform: uppercase; margin-bottom: 0.3rem; letter-spacing: 1px;}
        .info-block strong { font-size: 1.3rem; color: #2c3e50; }

        /* Right Panel */
        .ticket-right { flex: 1; background: #f8f9fa; padding: 2.5rem 2rem; display: flex; flex-direction: column; align-items: center; justify-content: center; text-align: center; z-index: 2; }
        .qr-code { width: 140px; height: 140px; margin-bottom: 1.5rem; border: 4px solid white; box-shadow: 0 4px 10px rgba(0,0,0,0.1); border-radius: 8px;}
        
        .status-badge { 
            background: <?= $badge_color ?>; 
            color: white; padding: 0.6rem 1.2rem; border-radius: 30px; 
            font-weight: bold; text-transform: uppercase; letter-spacing: 1px; 
            margin-bottom: 1.5rem; width: 100%; box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        
        .disclaimer { font-size: 0.8rem; color: #95a5a6; margin-top: 2rem; border-top: 1px solid #eee; padding-top: 1rem; line-height: 1.5;}

        /* Invalid Overlay styling to dim the ticket if cancelled */
        .dimmed { opacity: 0.6; filter: grayscale(50%); }

        /* Print CSS - Makes it look perfect when saving as PDF */
        @media print {
            body { background: white; padding: 0; align-items: flex-start; }
            .actions { display: none; } 
            .ticket-container { box-shadow: none; border: 1px solid #ddd; margin-top: 20px; }
            .ticket-left::before, .ticket-left::after { background: white; border: 1px solid #ddd; }
            .watermark { -webkit-print-color-adjust: exact; color: rgba(231, 76, 60, 0.2) !important; }
            .status-badge { -webkit-print-color-adjust: exact; }
        }
    </style>
</head>
<body>

    <div class="actions">
        <a href="dashboard.php" class="btn btn-home">⬅ Back to Dashboard</a>
        
        <?php if($is_valid): ?>
            <button onclick="window.print()" class="btn btn-download">📥 Download PDF / Print</button>
        <?php else: ?>
            <button disabled class="btn btn-download" title="Cannot download an invalid ticket">🚫 Ticket Invalid</button>
        <?php endif; ?>
    </div>

    <div class="ticket-container <?= !$is_valid ? 'dimmed' : '' ?>" id="ticket">
        
        <?php if($watermark): ?>
            <div class="watermark"><?= $watermark ?></div>
        <?php endif; ?>
        
        <div class="ticket-left">
            <div class="ticket-header">
                <div class="brand">⛵ BoatBooking Pass</div>
                <div class="tkt-num"><?= $ticket_no ?></div>
            </div>
            
            <div class="ticket-body">
                <div class="info-block">
                    <label>Passenger Name</label>
                    <strong><?= htmlspecialchars($ticket['full_name']) ?></strong>
                </div>
                <div class="info-block">
                    <label>Vessel Name</label>
                    <strong><?= htmlspecialchars($ticket['boat_name']) ?></strong>
                    <div style="color: #7f8c8d; font-size: 0.9rem;"><?= $ticket['boat_type'] ?></div>
                </div>
                
                <div class="info-block">
                    <label>Boarding Date</label>
                    <strong><?= date('F j, Y', strtotime($ticket['booking_date'])) ?></strong>
                </div>
                <div class="info-block">
                    <label>Time Slot</label>
                    <strong><?= date('g:i A', strtotime($ticket['start_time'])) ?> - <?= date('g:i A', strtotime($ticket['end_time'])) ?></strong>
                </div>
            </div>

            <div class="disclaimer">
                <strong>Important Instructions:</strong> Please arrive at the marina 15 minutes prior to your boarding time. Present this e-ticket (digital or printed) along with a valid Government ID to the captain before boarding.
            </div>
        </div>

        <div class="ticket-right">
            <div class="status-badge"><?= $badge_text ?></div>
            
            <?php if($is_valid): ?>
                <img src="<?= $qr_url ?>" alt="QR Code" class="qr-code">
            <?php else: ?>
                <div class="qr-code" style="display:flex; align-items:center; justify-content:center; background:#eee; color:#aaa; font-size:3rem;">❌</div>
            <?php endif; ?>
            
            <div class="info-block" style="margin-top: auto;">
                <label>Amount Paid</label>
                <strong style="color: <?= $badge_color ?>; font-size: 1.8rem;">₹<?= number_format($ticket['total_price'], 2) ?></strong>
            </div>
        </div>

    </div>

</body>
</html>