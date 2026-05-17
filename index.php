<?php
// index.php
// Landing Page for Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Check if user is already logged in
$is_logged_in = false;
$user_name = '';
$is_admin = false;

if (isset($_SESSION['user_id'])) {
    $is_logged_in = true;
    $user_name = $_SESSION['user_name'];
} elseif (isset($_SESSION['admin_id'])) {
    $is_logged_in = true;
    $user_name = $_SESSION['admin_name'];
    $is_admin = true;
}

// Fetch Live Database Stats dynamically using SQL aggregate functions
try {
    // 1. Total users
    $stmt = $pdo->query("SELECT COUNT(*) FROM users");
    $total_users = $stmt->fetchColumn();
    
    // 2. Total CO2 emissions tracked
    $stmt = $pdo->query("SELECT SUM(co2_emitted) FROM activities");
    $total_co2 = $stmt->fetchColumn();
    $total_co2 = $total_co2 ? number_format($total_co2, 1) : "0.0";
    
    // 3. Total activities tracked
    $stmt = $pdo->query("SELECT COUNT(*) FROM activities");
    $total_activities = $stmt->fetchColumn();
} catch (PDOException $e) {
    $total_users = 0;
    $total_co2 = "0.0";
    $total_activities = 0;
}

// Set theme from cookie
$theme_class = "";
if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') {
    $theme_class = 'data-theme="dark"';
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo $theme_class; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTrace - Carbon Footprint Management System</title>
    <meta name="description" content="An advanced database-driven carbon footprint tracking and management platform for engineering DBMS projects.">
    <!-- FontAwesome for Premium Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Global CSS stylesheet -->
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        /* Special styles for Landing page only */
        body {
            padding-top: 80px;
        }
        .hero-section {
            position: relative;
        }
        .dbms-highlight {
            padding: 100px 8%;
            background-color: var(--bg-card);
            border-top: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
        }
        .footer {
            background-color: #111827;
            color: #9ca3af;
            padding: 60px 8% 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }
        .footer-grid {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            gap: 40px;
            margin-bottom: 40px;
        }
        @media (max-width: 768px) {
            .footer-grid {
                grid-template-columns: 1fr;
            }
        }
        .footer-logo {
            color: white;
            font-family: var(--font-heading);
            font-weight: 800;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 16px;
        }
        .footer-logo i {
            color: var(--primary);
        }
        .footer-col h4 {
            color: white;
            font-size: 16px;
            font-weight: 700;
            margin-bottom: 20px;
        }
        .footer-links {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .footer-links a:hover {
            color: white;
        }
        .footer-bottom {
            padding-top: 30px;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            text-align: center;
            font-size: 13px;
        }
    </style>
</head>
<body>

    <!-- Loading Screen -->
    <div class="loader-overlay">
        <div class="loader-spinner"></div>
        <div class="loader-logo">
            <i class="fas fa-leaf"></i>
            <span>EcoTrace</span>
        </div>
    </div>

    <!-- Navigation Header -->
    <nav class="landing-navbar">
        <div class="logo">
            <i class="fas fa-leaf"></i>
            <span>EcoTrace</span>
        </div>
        <ul class="landing-nav-links">
            <li><a href="#hero">Home</a></li>
            <li><a href="#features">Features</a></li>
            <li><a href="#dbms">DBMS Architecture</a></li>
        </ul>
        <div class="landing-nav-btns">
            <!-- Dark Mode Toggle -->
            <label class="theme-switch" for="theme-toggle">
                <input type="checkbox" id="theme-toggle" <?php echo (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') ? 'checked' : ''; ?>>
                <span class="switch-slider">
                    <i class="fas fa-sun"></i>
                    <i class="fas fa-moon"></i>
                </span>
            </label>
            
            <?php if ($is_logged_in): ?>
                <?php if ($is_admin): ?>
                    <a href="admin_dashboard.php" class="btn-primary"><i class="fas fa-user-shield"></i> Admin Panel</a>
                <?php else: ?>
                    <a href="dashboard.php" class="btn-primary"><i class="fas fa-columns"></i> Go to Dashboard</a>
                <?php endif; ?>
            <?php else: ?>
                <a href="login.php" class="btn-secondary">Sign In</a>
                <a href="register.php" class="btn-primary">Get Started</a>
            <?php endif; ?>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section" id="hero">
        <div class="hero-content">
            <div class="hero-tagline">
                <i class="fas fa-seedling"></i> AI-Powered & SQL-Driven Sustainability
            </div>
            <h1>Track & Reduce Your <br><span class="highlight">Carbon Footprint</span> Smarter</h1>
            <p>An elite, database-driven environmental management platform. Log daily habits, compute precise emissions via standard SQL multipliers, and unlock carbon mitigation targets.</p>
            <div class="hero-btns">
                <?php if ($is_logged_in): ?>
                    <a href="dashboard.php" class="btn-primary"><i class="fas fa-tachometer-alt"></i> Access Dashboard</a>
                <?php else: ?>
                    <a href="register.php" class="btn-primary"><i class="fas fa-rocket"></i> Begin Carbon Journey</a>
                    <a href="login.php" class="btn-secondary"><i class="fas fa-user-lock"></i> Portal Login</a>
                <?php endif; ?>
            </div>
        </div>
        <div class="hero-illustration">
            <div class="earth-container">
                <!-- Earth Rotating SVG Map -->
                <svg class="earth-svg" viewBox="0 0 100 100" xmlns="http://www.w3.org/2000/svg">
                    <defs>
                        <radialGradient id="earthGrad" cx="50%" cy="50%" r="50%">
                            <stop offset="0%" stop-color="#1e3a8a"/>
                            <stop offset="60%" stop-color="#1e40af"/>
                            <stop offset="100%" stop-color="#0f172a"/>
                        </radialGradient>
                        <filter id="glow">
                            <feGaussianBlur stdDeviation="2" result="coloredBlur"/>
                            <feMerge>
                                <feMergeNode in="coloredBlur"/>
                                <feMergeNode in="SourceGraphic"/>
                            </feMerge>
                        </filter>
                    </defs>
                    <!-- Base ocean -->
                    <circle cx="50" cy="50" r="45" fill="url(#earthGrad)" filter="url(#glow)"/>
                    
                    <!-- Continents -->
                    <!-- North America -->
                    <path d="M 25,25 Q 23,20 28,15 T 38,20 T 36,35 T 25,35 Z" fill="#10b981" opacity="0.85" />
                    <!-- South America -->
                    <path d="M 25,35 Q 32,45 30,55 T 32,75 T 27,80 T 23,55 Z" fill="#10b981" opacity="0.85" />
                    <!-- Eurasia -->
                    <path d="M 45,15 Q 55,10 65,15 T 80,18 T 85,35 T 70,45 T 50,35 Z" fill="#10b981" opacity="0.85" />
                    <!-- Africa -->
                    <path d="M 48,35 Q 60,35 62,45 T 58,68 T 50,75 T 46,45 Z" fill="#10b981" opacity="0.85" />
                    <!-- Australia -->
                    <path d="M 75,55 Q 82,58 80,68 T 70,68 Z" fill="#10b981" opacity="0.85" />
                    
                    <!-- Graticules (Grid lines) -->
                    <circle cx="50" cy="50" r="45" fill="none" stroke="rgba(255,255,255,0.08)" stroke-width="0.5"/>
                    <line x1="5" y1="50" x2="95" y2="50" stroke="rgba(255,255,255,0.08)" stroke-width="0.5"/>
                    <line x1="50" y1="5" x2="50" y2="95" stroke="rgba(255,255,255,0.08)" stroke-width="0.5"/>
                </svg>
                
                <!-- Floating metrics cards -->
                <div class="floating-badge badge-1">
                    <i class="fas fa-smog" style="color: var(--danger); font-size: 20px;"></i>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">LIVE TRACKED</div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--text-main);"><?php echo $total_co2; ?> kg</div>
                    </div>
                </div>
                <div class="floating-badge badge-2">
                    <i class="fas fa-users" style="color: var(--secondary); font-size: 20px;"></i>
                    <div>
                        <div style="font-size: 11px; font-weight: 700; color: var(--text-muted);">ACTIVE USERS</div>
                        <div style="font-size: 15px; font-weight: 800; color: var(--text-main);"><?php echo max($total_users, 1); ?></div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Stats Section -->
    <section class="stats-section">
        <div class="stat-item">
            <h3><?php echo $total_co2; ?><span style="font-size: 20px; font-weight: 600;"> kg</span></h3>
            <p>CO₂ Carbon Emissions Monitored</p>
        </div>
        <div class="stat-item">
            <h3><?php echo max($total_users, 1); ?></h3>
            <p>Registered Carbon Warriors</p>
        </div>
        <div class="stat-item">
            <h3><?php echo max($total_activities, 1); ?></h3>
            <p>Activities Logged to Database</p>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section" id="features">
        <div class="section-header">
            <h2>Advanced System Features</h2>
            <p>Our carbon footprint suite delivers rich tools and high-fidelity reporting, backed by database schemas.</p>
        </div>
        <div class="features-grid">
            <div class="feature-item-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-keyboard"></i>
                </div>
                <h3>Activity Logging Wizard</h3>
                <p>Log emissions with category-specific forms: Transport, Electricity, Food, Shopping, and Waste with real-time UI previews.</p>
            </div>
            <div class="feature-item-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-database"></i>
                </div>
                <h3>Central Emission Factors</h3>
                <p>Calculations are powered by an index-optimized SQL lookup. Factors reside inside the database—never hardcoded in code.</p>
            </div>
            <div class="feature-item-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-chart-pie"></i>
                </div>
                <h3>Interactive Analytics</h3>
                <p>Beautiful line charts, breakdown donut graphs, and a real-time responsive SVG emission level meter visualizes your records.</p>
            </div>
            <div class="feature-item-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-lightbulb"></i>
                </div>
                <h3>Smart Recommendations</h3>
                <p>Calculated outputs link to the Recommendations database, supplying context-appropriate actions to lower your footprint.</p>
            </div>
            <div class="feature-item-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-trophy"></i>
                </div>
                <h3>Gamified Eco Badges</h3>
                <p>Earn awards and level up in the community leaderboard. Compare emissions, secure badges, and track your Net-Zero progress.</p>
            </div>
            <div class="feature-item-card">
                <div class="feature-icon-wrapper">
                    <i class="fas fa-user-cog"></i>
                </div>
                <h3>Comprehensive Admin Suite</h3>
                <p>Full database administrative interface allows total user control, logging inspection, and dynamic recommendation catalog editing.</p>
            </div>
        </div>
    </section>

    <!-- DBMS Architecture Highlights Section -->
    <section class="dbms-highlight" id="dbms">
        <div class="section-header">
            <h2>DBMS Design & Schema</h2>
            <p>Engineered for high academic rigor. Visualizing the structure powering the Carbon Footprint Tracker.</p>
        </div>
        
        <!-- Table Box Cards (Teaser of ER Diagram) -->
        <div class="schema-visualizer" style="margin-top: 10px;">
            <div class="db-table-box">
                <div class="db-table-header">
                    <span><i class="fas fa-user"></i> USERS</span>
                    <i class="fas fa-table"></i>
                </div>
                <ul class="db-columns-list">
                    <li class="db-column-row">
                        <span class="col-name"><span class="key-badge pk">PK</span> user_id</span>
                        <span class="col-type">INT AI</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">name</span>
                        <span class="col-type">VARCHAR(100)</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">email (UNIQUE)</span>
                        <span class="col-type">VARCHAR(100)</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">password</span>
                        <span class="col-type">VARCHAR(255)</span>
                    </li>
                </ul>
            </div>

            <div class="db-table-box">
                <div class="db-table-header">
                    <span><i class="fas fa-running"></i> ACTIVITIES</span>
                    <i class="fas fa-table"></i>
                </div>
                <ul class="db-columns-list">
                    <li class="db-column-row">
                        <span class="col-name"><span class="key-badge pk">PK</span> activity_id</span>
                        <span class="col-type">INT AI</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name"><span class="key-badge fk">FK</span> user_id</span>
                        <span class="col-type">INT</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">category</span>
                        <span class="col-type">VARCHAR(50)</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">co2_emitted</span>
                        <span class="col-type">DECIMAL(10,3)</span>
                    </li>
                </ul>
            </div>

            <div class="db-table-box">
                <div class="db-table-header">
                    <span><i class="fas fa-bolt"></i> EMISSION_FACTORS</span>
                    <i class="fas fa-table"></i>
                </div>
                <ul class="db-columns-list">
                    <li class="db-column-row">
                        <span class="col-name"><span class="key-badge pk">PK</span> factor_id</span>
                        <span class="col-type">INT AI</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">category</span>
                        <span class="col-type">VARCHAR(50)</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">sub_category (UNIQ)</span>
                        <span class="col-type">VARCHAR(50)</span>
                    </li>
                    <li class="db-column-row">
                        <span class="col-name">factor_value</span>
                        <span class="col-type">DECIMAL(10,4)</span>
                    </li>
                </ul>
            </div>
        </div>

        <div style="text-align: center; margin-top: 30px;">
            <?php if ($is_logged_in && !$is_admin): ?>
                <a href="dbms_section.php" class="btn-primary"><i class="fas fa-project-diagram"></i> Explore Dedicated DBMS Visualizer</a>
            <?php else: ?>
                <a href="login.php" class="btn-secondary" style="border-color: var(--primary); color: var(--primary);"><i class="fas fa-key"></i> Log In to See SQL Execution</a>
            <?php endif; ?>
        </div>
    </section>

    <!-- Footer -->
    <footer class="footer">
        <div class="footer-grid">
            <div class="footer-col">
                <div class="footer-logo">
                    <i class="fas fa-leaf"></i>
                    <span>EcoTrace</span>
                </div>
                <p style="margin-bottom: 20px;">An elite DBMS Mini-Project providing seamless interface workflows integrated with fully normalized MySQL databases and live carbon impact calculations.</p>
                <div style="display: flex; gap: 14px;">
                    <a href="#" style="color: #6b7280; font-size: 20px; transition: var(--transition);"><i class="fab fa-github"></i></a>
                    <a href="#" style="color: #6b7280; font-size: 20px; transition: var(--transition);"><i class="fab fa-linkedin"></i></a>
                    <a href="#" style="color: #6b7280; font-size: 20px; transition: var(--transition);"><i class="fab fa-twitter"></i></a>
                </div>
            </div>
            <div class="footer-col">
                <h4>System Portals</h4>
                <ul class="footer-links">
                    <li><a href="login.php">User Login</a></li>
                    <li><a href="register.php">User Registration</a></li>
                    <li><a href="admin_login.php">Admin Login Portal</a></li>
                </ul>
            </div>
            <div class="footer-col">
                <h4>Core Standards</h4>
                <ul class="footer-links">
                    <li><a href="#hero">Real-time calculations</a></li>
                    <li><a href="#features">No broken calculations</a></li>
                    <li><a href="#dbms">Strict Primary/Foreign Keys</a></li>
                </ul>
            </div>
        </div>
        <div class="footer-bottom">
            <p>&copy; <?php echo date('Y'); ?> EcoTrace Carbon Footprint Management System. Designed as an Engineering DBMS Mini Project.</p>
        </div>
    </footer>

    <!-- Global Javascript libraries -->
    <script src="assets/js/main.js"></script>
</body>
</html>
