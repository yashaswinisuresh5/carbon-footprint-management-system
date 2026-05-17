<?php
// profile.php
// User Profile Portal with SQL UPDATE - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];
$user_email = $_SESSION['user_email'];
$user_location = $_SESSION['user_location'];

$error_msg = "";
$success_msg = "";

// ----------------------------------------------------
// SQL UPDATE CONTROLLER (CRUD: Update)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $new_password = $_POST['new_password'] ?? '';
    
    if (empty($name) || !$age || empty($location)) {
        $error_msg = "Please fill in all mandatory fields with valid details.";
    } elseif ($age < 5 || $age > 120) {
        $error_msg = "Please enter a realistic age.";
    } else {
        try {
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $error_msg = "New password must be at least 6 characters long.";
                } else {
                    // Update profile WITH password change (BCRYPT hashing)
                    $hashed_pwd = password_hash($new_password, PASSWORD_BCRYPT);
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, age = ?, location = ?, password = ? WHERE user_id = ?");
                    $stmt->execute([$name, $age, $location, $hashed_pwd, $user_id]);
                    
                    $success_msg = "Profile information and security credentials updated successfully.";
                }
            } else {
                // Update profile WITHOUT password change
                $stmt = $pdo->prepare("UPDATE users SET name = ?, age = ?, location = ? WHERE user_id = ?");
                $stmt->execute([$name, $age, $location, $user_id]);
                
                $success_msg = "Profile details updated successfully.";
            }
            
            if (empty($error_msg)) {
                // Synchronize PHP session states with updated database values
                $_SESSION['user_name'] = $name;
                $_SESSION['user_location'] = $location;
                $user_name = $name;
                $user_location = $location;
            }
            
        } catch (PDOException $e) {
            $error_msg = "Database Error during update: " . $e->getMessage();
        }
    }
}

// Fetch user profile row from database to render in input fields
try {
    $stmt = $pdo->prepare("SELECT age, created_at FROM users WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $db_user = $stmt->fetch();
    
    $user_age = $db_user ? $db_user['age'] : 0;
    $created_date = $db_user ? $db_user['created_at'] : '';
    
    // Fetch user aggregates
    $stmt = $pdo->prepare("SELECT COUNT(*) as total_count, SUM(co2_emitted) as total_co2 FROM activities WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $aggregates = $stmt->fetch();
    
    $total_logs = intval($aggregates['total_count']);
    $total_co2 = floatval($aggregates['total_co2'] ?? 0.0);
    
    // Re-evaluate sustainability score
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
    die("Critical Error fetching user profile: " . $e->getMessage());
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
    <title>EcoTrace - My Profile Settings</title>
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
                <li class="menu-item">
                    <a href="leaderboard.php"><i class="fas fa-trophy"></i> <span>Leaderboard</span></a>
                </li>
                
                <li class="menu-label">DBMS Administration</li>
                <li class="menu-item">
                    <a href="dbms_section.php"><i class="fas fa-project-diagram"></i> <span>DBMS Conceptual</span></a>
                </li>
                <li class="menu-item active">
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
                    <h2>Personal Profile Center</h2>
                    <p>Manage account settings and review historical aggregate carbon statistics.</p>
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

            <!-- Alerts -->
            <?php if (!empty($success_msg)): ?>
                <div style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; max-width: 900px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; max-width: 900px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px; max-width: 900px;">
                
                <!-- Left: Edit Profile Form -->
                <section class="table-card" style="padding: 30px;">
                    <h3 style="font-size: 18px; font-weight: 700; margin-bottom: 20px; font-family: var(--font-heading);">Update Settings</h3>
                    
                    <form action="profile.php" method="POST">
                        <!-- Name Input -->
                        <div class="form-group">
                            <label class="form-label" for="name">Full Name</label>
                            <div class="form-control-wrapper">
                                <i class="far fa-user"></i>
                                <input class="form-control" type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user_name); ?>">
                            </div>
                        </div>

                        <!-- Email Input (Disabled/View Only to ensure logical email consistency) -->
                        <div class="form-group">
                            <label class="form-label" for="email">Email Address (Read-Only)</label>
                            <div class="form-control-wrapper">
                                <i class="far fa-envelope"></i>
                                <input class="form-control" style="background-color: var(--border-color); opacity: 0.65; cursor: not-allowed;" type="email" id="email" value="<?php echo htmlspecialchars($user_email); ?>" readonly>
                            </div>
                        </div>

                        <!-- Age & Location -->
                        <div class="form-row">
                            <div class="form-group">
                                <label class="form-label" for="age">Age</label>
                                <div class="form-control-wrapper">
                                    <i class="far fa-calendar-alt"></i>
                                    <input class="form-control" style="padding-left: 42px;" type="number" id="age" name="age" min="5" max="120" required value="<?php echo htmlspecialchars($user_age); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="form-label" for="location">Location</label>
                                <div class="form-control-wrapper">
                                    <i class="fas fa-map-marker-alt"></i>
                                    <input class="form-control" style="padding-left: 42px;" type="text" id="location" name="location" required value="<?php echo htmlspecialchars($user_location); ?>">
                                </div>
                            </div>
                        </div>

                        <!-- Password Update -->
                        <div class="form-group" style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                            <label class="form-label" for="new_password">Change Password</label>
                            <div class="form-control-wrapper">
                                <i class="fas fa-lock"></i>
                                <input class="form-control" type="password" id="new_password" name="new_password" placeholder="Leave blank to keep current password">
                            </div>
                        </div>

                        <button class="auth-btn" type="submit" style="width: auto; padding: 12px 30px;"><i class="fas fa-save"></i> Save Updates</button>
                    </form>
                </section>

                <!-- Right: Profile stats & metadata -->
                <div style="display: flex; flex-direction: column; gap: 20px;">
                    
                    <!-- Stats Card -->
                    <section class="table-card" style="padding: 24px;">
                        <h4 style="font-size: 13px; font-weight: 700; text-transform: uppercase; margin-bottom: 16px; letter-spacing: 0.5px; border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Audit Summary</h4>
                        
                        <div style="display: flex; flex-direction: column; gap: 14px;">
                            <div>
                                <span style="font-size: 12px; color: var(--text-muted);">Joined System:</span>
                                <div style="font-size: 14px; font-weight: 700; color: var(--text-main);"><?php echo date('F d, Y', strtotime($created_date)); ?></div>
                            </div>
                            <div>
                                <span style="font-size: 12px; color: var(--text-muted);">Total Activities Logged:</span>
                                <div style="font-size: 14px; font-weight: 700; color: var(--text-main);"><?php echo $total_logs; ?> rows</div>
                            </div>
                            <div>
                                <span style="font-size: 12px; color: var(--text-muted);">Total Carbon Weight:</span>
                                <div style="font-size: 14px; font-weight: 700; color: var(--primary); font-family: var(--font-heading);"><?php echo number_format($total_co2, 1); ?> kg CO₂</div>
                            </div>
                            <div>
                                <span style="font-size: 12px; color: var(--text-muted);">Eco Rating score:</span>
                                <div style="font-size: 14px; font-weight: 700; color: var(--warning);"><?php echo $sustainability_score; ?>/100</div>
                            </div>
                        </div>
                    </section>
                </div>

            </div>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
