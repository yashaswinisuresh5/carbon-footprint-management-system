<?php
// dashboard.php
// Main User Analytics Dashboard - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_location = $_SESSION['user_location'];

// Handle quick delete action
if (isset($_GET['delete_activity_id'])) {
    $del_id = filter_input(INPUT_GET, 'delete_activity_id', FILTER_VALIDATE_INT);
    if ($del_id) {
        try {
            $del_stmt = $pdo->prepare("DELETE FROM activities WHERE activity_id = ? AND user_id = ?");
            $del_stmt->execute([$del_id, $user_id]);
            header("Location: dashboard.php?deleted=1");
            exit;
        } catch (PDOException $e) {
            $error_msg = "Could not delete activity: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// DYNAMIC SQL METRICS QUERIES (Aggregate Functions)
// ----------------------------------------------------

try {
    // 1. Total Carbon Footprint (kg CO2)
    $stmt = $pdo->prepare("SELECT SUM(co2_emitted) FROM activities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_co2 = $stmt->fetchColumn();
    $total_co2_val = $total_co2 ? floatval($total_co2) : 0.0;
    
    // 2. Total Activities logged
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM activities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_activities = intval($stmt->fetchColumn());
    
    // 3. Average CO2 per activity
    $avg_co2 = 0.0;
    if ($total_activities > 0) {
        $avg_co2 = $total_co2_val / $total_activities;
    }
    
    // 4. Primary Emission level classification
    $stmt = $pdo->prepare("SELECT emission_level, COUNT(*) as count FROM activities WHERE user_id = ? GROUP BY emission_level ORDER BY count DESC LIMIT 1");
    $stmt->execute([$user_id]);
    $level_row = $stmt->fetch();
    $dominant_level = $level_row ? $level_row['emission_level'] : 'None';
    
    // 5. Dynamic Sustainability Score calculation (out of 100)
    // Formula: Starts at 100, drops depending on cumulative emissions relative to a standard allowance (e.g. 10kg/day)
    // Also awards points for "Low" activities and penalizes "High" activities
    $sustainability_score = 100;
    if ($total_activities > 0) {
        $stmt = $pdo->prepare("SELECT 
            SUM(CASE WHEN emission_level = 'Low' THEN 1 ELSE 0 END) as low_count,
            SUM(CASE WHEN emission_level = 'Medium' THEN 1 ELSE 0 END) as med_count,
            SUM(CASE WHEN emission_level = 'High' THEN 1 ELSE 0 END) as high_count
            FROM activities WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $counts = $stmt->fetch();
        
        $lows = intval($counts['low_count']);
        $meds = intval($counts['med_count']);
        $highs = intval($counts['high_count']);
        
        $sustainability_score = 80 + ($lows * 4) + ($meds * 1) - ($highs * 8);
        // Cap score between 10 and 100
        $sustainability_score = max(10, min(100, $sustainability_score));
    }
    
    // 6. Recent activities table (SQL SELECT query with INNER JOIN capability or straight table query)
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE user_id = ? ORDER BY activity_date DESC, created_at DESC LIMIT 5");
    $stmt->execute([$user_id]);
    $recent_activities = $stmt->fetchAll();
    
    // 7. Category Breakdown Data for Donut Chart
    $stmt = $pdo->prepare("SELECT category, SUM(co2_emitted) as total FROM activities WHERE user_id = ? GROUP BY category");
    $stmt->execute([$user_id]);
    $category_breakdown = $stmt->fetchAll();
    
    $chart_categories = [];
    $chart_emissions = [];
    foreach ($category_breakdown as $row) {
        $chart_categories[] = $row['category'];
        $chart_emissions[] = floatval($row['total']);
    }
    
    // 8. Last 7 Days Trend Data for Line Chart
    $stmt = $pdo->prepare("SELECT activity_date, SUM(co2_emitted) as total FROM activities WHERE user_id = ? AND activity_date >= DATE_SUB(CURRENT_DATE(), INTERVAL 6 DAY) GROUP BY activity_date ORDER BY activity_date ASC");
    $stmt->execute([$user_id]);
    $trend_data = $stmt->fetchAll();
    
    // Fill in dates to ensure clean 7-day trend even if some days have 0 logs
    $trend_labels = [];
    $trend_values = [];
    $trend_map = [];
    
    foreach ($trend_data as $row) {
        $trend_map[$row['activity_date']] = floatval($row['total']);
    }
    
    for ($i = 6; $i >= 0; $i--) {
        $date = date('Y-m-d', strtotime("-$i days"));
        $label = date('M d', strtotime("-$i days"));
        $trend_labels[] = $label;
        $trend_values[] = $trend_map[$date] ?? 0.0;
    }
    
} catch (PDOException $e) {
    die("Critical Error Fetching Dashboard Metrics: " . $e->getMessage());
}

// Determine gauge color and offset based on dominant emission level
$gauge_color = "#10b981"; // Low
$gauge_offset = 251.2; // Empty
$gauge_percent = 0;

if ($dominant_level === 'Low') {
    $gauge_color = "#10b981"; // Green
    $gauge_percent = 25;
} elseif ($dominant_level === 'Medium') {
    $gauge_color = "#f59e0b"; // Yellow
    $gauge_percent = 60;
} elseif ($dominant_level === 'High') {
    $gauge_color = "#ef4444"; // Red
    $gauge_percent = 90;
}

// 251.2 is full stroke-dasharray (representing 100% of semicircular stroke)
// To show a gauge percentage, calculate standard offset
$gauge_offset = 251.2 - (251.2 * ($gauge_percent / 100));

// Notifications Setup (dynamic based on carbon level)
$notifications = [];
if ($total_activities === 0) {
    $notifications[] = ['type' => 'info', 'text' => 'Welcome to EcoTrace! Log your first carbon activity to begin tracking.', 'time' => 'Just now'];
} else {
    if ($dominant_level === 'High') {
        $notifications[] = ['type' => 'warning', 'text' => 'High Carbon Footprint detected! Check Recommendations to optimize habits.', 'time' => '10m ago'];
    }
    if ($sustainability_score > 85) {
        $notifications[] = ['type' => 'success', 'text' => 'Excellent sustainability score! You have earned the Eco-Warrior badge.', 'time' => '1h ago'];
    }
    $notifications[] = ['type' => 'info', 'text' => 'Weekly report summary generated. Click Reports to export PDF.', 'time' => '1d ago'];
}

// Set theme from cookie
$theme_class = "";
$is_dark = false;
if (isset($_COOKIE['theme']) && $_COOKIE['theme'] === 'dark') {
    $theme_class = 'data-theme="dark"';
    $is_dark = true;
}
?>
<!DOCTYPE html>
<html lang="en" <?php echo $theme_class; ?>>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EcoTrace - User Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="assets/css/style.css">
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

    <div class="app-container">
        <!-- Sidebar Navigation -->
        <aside class="sidebar">
            <div class="sidebar-logo">
                <i class="fas fa-leaf"></i>
                <span>EcoTrace</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-label">Main</li>
                <li class="menu-item active">
                    <a href="dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
                </li>
                <li class="menu-item">
                    <a href="add_activity.php"><i class="fas fa-plus-circle"></i> <span>Log Activity</span></a>
                </li>
                <li class="menu-item">
                    <a href="history.php"><i class="fas fa-history"></i> <span>Activity Log</span></a>
                </li>
                
                <li class="menu-label">Insights</li>
                <li class="menu-item">
                    <a href="recommendations.php"><i class="fas fa-lightbulb"></i> <span>Recommendations</span></a>
                </li>
                <li class="menu-item">
                    <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Monthly Reports</span></a>
                </li>
                <li class="menu-item">
                    <a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a>
                </li>
                
                <li class="menu-label">DBMS Administration</li>
                <li class="menu-item">
                    <a href="dbms_section.php"><i class="fas fa-project-diagram"></i> <span>DBMS Conceptual</span></a>
                </li>
                <li class="menu-item">
                    <a href="profile.php"><i class="far fa-user-circle"></i> <span>My Profile</span></a>
                </li>
            </ul>
            
            <!-- User Box at bottom -->
            <div class="sidebar-user">
                <div class="user-avatar"><?php echo strtoupper(substr($user_name, 0, 1)); ?></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role">User</div>
                </div>
                <a href="logout.php" class="logout-btn" title="Sign Out"><i class="fas fa-power-off"></i></a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <!-- Top Navbar inside Dashboard -->
            <header class="top-navbar">
                <div class="nav-left">
                    <h2>Overview Dashboard</h2>
                    <p>Welcome back, <?php echo htmlspecialchars($user_name); ?>! Here is your environment profile.</p>
                </div>
                
                <div class="nav-right">
                    <!-- Dark Mode Toggle Switch -->
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    
                    <!-- Notification Bell Dropdown -->
                    <div style="position: relative;">
                        <button class="notif-btn" id="notif-btn">
                            <i class="far fa-bell"></i>
                            <?php if (count($notifications) > 0): ?>
                                <span class="notif-badge"></span>
                            <?php endif; ?>
                        </button>
                        
                        <div class="notif-dropdown" id="notif-dropdown">
                            <div class="notif-header">Notifications</div>
                            <div class="notif-list">
                                <?php if (count($notifications) === 0): ?>
                                    <div style="padding: 16px; text-align: center; color: var(--text-muted); font-size: 12px;">No new alerts.</div>
                                <?php else: ?>
                                    <?php foreach ($notifications as $notif): ?>
                                        <div class="notif-item">
                                            <i class="fas <?php echo $notif['type'] === 'warning' ? 'fa-exclamation-triangle' : ($notif['type'] === 'success' ? 'fa-trophy' : 'fa-info-circle'); ?>"></i>
                                            <div class="notif-item-body">
                                                <p><?php echo $notif['text']; ?></p>
                                                <span><?php echo $notif['time']; ?></span>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Quick Log Action Button -->
                    <a href="add_activity.php" class="nav-action-btn"><i class="fas fa-plus"></i> New Log</a>
                </div>
            </header>

            <!-- Success notifications -->
            <?php if (isset($_GET['registered'])): ?>
                <div style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 16px 20px; border-radius: var(--radius-md); font-size: 14px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 12px;">
                    <i class="fas fa-award" style="font-size: 20px;"></i>
                    <div>
                        <strong>Account Setup Complete!</strong> Welcome to EcoTrace. Log your first footprint activity below to begin dashboard analytics.
                    </div>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_GET['deleted'])): ?>
                <div style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle"></i>
                    <span>Activity deleted successfully. Dashboard metrics updated dynamically.</span>
                </div>
            <?php endif; ?>

            <!-- Welcome Overview Panel -->
            <section class="welcome-card">
                <h3>Empowering Eco-Conscious Living</h3>
                <p>You are registered from <strong><?php echo htmlspecialchars($user_location); ?></strong>. Your collective efforts in managing transport modes, electricity grid consumption, organic/inorganic waste volumes, shopping limits, and beef intake actively reduces greenhouse gases.</p>
                <div class="sustainability-tier">
                    <i class="fas fa-leaf"></i>
                    <span>Status: <?php echo $sustainability_score > 80 ? 'Net-Zero Ally' : ($sustainability_score > 50 ? 'Eco-Advocate' : 'Climate Learner'); ?></span>
                </div>
            </section>

            <!-- 4 Dynamic KPI Cards -->
            <section class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Total Footprint</h4>
                        <div class="metric-value"><?php echo number_format($total_co2_val, 1); ?><span class="metric-unit">kg CO₂</span></div>
                        <div class="metric-trend down">
                            <i class="fas fa-globe"></i> Cumulative total
                        </div>
                    </div>
                    <div class="metric-icon green">
                        <i class="fas fa-cloud-meatball"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Average Impact</h4>
                        <div class="metric-value"><?php echo number_format($avg_co2, 1); ?><span class="metric-unit">kg CO₂</span></div>
                        <div class="metric-trend <?php echo $avg_co2 > 30 ? 'up' : 'down'; ?>">
                            <i class="fas <?php echo $avg_co2 > 30 ? 'fa-arrow-up' : 'fa-arrow-down'; ?>"></i> Per logged activity
                        </div>
                    </div>
                    <div class="metric-icon blue">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Activities Logged</h4>
                        <div class="metric-value"><?php echo $total_activities; ?><span class="metric-unit">records</span></div>
                        <div class="metric-trend down">
                            <i class="fas fa-database"></i> SQL data rows
                        </div>
                    </div>
                    <div class="metric-icon yellow">
                        <i class="fas fa-server"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Dominant Level</h4>
                        <div class="metric-value" style="font-size: 24px; font-weight: 800; text-transform: uppercase;"><?php echo $dominant_level; ?></div>
                        <div class="metric-trend down">
                            <i class="fas fa-chart-bar"></i> Primary status classification
                        </div>
                    </div>
                    <div class="metric-icon red">
                        <i class="fas fa-exclamation-circle"></i>
                    </div>
                </div>
            </section>

            <!-- Dynamic Analytics Grid -->
            <section class="analytics-grid">
                <!-- Line Chart: Weekly Carbon Trends -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Weekly Emission Trend</h3>
                        <p style="font-size: 12px; color: var(--text-muted);">CO₂ emitted in past 7 days (kg)</p>
                    </div>
                    <div class="chart-container">
                        <canvas id="trendChart"></canvas>
                    </div>
                </div>

                <!-- CO2 Meter Gauge & Sustainability Score Circle -->
                <div class="gauge-card">
                    <h3>Carbon Level Index</h3>
                    
                    <!-- SVG Semicircular Gauge Meter -->
                    <div class="meter-svg">
                        <svg width="200" height="120">
                            <!-- Semicircle radius 80 cx 100 cy 100 -->
                            <path class="meter-bg" d="M 20 100 A 80 80 0 0 1 180 100" />
                            <path class="meter-fill" id="gauge-fill" d="M 20 100 A 80 80 0 0 1 180 100" 
                                  stroke="<?php echo $gauge_color; ?>" 
                                  stroke-dashoffset="<?php echo $gauge_offset; ?>" />
                        </svg>
                        <div class="meter-value-text">
                            <div class="value" style="color: <?php echo $gauge_color; ?>;"><?php echo number_format($total_co2_val, 1); ?></div>
                            <div class="lbl">Total kg CO₂</div>
                        </div>
                    </div>
                    
                    <span class="meter-label <?php echo strtolower($dominant_level); ?>">
                        <?php echo $dominant_level === 'None' ? 'No Data' : $dominant_level . ' Emission Level'; ?>
                    </span>
                    
                    <!-- Sustainability Score Circular Meter -->
                    <div style="margin-top: 24px; border-top: 1px solid var(--border-color); width: 100%; padding-top: 20px; display: flex; flex-direction: column; align-items: center;">
                        <h4 style="font-size: 14px; font-weight: 700; margin-bottom: 10px;">Sustainability Score</h4>
                        
                        <div class="score-container">
                            <svg class="score-circle" width="110" height="110">
                                <circle class="score-circle-bg" cx="55" cy="55" r="45"></circle>
                                <circle class="score-circle-fill" id="score-fill" cx="55" cy="55" r="45" 
                                        stroke-dashoffset="<?php echo 282.6 - (282.6 * ($sustainability_score / 100)); ?>"></circle>
                            </svg>
                            <div class="score-text"><?php echo $sustainability_score; ?>%</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom Row: Doughnut Chart & Recent Activities List -->
            <section class="analytics-grid" style="grid-template-columns: 1fr 2fr;">
                <!-- Doughnut Chart: Category Emissions Distribution -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Category Split</h3>
                        <p style="font-size: 12px; color: var(--text-muted);">CO₂ distribution by type</p>
                    </div>
                    <div class="chart-container" style="height: 240px;">
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>

                <!-- Recent Activities Table -->
                <div class="table-card">
                    <div class="table-header">
                        <h3>Recent Carbon Logs</h3>
                        <a href="history.php" class="btn-secondary" style="font-size: 13px; padding: 6px 14px;"><i class="fas fa-list"></i> Full Logs</a>
                    </div>
                    <div class="custom-table-wrapper">
                        <?php if (count($recent_activities) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-leaf"></i>
                                <p>No logged activities found! Start monitoring now.</p>
                                <a href="add_activity.php" class="btn-primary" style="display: inline-block; margin-top: 15px;"><i class="fas fa-plus"></i> Add Activity</a>
                            </div>
                        <?php else: ?>
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Category</th>
                                        <th>Sub-Category</th>
                                        <th>Quantity Input</th>
                                        <th>CO₂ Emitted</th>
                                        <th>Level</th>
                                        <th>Date</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activities as $act): ?>
                                        <tr>
                                            <td>
                                                <span class="badge <?php echo strtolower($act['category']); ?>">
                                                    <?php 
                                                    $icon = 'leaf';
                                                    if ($act['category'] === 'Transport') $icon = 'bus';
                                                    if ($act['category'] === 'Electricity') $icon = 'lightbulb';
                                                    if ($act['category'] === 'Food') $icon = 'utensils';
                                                    if ($act['category'] === 'Shopping') $icon = 'shopping-bag';
                                                    if ($act['category'] === 'Waste') $icon = 'trash';
                                                    if ($act['category'] === 'Fuel') $icon = 'gas-pump';
                                                    ?>
                                                    <i class="fas fa-<?php echo $icon; ?>"></i> <?php echo $act['category']; ?>
                                                </span>
                                            </td>
                                            <td style="font-weight: 600;"><?php echo htmlspecialchars($act['sub_category']); ?></td>
                                            <td><?php echo number_format($act['quantity'], 1); ?></td>
                                            <td style="font-weight: 700; color: var(--text-main);"><?php echo number_format($act['co2_emitted'], 2); ?> kg</td>
                                            <td>
                                                <span class="meter-label <?php echo strtolower($act['emission_level']); ?>" style="padding: 2px 8px; font-size: 10px; margin-top: 0;">
                                                    <?php echo $act['emission_level']; ?>
                                                </span>
                                            </td>
                                            <td style="color: var(--text-muted); font-size: 13px;"><?php echo date('M d, Y', strtotime($act['activity_date'])); ?></td>
                                            <td>
                                                <!-- Delete CRUD Action trigger -->
                                                <a href="dashboard.php?delete_activity_id=<?php echo $act['activity_id']; ?>" 
                                                   class="action-icon-btn" 
                                                   onclick="return confirm('Are you sure you want to delete this activity log from the MySQL database? This action is permanent and recalculates dashboard insights.')"
                                                   title="Delete Log Row">
                                                    <i class="far fa-trash-alt"></i>
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Chart.js and Global Scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        // Set up Chart.js styling variables depending on current theme
        let textColor = getCookie('theme') === 'dark' ? '#9ca3af' : '#6b7280';
        let borderColor = getCookie('theme') === 'dark' ? '#243049' : '#e5e7eb';

        // 1. Weekly trend line chart setup
        const trendCtx = document.getElementById('trendChart').getContext('2d');
        const trendLabels = <?php echo json_encode($trend_labels); ?>;
        const trendValues = <?php echo json_encode($trend_values); ?>;
        
        let trendChart = new Chart(trendCtx, {
            type: 'line',
            data: {
                labels: trendLabels,
                datasets: [{
                    label: 'CO₂ Emitted (kg)',
                    data: trendValues,
                    borderColor: '#10b981',
                    backgroundColor: 'rgba(16, 185, 129, 0.1)',
                    borderWidth: 3,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#10b981',
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        grid: { color: borderColor },
                        ticks: { color: textColor }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });

        // 2. Category split donut chart setup
        const catCtx = document.getElementById('categoryChart').getContext('2d');
        const catLabels = <?php echo json_encode($chart_categories); ?>;
        const catValues = <?php echo json_encode($chart_emissions); ?>;
        
        // Provide standard color mappings for categories
        const catColors = {
            'Transport': '#3b82f6',
            'Electricity': '#fbbf24',
            'Food': '#10b981',
            'Shopping': '#8b5cf6',
            'Waste': '#f43f5e',
            'Fuel': '#f97316'
        };
        
        const backgroundColors = catLabels.map(cat => catColors[cat] || '#6b7280');

        let categoryChart = new Chart(catCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: backgroundColors,
                    borderWidth: 2,
                    borderColor: getCookie('theme') === 'dark' ? '#151e2e' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            color: textColor,
                            font: { size: 11 }
                        }
                    }
                },
                cutout: '65%'
            }
        });

        // 3. Handle theme changes dynamically in Chart.js
        window.addEventListener('themechanged', (e) => {
            textColor = e.detail.theme === 'dark' ? '#9ca3af' : '#6b7280';
            borderColor = e.detail.theme === 'dark' ? '#243049' : '#e5e7eb';
            
            // Update Line Chart styles
            trendChart.options.scales.y.grid.color = borderColor;
            trendChart.options.scales.y.ticks.color = textColor;
            trendChart.options.scales.x.ticks.color = textColor;
            trendChart.update();
            
            // Update Donut Chart border and text styles
            categoryChart.options.plugins.legend.labels.color = textColor;
            categoryChart.data.datasets[0].borderColor = e.detail.theme === 'dark' ? '#151e2e' : '#ffffff';
            categoryChart.update();
        });
    </script>
</body>
</html>
