<?php
// recommendations.php
// Recommendation Engine & Achievements Page - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try {
    // 1. Fetch ALL recommendations from the database dynamically
    $stmt = $pdo->query("SELECT * FROM recommendations ORDER BY category, potential_saving DESC");
    $all_tips = $stmt->fetchAll();
    
    // 2. Fetch User activities to evaluate badges/achievements dynamically!
    // This executes aggregation checks to see what sectors user is active in
    $stmt = $pdo->prepare("SELECT 
        COUNT(*) as total_count,
        SUM(CASE WHEN category = 'Transport' AND emission_level = 'Low' THEN 1 ELSE 0 END) as transport_low,
        SUM(CASE WHEN category = 'Electricity' AND emission_level = 'Low' THEN 1 ELSE 0 END) as electricity_low,
        SUM(CASE WHEN category = 'Food' AND emission_level = 'Low' THEN 1 ELSE 0 END) as food_low,
        SUM(CASE WHEN category = 'Waste' AND emission_level = 'Low' THEN 1 ELSE 0 END) as waste_low
        FROM activities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $user_stats = $stmt->fetch();
    
    $total_logs = intval($user_stats['total_count']);
    $low_transport = intval($user_stats['transport_low']);
    $low_electricity = intval($user_stats['electricity_low']);
    $low_food = intval($user_stats['food_low']);
    $low_waste = intval($user_stats['waste_low']);
    
    // Evaluate Sustainability score to unlock elite badge
    $stmt = $pdo->prepare("SELECT SUM(co2_emitted) FROM activities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $total_co2 = floatval($stmt->fetchColumn() ?? 0);
    
    $sustainability_score = 100;
    if ($total_logs > 0) {
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
        
        $sustainability_score = max(10, min(100, 80 + ($lows * 4) + ($meds * 1) - ($highs * 8)));
    }
    
} catch (PDOException $e) {
    die("Database Error fetching recommendations: " . $e->getMessage());
}

// Badge logical criteria
$badges = [
    [
        'title' => 'First Steps',
        'desc' => 'Logged at least 1 activity log.',
        'icon' => 'fa-seedling',
        'unlocked' => $total_logs >= 1
    ],
    [
        'title' => 'Green Commuter',
        'desc' => 'Logged a low transport activity.',
        'icon' => 'fa-bicycle',
        'unlocked' => $low_transport >= 1
    ],
    [
        'title' => 'Grid Optimizer',
        'desc' => 'Logged a low electricity activity.',
        'icon' => 'fa-lightbulb',
        'unlocked' => $low_electricity >= 1
    ],
    [
        'title' => 'Low-Waste Hero',
        'desc' => 'Logged a low waste activity.',
        'icon' => 'fa-recycle',
        'unlocked' => $low_waste >= 1
    ],
    [
        'title' => 'Eco-Advocate',
        'desc' => 'Sustainability score above 80%.',
        'icon' => 'fa-leaf',
        'unlocked' => $sustainability_score >= 80 && $total_logs >= 1
    ],
    [
        'title' => 'Master Warrior',
        'desc' => 'Sustainability score above 90%.',
        'icon' => 'fa-award',
        'unlocked' => $sustainability_score >= 90 && $total_logs >= 1
    ]
];

// Set theme
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
    <title>EcoTrace - Recommendations & Badges</title>
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
                <li class="menu-item">
                    <a href="dashboard.php"><i class="fas fa-chart-pie"></i> <span>Dashboard</span></a>
                </li>
                <li class="menu-item">
                    <a href="add_activity.php"><i class="fas fa-plus-circle"></i> <span>Log Activity</span></a>
                </li>
                <li class="menu-item">
                    <a href="history.php"><i class="fas fa-history"></i> <span>Activity Log</span></a>
                </li>
                
                <li class="menu-label">Insights</li>
                <li class="menu-item active">
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
            <header class="top-navbar">
                <div class="nav-left">
                    <h2>Mitigation & Achievements</h2>
                    <p>Unlock custom tips driven by database analysis and review sustainability awards.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="add_activity.php" class="btn-primary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-plus"></i> Add Log</a>
                </div>
            </header>

            <div style="display: grid; grid-template-columns: 2fr 1.2fr; gap: 30px; margin-bottom: 30px;">
                
                <!-- Left panel: List of all database recommendations -->
                <section class="table-card" style="padding: 24px;">
                    <div class="table-header" style="padding: 0 0 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="font-size: 18px;"><i class="fas fa-list-ul" style="color: var(--primary);"></i> Recommendations Library</h3>
                    </div>
                    
                    <div style="display: flex; flex-direction: column; gap: 20px;">
                        <?php if (count($all_tips) === 0): ?>
                            <div style="text-align: center; padding: 40px; color: var(--text-muted);">No recommendations seeded inside database.</div>
                        <?php else: ?>
                            <?php foreach ($all_tips as $tip): ?>
                                <div class="rec-card" style="border-left: 4px solid var(--primary); padding-left: 20px; gap: 8px; flex-direction: row; align-items: center; justify-content: space-between;">
                                    <div style="flex: 1;">
                                        <div class="rec-top" style="margin-bottom: 6px;">
                                            <div class="rec-icon" style="width: 32px; height: 32px; font-size: 14px;">
                                                <?php 
                                                $icon = 'leaf';
                                                if ($tip['icon_type'] === 'bus') $icon = 'bus';
                                                if ($tip['icon_type'] === 'plane') $icon = 'plane';
                                                if ($tip['icon_type'] === 'sun') $icon = 'sun';
                                                if ($tip['icon_type'] === 'lightbulb') $icon = 'lightbulb';
                                                if ($tip['icon_type'] === 'utensils') $icon = 'utensils';
                                                if ($tip['icon_type'] === 'trash') $icon = 'trash';
                                                if ($tip['icon_type'] === 'ban') $icon = 'ban';
                                                if ($tip['icon_type'] === 'recycle') $icon = 'recycle';
                                                ?>
                                                <i class="fas fa-<?php echo $icon; ?>"></i>
                                            </div>
                                            <span class="rec-title" style="font-size: 15px; font-weight: 700;"><?php echo htmlspecialchars($tip['tip_title']); ?></span>
                                            
                                            <!-- Category tag -->
                                            <span class="badge <?php echo strtolower($tip['category']); ?>" style="padding: 2px 8px; font-size: 10px;">
                                                <?php echo $tip['category']; ?>
                                            </span>
                                            
                                            <!-- Level tag -->
                                            <span class="meter-label <?php echo strtolower($tip['emission_level']); ?>" style="padding: 2px 8px; font-size: 10px; margin-top: 0;">
                                                For <?php echo $tip['emission_level']; ?> Level
                                            </span>
                                        </div>
                                        <div class="rec-desc" style="font-size: 13px; color: var(--text-muted); line-height: 1.5;"><?php echo htmlspecialchars($tip['tip_text']); ?></div>
                                    </div>
                                    <div style="text-align: right; margin-left: 20px;">
                                        <span class="rec-saving" style="white-space: nowrap; font-size: 12px;">Saving: -<?php echo number_format($tip['potential_saving'], 1); ?> kg</span>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </section>

                <!-- Right panel: Gamified Sustainability Badges / Achievements -->
                <section class="table-card" style="padding: 24px; align-self: flex-start;">
                    <div class="table-header" style="padding: 0 0 20px; margin-bottom: 20px; border-bottom: 1px solid var(--border-color);">
                        <h3 style="font-size: 18px;"><i class="fas fa-award" style="color: var(--warning);"></i> Carbon Badges</h3>
                    </div>
                    <p style="font-size: 13px; color: var(--text-muted); margin-bottom: 20px;">Achievements unlock automatically based on your historical carbon activities and average emission levels.</p>
                    
                    <div class="achievements-grid" style="grid-template-columns: 1fr; gap: 14px;">
                        <?php foreach ($badges as $badge): ?>
                            <div class="badge-card <?php echo $badge['unlocked'] ? '' : 'locked'; ?>" style="flex-direction: row; text-align: left; padding: 14px 20px; justify-content: flex-start; gap: 16px; background-color: var(--bg-card); border-color: <?php echo $badge['unlocked'] ? 'var(--primary)' : 'var(--border-color)'; ?>;">
                                <div class="badge-icon" style="flex-shrink: 0; width: 44px; height: 44px; border-color: <?php echo $badge['unlocked'] ? 'var(--primary)' : 'var(--text-muted)'; ?>; background-color: <?php echo $badge['unlocked'] ? 'var(--primary-light)' : 'transparent'; ?>; color: <?php echo $badge['unlocked'] ? 'var(--primary)' : 'var(--text-muted)'; ?>;">
                                    <i class="fas <?php echo $badge['icon']; ?>"></i>
                                </div>
                                <div style="flex-grow: 1;">
                                    <h4 style="font-size: 14px; font-weight: 700; color: <?php echo $badge['unlocked'] ? 'var(--text-main)' : 'var(--text-muted)'; ?>; display: flex; align-items: center; justify-content: space-between;">
                                        <span><?php echo $badge['title']; ?></span>
                                        <?php if ($badge['unlocked']): ?>
                                            <i class="fas fa-check-circle" style="color: var(--success); font-size: 14px;"></i>
                                        <?php else: ?>
                                            <i class="fas fa-lock" style="color: var(--text-muted); font-size: 12px;"></i>
                                        <?php endif; ?>
                                    </h4>
                                    <p style="font-size: 11px; color: var(--text-muted);"><?php echo $badge['desc']; ?></p>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
