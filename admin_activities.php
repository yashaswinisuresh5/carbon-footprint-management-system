<?php
// admin_activities.php
// Global Platform Carbon Activity Monitoring Log (CRUD: R, D) - Carbon Footprint Management System
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
// LOG DELETE CONTROLLER (CRUD: Delete)
// ----------------------------------------------------
if (isset($_GET['delete_activity_id'])) {
    $del_act_id = filter_input(INPUT_GET, 'delete_activity_id', FILTER_VALIDATE_INT);
    if ($del_act_id) {
        try {
            // Prepared DELETE statement on activities
            $stmt = $pdo->prepare("DELETE FROM activities WHERE activity_id = ?");
            $stmt->execute([$del_act_id]);
            
            $success_msg = "Carbon activity record #{$del_act_id} deleted successfully from MySQL tables.";
        } catch (PDOException $e) {
            $error_msg = "Database Error deleting log row: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// COMPILE PLATFORM ACTIVITIES WITH INNER JOIN
// ----------------------------------------------------
try {
    $search = filter_input(INPUT_GET, 'search', FILTER_DEFAULT);
    $category_filter = filter_input(INPUT_GET, 'category', FILTER_DEFAULT);
    
    // Complex SQL SELECT with INNER JOIN
    $query = "SELECT 
        a.activity_id, 
        a.category, 
        a.sub_category, 
        a.quantity, 
        a.co2_emitted, 
        a.emission_level, 
        a.activity_date, 
        u.name as user_name, 
        u.email as user_email 
        FROM activities a 
        INNER JOIN users u ON a.user_id = u.user_id";
    
    $where_clauses = [];
    $params = [];
    
    if (!empty($search)) {
        $where_clauses[] = "(u.name LIKE :search OR u.email LIKE :search OR a.sub_category LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }
    
    if (!empty($category_filter) && $category_filter !== 'All') {
        $where_clauses[] = "a.category = :category";
        $params[':category'] = $category_filter;
    }
    
    if (count($where_clauses) > 0) {
        $query .= " WHERE " . implode(" AND ", $where_clauses);
    }
    
    $query .= " ORDER BY a.activity_date DESC, a.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $global_activities = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_msg = "Database query failure: " . $e->getMessage();
    $global_activities = [];
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
    <title>EcoTrace Admin - Activity Monitor</title>
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
                <li class="menu-item">
                    <a href="admin_users.php"><i class="fas fa-users-cog"></i> <span>Manage Users</span></a>
                </li>
                <li class="menu-item active">
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
                    <h2>Global Footprint Logs</h2>
                    <p>Observe, search, and delete logged entries across the entire database directory.</p>
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
                <form action="admin_activities.php" method="GET" style="display: grid; grid-template-columns: 2fr 1fr 120px 60px; gap: 12px; align-items: center;">
                    <div style="position: relative;">
                        <input class="search-input" style="width: 100%; padding-left: 40px;" type="text" name="search" placeholder="Search logs by name, email, subcategory..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        <i class="fas fa-search" style="position: absolute; left: 16px; top: 50%; transform: translateY(-50%); color: var(--text-muted);"></i>
                    </div>
                    
                    <div>
                        <select class="filter-select" style="width: 100%; height: 42px;" name="category">
                            <option value="All">All Categories</option>
                            <option value="Transport" <?php echo $category_filter === 'Transport' ? 'selected' : ''; ?>>Transport</option>
                            <option value="Electricity" <?php echo $category_filter === 'Electricity' ? 'selected' : ''; ?>>Electricity</option>
                            <option value="Food" <?php echo $category_filter === 'Food' ? 'selected' : ''; ?>>Food</option>
                            <option value="Shopping" <?php echo $category_filter === 'Shopping' ? 'selected' : ''; ?>>Shopping</option>
                            <option value="Waste" <?php echo $category_filter === 'Waste' ? 'selected' : ''; ?>>Waste</option>
                            <option value="Fuel" <?php echo $category_filter === 'Fuel' ? 'selected' : ''; ?>>Fuel</option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary" style="background: linear-gradient(135deg, var(--secondary) 0%, #2563eb 100%); padding: 10px; font-size: 13px; height: 42px; text-align: center;"><i class="fas fa-filter"></i> Apply</button>
                    <a href="admin_activities.php" class="btn-secondary" style="padding: 10px; height: 42px; display: flex; align-items: center; justify-content: center;" title="Clear Filters"><i class="fas fa-undo"></i></a>
                </form>
            </section>

            <!-- SQL Platform-wide Activities Table -->
            <section class="table-card">
                <div class="table-header">
                    <h3>Master Activities Database Log (<?php echo count($global_activities); ?> entries)</h3>
                </div>

                <div class="custom-table-wrapper">
                    <?php if (count($global_activities) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-server"></i>
                            <p>No carbon activities matching search criteria recorded in MySQL database.</p>
                        </div>
                    <?php else: ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Warriors Account</th>
                                    <th>Category</th>
                                    <th>Sub-Category</th>
                                    <th>Quantity</th>
                                    <th>CO₂ Emitted</th>
                                    <th>Level</th>
                                    <th>Record Date</th>
                                    <th>Admin Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($global_activities as $act): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight: bold; color: var(--text-muted);">#<?php echo $act['activity_id']; ?></td>
                                        <td>
                                            <div style="display: flex; align-items: center; gap: 8px;">
                                                <div class="user-avatar" style="width: 26px; height: 26px; font-size: 10px; background-color: var(--secondary-light); color: var(--secondary);"><?php echo strtoupper(substr($act['user_name'], 0, 1)); ?></div>
                                                <div style="text-align: left;">
                                                    <div style="font-weight: 600; color: var(--text-main); font-size: 12px;"><?php echo htmlspecialchars($act['user_name']); ?></div>
                                                    <div style="font-size: 10px; color: var(--text-muted);"><?php echo htmlspecialchars($act['user_email']); ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo strtolower($act['category']); ?>" style="padding: 2px 6px; font-size: 10px;">
                                                <?php echo $act['category']; ?>
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
                                        <td style="color: var(--text-muted); font-size: 12px;"><?php echo date('M d, Y', strtotime($act['activity_date'])); ?></td>
                                        <td>
                                            <a href="admin_activities.php?delete_activity_id=<?php echo $act['activity_id']; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo urlencode($category_filter ?? ''); ?>" 
                                               class="action-icon-btn" 
                                               onclick="return confirm('Are you sure you want to delete this activity log from the MySQL database? This action is permanent and recalculates platform metrics.')"
                                               title="Delete Row Record">
                                                <i class="far fa-trash-alt"></i>
                                            </a>
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
