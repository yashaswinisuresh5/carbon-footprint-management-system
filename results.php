<?php
// results.php
// Live Emission Results Portal - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

// Get activity details
$activity_id = filter_input(INPUT_GET, 'activity_id', FILTER_VALIDATE_INT);
if (!$activity_id) {
    header("Location: dashboard.php");
    exit;
}

try {
    // Select activity row while securing user ownership (Primary Key & Foreign Key verification)
    $stmt = $pdo->prepare("SELECT * FROM activities WHERE activity_id = ? AND user_id = ?");
    $stmt->execute([$activity_id, $user_id]);
    $activity = $stmt->fetch();
    
    if (!$activity) {
        // Activity doesn't exist or doesn't belong to this user
        header("Location: dashboard.php");
        exit;
    }
    
    // Fetch context-appropriate Eco Recommendations dynamically from the Database!
    // This queries the SQL recommendations table matching the logged category and emission level
    $rec_stmt = $pdo->prepare("SELECT * FROM recommendations WHERE category = ? AND emission_level = ? LIMIT 2");
    $rec_stmt->execute([$activity['category'], $activity['emission_level']]);
    $matching_recs = $rec_stmt->fetchAll();
    
    // If no exact match (e.g. fresh custom levels), fall back to general tips
    if (count($matching_recs) === 0) {
        $rec_stmt = $pdo->prepare("SELECT * FROM recommendations WHERE category = ? LIMIT 2");
        $rec_stmt->execute([$activity['category']]);
        $matching_recs = $rec_stmt->fetchAll();
    }
    
} catch (PDOException $e) {
    die("Error retrieving carbon results: " . $e->getMessage());
}

// Calculate slider parameters for comparison gauges
$daily_allowance = 10.0; // standard 10 kg allowance per day
$percentage_of_allowance = ($activity['co2_emitted'] / $daily_allowance) * 100;
$percentage_of_allowance = min(100, max(5, $percentage_of_allowance));

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
    <title>EcoTrace - Calculation Results</title>
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
                <li class="menu-item active">
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
                    <h2>Calculation Resolved</h2>
                    <p>MySQL record inserted successfully. Review environmental carbon impact below.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="add_activity.php" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-plus"></i> Log Another</a>
                </div>
            </header>

            <!-- Results Card Panel -->
            <section class="results-card">
                <div class="results-grid">
                    <!-- Left Panel: Large Value Display -->
                    <div class="results-left">
                        <div style="font-size: 13px; font-weight: 700; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; margin-bottom: 8px;">Footprint Emitted</div>
                        <div class="result-emission-val">
                            <?php echo number_format($activity['co2_emitted'], 2); ?>
                            <span>kg CO₂e</span>
                        </div>
                        
                        <span class="meter-label <?php echo strtolower($activity['emission_level']); ?>" style="padding: 6px 16px; font-size: 12px; margin-top: 10px;">
                            <?php echo $activity['emission_level']; ?> Carbon Impact
                        </span>
                        
                        <div class="results-comparison">
                            <p style="margin-bottom: 8px; font-weight: 600;">Comparison to Target Allowance</p>
                            <!-- Progress Bar representation -->
                            <div style="background-color: var(--border-color); height: 8px; border-radius: var(--radius-full); overflow: hidden; margin-bottom: 8px;">
                                <div style="background-color: <?php echo $activity['emission_level'] === 'Low' ? 'var(--success)' : ($activity['emission_level'] === 'Medium' ? 'var(--warning)' : 'var(--danger)'); ?>; width: <?php echo $percentage_of_allowance; ?>%; height: 100%; border-radius: var(--radius-full); transition: width 1s ease;"></div>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 11px;">
                                <span>0% Daily Cap</span>
                                <strong><?php echo number_format(($activity['co2_emitted'] / $daily_allowance) * 100, 1); ?>% of Allowance</strong>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right Panel: Contextual Breakdown & CRUD data metadata -->
                    <div>
                        <h3 style="font-size: 18px; margin-bottom: 12px; font-family: var(--font-heading);">Logged SQL Row Metadata</h3>
                        <p style="color: var(--text-muted); font-size: 13px; margin-bottom: 20px;">This row represents verified normalized inputs committed to the server. PK ID is <strong>#<?php echo $activity['activity_id']; ?></strong>.</p>
                        
                        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 24px;">
                            <div style="display: flex; justify-content: space-between; font-size: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                                <span style="color: var(--text-muted);">Sector Category:</span>
                                <strong style="color: var(--text-main);"><?php echo $activity['category']; ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                                <span style="color: var(--text-muted);">Configured Subtype:</span>
                                <strong style="color: var(--text-main);"><?php echo $activity['sub_category']; ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; border-bottom: 1px solid var(--border-color); padding-bottom: 6px;">
                                <span style="color: var(--text-muted);">Raw Input Quantity:</span>
                                <strong style="color: var(--text-main);"><?php echo number_format($activity['quantity'], 1); ?></strong>
                            </div>
                            <div style="display: flex; justify-content: space-between; font-size: 14px; padding-bottom: 6px;">
                                <span style="color: var(--text-muted);">Record Date:</span>
                                <strong style="color: var(--text-main);"><?php echo date('M d, Y', strtotime($activity['activity_date'])); ?></strong>
                            </div>
                        </div>

                        <!-- Actions bar -->
                        <div style="display: flex; gap: 12px;">
                            <a href="dashboard.php" class="btn-primary" style="flex: 1; text-align: center; font-size: 14px;"><i class="fas fa-columns"></i> Dashboard Overview</a>
                            <a href="history.php" class="btn-secondary" style="flex: 1; text-align: center; font-size: 14px;"><i class="fas fa-history"></i> See History log</a>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Dynamic SQL Recommendation Section -->
            <section style="max-width: 900px; margin: 0 auto 30px;">
                <h3 style="font-size: 20px; font-weight: 800; margin-bottom: 20px; font-family: var(--font-heading);"><i class="fas fa-lightbulb" style="color: var(--warning);"></i> Dynamic DBMS Recommendations</h3>
                
                <div class="recs-grid" style="grid-template-columns: 1fr 1fr;">
                    <?php if (count($matching_recs) === 0): ?>
                        <div class="rec-card" style="grid-column: span 2; text-align: center;">
                            <div class="rec-desc">You are already operating at absolute carbon efficiency! Keep doing what you are doing.</div>
                        </div>
                    <?php else: ?>
                        <?php foreach ($matching_recs as $rec): ?>
                            <div class="rec-card">
                                <div class="rec-top">
                                    <div class="rec-icon">
                                        <?php 
                                        $icon = 'leaf';
                                        if ($rec['icon_type'] === 'bus') $icon = 'bus';
                                        if ($rec['icon_type'] === 'plane') $icon = 'plane';
                                        if ($rec['icon_type'] === 'sun') $icon = 'sun';
                                        if ($rec['icon_type'] === 'lightbulb') $icon = 'lightbulb';
                                        if ($rec['icon_type'] === 'utensils') $icon = 'utensils';
                                        if ($rec['icon_type'] === 'trash') $icon = 'trash';
                                        if ($rec['icon_type'] === 'ban') $icon = 'ban';
                                        if ($rec['icon_type'] === 'recycle') $icon = 'recycle';
                                        ?>
                                        <i class="fas fa-<?php echo $icon; ?>"></i>
                                    </div>
                                    <span class="rec-title"><?php echo htmlspecialchars($rec['tip_title']); ?></span>
                                </div>
                                <div class="rec-desc"><?php echo htmlspecialchars($rec['tip_text']); ?></div>
                                <span class="rec-saving">Potential Savings: -<?php echo number_format($rec['potential_saving'], 1); ?> kg CO₂</span>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
