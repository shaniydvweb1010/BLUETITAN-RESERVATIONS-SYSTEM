<?php
session_start();
require_once "config/database.php";

// Redirect if already logged in
if(isset($_SESSION['user_id'])){
    header("Location: dashboard.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

$error = '';
$success = '';

// Handle Registration (DIRECT PDO METHOD)
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Do not alter the raw password
    $full_name = trim($_POST['full_name']);
    $phone = trim($_POST['phone']);
    
    // 1. Check if username exists
    $check_username = $db->prepare("SELECT id FROM users WHERE username = ?");
    $check_username->execute([$username]);
    
    // 2. Check if email exists
    $check_email = $db->prepare("SELECT id FROM users WHERE email = ?");
    $check_email->execute([$email]);
    
    if($check_username->rowCount() > 0){
        $error = "Username already exists. Please choose another.";
    }
    elseif($check_email->rowCount() > 0){
        $error = "Email is already registered. Please log in.";
    }
    else {
        // 3. Hash password securely
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // 4. Insert new user
        $query = "INSERT INTO users (username, email, password, full_name, phone, role) 
                  VALUES (?, ?, ?, ?, ?, 'user')";
        $stmt = $db->prepare($query);
        
        if($stmt->execute([$username, $email, $password_hash, $full_name, $phone])){
            $success = "🎉 Registration successful! You will be redirected to login.";
        } else {
            $error = "❌ Registration failed. Please try again.";
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
    <title>Create Account - BoatBooking</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

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
            --shadow-lg: 0 20px 40px rgba(0, 0, 0, 0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            color: var(--text-dark);
            /* Full screen subtle animated background */
            background: linear-gradient(-45deg, #f8f9fa, #e0eafc, #cfdef3);
            background-size: 400% 400%;
            animation: gradientBG 15s ease infinite;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        @keyframes gradientBG {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Modern Navbar */
        header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            padding: 1rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        header .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 1.8rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .nav-links { display: flex; list-style: none; gap: 1.5rem; align-items: center; }
        .nav-links a {
            color: var(--navy); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 20px; transition: 0.3s;
        }
        .nav-links a:hover { background: rgba(0, 210, 255, 0.1); color: var(--primary-blue); }

        /* Auth Container - Split Design */
        .auth-wrapper {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 100px 20px 40px; /* Space for fixed header */
        }

        .auth-card {
            display: flex;
            width: 100%;
            max-width: 1000px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 20px;
            box-shadow: var(--shadow-lg);
            overflow: hidden;
            animation: slideUp 0.8s ease forwards;
        }

        @keyframes slideUp {
            from { opacity: 0; transform: translateY(40px); }
            to { opacity: 1; transform: translateY(0); }
        }

        /* Left Side: Visual */
        .auth-visual {
            flex: 1;
            background: linear-gradient(to bottom, rgba(15, 32, 39, 0.4), rgba(44, 83, 100, 0.8)), 
                        url('https://images.unsplash.com/photo-1544551763-46a013bb70d5?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;
            padding: 3rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            color: var(--white);
            position: relative;
        }

        .auth-visual h2 { font-size: 2.5rem; margin-bottom: 1rem; line-height: 1.2; text-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .auth-visual p { font-size: 1.1rem; opacity: 0.9; text-shadow: 0 2px 5px rgba(0,0,0,0.3); }
        .auth-badge { 
            position: absolute; top: 2rem; left: 2rem; background: rgba(255,255,255,0.2); 
            backdrop-filter: blur(5px); padding: 0.5rem 1rem; border-radius: 20px; font-weight: bold; letter-spacing: 1px;
        }

        /* Right Side: Form */
        .auth-form-container {
            flex: 1.2;
            padding: 3rem 4rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }

        .auth-header { margin-bottom: 2rem; }
        .auth-header h3 { font-size: 2rem; color: var(--navy); margin-bottom: 0.5rem; }
        .auth-header p { color: var(--text-light); }

        /* Form Styling */
        .form-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; }
        .form-group { margin-bottom: 1.2rem; position: relative; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.9rem; color: var(--navy); }
        
        input {
            width: 100%;
            padding: 0.9rem 1.2rem;
            border: 2px solid var(--gray);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--light-gray);
            color: var(--navy);
        }

        input:focus {
            outline: none;
            border-color: var(--primary-blue);
            background: var(--white);
            box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1);
        }

        .password-container { position: relative; }
        .toggle-password {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1.2rem;
            color: var(--text-light);
            transition: color 0.3s;
        }
        .toggle-password:hover { color: var(--primary-blue); }

        /* Password Strength */
        .password-strength { height: 4px; background: var(--gray); border-radius: 2px; margin-top: 0.5rem; overflow: hidden; }
        .password-strength-bar { height: 100%; width: 0%; border-radius: 2px; transition: all 0.4s ease; }
        .strength-weak { background: var(--red); width: 25%; }
        .strength-fair { background: #f39c12; width: 50%; }
        .strength-good { background: var(--primary-blue); width: 75%; }
        .strength-strong { background: var(--green); width: 100%; }

        .password-requirements { display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem; font-size: 0.75rem; margin-top: 0.8rem; }
        .requirement { display: flex; align-items: center; gap: 4px; color: var(--text-light); transition: color 0.3s; }
        .requirement.met { color: var(--green); font-weight: 600; }

        /* Button */
        .btn-submit {
            width: 100%;
            padding: 1rem;
            margin-top: 1rem;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white);
            border: none;
            border-radius: 12px;
            font-size: 1.1rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 210, 255, 0.3);
            display: flex;
            justify-content: center;
            align-items: center;
            gap: 10px;
        }

        .btn-submit:hover:not(:disabled) {
            transform: translateY(-3px);
            box-shadow: 0 12px 25px rgba(0, 210, 255, 0.4);
        }

        .btn-submit:disabled { opacity: 0.6; cursor: not-allowed; transform: none; }

        /* Links */
        .auth-footer { text-align: center; margin-top: 2rem; font-size: 0.95rem; color: var(--text-light); }
        .auth-footer a { color: var(--dark-blue); font-weight: 700; text-decoration: none; transition: 0.3s; }
        .auth-footer a:hover { color: var(--primary-blue); text-decoration: underline; }

        /* Alerts */
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600; font-size: 0.95rem; display: flex; align-items: center; gap: 10px; }
        .alert-error { background: #fee2e2; color: #991b1b; border-left: 4px solid var(--red); }
        .alert-success { background: #dcfce7; color: #166534; border-left: 4px solid var(--green); }
        
        /* Loading Spinner */
        .spinner { width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-card { flex-direction: column; max-width: 500px; }
            .auth-visual { padding: 4rem 2rem; text-align: center; }
            .auth-badge { display: none; }
            .auth-form-container { padding: 2.5rem; }
        }

        @media (max-width: 600px) {
            .form-grid { grid-template-columns: 1fr; gap: 0; }
            .auth-form-container { padding: 2rem 1.5rem; }
            .password-requirements { grid-template-columns: 1fr; }
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
                    <li><a href="<?php echo $base_url; ?>login.php">Log In</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card">
            
            <div class="auth-visual">
                <div class="auth-badge">🌟 Premium Members</div>
                <h2>Set sail on a new journey.</h2>
                <p>Create an account to instantly book luxury yachts, speedboats, and more. Your next adventure awaits.</p>
            </div>

            <div class="auth-form-container">
                <div class="auth-header">
                    <h3>Create Account</h3>
                    <p>Join our community of ocean explorers.</p>
                </div>

                <?php if($error): ?>
                    <div class="alert alert-error">⚠️ <?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if($success): ?>
                    <div class="alert alert-success">✅ <?php echo $success; ?></div>
                    <script>
                        // Redirect to login after 3 seconds if successful
                        setTimeout(() => { window.location.href = 'login.php'; }, 3000);
                    </script>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="registerForm">
                    
                    <div class="form-group">
                        <label for="full_name">Full Name</label>
                        <input type="text" id="full_name" name="full_name" required 
                               value="<?php echo isset($_POST['full_name']) ? htmlspecialchars($_POST['full_name']) : ''; ?>"
                               placeholder="John Doe">
                    </div>

                    <div class="form-grid">
                        <div class="form-group">
                            <label for="username">Username</label>
                            <input type="text" id="username" name="username" required 
                                   value="<?php echo isset($_POST['username']) ? htmlspecialchars($_POST['username']) : ''; ?>"
                                   placeholder="johndoe99">
                        </div>

                        <div class="form-group">
                            <label for="phone">Phone Number</label>
                            <input type="tel" id="phone" name="phone" 
                                   value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                                   placeholder="+91 98765 43210">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="john@example.com">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Create a strong password"
                                   oninput="checkPasswordStrength()">
                            <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                        </div>
                        <div class="password-strength">
                            <div class="password-strength-bar" id="passwordStrengthBar"></div>
                        </div>
                        <div class="password-requirements">
                            <div class="requirement unmet" id="reqLength"><span>○</span> 8+ characters</div>
                            <div class="requirement unmet" id="reqUpper"><span>○</span> 1 uppercase</div>
                            <div class="requirement unmet" id="reqLower"><span>○</span> 1 lowercase</div>
                            <div class="requirement unmet" id="reqNumber"><span>○</span> 1 number</div>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="registerBtn" disabled>
                        <span id="btnText">Create Account</span>
                        <div class="spinner" id="btnLoading"></div>
                    </button>
                </form>

                <div class="auth-footer">
                    <p>Already have an account? <a href="<?php echo $base_url; ?>login.php">Log in here</a></p>
                </div>
            </div>

        </div>
    </div>

    <script>
        // Password Visibility Toggle
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.toggle-password');
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🔒';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        // Real-time Password Strength Checker
        function checkPasswordStrength() {
            const password = document.getElementById('password').value;
            const strengthBar = document.getElementById('passwordStrengthBar');
            const requirements = {
                length: document.getElementById('reqLength'),
                upper: document.getElementById('reqUpper'),
                lower: document.getElementById('reqLower'),
                number: document.getElementById('reqNumber')
            };

            let strength = 0;
            let meetsRequirements = 0;

            // Helper function to update UI for requirements
            const updateReq = (reqElement, isMet) => {
                if(isMet) {
                    reqElement.classList.add('met');
                    reqElement.classList.remove('unmet');
                    reqElement.innerHTML = '<span>✓</span> ' + reqElement.innerText.substring(2);
                    strength += 25;
                    meetsRequirements++;
                } else {
                    reqElement.classList.add('unmet');
                    reqElement.classList.remove('met');
                    reqElement.innerHTML = '<span>○</span> ' + reqElement.innerText.substring(2);
                }
            };

            updateReq(requirements.length, password.length >= 8);
            updateReq(requirements.upper, /[A-Z]/.test(password));
            updateReq(requirements.lower, /[a-z]/.test(password));
            updateReq(requirements.number, /[0-9]/.test(password));

            // Update strength bar color and width
            strengthBar.className = 'password-strength-bar';
            if (strength === 0) { strengthBar.style.width = '0%'; }
            else if (strength <= 25) { strengthBar.classList.add('strength-weak'); }
            else if (strength <= 50) { strengthBar.classList.add('strength-fair'); }
            else if (strength <= 75) { strengthBar.classList.add('strength-good'); }
            else { strengthBar.classList.add('strength-strong'); }

            // Enable/disable submit button
            const submitBtn = document.getElementById('registerBtn');
            if (meetsRequirements >= 3 && password.length >= 8) {
                submitBtn.disabled = false;
            } else {
                submitBtn.disabled = true;
            }
        }

        // Form submission loading state
        document.getElementById('registerForm').addEventListener('submit', function() {
            const btn = document.getElementById('registerBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            // Prevent double clicking
            if(btn.disabled) return false;
            
            btn.style.opacity = '0.8';
            btnText.textContent = 'Setting up...';
            btnLoading.style.display = 'block';
        });

        // Initialize password check in case of browser autofill
        document.addEventListener('DOMContentLoaded', function() {
            if(document.getElementById('password').value !== '') {
                checkPasswordStrength();
            }
            document.getElementById('full_name').focus();
        });
    </script>
</body>
</html>