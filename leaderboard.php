<?php
// leaderboard.php
// Community Leaderboard Portal - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

try {
    // Dynamic SQL query utilizing INNER/LEFT JOIN and GROUP BY
    // Calculates total CO2 emissions for each user and ranks them (lowest emissions = best/rank 1!)
    // We only rank users who have logged at least 1 activity for competitive realism
    $stmt = $pdo->query("SELECT 
        u.user_id, 
        u.name, 
        u.location, 
        SUM(a.co2_emitted) as total_co2, 
        COUNT(a.activity_id) as activity_count 
        FROM users u 
        INNER JOIN activities a ON u.user_id = a.user_id 
        GROUP BY u.user_id 
        ORDER BY total_co2 ASC");
    $rankings = $stmt->fetchAll();
    
    // Extract podium winners (top 3)
    $podium = [];
    if (count($rankings) > 0) $podium[1] = $rankings[0]; // 1st Place (Lowest CO2)
    if (count($rankings) > 1) $podium[2] = $rankings[1]; // 2nd Place
    if (count($rankings) > 2) $podium[3] = $rankings[2]; // 3rd Place
    
} catch (PDOException $e) {
    die("Database Error compiling eco leaderboard: " . $e->getMessage());
}

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
    <title>EcoTrace - Community Leaderboard</title>
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
                <li class="menu-item">
                    <a href="recommendations.php"><i class="fas fa-lightbulb"></i> <span>Recommendations</span></a>
                </li>
                <li class="menu-item">
                    <a href="reports.php"><i class="fas fa-file-alt"></i> <span>Monthly Reports</span></a>
                </li>
                <li class="menu-item active">
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
                    <h2>Eco-Warrior Leaderboard</h2>
                    <p>Compete in carbon reduction. Rank is resolved by lowest cumulative CO₂ emissions.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="dashboard.php" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </header>

            <div class="leaderboard-grid">
                
                <!-- Left panel: Visual podium graphics for top 3 -->
                <section class="podium-card">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 6px;"><i class="fas fa-crown" style="color: #fbbf24;"></i> Champions Podium</h3>
                    <p style="font-size: 12px; color: var(--text-muted); text-align: center;">Top 3 players with the minimal greenhouse impact.</p>
                    
                    <div class="podium-container">
                        <!-- 2ND PLACE -->
                        <div class="podium-spot second">
                            <?php if (isset($podium[2])): ?>
                                <div class="podium-avatar"><?php echo strtoupper(substr($podium[2]['name'], 0, 1)); ?></div>
                                <div style="font-size: 11px; font-weight: 700; text-align: center; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 80px;"><?php echo explode(' ', $podium[2]['name'])[0]; ?></div>
                                <div style="font-size: 9px; color: var(--text-muted); margin-bottom: 4px;"><?php echo number_format($podium[2]['total_co2'], 1); ?>kg</div>
                            <?php endif; ?>
                            <div class="podium-block">2</div>
                        </div>
                        
                        <!-- 1ST PLACE -->
                        <div class="podium-spot first">
                            <?php if (isset($podium[1])): ?>
                                <div class="podium-avatar"><?php echo strtoupper(substr($podium[1]['name'], 0, 1)); ?></div>
                                <div style="font-size: 12px; font-weight: 800; text-align: center; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 90px;"><?php echo explode(' ', $podium[1]['name'])[0]; ?></div>
                                <div style="font-size: 10px; color: var(--primary); font-weight: 700; margin-bottom: 4px;"><?php echo number_format($podium[1]['total_co2'], 1); ?>kg</div>
                            <?php endif; ?>
                            <div class="podium-block">1</div>
                        </div>
                        
                        <!-- 3RD PLACE -->
                        <div class="podium-spot third">
                            <?php if (isset($podium[3])): ?>
                                <div class="podium-avatar"><?php echo strtoupper(substr($podium[3]['name'], 0, 1)); ?></div>
                                <div style="font-size: 11px; font-weight: 700; text-align: center; color: var(--text-main); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; width: 80px;"><?php echo explode(' ', $podium[3]['name'])[0]; ?></div>
                                <div style="font-size: 9px; color: var(--text-muted); margin-bottom: 4px;"><?php echo number_format($podium[3]['total_co2'], 1); ?>kg</div>
                            <?php endif; ?>
                            <div class="podium-block">3</div>
                        </div>
                    </div>
                </section>

                <!-- Right panel: Leaderboard table list of all rankings -->
                <section class="table-card">
                    <div class="table-header">
                        <h3>Community Standings</h3>
                    </div>
                    <div class="custom-table-wrapper">
                        <?php if (count($rankings) === 0): ?>
                            <div class="empty-state">
                                <i class="fas fa-globe-asia"></i>
                                <p>No competitive standings compiled. Log activities to generate rankings!</p>
                            </div>
                        <?php else: ?>
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Rank</th>
                                        <th>Eco Warrior</th>
                                        <th>Location</th>
                                        <th>Activities Logged</th>
                                        <th>Cumulative CO₂</th>
                                        <th>Standing</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($rankings as $idx => $rank): ?>
                                        <?php 
                                        $current_rank = $idx + 1;
                                        $is_current_user = ($rank['user_id'] == $user_id);
                                        ?>
                                        <tr style="<?php echo $is_current_user ? 'background-color: var(--primary-light); font-weight: bold;' : ''; ?>">
                                            <td>
                                                <?php if ($current_rank === 1): ?>
                                                    <span style="color: #fbbf24; font-size: 18px;"><i class="fas fa-medal"></i></span>
                                                <?php elseif ($current_rank === 2): ?>
                                                    <span style="color: #cbd5e1; font-size: 18px;"><i class="fas fa-medal"></i></span>
                                                <?php elseif ($current_rank === 3): ?>
                                                    <span style="color: #b45309; font-size: 18px;"><i class="fas fa-medal"></i></span>
                                                <?php else: ?>
                                                    <span style="font-family: monospace; font-size: 14px; font-weight: bold; color: var(--text-muted);"><?php echo $current_rank; ?></span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <div style="display: flex; align-items: center; gap: 10px;">
                                                    <div class="user-avatar" style="width: 28px; height: 28px; font-size: 11px;"><?php echo strtoupper(substr($rank['name'], 0, 1)); ?></div>
                                                    <span>
                                                        <?php echo htmlspecialchars($rank['name']); ?>
                                                        <?php if ($is_current_user) echo ' <span style="font-size: 9px; padding: 2px 6px; background-color: var(--primary); color: white; border-radius: 9px; font-weight: 800; text-transform: uppercase;">YOU</span>'; ?>
                                                    </span>
                                                </div>
                                            </td>
                                            <td style="color: var(--text-muted); font-size: 13px;"><?php echo htmlspecialchars($rank['location']); ?></td>
                                            <td><?php echo $rank['activity_count']; ?></td>
                                            <td style="font-weight: 700; color: var(--text-main);"><?php echo number_format($rank['total_co2'], 1); ?> kg</td>
                                            <td>
                                                <span class="meter-label <?php echo $current_rank <= 3 ? 'low' : ($current_rank <= 10 ? 'medium' : 'high'); ?>" style="padding: 2px 8px; font-size: 10px; margin-top: 0;">
                                                    <?php echo $current_rank <= 3 ? 'Elite Hero' : 'Eco-Ally'; ?>
                                                </span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php endif; ?>
                    </div>
                </section>

            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
