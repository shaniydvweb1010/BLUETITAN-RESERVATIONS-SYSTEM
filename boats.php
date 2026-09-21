<?php
session_start();
require_once "config/database.php";

$database = new Database();
$db = $database->getConnection();

// Fetch all available boats
$query = "SELECT * FROM boats WHERE available = 1 ORDER BY id DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$boats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Define base URL for consistent paths
$base_url = 'http://' . $_SERVER['HTTP_HOST'] . '/bbs/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Fleet - BoatBooking</title>
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
            --accent: #ff4b2b;
            --white: #ffffff;
            --light-gray: #f8f9fa;
            --gray: #ecf0f1;
            --text-dark: #2c3e50;
            --text-light: #7f8c8d;
            --shadow-sm: 0 4px 6px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 15px 30px rgba(0, 0, 0, 0.15);
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: var(--text-dark);
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

        .container {
            width: 90%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 15px;
        }

        /* Modern Navbar */
        header {
            background: rgba(255, 255, 255, 0.85);
            backdrop-filter: blur(12px);
            padding: 1rem 0;
            position: sticky;
            top: 0;
            z-index: 1000;
            box-shadow: 0 2px 10px rgba(0,0,0,0.05);
        }

        header .container {
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
        .nav-links a:hover, .nav-links a.active { background: rgba(0, 210, 255, 0.1); color: var(--primary-blue); }
        .nav-btn {
            background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue));
            color: var(--white) !important;
            box-shadow: 0 4px 15px rgba(0, 210, 255, 0.3);
        }
        .nav-btn:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(0, 210, 255, 0.4); }

        /* Page Header */
        .page-header {
            background: linear-gradient(to right, rgba(15, 32, 39, 0.8), rgba(32, 58, 67, 0.8)), 
                        url('https://images.unsplash.com/photo-1567899378494-47b22a2ae96a?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80') center/cover;
            color: var(--white);
            text-align: center;
            padding: 5rem 0 4rem;
            margin-bottom: 2rem;
            border-radius: 0 0 30px 30px;
            box-shadow: var(--shadow-lg);
        }

        .page-header h1 { font-size: 3.5rem; font-weight: 800; margin-bottom: 1rem; text-shadow: 2px 4px 10px rgba(0,0,0,0.3); }
        .page-header p { font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto; }

        /* Filters Section */
        .filters {
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            padding: 1.5rem 2rem;
            border-radius: 15px;
            margin: -4rem auto 3rem;
            max-width: 800px;
            box-shadow: var(--shadow-lg);
            position: relative;
            z-index: 10;
            border: 1px solid rgba(255,255,255,0.5);
        }

        .filter-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
        }

        .filter-group { display: flex; flex-direction: column; gap: 0.5rem; }
        .filter-group label { font-weight: 600; color: var(--navy); font-size: 0.9rem; text-transform: uppercase; letter-spacing: 0.5px; }
        .filter-group select {
            padding: 0.8rem 1rem;
            border: 2px solid var(--gray);
            border-radius: 10px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: var(--white);
            color: var(--navy);
            cursor: pointer;
        }
        .filter-group select:focus { outline: none; border-color: var(--primary-blue); box-shadow: 0 0 0 3px rgba(0, 210, 255, 0.1); }

        /* Boats Grid */
        .boats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.5rem;
            padding-bottom: 4rem;
        }

        /* Boat Card */
        .boat-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            transition: all 0.4s ease;
            border: 1px solid rgba(255, 255, 255, 0.5);
            display: flex;
            flex-direction: column;
        }

        .boat-card:hover {
            transform: translateY(-10px);
            box-shadow: var(--shadow-lg);
            border-color: rgba(0, 210, 255, 0.3);
        }

        .boat-image-container { position: relative; height: 220px; overflow: hidden; }
        
        .boat-image {
            width: 100%; height: 100%; object-fit: cover; transition: transform 0.6s ease;
        }
        .boat-card:hover .boat-image { transform: scale(1.08); }

        .boat-badge {
            position: absolute; top: 15px; right: 15px;
            background: linear-gradient(135deg, var(--primary-blue), var(--dark-blue));
            color: var(--white); padding: 0.5rem 1rem; border-radius: 20px;
            font-size: 0.8rem; font-weight: 700; box-shadow: var(--shadow-sm);
            letter-spacing: 0.5px;
        }

        .boat-info { padding: 1.5rem; flex: 1; display: flex; flex-direction: column; }
        .boat-info h3 { font-size: 1.4rem; color: var(--navy); margin-bottom: 0.5rem; }
        
        .boat-specs {
            display: flex; gap: 1rem; margin: 1rem 0; padding: 1rem 0;
            border-top: 1px solid var(--gray); border-bottom: 1px solid var(--gray);
        }
        .spec-item { display: flex; align-items: center; gap: 0.4rem; font-size: 0.9rem; color: var(--text-light); font-weight: 500; }
        
        .boat-price { color: var(--dark-blue); font-size: 1.5rem; font-weight: 800; margin-bottom: 1rem; }
        .boat-description { color: var(--text-light); font-size: 0.95rem; margin-bottom: 1.5rem; flex: 1; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; }

        /* Buttons */
        .btn-book {
            display: block; width: 100%; text-align: center;
            background: linear-gradient(135deg, var(--navy), var(--dark-blue));
            color: var(--white); padding: 1rem; border-radius: 12px;
            text-decoration: none; font-weight: 700; text-transform: uppercase;
            letter-spacing: 1px; transition: all 0.3s ease;
        }
        .btn-book:hover { background: linear-gradient(135deg, var(--dark-blue), var(--primary-blue)); transform: translateY(-2px); box-shadow: 0 5px 15px rgba(0, 210, 255, 0.3); }

        .btn-login {
            background: transparent; color: var(--navy); border: 2px solid var(--navy);
        }
        .btn-login:hover { background: var(--navy); color: var(--white); }

        /* Empty State */
        .empty-state { text-align: center; padding: 4rem 2rem; grid-column: 1 / -1; }
        .empty-state .icon { font-size: 4rem; margin-bottom: 1rem; opacity: 0.5; }
        .empty-state h3 { font-size: 1.8rem; color: var(--navy); margin-bottom: 0.5rem; }
        .empty-state p { color: var(--text-light); }

        /* Footer */
        footer { background: #0a141a; color: rgba(255,255,255,0.7); padding: 3rem 0; text-align: center; margin-top: auto; }

        /* Animations */
        .fade-in { animation: fadeInUp 0.6s ease forwards; opacity: 0; transform: translateY(30px); }
        @keyframes fadeInUp { to { opacity: 1; transform: translateY(0); } }

        /* Responsive */
        @media (max-width: 768px) {
            .filters { margin: 1rem auto 2rem; border-radius: 10px; }
            .page-header { padding: 3rem 0; border-radius: 0; }
            .page-header h1 { font-size: 2.5rem; }
            .nav-links { display: none; /* Can be replaced with hamburger menu */ }
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
                    <li><a href="<?php echo $base_url; ?>boats.php" class="active">Fleet</a></li>
                    
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

    <div class="page-header">
        <div class="container">
            <h1>Our Premium Fleet</h1>
            <p>From high-speed thrill rides to luxurious relaxation, find the perfect vessel for your next aquatic adventure.</p>
        </div>
    </div>

    <section class="container">
        <div class="filters fade-in">
            <div class="filter-grid">
                <div class="filter-group">
                    <label for="boat-type">Vessel Category</label>
                    <select id="boat-type" onchange="filterBoats()">
                        <option value="all">All Categories</option>
                        <option value="Speedboat">🚤 Speedboat</option>
                        <option value="Yacht">🛥️ Luxury Yacht</option>
                        <option value="Fishing Boat">🎣 Fishing Boat</option>
                        <option value="Pontoon">⛵ Pontoon</option>
                        <option value="Ship">🚢 Ship / Cruise</option>
                        <option value="Sailboat">⛵ Sailboat</option>
                        <option value="Catamaran">🛶 Catamaran</option>
                        <option value="Other">🌊 Other</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="capacity">Minimum Guests</label>
                    <select id="capacity" onchange="filterBoats()">
                        <option value="0">Any Size</option>
                        <option value="2">2+ Guests (Couples)</option>
                        <option value="4">4+ Guests (Family)</option>
                        <option value="8">8+ Guests (Groups)</option>
                        <option value="15">15+ Guests (Parties)</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="boats-grid" id="boats-container">
            <?php if(empty($boats)): ?>
                <div class="empty-state">
                    <div class="icon">⚓</div>
                    <h3>No Vessels Available</h3>
                    <p>We're currently updating our fleet. Please check back soon.</p>
                </div>
            <?php else: ?>
                <?php foreach($boats as $index => $boat): ?>
                    <div class="boat-card fade-in" 
                         style="animation-delay: <?php echo $index * 0.1; ?>s;" 
                         data-type="<?php echo htmlspecialchars($boat['type']); ?>" 
                         data-capacity="<?php echo $boat['capacity']; ?>">
                        
                        <div class="boat-image-container">
                            <img src="classes/assets/images/<?php echo htmlspecialchars($boat['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($boat['name']); ?>" 
                                 class="boat-image"
                                 onerror="this.src='https://images.unsplash.com/photo-1544644181-1484b3fdfc62?ixlib=rb-4.0.3&auto=format&fit=crop&w=600&q=80'">
                            <div class="boat-badge">Available Now</div>
                        </div>
                        
                        <div class="boat-info">
                            <h3><?php echo htmlspecialchars($boat['name']); ?></h3>
                            
                            <div class="boat-specs">
                                <div class="spec-item">
                                    <span>🛥️</span>
                                    <span><?php echo htmlspecialchars($boat['type']); ?></span>
                                </div>
                                <div class="spec-item">
                                    <span>👥</span>
                                    <span>Up to <?php echo $boat['capacity']; ?></span>
                                </div>
                            </div>
                            
                            <div class="boat-price">₹<?php echo number_format($boat['price_per_hour'], 2); ?> <span style="font-size:0.9rem; color:var(--text-light); font-weight:normal;">/ hr</span></div>
                            <p class="boat-description"><?php echo htmlspecialchars($boat['description']); ?></p>
                            
                            <?php if(isset($_SESSION['user_id'])): ?>
                                <?php if(isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                                    <button class="btn-book" style="background: var(--gray); color: var(--text-light); cursor: not-allowed; box-shadow: none;">Admin Cannot Book</button>
                                <?php else: ?>
                                    <a href="book.php?boat_id=<?php echo $boat['id']; ?>" class="btn-book">Reserve This Boat</a>
                                <?php endif; ?>
                            <?php else: ?>
                                <a href="login.php" class="btn-book btn-login">Log In to Book</a>
                            <?php endif; ?> 
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <footer>
        <div class="container">
            <p>&copy; <?php echo date('Y'); ?> BoatBooking System. Crafted for maritime excellence.</p>
        </div>
    </footer>

    <script>
        // Real-time JavaScript Filtering
        function filterBoats() {
            const typeFilter = document.getElementById('boat-type').value;
            const capacityFilter = parseInt(document.getElementById('capacity').value);
            const boatCards = document.querySelectorAll('.boat-card');
            
            let visibleCount = 0;
            const emptyState = document.querySelector('.empty-state') || createEmptyState();

            boatCards.forEach(card => {
                const boatType = card.getAttribute('data-type');
                const boatCapacity = parseInt(card.getAttribute('data-capacity'));
                
                const typeMatch = (typeFilter === 'all' || boatType === typeFilter);
                const capacityMatch = (capacityFilter === 0 || boatCapacity >= capacityFilter);
                
                if (typeMatch && capacityMatch) {
                    card.style.display = 'flex';
                    // Re-trigger animation
                    card.style.animation = 'none';
                    card.offsetHeight; /* trigger reflow */
                    card.style.animation = null; 
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });

            // Handle "No Results" state dynamically
            if (visibleCount === 0 && boatCards.length > 0) {
                if(!document.contains(emptyState)) {
                    document.getElementById('boats-container').appendChild(emptyState);
                }
                emptyState.style.display = 'block';
            } else if (emptyState) {
                emptyState.style.display = 'none';
            }
        }

        function createEmptyState() {
            const div = document.createElement('div');
            div.className = 'empty-state fade-in';
            div.innerHTML = `
                <div class="icon">🔍</div>
                <h3>No Matches Found</h3>
                <p>Try adjusting your filters to see more available vessels.</p>
            `;
            return div;
        }
    </script>
</body>
</html>