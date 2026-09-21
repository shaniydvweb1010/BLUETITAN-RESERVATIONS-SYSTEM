<?php 
session_start();
// Define base URL for CSS and other assets
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/bbs/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Premium Boat Booking | Set Sail</title>
    <style>
        /* Reset and Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            scroll-behavior: smooth;
        }

        :root {
            --primary-blue: #00d2ff;
            --dark-blue: #3a7bd5;
            --navy: #0f2027;
            --navy-light: #203a43;
            --accent: #ff4b2b;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 15px 30px rgba(0, 0, 0, 0.2);
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
            background-color: var(--light-gray);
            overflow-x: hidden;
        }

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Header Styles - Glassmorphism */
        header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 1.2rem 0;
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            box-shadow: var(--shadow-sm);
            transition: all 0.3s ease;
        }

        header.scrolled {
            padding: 0.8rem 0;
            background: rgba(255, 255, 255, 0.95);
        }

        header .container {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .logo {
            font-size: 2rem;
            font-weight: 800;
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .nav-links {
            display: flex;
            list-style: none;
            gap: 1.5rem;
            align-items: center;
        }

        .nav-links a {
            color: var(--navy);
            text-decoration: none;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            transition: all 0.3s ease;
            font-weight: 600;
            font-size: 0.95rem;
        }

        .nav-links a:hover, .nav-links a.active {
            color: var(--primary-blue);
            background: rgba(0, 210, 255, 0.1);
        }

        .nav-btn {
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white) !important;
            box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
        }
        
        .nav-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 210, 255, 0.4);
        }

        /* Hero Section with Parallax Background */
        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(to right, rgba(15, 32, 39, 0.9), rgba(32, 58, 67, 0.8), rgba(44, 83, 100, 0.7)), 
                        url('https://images.unsplash.com/photo-1534447677768-be436bb09401?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover fixed;
            color: var(--white);
            text-align: center;
            padding-top: 80px;
            overflow: hidden;
        }

        .hero::after {
            content: '';
            position: absolute;
            bottom: -50px;
            left: 0;
            width: 100%;
            height: 100px;
            background: var(--light-gray);
            transform: skewY(-2deg);
        }

        .hero-content {
            position: relative;
            z-index: 2;
            max-width: 800px;
            animation: slideUpFade 1s ease forwards;
        }

        .hero-content h1 {
            font-size: 4.5rem;
            font-weight: 800;
            margin-bottom: 1.5rem;
            line-height: 1.1;
            text-shadow: 2px 4px 10px rgba(0,0,0,0.3);
        }

        .hero-content h1 span {
            color: var(--primary-blue);
        }

        .hero-content p {
            font-size: 1.2rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            font-weight: 300;
        }

        /* Hero Quick Stats */
        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 3rem;
            margin-top: 3rem;
            border-top: 1px solid rgba(255,255,255,0.2);
            padding-top: 2rem;
        }

        .h-stat h4 { font-size: 2rem; color: var(--primary-blue); }
        .h-stat p { font-size: 0.9rem; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; }

        /* Buttons */
        .btn-large {
            display: inline-block;
            padding: 1.2rem 3rem;
            font-size: 1.2rem;
            font-weight: 700;
            border-radius: 50px;
            text-decoration: none;
            text-transform: uppercase;
            letter-spacing: 1px;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            position: relative;
            overflow: hidden;
        }

        .btn-glow {
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white);
            box-shadow: 0 10px 20px rgba(0, 210, 255, 0.3);
        }

        .btn-glow:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 30px rgba(0, 210, 255, 0.5);
            color: var(--white);
        }

        /* Section Global Styles */
        section { padding: 6rem 0; }
        .section-title { text-align: center; margin-bottom: 4rem; }
        .section-title h2 { font-size: 2.8rem; color: var(--navy); margin-bottom: 1rem; }
        .section-title p { color: var(--text-light); font-size: 1.1rem; max-width: 600px; margin: 0 auto; }

        /* Features Section */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
        }

        .feature-card {
            background: var(--white);
            padding: 3rem 2rem;
            border-radius: 20px;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
            text-align: center;
            border-bottom: 4px solid transparent;
            position: relative;
            overflow: hidden;
        }

        .feature-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-bottom-color: var(--primary-blue);
        }

        .feature-icon {
            width: 80px;
            height: 80px;
            margin: 0 auto 1.5rem;
            background: linear-gradient(135deg, rgba(0,210,255,0.1), rgba(58,123,213,0.1));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.5rem;
            color: var(--dark-blue);
            transition: transform 0.3s ease;
        }

        .feature-card:hover .feature-icon {
            transform: scale(1.1) rotate(5deg);
        }

        /* Featured Fleet Section */
        .fleet-section { background: var(--white); }
        .fleet-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 2.5rem; }
        
        .fleet-card {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
            background: var(--light-gray);
            position: relative;
        }

        .fleet-card:hover { transform: translateY(-10px); box-shadow: var(--shadow-lg); }

        .fleet-img {
            height: 250px;
            width: 100%;
            object-fit: cover;
            transition: transform 0.5s ease;
        }

        .fleet-card:hover .fleet-img { transform: scale(1.05); }

        .fleet-badge {
            position: absolute;
            top: 20px;
            right: 20px;
            background: var(--primary-blue);
            color: var(--white);
            padding: 5px 15px;
            border-radius: 20px;
            font-weight: bold;
            font-size: 0.8rem;
            box-shadow: var(--shadow-sm);
        }

        .fleet-info { padding: 2rem; }
        .fleet-info h3 { font-size: 1.5rem; color: var(--navy); margin-bottom: 0.5rem; }
        .fleet-price { color: var(--dark-blue); font-weight: 700; font-size: 1.2rem; margin-bottom: 1rem; }
        .fleet-stats { display: flex; gap: 15px; color: var(--text-light); font-size: 0.9rem; margin-bottom: 1.5rem; border-top: 1px solid #ddd; padding-top: 1rem; }
        
        /* How it works */
        .steps-container { display: flex; justify-content: space-between; position: relative; margin-top: 3rem; }
        .steps-container::before {
            content: ''; position: absolute; top: 40px; left: 10%; right: 10%; height: 2px; background: #ddd; z-index: 1;
        }
        .step { text-align: center; flex: 1; position: relative; z-index: 2; padding: 0 1rem; }
        .step-num { 
            width: 80px; height: 80px; background: var(--navy); color: var(--white); border-radius: 50%; 
            display: flex; align-items: center; justify-content: center; font-size: 2rem; font-weight: bold; 
            margin: 0 auto 1.5rem; border: 5px solid var(--light-gray); transition: all 0.3s ease; box-shadow: var(--shadow-sm);
        }
        .step:hover .step-num { background: var(--primary-blue); transform: scale(1.1); }
        .step h4 { font-size: 1.3rem; margin-bottom: 0.5rem; color: var(--navy); }

        /* CTA Section */
        .cta-section {
            background: linear-gradient(135deg, var(--navy), var(--navy-light));
            color: var(--white);
            text-align: center;
            padding: 8rem 0;
            position: relative;
            overflow: hidden;
        }
        .cta-section::before {
            content: '🌊'; position: absolute; font-size: 20rem; opacity: 0.05; right: -50px; bottom: -100px;
        }

        /* Footer */
        footer {
            background: #0a141a;
            color: rgba(255,255,255,0.7);
            padding: 4rem 0 2rem;
            text-align: center;
        }
        .footer-logo { font-size: 2rem; color: var(--white); margin-bottom: 1rem; font-weight: bold; }

        /* Animations */
        .reveal { opacity: 0; transform: translateY(50px); transition: all 0.8s ease-out; }
        .reveal.active { opacity: 1; transform: translateY(0); }
        
        @keyframes slideUpFade {
            0% { opacity: 0; transform: translateY(50px); }
            100% { opacity: 1; transform: translateY(0); }
        }

        /* Responsive */
        @media (max-width: 968px) {
            .hero-content h1 { font-size: 3rem; }
            .steps-container { flex-direction: column; gap: 3rem; }
            .steps-container::before { display: none; }
            .hero-stats { flex-wrap: wrap; gap: 1.5rem; }
        }

        @media (max-width: 768px) {
            .nav-links { display: none; /* In a full app, add a hamburger menu here */ }
            .hero-content h1 { font-size: 2.5rem; }
        }
    </style>
</head>
<body>

    <header id="main-header">
        <div class="container">
            <div class="logo">
                ⛵ BoatBooking
            </div>
            <nav>
                <ul class="nav-links">
                    <li><a href="<?php echo $base_url; ?>index.php" class="active">Home</a></li>
                    <li><a href="<?php echo $base_url; ?>boats.php">Fleet</a></li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                            <li><a href="<?php echo $base_url; ?>admin.php" style="color: var(--accent);">Admin Panel</a></li>
                        <?php else: ?>
                            <li><a href="<?php echo $base_url; ?>dashboard.php">Dashboard</a></li>
                            <li><a href="<?php echo $base_url; ?>profile.php">Profile</a></li>
                        <?php endif; ?>
                        
                        <li><a href="<?php echo $base_url; ?>logout.php" class="nav-btn" style="background: var(--accent);">Logout</a></li>
                    <?php else: ?>
                        <li><a href="<?php echo $base_url; ?>login.php">Log In</a></li>
                        <li><a href="<?php echo $base_url; ?>registration.php" class="nav-btn">Sign Up Free</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    </header>

    <section class="hero">
        <div class="container">
            <div class="hero-content">
                <h1>Set Sail on Your <span>Next Great Adventure</span></h1>
                <p>Discover, compare, and instantly book the world's finest yachts, speedboats, and pontoons. Escape the ordinary and conquer the waves today.</p>
                
                <a href="<?php echo $base_url; ?>boats.php" class="btn-large btn-glow">Explore Fleet 🚤</a>
                
                <div class="hero-stats">
                    <div class="h-stat"><h4>50+</h4><p>Premium Boats</p></div>
                    <div class="h-stat"><h4>1000+</h4><p>Happy Sailors</p></div>
                    <div class="h-stat"><h4>24/7</h4><p>Support</p></div>
                </div>
            </div>
        </div>
    </section>

    <section class="container reveal">
        <div class="section-title">
            <h2>Why Choose BoatBooking?</h2>
            <p>We provide a seamless, secure, and luxurious watercraft rental experience tailored for you.</p>
        </div>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">💎</div>
                <h3>Luxury Selection</h3>
                <p>From high-speed powerboats to massive party yachts, we have the perfect vessel for your vibe.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">⚡</div>
                <h3>Instant Booking</h3>
                <p>No waiting around. Browse availability in real-time and secure your reservation instantly.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">🛡️</div>
                <h3>Fully Insured</h3>
                <p>Sail with peace of mind. All our boats are fully insured and captained by certified professionals.</p>
            </div>
            <div class="feature-card">
                <div class="feature-icon">💰</div>
                <h3>Transparent Pricing</h3>
                <p>What you see is what you pay. No hidden fees, no middleman markups. Just pure value.</p>
            </div>
        </div>
    </section>

    <section class="fleet-section reveal">
        <div class="container">
            <div class="section-title">
                <h2>Top Trending Vessels</h2>
                <p>Take a peek at our most popular rentals this week.</p>
            </div>

            <div class="fleet-grid">
                <div class="fleet-card">
                    <div class="fleet-badge">⭐ Popular</div>
                    <img src="https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Yacht" class="fleet-img">
                    <div class="fleet-info">
                        <h3>Ocean Majestic</h3>
                        <div class="fleet-price">₹12,000 / hour</div>
                        <div class="fleet-stats">
                            <span>🛥️ Luxury Yacht</span>
                            <span>👥 12 Guests</span>
                        </div>
                        <a href="<?php echo $base_url; ?>boats.php" style="color:var(--primary-blue); font-weight:bold; text-decoration:none;">View Details →</a>
                    </div>
                </div>

                <div class="fleet-card">
                    <div class="fleet-badge">⚡ Fast</div>
                    <img src="https://images.unsplash.com/photo-1605281317010-fe5ffe798166?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Speedboat" class="fleet-img">
                    <div class="fleet-info">
                        <h3>Nitro Wave</h3>
                        <div class="fleet-price">₹3,500 / hour</div>
                        <div class="fleet-stats">
                            <span>🚤 Speedboat</span>
                            <span>👥 6 Guests</span>
                        </div>
                        <a href="<?php echo $base_url; ?>boats.php" style="color:var(--primary-blue); font-weight:bold; text-decoration:none;">View Details →</a>
                    </div>
                </div>

                <div class="fleet-card">
                    <img src="https://images.unsplash.com/photo-1544644181-1484b3fdfc62?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80" alt="Pontoon" class="fleet-img">
                    <div class="fleet-info">
                        <h3>Sunset Relaxer</h3>
                        <div class="fleet-price">₹1,800 / hour</div>
                        <div class="fleet-stats">
                            <span>⛵ Pontoon</span>
                            <span>👥 8 Guests</span>
                        </div>
                        <a href="<?php echo $base_url; ?>boats.php" style="color:var(--primary-blue); font-weight:bold; text-decoration:none;">View Details →</a>
                    </div>
                </div>
            </div>
            
            <div style="text-align: center; margin-top: 4rem;">
                <a href="<?php echo $base_url; ?>boats.php" class="btn-large" style="background: transparent; border: 2px solid var(--navy); color: var(--navy);">View Entire Fleet</a>
            </div>
        </div>
    </section>

    <section class="container reveal">
        <div class="section-title">
            <h2>How It Works</h2>
            <p>Three simple steps to get you on the water.</p>
        </div>
        
        <div class="steps-container">
            <div class="step">
                <div class="step-num">1</div>
                <h4>Browse the Fleet</h4>
                <p style="color: var(--text-light);">Find the perfect boat for your occasion, budget, and group size.</p>
            </div>
            <div class="step">
                <div class="step-num">2</div>
                <h4>Pick a Time</h4>
                <p style="color: var(--text-light);">Select your preferred date and time slot. We guarantee real-time availability.</p>
            </div>
            <div class="step">
                <div class="step-num">3</div>
                <h4>Set Sail</h4>
                <p style="color: var(--text-light);">Arrive at the dock, meet your captain, and enjoy a memorable experience.</p>
            </div>
        </div>
    </section>

    <section class="cta-section reveal">
        <div class="container" style="position: relative; z-index: 2;">
            <h2 style="font-size: 3.5rem; margin-bottom: 1.5rem;">Ready to make waves?</h2>
            <p style="font-size: 1.2rem; margin-bottom: 3rem; opacity: 0.9;">Join thousands of others and create unforgettable memories.</p>
            <?php if(!isset($_SESSION['user_id'])): ?>
                <a href="<?php echo $base_url; ?>registration.php" class="btn-large btn-glow" style="background: var(--accent);">Create Free Account</a>
            <?php else: ?>
                <a href="<?php echo $base_url; ?>boats.php" class="btn-large btn-glow" style="background: var(--accent);">Book a Boat Now</a>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <div class="footer-logo">⛵ BoatBooking</div>
            <p style="margin-bottom: 1rem;">Mumbai's Premium Watercraft Rental Service.</p>
            <p style="font-size: 0.9rem; opacity: 0.5;">&copy; <?php echo date('Y'); ?> BoatBooking System. All rights reserved.</p>
        </div>
    </footer>

    <script>
        // Header scroll effect
        window.addEventListener('scroll', () => {
            const header = document.getElementById('main-header');
            if (window.scrollY > 50) {
                header.classList.add('scrolled');
            } else {
                header.classList.remove('scrolled');
            }
        });

        // Scroll Reveal Animation Observer
        function reveal() {
            var reveals = document.querySelectorAll(".reveal");
            for (var i = 0; i < reveals.length; i++) {
                var windowHeight = window.innerHeight;
                var elementTop = reveals[i].getBoundingClientRect().top;
                var elementVisible = 100;
                
                if (elementTop < windowHeight - elementVisible) {
                    reveals[i].classList.add("active");
                }
            }
        }
        window.addEventListener("scroll", reveal);
        // Trigger once on load
        reveal();
    </script>
</body>
</html>