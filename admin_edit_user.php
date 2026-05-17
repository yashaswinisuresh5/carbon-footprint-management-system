<?php
// admin_edit_user.php
// Administrative User Update Panel (CRUD: U) - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force administrator login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_name = $_SESSION['admin_name'];
$error_msg = "";
$success_msg = "";

// Extract user ID
$edit_user_id = filter_input(INPUT_GET, 'user_id', FILTER_VALIDATE_INT);
if (!$edit_user_id) {
    header("Location: admin_users.php");
    exit;
}

// ----------------------------------------------------
// SQL UPDATE CONTROLLER (CRUD: Update)
// ----------------------------------------------------
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_SPECIAL_CHARS);
    $age = filter_input(INPUT_POST, 'age', FILTER_VALIDATE_INT);
    $email = filter_input(INPUT_POST, 'email', FILTER_VALIDATE_EMAIL);
    $location = filter_input(INPUT_POST, 'location', FILTER_SANITIZE_SPECIAL_CHARS);
    $override_password = $_POST['override_password'] ?? '';
    
    if (empty($name) || !$age || empty($email) || empty($location)) {
        $error_msg = "Please fill in all mandatory parameters.";
    } elseif ($age < 5 || $age > 120) {
        $error_msg = "Please enter a realistic age.";
    } else {
        try {
            // Check email uniqueness while excluding current editing user ID!
            $email_stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
            $email_stmt->execute([$email, $edit_user_id]);
            if ($email_stmt->fetchColumn() > 0) {
                $error_msg = "The email address is already registered to another user profile.";
            } else {
                if (!empty($override_password)) {
                    if (strlen($override_password) < 6) {
                        $error_msg = "Override password must be at least 6 characters long.";
                    } else {
                        // SQL Update with password change (BCRYPT hashing)
                        $hashed_pwd = password_hash($override_password, PASSWORD_BCRYPT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, age = ?, email = ?, location = ?, password = ? WHERE user_id = ?");
                        $stmt->execute([$name, $age, $email, $location, $hashed_pwd, $edit_user_id]);
                        
                        $success_msg = "User profile #{$edit_user_id} and credentials updated successfully.";
                    }
                } else {
                    // SQL Update without password change
                    $stmt = $pdo->prepare("UPDATE users SET name = ?, age = ?, email = ?, location = ? WHERE user_id = ?");
                    $stmt->execute([$name, $age, $email, $location, $edit_user_id]);
                    
                    $success_msg = "User profile details updated successfully.";
                }
            }
        } catch (PDOException $e) {
            $error_msg = "Database Error during update: " . $e->getMessage();
        }
    }
}

// Fetch the targeted user's current database row
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmt->execute([$edit_user_id]);
    $user_row = $stmt->fetch();
    
    if (!$user_row) {
        header("Location: admin_users.php");
        exit;
    }
} catch (PDOException $e) {
    die("Database Error fetching target user: " . $e->getMessage());
}

// Theme check
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
    <title>EcoTrace Admin - Edit User Profile</title>
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
                <li class="menu-item">
                    <a href="admin_dashboard.php"><i class="fas fa-sliders-h"></i> <span>Overview Stats</span></a>
                </li>
                <li class="menu-item active">
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
                    <h2>Edit User Account</h2>
                    <p>Modify credentials and location profile variables for User ID <strong>#<?php echo $edit_user_id; ?></strong>.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="admin_users.php" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-arrow-left"></i> User Directory</a>
                </div>
            </header>

            <!-- Alerts -->
            <?php if (!empty($success_msg)): ?>
                <div style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; max-width: 600px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px; max-width: 600px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- Form Card Panel -->
            <section class="table-card" style="padding: 30px; max-width: 600px;">
                <h3 style="font-size: 16px; font-weight: 700; margin-bottom: 20px; font-family: var(--font-heading); border-bottom: 1px solid var(--border-color); padding-bottom: 10px;">Update User Form</h3>
                
                <form action="admin_edit_user.php?user_id=<?php echo $edit_user_id; ?>" method="POST">
                    
                    <!-- Full Name -->
                    <div class="form-group">
                        <label class="form-label" for="name">Full Name</label>
                        <div class="form-control-wrapper">
                            <i class="far fa-user"></i>
                            <input class="form-control" type="text" id="name" name="name" required value="<?php echo htmlspecialchars($user_row['name']); ?>">
                        </div>
                    </div>

                    <!-- Email address -->
                    <div class="form-group">
                        <label class="form-label" for="email">Email Address</label>
                        <div class="form-control-wrapper">
                            <i class="far fa-envelope"></i>
                            <input class="form-control" type="email" id="email" name="email" required value="<?php echo htmlspecialchars($user_row['email']); ?>">
                        </div>
                    </div>

                    <!-- Age & Location -->
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label" for="age">Age</label>
                            <div class="form-control-wrapper">
                                <i class="far fa-calendar-alt"></i>
                                <input class="form-control" style="padding-left: 42px;" type="number" id="age" name="age" min="5" max="120" required value="<?php echo htmlspecialchars($user_row['age']); ?>">
                            </div>
                        </div>
                        <div class="form-group">
                            <label class="form-label" for="location">Location</label>
                            <div class="form-control-wrapper">
                                <i class="fas fa-map-marker-alt"></i>
                                <input class="form-control" style="padding-left: 42px;" type="text" id="location" name="location" required value="<?php echo htmlspecialchars($user_row['location']); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- Password Update -->
                    <div class="form-group" style="margin-top: 24px; border-top: 1px solid var(--border-color); padding-top: 20px;">
                        <label class="form-label" for="override_password">Security Password Override</label>
                        <div class="form-control-wrapper">
                            <i class="fas fa-lock"></i>
                            <input class="form-control" type="password" id="override_password" name="override_password" placeholder="Leave blank to keep existing password">
                        </div>
                    </div>

                    <div style="display: flex; gap: 12px; margin-top: 20px;">
                        <button class="auth-btn" type="submit" style="width: auto; padding: 12px 30px; background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%);"><i class="fas fa-save"></i> Save Changes</button>
                        <a href="admin_users.php" class="btn-secondary" style="padding: 12px 24px; display: flex; align-items: center; justify-content: center;">Cancel</a>
                    </div>
                </form>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
