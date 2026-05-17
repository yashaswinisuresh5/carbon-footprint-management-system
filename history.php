<?php
// history.php
// Professional Activity History Log - Carbon Footprint Management System
require_once __DIR__ . '/config/db.php';

// Force user login
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'];

$error_msg = "";
$success_msg = "";

// ----------------------------------------------------
// DELETE CONTROLLER (CRUD: Delete)
// ----------------------------------------------------
if (isset($_GET['delete_id'])) {
    $delete_id = filter_input(INPUT_GET, 'delete_id', FILTER_VALIDATE_INT);
    if ($delete_id) {
        try {
            // Prepared DELETE statement securing user ownership
            $del_stmt = $pdo->prepare("DELETE FROM activities WHERE activity_id = ? AND user_id = ?");
            $del_stmt->execute([$delete_id, $user_id]);
            
            $success_msg = "Activity record #{$delete_id} deleted successfully. Database aggregates updated.";
        } catch (PDOException $e) {
            $error_msg = "Database Error deleting record: " . $e->getMessage();
        }
    }
}

// ----------------------------------------------------
// DYNAMIC SQL FILTERING & SEARCH
// ----------------------------------------------------

// Collect filter parameters
$search = filter_input(INPUT_GET, 'search', FILTER_DEFAULT);
$category_filter = filter_input(INPUT_GET, 'category', FILTER_DEFAULT);
$level_filter = filter_input(INPUT_GET, 'level', FILTER_DEFAULT);
$sort_by = filter_input(INPUT_GET, 'sort_by', FILTER_DEFAULT) ?? 'activity_date';
$sort_order = filter_input(INPUT_GET, 'sort_order', FILTER_DEFAULT) ?? 'desc';

// Build base SQL query
$query = "SELECT * FROM activities WHERE user_id = :user_id";
$params = [':user_id' => $user_id];

// Append search keyword filter
if (!empty($search)) {
    $query .= " AND (category LIKE :search OR sub_category LIKE :search)";
    $params[':search'] = '%' . $search . '%';
}

// Append Category filter
if (!empty($category_filter) && $category_filter !== 'All') {
    $query .= " AND category = :category";
    $params[':category'] = $category_filter;
}

// Append Emission Level filter
if (!empty($level_filter) && $level_filter !== 'All') {
    $query .= " AND emission_level = :level";
    $params[':level'] = $level_filter;
}

// Validate sorting inputs against whitelist to prevent SQL injection
$allowed_sort_columns = ['activity_date', 'co2_emitted', 'quantity'];
if (!in_array($sort_by, $allowed_sort_columns)) {
    $sort_by = 'activity_date';
}

$sort_order = strtoupper($sort_order) === 'ASC' ? 'ASC' : 'DESC';

// Append sorting clauses
$query .= " ORDER BY {$sort_by} {$sort_order}, created_at DESC";

try {
    // Execute dynamic SELECT query
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $activities = $stmt->fetchAll();
} catch (PDOException $e) {
    $error_msg = "Query Error: " . $e->getMessage();
    $activities = [];
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
    <title>EcoTrace - Activity Log</title>
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
                <li class="menu-item active">
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
                    <h2>Carbon Footprint Logs</h2>
                    <p>Search, filter, and inspect your recorded activity tables in real-time.</p>
                </div>
                <div class="nav-right">
                    <label class="theme-switch" for="theme-toggle">
                        <input type="checkbox" id="theme-toggle" <?php echo $is_dark ? 'checked' : ''; ?>>
                        <span class="switch-slider">
                            <i class="fas fa-sun"></i>
                            <i class="fas fa-moon"></i>
                        </span>
                    </label>
                    <a href="add_activity.php" class="btn-primary" style="display: flex; align-items: center; gap: 8px;"><i class="fas fa-plus"></i> New Record</a>
                </div>
            </header>

            <!-- Success / Error notification boxes -->
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
            <section class="table-card" style="padding: 24px;">
                <form action="history.php" method="GET" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 16px; align-items: flex-end;">
                    
                    <!-- Keyword Search -->
                    <div>
                        <label class="form-label" for="search">Search Keywords</label>
                        <input type="text" class="search-input" style="width: 100%;" id="search" name="search" placeholder="e.g. Car, Beef, Plastic..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>

                    <!-- Category filter -->
                    <div>
                        <label class="form-label" for="category">Category</label>
                        <select class="filter-select" style="width: 100%;" id="category" name="category">
                            <option value="All">All Categories</option>
                            <option value="Transport" <?php echo $category_filter === 'Transport' ? 'selected' : ''; ?>>Transport</option>
                            <option value="Electricity" <?php echo $category_filter === 'Electricity' ? 'selected' : ''; ?>>Electricity</option>
                            <option value="Food" <?php echo $category_filter === 'Food' ? 'selected' : ''; ?>>Food</option>
                            <option value="Shopping" <?php echo $category_filter === 'Shopping' ? 'selected' : ''; ?>>Shopping</option>
                            <option value="Waste" <?php echo $category_filter === 'Waste' ? 'selected' : ''; ?>>Waste</option>
                            <option value="Fuel" <?php echo $category_filter === 'Fuel' ? 'selected' : ''; ?>>Fuel</option>
                        </select>
                    </div>

                    <!-- Level filter -->
                    <div>
                        <label class="form-label" for="level">Emission Level</label>
                        <select class="filter-select" style="width: 100%;" id="level" name="level">
                            <option value="All">All Impact Levels</option>
                            <option value="Low" <?php echo $level_filter === 'Low' ? 'selected' : ''; ?>>Low</option>
                            <option value="Medium" <?php echo $level_filter === 'Medium' ? 'selected' : ''; ?>>Medium</option>
                            <option value="High" <?php echo $level_filter === 'High' ? 'selected' : ''; ?>>High</option>
                        </select>
                    </div>

                    <!-- Sort field selection -->
                    <div>
                        <label class="form-label" for="sort_by">Sort By</label>
                        <select class="filter-select" style="width: 100%;" id="sort_by" name="sort_by">
                            <option value="activity_date" <?php echo $sort_by === 'activity_date' ? 'selected' : ''; ?>>Date</option>
                            <option value="co2_emitted" <?php echo $sort_by === 'co2_emitted' ? 'selected' : ''; ?>>CO₂ Emission</option>
                            <option value="quantity" <?php echo $sort_by === 'quantity' ? 'selected' : ''; ?>>Raw Quantity</option>
                        </select>
                    </div>

                    <!-- Sort order selection -->
                    <div>
                        <label class="form-label" for="sort_order">Order</label>
                        <select class="filter-select" style="width: 100%;" id="sort_order" name="sort_order">
                            <option value="desc" <?php echo $sort_order === 'DESC' ? 'selected' : ''; ?>>Descending</option>
                            <option value="asc" <?php echo $sort_order === 'ASC' ? 'selected' : ''; ?>>Ascending</option>
                        </select>
                    </div>

                    <!-- Submit / Reset controls -->
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn-primary" style="padding: 10px; flex: 1; text-align: center;"><i class="fas fa-filter"></i> Apply</button>
                        <a href="history.php" class="btn-secondary" style="padding: 10px; flex: 1; text-align: center;" title="Clear Filters"><i class="fas fa-undo"></i> Reset</a>
                    </div>
                </form>
            </section>

            <!-- SQL Data Table Card -->
            <section class="table-card">
                <div class="table-header">
                    <h3>Database Records (<?php echo count($activities); ?> rows returned)</h3>
                </div>
                
                <div class="custom-table-wrapper">
                    <?php if (count($activities) === 0): ?>
                        <div class="empty-state">
                            <i class="fas fa-database"></i>
                            <p>No carbon logs found matching the selected SQL queries.</p>
                        </div>
                    <?php else: ?>
                        <table class="custom-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Category</th>
                                    <th>Sub-Category</th>
                                    <th>Input Quantity</th>
                                    <th>CO₂ Carbon Emitted</th>
                                    <th>Impact Status</th>
                                    <th>Record Date</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $act): ?>
                                    <tr>
                                        <td style="font-family: monospace; font-weight: bold; color: var(--text-muted);">#<?php echo $act['activity_id']; ?></td>
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
                                        <td style="color: var(--text-muted); font-size: 13px;"><?php echo date('F d, Y', strtotime($act['activity_date'])); ?></td>
                                        <td>
                                            <a href="history.php?delete_id=<?php echo $act['activity_id']; ?>&search=<?php echo urlencode($search ?? ''); ?>&category=<?php echo urlencode($category_filter ?? ''); ?>&level=<?php echo urlencode($level_filter ?? ''); ?>&sort_by=<?php echo urlencode($sort_by); ?>&sort_order=<?php echo urlencode($sort_order); ?>" 
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
            </section>
        </main>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
