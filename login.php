<?php
session_start();
require_once "config/database.php";

// 1. Redirect if already logged in
if(isset($_SESSION['user_id'])){
    if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'){
        header("Location: admin.php");
    } else {
        header("Location: dashboard.php");
    }
    exit();
}

$database = new Database();
$db = $database->getConnection();
$error = '';

// 2. Handle Login Request (DIRECT PDO METHOD)
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    $email = trim($_POST['email']);
    $password = $_POST['password']; // Never trim passwords!
    
    // Direct Database Query
    $query = "SELECT id, username, password, full_name, role FROM users WHERE email = ? LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$email]);
    
    if($stmt->rowCount() > 0) {
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        // Verify the raw password against the hashed password in the DB
        if(password_verify($password, $user['password'])){
            
            // Create Session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            // Route based on role
            if($_SESSION['role'] === 'admin'){
                header("Location: admin.php");
            } else {
                header("Location: dashboard.php");
            }
            exit();
        } else {
            $error = "Invalid email or password.";
        }
    } else {
        $error = "Invalid email or password.";
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
    <title>Log In - BoatBooking</title>
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

        @keyframes gradientBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        /* Modern Navbar */
        header {
            background: rgba(255, 255, 255, 0.85); backdrop-filter: blur(12px);
            padding: 1rem 0; position: fixed; width: 100%; top: 0; z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }
        header .container { width: 90%; max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }
        .logo { font-size: 1.8rem; font-weight: 800; background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); -webkit-background-clip: text; -webkit-text-fill-color: transparent; text-decoration: none; }
        .nav-links { display: flex; list-style: none; gap: 1.5rem; align-items: center; }
        .nav-links a { color: var(--navy); text-decoration: none; font-weight: 600; padding: 0.5rem 1rem; border-radius: 20px; transition: 0.3s; }
        .nav-links a:hover { background: rgba(0, 210, 255, 0.1); color: var(--primary-blue); }

        /* Auth Container - Split Design */
        .auth-wrapper {
            flex: 1; display: flex; align-items: center; justify-content: center;
            padding: 100px 20px 40px; /* Space for fixed header */
        }

        .auth-card {
            display: flex; width: 100%; max-width: 900px;
            background: rgba(255, 255, 255, 0.95); backdrop-filter: blur(20px);
            border-radius: 20px; box-shadow: var(--shadow-lg);
            overflow: hidden; animation: slideUp 0.8s ease forwards;
        }

        @keyframes slideUp { from { opacity: 0; transform: translateY(40px); } to { opacity: 1; transform: translateY(0); } }

        /* Left Side: Visual */
        .auth-visual {
            flex: 1;
            background: linear-gradient(to bottom, rgba(15, 32, 39, 0.2), rgba(44, 83, 100, 0.8)), 
                        url('https://images.unsplash.com/photo-1569263979104-865ab7cd8d13?ixlib=rb-4.0.3&auto=format&fit=crop&w=1000&q=80') center/cover;
            padding: 3rem; display: flex; flex-direction: column; justify-content: center;
            color: var(--white); position: relative;
        }
        .auth-visual h2 { font-size: 2.5rem; margin-bottom: 1rem; line-height: 1.2; text-shadow: 0 4px 10px rgba(0,0,0,0.3); }
        .auth-visual p { font-size: 1.1rem; opacity: 0.9; text-shadow: 0 2px 5px rgba(0,0,0,0.3); }

        /* Right Side: Form */
        .auth-form-container { flex: 1; padding: 4rem; display: flex; flex-direction: column; justify-content: center; }
        .auth-header { margin-bottom: 2rem; }
        .auth-header h3 { font-size: 2.2rem; color: var(--navy); margin-bottom: 0.5rem; }
        .auth-header p { color: var(--text-light); }

        /* Form Styling */
        .form-group { margin-bottom: 1.5rem; position: relative; }
        .form-group label { display: block; margin-bottom: 0.5rem; font-weight: 600; font-size: 0.95rem; color: var(--navy); }
        
        input {
            width: 100%; padding: 1rem 1.2rem; border: 2px solid var(--gray); border-radius: 12px;
            font-size: 1rem; transition: all 0.3s ease; background: var(--light-gray); color: var(--navy);
        }
        input:focus { outline: none; border-color: var(--primary-blue); background: var(--white); box-shadow: 0 0 0 4px rgba(0, 210, 255, 0.1); }

        .password-container { position: relative; }
        .toggle-password {
            position: absolute; right: 15px; top: 50%; transform: translateY(-50%);
            background: none; border: none; cursor: pointer; font-size: 1.2rem; color: var(--text-light); transition: 0.3s;
        }
        .toggle-password:hover { color: var(--primary-blue); }

        /* Button */
        .btn-submit {
            width: 100%; padding: 1.2rem; margin-top: 1rem;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white); border: none; border-radius: 12px;
            font-size: 1.1rem; font-weight: 700; cursor: pointer; transition: all 0.3s ease;
            box-shadow: 0 8px 20px rgba(0, 210, 255, 0.3); display: flex; justify-content: center; align-items: center; gap: 10px;
        }
        .btn-submit:hover:not(:disabled) { transform: translateY(-3px); box-shadow: 0 12px 25px rgba(0, 210, 255, 0.4); }
        .btn-submit:disabled { opacity: 0.7; cursor: not-allowed; }

        /* Links */
        .auth-footer { text-align: center; margin-top: 2.5rem; font-size: 0.95rem; color: var(--text-light); }
        .auth-footer a { color: var(--dark-blue); font-weight: 700; text-decoration: none; transition: 0.3s; }
        .auth-footer a:hover { color: var(--primary-blue); text-decoration: underline; }

        /* Alerts */
        .alert { padding: 1rem; border-radius: 10px; margin-bottom: 1.5rem; font-weight: 600; font-size: 0.95rem; background: #fee2e2; color: #991b1b; border-left: 4px solid var(--red); animation: shake 0.5s ease;}
        @keyframes shake { 0%, 100% { transform: translateX(0); } 25% { transform: translateX(-5px); } 75% { transform: translateX(5px); } }

        /* Loading Spinner */
        .spinner { width: 20px; height: 20px; border: 3px solid rgba(255,255,255,0.3); border-radius: 50%; border-top-color: #fff; animation: spin 1s ease-in-out infinite; display: none; }
        @keyframes spin { to { transform: rotate(360deg); } }

        /* Responsive */
        @media (max-width: 900px) {
            .auth-card { flex-direction: column; max-width: 450px; }
            .auth-visual { padding: 4rem 2rem; text-align: center; }
            .auth-form-container { padding: 2.5rem; }
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
                    <li><a href="<?php echo $base_url; ?>registration.php">Sign Up</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <div class="auth-wrapper">
        <div class="auth-card">
            
            <div class="auth-visual">
                <h2>Welcome Back!</h2>
                <p>Log in to manage your reservations, explore the fleet, and plan your next voyage.</p>
            </div>

            <div class="auth-form-container">
                <div class="auth-header">
                    <h3>Sign In</h3>
                    <p>Access your BoatBooking account.</p>
                </div>

                <?php if($error): ?>
                    <div class="alert">⚠️ <?php echo $error; ?></div>
                <?php endif; ?>

                <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" id="loginForm">
                    
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <input type="email" id="email" name="email" required 
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               placeholder="Enter your email">
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="password-container">
                            <input type="password" id="password" name="password" required 
                                   placeholder="Enter your password">
                            <button type="button" class="toggle-password" onclick="togglePassword()">👁️</button>
                        </div>
                    </div>

                    <button type="submit" class="btn-submit" id="loginBtn">
                        <span id="btnText">Log In</span>
                        <div class="spinner" id="btnLoading"></div>
                    </button>
                </form>

                <div class="auth-footer">
                    <p>New to BoatBooking? <a href="<?php echo $base_url; ?>registration.php">Create an account</a></p>
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

        // Form submission loading state
        document.getElementById('loginForm').addEventListener('submit', function() {
            const btn = document.getElementById('loginBtn');
            const btnText = document.getElementById('btnText');
            const btnLoading = document.getElementById('btnLoading');
            
            if(btn.disabled) return false;
            
            btn.disabled = true;
            btnText.textContent = 'Authenticating...';
            btnLoading.style.display = 'block';
        });

        // Auto-focus email field on load
        document.addEventListener('DOMContentLoaded', function() {
            if(!document.getElementById('email').value) {
                document.getElementById('email').focus();
            } else {
                document.getElementById('password').focus();
            }
        });
    </script>
</body>
</html>