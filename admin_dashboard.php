<?php
// admin_dashboard.php
// Master Platform Administration Dashboard - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force administrator login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_name = $_SESSION['admin_name'];

try {
    // 1. Fetch system-wide platform aggregates using SQL aggregate functions
    // Total Registered Users
    $user_count = intval($pdo->query("SELECT COUNT(*) FROM users")->fetchColumn());
    
    // Total Activities logged across the entire system
    $activity_count = intval($pdo->query("SELECT COUNT(*) FROM activities")->fetchColumn());
    
    // Cumulative Carbon Footprint tracked platform-wide
    $total_co2 = floatval($pdo->query("SELECT SUM(co2_emitted) FROM activities")->fetchColumn() ?? 0.0);
    
    // Platform Average impact per log
    $avg_co2 = 0.0;
    if ($activity_count > 0) {
        $avg_co2 = $total_co2 / $activity_count;
    }
    
    // 2. Platform-wide Category Breakdown for Doughnut Chart
    $category_breakdown = $pdo->query("SELECT category, SUM(co2_emitted) as total FROM activities GROUP BY category")->fetchAll();
    $cat_labels = [];
    $cat_values = [];
    foreach ($category_breakdown as $row) {
        $cat_labels[] = $row['category'];
        $cat_values[] = floatval($row['total']);
    }
    
    // 3. User distribution by location (GROUP BY location) for Bar Chart
    $location_stats = $pdo->query("SELECT location, COUNT(*) as count FROM users GROUP BY location ORDER BY count DESC LIMIT 8")->fetchAll();
    $loc_labels = [];
    $loc_values = [];
    foreach ($location_stats as $row) {
        $loc_labels[] = $row['location'];
        $loc_values[] = intval($row['count']);
    }

} catch (PDOException $e) {
    die("Critical Error Fetching Platform Statistics: " . $e->getMessage());
}

// Check dark theme
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
    <title>EcoTrace - Admin Panel</title>
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
        <!-- Admin Navigation Sidebar -->
        <aside class="sidebar" style="border-right-color: var(--secondary-light);">
            <div class="sidebar-logo" style="color: var(--secondary);">
                <i class="fas fa-user-shield"></i>
                <span>EcoTrace Admin</span>
            </div>
            
            <ul class="sidebar-menu">
                <li class="menu-label">System Control</li>
                <li class="menu-item active">
                    <a href="admin_dashboard.php"><i class="fas fa-sliders-h"></i> <span>Overview Stats</span></a>
                </li>
                <li class="menu-item">
                    <a href="admin_users.php"><i class="fas fa-users-cog"></i> <span>Manage Users</span></a>
                </li>
                <li class="menu-item">
                    <a href="admin_activities.php"><i class="fas fa-globe-americas"></i> <span>Platform Activity</span></a>
                </li>
                <li class="menu-item">
                    <a href="admin_factors.php"><i class="fas fa-balance-scale-right"></i> <span>Emission Catalog</span></a>
                </li>
                
                <li class="menu-label">Portal Return</li>
                <li class="menu-item">
                    <a href="dashboard.php" style="color: var(--primary);"><i class="fas fa-arrow-left"></i> <span>User Dashboard</span></a>
                </li>
            </ul>
            
            <div class="sidebar-user">
                <div class="user-avatar" style="background-color: var(--secondary-light); color: var(--secondary);"><i class="fas fa-user-cog"></i></div>
                <div class="user-info">
                    <div class="user-name"><?php echo htmlspecialchars($admin_name); ?></div>
                    <div class="user-role">Administrator</div>
                </div>
                <a href="logout.php" class="logout-btn" title="Sign Out"><i class="fas fa-power-off"></i></a>
            </div>
        </aside>

        <!-- Main Workspace -->
        <main class="main-content">
            <header class="top-navbar">
                <div class="nav-left">
                    <h2>Platform Overview Analytics</h2>
                    <p>System monitoring, user volumes, cumulative carbon metrics, and relational catalog editors.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="admin_users.php" class="btn-primary" style="background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%);"><i class="fas fa-users-cog"></i> Audit Users</a>
                </div>
            </header>

            <!-- 4 Dynamic KPI Cards -->
            <section class="metrics-grid">
                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Platform Users</h4>
                        <div class="metric-value"><?php echo $user_count; ?><span class="metric-unit">Warriors</span></div>
                        <div class="metric-trend down">
                            <i class="fas fa-users"></i> Registered profiles
                        </div>
                    </div>
                    <div class="metric-icon blue">
                        <i class="fas fa-user-friends"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Carbon Managed</h4>
                        <div class="metric-value"><?php echo number_format($total_co2, 1); ?><span class="metric-unit">kg CO₂</span></div>
                        <div class="metric-trend down">
                            <i class="fas fa-globe"></i> Cumulative platform total
                        </div>
                    </div>
                    <div class="metric-icon red">
                        <i class="fas fa-cloud-meatball"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Activities Logged</h4>
                        <div class="metric-value"><?php echo $activity_count; ?><span class="metric-unit">rows</span></div>
                        <div class="metric-trend down">
                            <i class="fas fa-database"></i> Total committed logs
                        </div>
                    </div>
                    <div class="metric-icon yellow">
                        <i class="fas fa-list-alt"></i>
                    </div>
                </div>

                <div class="metric-card">
                    <div class="metric-info">
                        <h4>Average Platform Impact</h4>
                        <div class="metric-value"><?php echo number_format($avg_co2, 1); ?><span class="metric-unit">kg CO₂</span></div>
                        <div class="metric-trend down">
                            <i class="fas fa-chart-line"></i> Overall mean impact
                        </div>
                    </div>
                    <div class="metric-icon green">
                        <i class="fas fa-balance-scale"></i>
                    </div>
                </div>
            </section>

            <!-- Charts Grid -->
            <section class="analytics-grid">
                <!-- Platform category split doughnut -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Global Carbon Split</h3>
                        <p style="font-size: 12px; color: var(--text-muted);">CO₂ distribution by sector category platform-wide</p>
                    </div>
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="adminCatChart"></canvas>
                    </div>
                </div>

                <!-- User registered by location bar chart -->
                <div class="chart-card">
                    <div class="chart-header">
                        <h3>Geographical Density</h3>
                        <p style="font-size: 12px; color: var(--text-muted);">User count density by registered locations</p>
                    </div>
                    <div class="chart-container" style="height: 250px;">
                        <canvas id="adminLocChart"></canvas>
                    </div>
                </div>
            </section>

            <!-- Bottom administrative catalog preview card -->
            <section class="table-card" style="padding: 24px; margin-bottom: 30px;">
                <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--border-color); padding-bottom: 15px; margin-bottom: 15px;">
                    <h3 style="font-size: 16px;"><i class="fas fa-cog"></i> Platform Database Diagnostics</h3>
                    <span class="meter-label low" style="margin-top: 0; padding: 2px 8px; font-size: 10px;">SQL Status: Healthy</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; font-size: 13px; color: var(--text-muted);">
                    <div>
                        <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">DBMS Engine:</strong>
                        <span>MySQL InnoDB Engine (Standard compatibility)</span>
                    </div>
                    <div>
                        <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Storage Format:</strong>
                        <span>Prepared statements execution layer</span>
                    </div>
                    <div>
                        <strong style="color: var(--text-main); display: block; margin-bottom: 4px;">Cascading Triggers:</strong>
                        <span>Verified Foreign Key Constraints ON DELETE CASCADE</span>
                    </div>
                </div>
            </section>
        </main>
    </div>

    <!-- Chart.js and scripts -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="assets/js/main.js"></script>
    <script>
        let textColor = getCookie('theme') === 'dark' ? '#9ca3af' : '#6b7280';
        let borderColor = getCookie('theme') === 'dark' ? '#243049' : '#e5e7eb';

        // 1. Doughnut Chart platform breakdown
        const catCtx = document.getElementById('adminCatChart').getContext('2d');
        const catLabels = <?php echo json_encode($cat_labels); ?>;
        const catValues = <?php echo json_encode($cat_values); ?>;
        
        const catColors = {
            'Transport': '#3b82f6',
            'Electricity': '#fbbf24',
            'Food': '#10b981',
            'Shopping': '#8b5cf6',
            'Waste': '#f43f5e'
        };
        const backgroundColors = catLabels.map(cat => catColors[cat] || '#6b7280');

        let catChart = new Chart(catCtx, {
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

        // 2. Bar Chart User Location distribution
        const locCtx = document.getElementById('adminLocChart').getContext('2d');
        const locLabels = <?php echo json_encode($loc_labels); ?>;
        const locValues = <?php echo json_encode($loc_values); ?>;

        let locChart = new Chart(locCtx, {
            type: 'bar',
            data: {
                labels: locLabels,
                datasets: [{
                    label: 'Warriors Count',
                    data: locValues,
                    backgroundColor: '#3b82f6',
                    borderRadius: 4,
                    barThickness: 24
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
                        ticks: { color: textColor, stepSize: 1 }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { color: textColor }
                    }
                }
            }
        });

        // 3. Theme Toggle event handler
        window.addEventListener('themechanged', (e) => {
            textColor = e.detail.theme === 'dark' ? '#9ca3af' : '#6b7280';
            borderColor = e.detail.theme === 'dark' ? '#243049' : '#e5e7eb';
            
            catChart.options.plugins.legend.labels.color = textColor;
            catChart.data.datasets[0].borderColor = e.detail.theme === 'dark' ? '#151e2e' : '#ffffff';
            catChart.update();

            locChart.options.scales.y.grid.color = borderColor;
            locChart.options.scales.y.ticks.color = textColor;
            locChart.options.scales.x.ticks.color = textColor;
            locChart.update();
        });
    </script>
</body>
</html>
