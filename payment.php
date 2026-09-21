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

// Fetch booking details
$query = "SELECT b.*, bt.name as boat_name 
          FROM bookings b 
          JOIN boats bt ON b.boat_id = bt.id 
          WHERE b.id = ? AND b.user_id = ? AND b.status = 'Approved'";
$stmt = $db->prepare($query);
$stmt->execute([$booking_id, $_SESSION['user_id']]);
$booking = $stmt->fetch(PDO::FETCH_ASSOC);

if(!$booking) {
    header("Location: dashboard.php");
    exit();
}

$msg = '';

// Process Payment
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['payment_method'])) {
    
    // In a real app, you would use $_POST['payment_method'] to trigger the correct API.
    
    $update_query = "UPDATE bookings SET status = 'Confirmed' WHERE id = ?";
    $update_stmt = $db->prepare($update_query);
    
    if($update_stmt->execute([$booking_id])) {
        $msg = "<div class='alert success'>✅ Payment Successful! Generating your boarding pass...</div>";
        echo "<script>setTimeout(() => { window.location.href = 'ticket.php?id=$booking_id'; }, 2000);</script>";
    } else {
        $msg = "<div class='alert error'>❌ Payment failed. Please try again.</div>";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Checkout - BoatBooking</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; font-family: 'Segoe UI', Tahoma, sans-serif; }
        body { background: linear-gradient(-45deg, #f8f9fa, #e0eafc, #cfdef3); background-size: 400% 400%; animation: gradientBG 15s ease infinite; display: flex; justify-content: center; align-items: center; min-height: 100vh; color: #2c3e50; padding: 2rem; }
        @keyframes gradientBG { 0% {background-position: 0% 50%;} 50% {background-position: 100% 50%;} 100% {background-position: 0% 50%;} }
        
        .checkout-container { background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px); width: 100%; max-width: 900px; border-radius: 20px; box-shadow: 0 15px 40px rgba(0,0,0,0.15); display: flex; overflow: hidden; animation: slideUp 0.5s ease; border: 1px solid rgba(255,255,255,0.4); }
        @keyframes slideUp { from { opacity: 0; transform: translateY(20px); } to { opacity: 1; transform: translateY(0); } }
        
        /* Left: Order Summary */
        .order-summary { background: linear-gradient(135deg, #0f2027, #203a43); color: white; padding: 3rem 2.5rem; width: 40%; position: relative; }
        .order-summary::after { content: ''; position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: url('https://www.transparenttextures.com/patterns/cubes.png'); opacity: 0.1; pointer-events: none; }
        .order-summary h2 { margin-bottom: 2rem; font-size: 1.5rem; color: #00d2ff; display: flex; align-items: center; gap: 10px; }
        .summary-item { display: flex; flex-direction: column; margin-bottom: 1.5rem; padding-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .summary-item span { color: #8e9eab; font-size: 0.85rem; text-transform: uppercase; letter-spacing: 1px; margin-bottom: 0.3rem; }
        .summary-item strong { font-size: 1.2rem; }
        .total-row { display: flex; justify-content: space-between; align-items: center; margin-top: 2rem; font-size: 1.5rem; font-weight: bold; color: #00d2ff; background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 10px; }
        
        /* Right: Payment Form */
        .payment-form { padding: 3rem; width: 60%; }
        .payment-form h2 { margin-bottom: 0.5rem; font-size: 1.8rem; color: #2c3e50; }
        .secure-badge { color: #27ae60; font-weight: bold; font-size: 0.85rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 5px; }

        /* Payment Method Selector */
        .payment-methods { display: flex; gap: 10px; margin-bottom: 2rem; border-bottom: 2px solid #eee; padding-bottom: 1rem; }
        .method-btn { flex: 1; padding: 0.8rem; text-align: center; background: #f8f9fa; border: 2px solid #eee; border-radius: 10px; cursor: pointer; font-weight: 600; color: #7f8c8d; transition: 0.3s; }
        .method-btn.active { background: rgba(0, 210, 255, 0.1); border-color: #00d2ff; color: #00d2ff; box-shadow: 0 4px 10px rgba(0, 210, 255, 0.1); }
        .method-btn:hover:not(.active) { background: #eee; }

        /* Form Sections */
        .payment-section { display: none; animation: fadeIn 0.4s ease; }
        .payment-section.active { display: block; }
        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }

        .form-group { margin-bottom: 1.5rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: #34495e; }
        input, select { width: 100%; padding: 1rem 1.2rem; border: 2px solid #e0e0e0; border-radius: 10px; font-size: 1rem; background: #fff; transition: 0.3s; }
        input:focus, select:focus { outline: none; border-color: #00d2ff; box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1); }
        .input-error { border-color: #e74c3c !important; background: #fdf2f2 !important; }
        .error-msg { color: #e74c3c; font-size: 0.8rem; font-weight: bold; margin-top: 5px; display: none; }
        
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; }
        
        /* Specific UI elements */
        .upi-qr-placeholder { background: #f8f9fa; border: 2px dashed #ccc; border-radius: 10px; padding: 2rem; text-align: center; margin-bottom: 1.5rem; color: #7f8c8d; }
        
        .btn-pay { width: 100%; padding: 1.2rem; background: linear-gradient(135deg, #27ae60, #229954); color: white; border: none; border-radius: 10px; font-size: 1.1rem; font-weight: bold; cursor: pointer; transition: 0.3s; margin-top: 1rem; box-shadow: 0 8px 15px rgba(39, 174, 96, 0.3); display: flex; justify-content: center; align-items: center; gap: 10px; text-transform: uppercase; letter-spacing: 1px;}
        .btn-pay:hover { transform: translateY(-2px); box-shadow: 0 12px 20px rgba(39, 174, 96, 0.4); }
        .btn-pay:disabled { background: #95a5a6; cursor: not-allowed; box-shadow: none; transform: none; }
        
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; text-align: center; font-weight: bold; }
        .success { background: #dcfce7; color: #166534; border-left: 4px solid #27ae60; }
        .error { background: #fee2e2; color: #991b1b; border-left: 4px solid #e74c3c; }
        
        /* Loading Spinner */
        .spinner { display: none; width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s linear infinite; }
        @keyframes spin { to { transform: rotate(360deg); } }
        
        .cancel-link { display: block; text-align: center; margin-top: 1.5rem; color: #7f8c8d; text-decoration: none; font-size: 0.9rem; font-weight: 600; transition: 0.3s; }
        .cancel-link:hover { color: #e74c3c; }

        @media(max-width: 768px) { .checkout-container { flex-direction: column; } .order-summary, .payment-form { width: 100%; } .payment-methods { flex-direction: column; gap: 5px; } }
    </style>
</head>
<body>

<div class="checkout-container">
    
    <div class="order-summary">
        <h2>🧾 Order Summary</h2>
        <div class="summary-item">
            <span>Vessel Selected</span>
            <strong><?= htmlspecialchars($booking['boat_name']) ?></strong>
        </div>
        <div class="summary-item">
            <span>Date of Boarding</span>
            <strong><?= date('M j, Y', strtotime($booking['booking_date'])) ?></strong>
        </div>
        <div class="summary-item">
            <span>Time Slot</span>
            <strong><?= date('g:i A', strtotime($booking['start_time'])) ?> - <?= date('g:i A', strtotime($booking['end_time'])) ?></strong>
        </div>
        <div class="total-row">
            <span>Total:</span>
            <span>₹<?= number_format($booking['total_price'], 2) ?></span>
        </div>
    </div>
    
    <div class="payment-form">
        <h2>Complete Payment</h2>
        <div class="secure-badge">🔒 256-bit SSL End-to-End Encrypted</div>
        
        <?= $msg ?>
        
        <form method="POST" id="payForm">
            <input type="hidden" name="payment_method" id="payment_method" value="card">

            <div class="payment-methods">
                <div class="method-btn active" onclick="switchMethod('card', this)">💳 Card</div>
                <div class="method-btn" onclick="switchMethod('upi', this)">📱 UPI Apps</div>
                <div class="method-btn" onclick="switchMethod('netbanking', this)">🏦 Net Banking</div>
            </div>

            <div id="sec-card" class="payment-section active">
                <div class="form-group">
                    <label>Name on Card</label>
                    <input type="text" id="card_name" placeholder="JOHN DOE" value="<?= htmlspecialchars($_SESSION['full_name']) ?>">
                    <div class="error-msg">Please enter the name on the card</div>
                </div>
                <div class="form-group">
                    <label>Card Number</label>
                    <input type="text" id="card_num" placeholder="XXXX XXXX XXXX XXXX" maxlength="19" onkeyup="this.value = this.value.replace(/[^0-9\s]/g, '')">
                    <div class="error-msg">Please enter a valid card number</div>
                </div>
                <div class="grid-2">
                    <div class="form-group">
                        <label>Expiry (MM/YY)</label>
                        <input type="text" id="card_exp" placeholder="12/25" maxlength="5" onkeyup="formatExpiry(this)">
                        <div class="error-msg">Enter expiry date</div>
                    </div>
                    <div class="form-group">
                        <label>CVV</label>
                        <input type="password" id="card_cvv" placeholder="XXX" maxlength="3" onkeyup="this.value = this.value.replace(/[^0-9]/g, '')">
                        <div class="error-msg">Enter CVV</div>
                    </div>
                </div>
            </div>

            <div id="sec-upi" class="payment-section">
                <div class="upi-qr-placeholder">
                    <div style="font-size: 3rem; margin-bottom: 10px;">📱</div>
                    <p>Open GPay, PhonePe, or Paytm and scan to pay</p>
                    <strong style="color: #2c3e50; display: block; margin-top: 10px;">Or enter your UPI ID below</strong>
                </div>
                <div class="form-group">
                    <label>Virtual Payment Address (UPI ID)</label>
                    <input type="text" id="upi_id" placeholder="username@upi">
                    <div class="error-msg">Please enter a valid UPI ID</div>
                </div>
            </div>

            <div id="sec-netbanking" class="payment-section">
                <div class="form-group">
                    <label>Select Your Bank</label>
                    <select id="bank_select">
                        <option value="" disabled selected>-- Choose Bank --</option>
                        <option value="sbi">State Bank of India (SBI)</option>
                        <option value="hdfc">HDFC Bank</option>
                        <option value="icici">ICICI Bank</option>
                        <option value="axis">Axis Bank</option>
                        <option value="kotak">Kotak Mahindra Bank</option>
                        <option value="other">Other Bank...</option>
                    </select>
                    <div class="error-msg">Please select a bank to proceed</div>
                </div>
                <p style="font-size: 0.85rem; color: #7f8c8d; margin-top: 1rem; line-height: 1.5;">
                    You will be securely redirected to your bank's portal to complete the authentication process.
                </p>
            </div>
            
            <button type="submit" class="btn-pay" id="payBtn">
                <span id="btnText">Pay ₹<?= number_format($booking['total_price'], 2) ?></span>
                <div class="spinner" id="btnLoading"></div>
            </button>

            <a href="dashboard.php" class="cancel-link">Cancel & Return to Dashboard</a>
        </form>
    </div>
</div>

<script>
    function switchMethod(methodId, btnElement) {
        document.getElementById('payment_method').value = methodId;
        document.querySelectorAll('.method-btn').forEach(btn => btn.classList.remove('active'));
        btnElement.classList.add('active');

        document.querySelectorAll('.payment-section').forEach(sec => sec.classList.remove('active'));
        document.getElementById('sec-' + methodId).classList.add('active');

        // Clear any previous error styles when switching tabs
        document.querySelectorAll('input, select').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

        const btnText = document.getElementById('btnText');
        const price = "<?= number_format($booking['total_price'], 2) ?>";
        
        if(methodId === 'netbanking') {
            btnText.textContent = `Proceed to Bank (₹${price})`;
        } else if (methodId === 'upi') {
            btnText.textContent = `Verify & Pay ₹${price}`;
        } else {
            btnText.textContent = `Pay ₹${price}`;
        }
    }

    function formatExpiry(input) {
        var val = input.value.replace(/\D/g, '');
        if(val.length > 2) {
            input.value = val.substring(0, 2) + '/' + val.substring(2, 4);
        } else {
            input.value = val;
        }
    }

    // Advanced Form Validation before submission
    document.getElementById('payForm').addEventListener('submit', function(e) {
        // Prevent default submission to validate first
        e.preventDefault(); 
        
        if(<?= $msg ? 'true' : 'false' ?>) { return; }
        
        const method = document.getElementById('payment_method').value;
        let isValid = true;

        // Reset all error states
        document.querySelectorAll('input, select').forEach(el => el.classList.remove('input-error'));
        document.querySelectorAll('.error-msg').forEach(el => el.style.display = 'none');

        // Validate based on the active tab
        if (method === 'card') {
            const fields = ['card_name', 'card_num', 'card_exp', 'card_cvv'];
            fields.forEach(id => {
                const el = document.getElementById(id);
                if(el.value.trim() === '') {
                    el.classList.add('input-error');
                    el.nextElementSibling.style.display = 'block';
                    isValid = false;
                }
            });
        } else if (method === 'upi') {
            const upi = document.getElementById('upi_id');
            if(upi.value.trim() === '') {
                upi.classList.add('input-error');
                upi.nextElementSibling.style.display = 'block';
                isValid = false;
            }
        } else if (method === 'netbanking') {
            const bank = document.getElementById('bank_select');
            if(bank.value === '') {
                bank.classList.add('input-error');
                bank.nextElementSibling.style.display = 'block';
                isValid = false;
            }
        }

        // If validation passes, process the dummy payment
        if (isValid) {
            const btnText = document.getElementById('btnText');
            btnText.style.display = 'none';
            document.getElementById('btnLoading').style.display = 'block';
            document.getElementById('payBtn').style.opacity = '0.8';
            document.getElementById('payBtn').style.pointerEvents = 'none'; 
            
            // Actually submit the form to PHP
            this.submit();
        }
    });
</script>

</body>
</html>