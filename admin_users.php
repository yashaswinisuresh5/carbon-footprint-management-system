<?php
// admin_users.php
// Administrator User Management Panel (CRUD: R, D) - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force administrator login check
if (!isset($_SESSION['admin_id'])) {
    header("Location: admin_login.php");
    exit;
}

$admin_name = $_SESSION['admin_name'];
$error_msg = "";
$success_msg = "";

// ----------------------------------------------------
// USER DELETE CONTROLLER (CRUD: Delete)
// ----------------------------------------------------
if (isset($_GET['delete_user_id'])) {
    $del_user_id = filter_input(INPUT_GET, 'delete_user_id', FILTER_VALIDATE_INT);
    if ($del_user_id) {
        try {
            // Prepared DELETE statement on users table
            // Because of ON DELETE CASCADE, all activities and reports automatically delete!
            $stmt = $pdo->prepare("DELETE FROM users WHERE user_id = ?");
            $stmt->execute([$del_user_id]);
            
            $success_msg = "User profile #{$del_user_id} and all cascading activity logs deleted successfully.";
        } catch (PDOException $e) {
            $error_msg = "Database Error deleting user: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// FETCH USERS INDEX WITH AGGREGATE CO2 STATS
// ----------------------------------------------------
try {
    $search = filter_input(INPUT_GET, 'search', FILTER_DEFAULT);
    
    $query = "SELECT 
        u.user_id, 
        u.name, 
        u.age, 
        u.email, 
        u.location, 
        u.created_at, 
        COALESCE(SUM(a.co2_emitted), 0) as cumulative_co2, 
        COUNT(a.activity_id) as total_logs 
        FROM users u 
        LEFT JOIN activities a ON u.user_id = a.user_id";
    
    $params = [];
    if (!empty($search)) {
        $query .= " WHERE u.name LIKE :search OR u.email LIKE :search OR u.location LIKE :search";
        $params[':search'] = '%' . $search . '%';
    }
    
    $query .= " GROUP BY u.user_id ORDER BY u.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $users_list = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_msg = "Failed to load platform users: " . $e->getMessage();
    $users_list = [];
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
    <title>EcoTrace Admin - User Management</title>
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
                    <h2>Platform User Administration</h2>
                    <p>Audit and manage registered user profiles and coordinate cascading data overrides.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="admin_dashboard.php" class="btn-secondary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-arrow-left"></i> Dashboard</a>
                </div>
            </header>

            <!-- Alerts -->
            <?php if (!empty($success_msg)): ?>
                <div style="background-color: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.2); color: var(--success); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-check-circle"></i>
                    <span><?php echo $success_msg; ?></span>
                </div>
            <?php endif; ?>
            <?php if (!empty($error_msg)): ?>
                <div style="background-color: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); color: var(--danger); padding: 12px 16px; border-radius: var(--radius-md); font-size: 13px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-exclamation-triangle"></i>
                    <span><?php echo $error_msg; ?></span>
                </div>
            <?php endif; ?>

            <!-- Table Filters Panel Card -->
            <section class="table-card" style="padding: 20px; margin-bottom: 25px;">
                <form action="admin_users.php" method="GET" style="display: flex; gap: 12px; align-items: center;">
                    <div style="position: relative; flex-grow: 1;">
                        <input class="search-input" style="width: 100%; padding-left: 40px;" type="text" name="search" placeholder="Search users by name, email, or location..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                    <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%); padding: 10px 24px; font-size: 13px; height: 42px;"><i class="fas fa-filter"></i> Search</button>
                    <a href="admin_users.php" class="btn-secondary" style="padding: 10px 16px; height: 42px; display: flex; align-items: center;"><i class="fas fa-undo"></i></a>
                </form>
            </section>

            <!-- Platform Users Master SQL Table -->
            <section class="table-card">
                <div class="table-header">
                    <h3>Registered Platform Users (<?php echo count($users_list); ?> accounts)</h3>
                </div>

                <div class="custom-table-wrapper">
                    <?php if (count($users_list) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-users-slash"></i>
                            <p>No user profiles matching standard queries found.</p>
                        </div>
                    <?php else: ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Warriors</th>
                                    <th>Age</th>
                                    <th>Location</th>
                                    <th>Total Logs</th>
                                    <th>CO₂ Platform Weight</th>
                                    <th>Registered Date</th>
                                    <th>Admin Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users_list as $row): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight: bold; color: var(--text-muted);">#<?php echo $row['user_id']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 10px;">
                                                <div class="user-avatar" style="width: 32px; height: 32px; font-size: 12px; background-color: var(--secondary-light); color: var(--secondary);"><?php echo strtoupper(substr($row['name'], 0, 1)); ?></div>
                                                <div style="text-align: left;">
                                                    <div style="font-weight: 600; color: var(--text-main);"><?php echo htmlspecialchars($row['name']); ?></div>
                                                    <div style="font-size: 11px; color: var(--text-muted);"><?php echo htmlspecialchars($row['email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?php echo $row['age']; ?></td>
                                        <td style="font-weight: 500; color: var(--text-main);"><?php echo htmlspecialchars($row['location']); ?></td>
                                        <td><?php echo $row['total_logs']; ?></td>
                                        <td style="font-weight: 700; color: var(--text-main);"><?php echo number_format($row['cumulative_co2'], 1); ?> kg</td>
                                        <td style="color: var(--text-muted); font-size: 13px;"><?php echo date('M d, Y', strtotime($row['created_at'])); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 8px;">
                                                <!-- Update Action trigger -->
                                                <a href="admin_edit_user.php?user_id=<?php echo $row['user_id']; ?>" 
                                                   class="action-icon-btn" 
                                                   style="color: var(--secondary);"
                                                   title="Edit User Settings">
                                                    <i class="far fa-edit"></i>
                                                </a>
                                                
                                                <!-- Delete Action trigger -->
                                                <a href="admin_users.php?delete_user_id=<?php echo $row['user_id']; ?>&search=<?php echo urlencode($search ?? ''); ?>" 
                                                   class="action-icon-btn" 
                                                   onclick="return confirm('WARNING: Are you sure you want to delete this user profile and all their related carbon history? This will perform a MySQL CASCADE delete. This action is irreversible.')"
                                                   title="Delete Account">
                                                    <i class="far fa-trash-alt"></i>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
